<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Models\RadioJingle;
use App\Models\RadioPodcast;
use App\Models\Sound;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class RadioPlaylistExportService
{
    public function __construct(
        private readonly RadioStreamService $streamService,
        private readonly RadioDjService $djService,
        private readonly RadioAudioCacheService $cache,
    ) {}

    public function liquidsoapPlaylist(): array
    {
        $sounds = $this->streamService->getDeterministicPlaylist();
        $items = [];
        $podcastEnabled = config('radio.podcast.enabled', false)
            || config('radio.host.flash_enabled', false)
            || config('radio.host.emission_enabled', false);
        $podcasts = $podcastEnabled ? $this->nextPodcasts() : collect();

        $soundCount = count($sounds);
        $configuredInterval = (int) config('radio.podcast.interval_tracks', 15);
        // Si moins de sons que l'intervalle, insérer au milieu du cycle
        $interval = $soundCount > 0 ? min($configuredInterval, max(1, (int) ceil($soundCount / 2))) : $configuredInterval;

        $trackCounter = 0;

        foreach ($sounds as $index => $sound) {
            if ($podcasts->isNotEmpty() && $interval > 0 && $trackCounter > 0 && $trackCounter % $interval === 0) {
                $podcast = $podcasts[(int) ((intdiv($trackCounter, $interval) - 1) % $podcasts->count())];
                $podcastItem = $this->mapPodcast($podcast, $trackCounter);
                if ($podcastItem) {
                    $items[] = $podcastItem;
                }
            }

            $mapped = $this->mapSound($sound, $index);
            if ($mapped) {
                $items[] = $mapped;
                $trackCounter++;
            }
        }

        return $items;
    }

    public function m3u(): string
    {
        $lines = ['#EXTM3U'];

        foreach ($this->liquidsoapPlaylist() as $item) {
            $lines[] = sprintf(
                '#EXTINF:%d,%s - %s',
                $item['duration'] ?? -1,
                $item['artist'] ?? 'Arborisis',
                $item['title']
            );

            $annotations = $this->buildAnnotations($item);
            $uri = $item['storage_url'] ?? $item['url'];
            $lines[] = $annotations . $uri;
        }

        return implode("\n", $lines)."\n";
    }

    public function liq(): string
    {
        $metaEntries = [];
        $annotationEntries = [];

        foreach ($this->liquidsoapPlaylist() as $item) {
            $uri = $item['storage_url'] ?? $item['url'];

            $metaFields = [
                sprintf('("title", "%s")', $this->escapeLiq($item['title'])),
                sprintf('("artist", "%s")', $this->escapeLiq($item['artist'] ?? 'Arborisis')),
                sprintf('("sound_id", "%s")', $item['kind'] === 'sound' ? $item['id'] : ''),
                sprintf('("slug", "%s")', $this->escapeLiq($item['slug'] ?? '')),
                sprintf('("kind", "%s")', $item['kind']),
                sprintf('("show_type", "%s")', $this->escapeLiq($item['show_type'] ?? '')),
                sprintf('("duration", "%s")', $item['duration'] ?? ''),
                sprintf('("podcast_id", "%s")', $item['kind'] === 'podcast' ? ($item['podcast_id'] ?? '') : ''),
            ];

            $metaEntries[] = sprintf('("%s", [%s])', $uri, implode(', ', $metaFields));

            // Toutes les métadonnées dans l'annotation pour qu'elles survivent au lookup par filename
            $annotationParts = [
                'kind=' . $item['kind'],
                'title=' . $this->escapeLiqAnnotation($item['title']),
                'artist=' . $this->escapeLiqAnnotation($item['artist'] ?? 'Arborisis'),
                'sound_id=' . ($item['kind'] === 'sound' ? $item['id'] : ''),
                'podcast_id=' . ($item['kind'] === 'podcast' ? ($item['podcast_id'] ?? '') : ''),
                'show_type=' . $this->escapeLiqAnnotation($item['show_type'] ?? ''),
                'duration=' . ($item['duration'] ?? ''),
                'slug=' . $this->escapeLiqAnnotation($item['slug'] ?? ''),
            ];

            $annotationEntries[] = sprintf('"annotate:%s:%s"', implode(',', $annotationParts), $uri);
        }

        $lines = [];
        $lines[] = 'playlist_meta_lookup = [';
        $lines[] = '  ' . implode(",\n  ", $metaEntries);
        $lines[] = ']';
        $lines[] = '';
        $lines[] = 'playlist_annotations = [' . implode(', ', $annotationEntries) . ']';
        $lines[] = '';
        $lines[] = 'playlist_index = ref(0)';
        $lines[] = '';
        $lines[] = 'def playlist_next_request()';
        $lines[] = '  i = playlist_index()';
        $lines[] = '  annotation = list.nth(default="", playlist_annotations, i)';
        $lines[] = '  playlist_index := (i + 1) mod list.length(playlist_annotations)';
        $lines[] = '  if annotation != "" then';
        $lines[] = '    request.create(annotation)';
        $lines[] = '  else';
        $lines[] = '    null()';
        $lines[] = '  end';
        $lines[] = 'end';

        return implode("\n", $lines) . "\n";
    }

    private function escapeLiq(string $value): string
    {
        return str_replace(['"', '\\'], ['\"', '\\\\'], $value);
    }

    private function escapeLiqAnnotation(string $value): string
    {
        // Dans les annotations Liquidsoap, échapper virgule, deux-points, guillemet
        return str_replace([',', ':', '"', '\\'], ['\\,', '\\:', '\\"', '\\\\'], $value);
    }

    /**
     * Résout l'URL publique de stockage (R2, S3...) pour un fichier audio.
     * Liquidsoap l'utilisera directement sans cache local.
     *
     * @param array{disk: string, path: string} $source
     */
    private function resolveStorageUrl(array $source): string
    {
        $localDisks = ['radio_cache', 'local', 'public'];

        if (in_array($source['disk'], $localDisks, true)) {
            // Fichier local : proxy via Laravel (cas rare, anciens enregistrements)
            return $this->cache->urlFor(
                explode('/', $source['path'])[0],
                (int) basename($source['path'], '.mp3')
            );
        }

        return Storage::disk($source['disk'])->url($source['path']);
    }

    public function activeJingles(): Collection
    {
        return RadioJingle::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->filter(fn (RadioJingle $jingle): bool => $jingle->isCurrentlyActive())
            ->filter(fn (RadioJingle $jingle): bool => Storage::disk($jingle->disk)->exists($jingle->path))
            ->map(function (RadioJingle $jingle): array {
                $this->ensureCached('jingles', $jingle->id, fn () => $this->cache->warmJingle($jingle));

                return [
                    'id' => $jingle->id,
                    'title' => $jingle->name,
                    'artist' => 'Arborisis Radio',
                    'kind' => 'jingle',
                    'placement' => $jingle->placement->value,
                    'frequency' => $jingle->frequency,
                    'duration' => $jingle->duration,
                    'url' => $this->cache->urlFor('jingles', $jingle->id),
                ];
            })
            ->values();
    }

    private function mapSound(Sound $sound, int $index): ?array
    {
        $source = $this->resolvePlayableSource($sound);

        if (! $source) {
            return null;
        }

        $localDisks = ['radio_cache', 'local', 'public'];
        if (in_array($source['disk'], $localDisks, true)) {
            $this->ensureCached('sounds', $sound->id, fn () => $this->cache->warmSound($sound));
            $storageUrl = $this->cache->localPathFor('sounds', $sound->id);
        } else {
            // URL directe vers le stockage (R2/S3) — pas de cache local nécessaire pour Liquidsoap
            $storageUrl = $this->resolveStorageUrl($source);
        }

        $djAnnouncement = config('radio.dj.enabled')
            ? $this->djService->announcementFor($sound)
            : null;

        $djUrl = null;
        if ($djAnnouncement) {
            $this->ensureCached('dj', $djAnnouncement->id, fn () => $this->cache->warmDjAnnouncement($djAnnouncement));
            $djUrl = $this->cache->urlFor('dj', $djAnnouncement->id);
        }

        return [
            'id' => $sound->id,
            'title' => $sound->title,
            'artist' => $sound->user?->name ?? 'Arborisis',
            'slug' => $sound->slug,
            'cover' => $sound->cover_url,
            'duration' => $sound->duration,
            'position' => $index,
            'kind' => 'sound',
            'storage_url' => $storageUrl,
            'url' => $this->cache->urlFor('sounds', $sound->id),
            'dj_announcement_url' => $djUrl,
            'dj_announcement_text' => $djAnnouncement?->text,
        ];
    }

    private function mapPodcast(RadioPodcast $podcast, int $position): ?array
    {
        // Cache local maintenu pour le stream PHP (/radio/cache/podcasts/{id})
        $this->ensureCached('podcasts', $podcast->id, fn () => $this->cache->warmPodcast($podcast));

        // URL directe vers R2 pour Liquidsoap
        $localDisks = ['radio_cache', 'local', 'public'];
        $storageUrl = $podcast->disk && $podcast->path && ! in_array($podcast->disk, $localDisks, true)
            ? $this->resolveStorageUrl(['disk' => $podcast->disk, 'path' => $podcast->path])
            : $this->cache->localPathFor('podcasts', $podcast->id);

        return [
            'id' => $podcast->id,
            'title' => $podcast->title,
            'artist' => 'Arborisis Radio',
            'slug' => null,
            'cover' => null,
            'duration' => (int) ($podcast->actual_duration_seconds ?? 180),
            'position' => $position,
            'kind' => 'podcast',
            'show_type' => $podcast->show_type?->value ?? 'podcast',
            'storage_url' => $storageUrl,
            'url' => $this->cache->urlFor('podcasts', $podcast->id),
            'podcast_id' => $podcast->id,
        ];
    }

    /** @return Collection<int, RadioPodcast> */
    private function nextPodcasts(): Collection
    {
        return RadioPodcast::query()
            ->readyForAir()
            ->latest('published_at')
            ->limit((int) config('radio.podcast.rotation_limit', 10))
            ->get();
    }

    private function resolvePlayableSource(Sound $sound): ?array
    {
        $soundFile = $sound->soundFile;

        if (! $soundFile) {
            return null;
        }

        $disk = $soundFile->disk;

        if (
            $soundFile->radio_path
            && $soundFile->radio_mime_type === 'audio/mpeg'
            && Storage::disk($disk)->exists($soundFile->radio_path)
        ) {
            return ['disk' => $disk, 'path' => $soundFile->radio_path];
        }

        if (
            $soundFile->mime_type === 'audio/mpeg'
            && Storage::disk($disk)->exists($soundFile->path)
        ) {
            return ['disk' => $disk, 'path' => $soundFile->path];
        }

        return null;
    }

    private function buildAnnotations(array $item): string
    {
        $parts = [];
        $parts[] = 'duration=' . ($item['duration'] ?? '');
        $parts[] = 'sound_id=' . ($item['kind'] === 'sound' ? ($item['id'] ?? '') : '');
        $parts[] = 'slug=' . ($item['slug'] ?? '');
        $parts[] = 'kind=' . ($item['kind'] ?? 'sound');
        $parts[] = 'podcast_id=' . ($item['kind'] === 'podcast' ? ($item['podcast_id'] ?? '') : '');
        $parts[] = 'show_type=' . ($item['show_type'] ?? '');

        return 'annotate:' . implode(',', $parts) . ':';
    }

    private function ensureCached(string $type, int $id, callable $warmer): void
    {
        if (! $this->cache->exists($type, $id)) {
            try {
                $warmer();
            } catch (\Throwable $e) {
                // Log but don't break playlist generation; Liquidsoap will skip if file missing
                \Illuminate\Support\Facades\Log::warning('Playlist cache warm failed', [
                    'type' => $type,
                    'id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
