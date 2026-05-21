<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\Models\Sound;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserAgentMemoryService
{
    private const CONVERSATION_NOTES_FILE = 'CONVERSATION_NOTES.json';
    private const MAX_CONVERSATION_NOTES = 12;

    /**
     * @return array<string, string>
     */
    public function ensureForUser(User $user): array
    {
        $user->loadMissing('profile');
        $this->loadCounts($user);

        $files = [
            'AGENT.md' => $this->agentDocument($user),
            'USER.md' => $this->userDocument($user),
            'MEMORY.md' => $this->memoryDocument($user),
        ];

        foreach ($files as $name => $content) {
            Storage::disk($this->disk())->put($this->path($user, $name), $content);
        }

        return $files;
    }

    /**
     * @return array<string, string>
     */
    public function readForUser(User $user): array
    {
        return collect(['AGENT.md', 'USER.md', 'MEMORY.md'])
            ->mapWithKeys(function (string $name) use ($user): array {
                $path = $this->path($user, $name);
                $content = Storage::disk($this->disk())->exists($path)
                    ? (string) Storage::disk($this->disk())->get($path)
                    : '';

                return [$name => Str::limit($content, 6000, '')];
            })
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function pathsForUser(User $user): array
    {
        return [
            'AGENT.md' => $this->path($user, 'AGENT.md'),
            'USER.md' => $this->path($user, 'USER.md'),
            'MEMORY.md' => $this->path($user, 'MEMORY.md'),
        ];
    }

    private function agentDocument(User $user): string
    {
        $firstName = $this->firstName($user);

        return <<<MARKDOWN
# AGENT.md — Sylve pour {$firstName}

## Identité
Sylve est l'agent IA personnel de field recording pour {$firstName} dans Arborisis.
Elle accompagne la préparation, l'enregistrement, la publication, l'analyse et l'exploration de sons de nature.

## Rôle concret
- Appeler {$firstName} par son prénom dans les réponses naturelles.
- Agir comme un assistant de terrain autonome: diagnostiquer le contexte, choisir les données utiles, proposer la prochaine action sans attendre une question parfaite.
- Aider à choisir un lieu, une heure, une posture micro, un protocole d'écoute, une check-list et une stratégie de publication.
- Relier les conseils aux sons, quêtes, statistiques, radio, points d'écoute et pages Arborisis disponibles.
- Proposer des actions claires dans le site: enregistrer, publier, consulter la carte, créer un point, créer une SoundWalk, enrichir une fiche son, relancer une analyse.
- Utiliser les API Arborisis quand une question porte sur l'état actuel du site ou sur le parcours de {$firstName}.
- Résumer les décisions prises et garder une continuité entre préparation, terrain, tri, publication et amélioration des métadonnées.

## Sécurité et éthique
- Ne jamais exposer de coordonnées GPS exactes.
- Ne jamais inventer de données utilisateur absentes de USER.md ou MEMORY.md.
- Ne jamais présenter ECHO comme une cryptomonnaie ou un investissement.
- Respecter la nature: pas de dérangement, pas d'appâtage sonore agressif, pas de pression FOMO.

## Style
Précis, sensible, opérationnel. Réponses en français, courtes si la question est simple, plus structurées pour une session de terrain.
Toujours terminer les réponses complexes par une action concrète et réaliste.
MARKDOWN;
    }

    private function userDocument(User $user): string
    {
        $profile = $user->profile;
        $bio = filled($profile?->bio) ? Str::limit((string) $profile->bio, 500, '') : 'Non renseignée';
        $location = filled($profile?->location) ? 'Renseignée par l’utilisateur, non transmise comme position exacte' : 'Non renseignée';
        $conversationProfile = $this->conversationProfile($user);

        return <<<MARKDOWN
# USER.md — Profil Arborisis

## Identité de session
- Prénom: {$this->firstName($user)}
- Nom public: {$user->name}
- Slug public: {$user->slug}
- Relation Arborisis: {$this->relationship($user)}

## Profil
- Bio: {$bio}
- Localisation déclarative: {$location}
- Profil créateur: {$this->yesNo((bool) $profile?->is_creator)}

## Activité
- Sons publiés ou créés: {$user->sounds_count}
- Abonnés: {$user->followers_count}
- Abonnements: {$user->following_count}
- Points Arborisis créés: {$user->arborisis_points_count}
- Visites Arborisis: {$user->arborisis_visits_count}
- Quêtes suivies: {$user->quest_progress_count}
- Succès débloqués: {$user->achievements_count}
- Médailles: {$user->medals_count}
- Niveau: {$user->level}
- XP: {$user->xp_total}
- Série actuelle: {$user->current_streak}

## Confidentialité
Ce fichier ne contient pas d'email, de token, de mot de passe, ni de coordonnées GPS exactes.

## Préférences observées via Sylve
{$conversationProfile}
MARKDOWN;
    }

    private function memoryDocument(User $user): string
    {
        $recentSounds = $this->recentSounds($user);
        $sounds = $recentSounds
            ->map(function (Sound $sound): string {
                $category = $sound->category?->name ?? 'non classée';
                $environment = $sound->environment?->name ?? 'environnement non renseigné';
                $duration = $sound->duration ? $sound->duration.' s' : 'durée inconnue';
                $recordedAt = $sound->recorded_at?->format('Y-m-d') ?? 'date inconnue';
                $status = $sound->status?->value ?? (string) $sound->status;
                $visibility = $sound->visibility?->value ?? (string) $sound->visibility;
                $equipment = filled($sound->equipment) ? "materiel: {$sound->equipment}" : 'materiel non renseigne';
                $microphone = filled($sound->microphone_position) ? "micro: {$sound->microphone_position}" : 'position micro non renseignee';
                $weather = filled($sound->weather_notes) ? "meteo: {$sound->weather_notes}" : 'meteo non renseignee';
                $species = $this->speciesSummary($sound);

                return "- {$sound->title} ({$category}, {$environment}, {$duration}, enregistre le {$recordedAt}, {$status}/{$visibility}; {$equipment}; {$microphone}; {$weather}; {$species})";
            })
            ->implode("\n");

        if ($sounds === '') {
            $sounds = '- Aucun son récent disponible.';
        }

        $profile = $this->fieldRecordingProfile($recentSounds);
        $metadataGaps = $this->metadataGaps($recentSounds);
        $nextActions = $this->nextActions($user, $recentSounds);
        $conversationMemory = $this->conversationMemory($user);
        $updatedAt = now()->toIso8601String();

        return <<<MARKDOWN
# MEMORY.md — Mémoire field recording

## Dernière mise à jour
{$updatedAt}

## Profil d'écoute inféré
Cette mémoire est générée à partir des données Arborisis de l'utilisateur. Elle sert à personnaliser Sylve sans exposer de données sensibles.

{$profile}

## Sons récents
{$sounds}

## Lacunes à surveiller
{$metadataGaps}

## Prochaines actions suggérées
{$nextActions}

## Mémoire conversationnelle récente
{$conversationMemory}

## Priorités agent
- Encourager une pratique de terrain calme, documentée et respectueuse.
- Aider à améliorer les métadonnées: date, durée, matériel, position micro, environnement, météo, point d'écoute, description d'écoute.
- Orienter vers les analyses scientifiques quand elles existent.
- Proposer des prochaines actions adaptées au niveau d'activité de l'utilisateur, pas des conseils génériques.
- Préparer des plans de sortie avec: objectif d'écoute, créneau, matériel, réglages, protocole de silence, notes terrain, publication Arborisis.

## Limites
Ne contient pas de coordonnées exactes, d'email, de clé API, de données de paiement ou de message privé.
MARKDOWN;
    }

    /**
     * @param array<int, array{role?: string, content?: string}> $history
     * @param array<string, mixed> $page
     */
    public function rememberConversation(User $user, string $message, string $answer, array $history = [], array $page = []): void
    {
        $notes = $this->conversationNotes($user);
        $note = [
            'remembered_at' => now()->toIso8601String(),
            'page_section' => $this->safeText((string) ($page['section'] ?? 'site'), 80),
            'page_url' => $this->safeText((string) ($page['url'] ?? ''), 160),
            'user_intent' => $this->summarizeText($message, 220),
            'assistant_follow_up' => $this->summarizeText($answer, 260),
            'conversation_size' => count($history) + 2,
        ];

        $notes = collect([$note, ...$notes])
            ->unique(fn (array $item): string => ($item['user_intent'] ?? '').'|'.($item['assistant_follow_up'] ?? ''))
            ->take(self::MAX_CONVERSATION_NOTES)
            ->values()
            ->all();

        Storage::disk($this->disk())->put(
            $this->path($user, self::CONVERSATION_NOTES_FILE),
            json_encode($notes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->ensureForUser($user);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function conversationNotes(User $user): array
    {
        $path = $this->path($user, self::CONVERSATION_NOTES_FILE);

        if (! Storage::disk($this->disk())->exists($path)) {
            return [];
        }

        $decoded = json_decode((string) Storage::disk($this->disk())->get($path), true);

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn ($item): bool => is_array($item))
            ->take(self::MAX_CONVERSATION_NOTES)
            ->values()
            ->all();
    }

    private function conversationProfile(User $user): string
    {
        $notes = $this->conversationNotes($user);

        if ($notes === []) {
            return '- Aucune préférence de conversation récente enregistrée.';
        }

        return collect($notes)
            ->take(5)
            ->map(function (array $note): string {
                $section = filled($note['page_section'] ?? null) ? " depuis {$note['page_section']}" : '';

                return '- Demande récente'.$section.': '.$this->safeText((string) ($note['user_intent'] ?? 'demande non renseignée'), 180);
            })
            ->implode("\n");
    }

    private function conversationMemory(User $user): string
    {
        $notes = $this->conversationNotes($user);

        if ($notes === []) {
            return '- Aucune conversation récente à reprendre.';
        }

        return collect($notes)
            ->take(6)
            ->map(function (array $note): string {
                $date = filled($note['remembered_at'] ?? null) ? (string) $note['remembered_at'] : 'date inconnue';
                $intent = $this->safeText((string) ($note['user_intent'] ?? 'demande non renseignée'), 180);
                $followUp = $this->safeText((string) ($note['assistant_follow_up'] ?? 'réponse non renseignée'), 220);

                return "- {$date}: demande utilisateur: {$intent}; suite Sylve: {$followUp}";
            })
            ->implode("\n");
    }

    private function loadCounts(User $user): void
    {
        $user->loadCount([
            'sounds',
            'followers',
            'following',
            'arborisisPoints',
            'arborisisVisits',
            'questProgress',
            'achievements',
            'medals',
        ]);
    }

    public function fieldRecordingBrief(User $user): array
    {
        $user->loadMissing('profile');
        $this->loadCounts($user);
        $recentSounds = $this->recentSounds($user);

        return [
            'identity' => [
                'first_name' => $this->firstName($user),
                'relationship' => $this->relationship($user),
                'is_creator' => (bool) $user->profile?->is_creator,
            ],
            'activity' => [
                'sounds' => $user->sounds_count,
                'followers' => $user->followers_count,
                'following' => $user->following_count,
                'points' => $user->arborisis_points_count,
                'visits' => $user->arborisis_visits_count,
                'quests' => $user->quest_progress_count,
                'level' => $user->level,
                'xp' => $user->xp_total,
                'streak' => $user->current_streak,
            ],
            'profile' => $this->fieldRecordingProfile($recentSounds),
            'metadata_gaps' => $this->metadataGaps($recentSounds),
            'next_actions' => $this->nextActions($user, $recentSounds),
            'recent_sounds' => $recentSounds->map(fn (Sound $sound): array => $this->soundPayload($sound))->values()->all(),
        ];
    }

    /**
     * @return Collection<int, Sound>
     */
    private function recentSounds(User $user): Collection
    {
        return $user->sounds()
            ->with(['category', 'environment', 'listeningPoint', 'environmentalObservation', 'soundAnalysis.birdnetDetections'])
            ->latest()
            ->limit(8)
            ->get();
    }

    /**
     * @param Collection<int, Sound> $sounds
     */
    private function fieldRecordingProfile(Collection $sounds): string
    {
        if ($sounds->isEmpty()) {
            return '- Aucun historique sonore récent: proposer une première sortie courte, bien documentée, avec publication guidée.';
        }

        $categories = $sounds->pluck('category.name')->filter()->countBy()->sortDesc()->take(3);
        $environments = $sounds->pluck('environment.name')->filter()->countBy()->sortDesc()->take(3);
        $equipment = $sounds->pluck('equipment')->filter()->countBy()->sortDesc()->take(3);
        $species = $sounds
            ->flatMap(fn (Sound $sound) => $sound->soundAnalysis?->birdnetDetections?->pluck('common_name') ?? collect())
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(5);

        return collect([
            '- Categories recentes: '.$this->countSummary($categories, 'non detectees'),
            '- Environnements frequents: '.$this->countSummary($environments, 'non renseignes'),
            '- Materiel mentionne: '.$this->countSummary($equipment, 'non renseigne'),
            '- Especes BirdNET recurrentes: '.$this->countSummary($species, 'aucune detection recente'),
        ])->implode("\n");
    }

    /**
     * @param Collection<int, Sound> $sounds
     */
    private function metadataGaps(Collection $sounds): string
    {
        if ($sounds->isEmpty()) {
            return '- Aucun son recent a auditer.';
        }

        $gaps = [
            'date' => $sounds->whereNull('recorded_at')->count(),
            'materiel' => $sounds->filter(fn (Sound $sound): bool => blank($sound->equipment))->count(),
            'position_micro' => $sounds->filter(fn (Sound $sound): bool => blank($sound->microphone_position))->count(),
            'meteo' => $sounds->filter(fn (Sound $sound): bool => blank($sound->weather_notes) && $sound->environmentalObservation === null)->count(),
            'point_ecoute' => $sounds->whereNull('listening_point_id')->count(),
            'analyse' => $sounds->filter(fn (Sound $sound): bool => $sound->soundAnalysis === null)->count(),
        ];

        return collect($gaps)
            ->filter(fn (int $count): bool => $count > 0)
            ->map(fn (int $count, string $label): string => "- {$label}: {$count} son(s) recent(s) a completer")
            ->values()
            ->whenEmpty(fn (Collection $items): Collection => $items->push('- Les sons recents sont correctement documentes.'))
            ->implode("\n");
    }

    /**
     * @param Collection<int, Sound> $sounds
     */
    private function nextActions(User $user, Collection $sounds): string
    {
        $actions = [];

        if ((int) $user->sounds_count === 0) {
            $actions[] = 'Planifier une premiere prise de 10 minutes avec lieu approximatif, heure, materiel et note meteo.';
        }

        if ($sounds->contains(fn (Sound $sound): bool => blank($sound->weather_notes) && $sound->environmentalObservation === null)) {
            $actions[] = 'Completer la meteo et le niveau d activite percue sur les sons recents.';
        }

        if ($sounds->contains(fn (Sound $sound): bool => $sound->soundAnalysis === null)) {
            $actions[] = 'Verifier ou relancer les analyses audio des sons sans analyse.';
        }

        if ((int) $user->arborisis_points_count === 0) {
            $actions[] = 'Creer un premier point d ecoute public approximatif si un lieu recurrent existe.';
        }

        if ($actions === []) {
            $actions[] = 'Proposer une sortie comparative: meme point, autre heure, protocole identique pour enrichir les donnees scientifiques.';
        }

        return collect($actions)->map(fn (string $action): string => "- {$action}")->implode("\n");
    }

    /**
     * @param Collection<string, int> $counts
     */
    private function countSummary(Collection $counts, string $empty): string
    {
        if ($counts->isEmpty()) {
            return $empty;
        }

        return $counts
            ->map(fn (int $count, string $label): string => "{$label} ({$count})")
            ->values()
            ->implode(', ');
    }

    private function speciesSummary(Sound $sound): string
    {
        $detections = $sound->soundAnalysis?->birdnetDetections;

        if (! $detections || $detections->isEmpty()) {
            return 'aucune detection BirdNET recente';
        }

        $species = $detections
            ->sortByDesc('confidence')
            ->take(3)
            ->map(fn ($detection): string => trim(($detection->common_name ?: $detection->scientific_name).' '.round((float) $detection->confidence * 100).'%'))
            ->implode(', ');

        return 'BirdNET: '.$species;
    }

    /**
     * @return array<string, mixed>
     */
    private function soundPayload(Sound $sound): array
    {
        return [
            'id' => $sound->id,
            'title' => $sound->title,
            'url' => url('/sounds/'.$sound->slug),
            'category' => $sound->category?->name,
            'environment' => $sound->environment?->name,
            'duration_seconds' => $sound->duration,
            'recorded_at' => $sound->recorded_at?->toDateString(),
            'equipment' => $sound->equipment,
            'microphone_position' => $sound->microphone_position,
            'weather_notes' => $sound->weather_notes,
            'listening_point' => $sound->listeningPoint?->title,
            'analysis_status' => $sound->soundAnalysis?->status?->value,
            'detected_species' => $sound->soundAnalysis?->birdnetDetections
                ?->sortByDesc('confidence')
                ->take(5)
                ->map(fn ($detection): array => [
                    'common_name' => $detection->common_name,
                    'scientific_name' => $detection->scientific_name,
                    'confidence' => round((float) $detection->confidence, 3),
                ])
                ->values()
                ->all() ?? [],
        ];
    }

    private function firstName(User $user): string
    {
        $name = trim((string) $user->name);

        return Str::of($name)->before(' ')->trim()->toString() ?: $name;
    }

    private function relationship(User $user): string
    {
        if ($user->isModerator()) {
            return 'moderateur';
        }

        return $user->isCreator() ? 'createur' : 'membre';
    }

    private function yesNo(bool $value): string
    {
        return $value ? 'oui' : 'non';
    }

    private function summarizeText(string $value, int $limit): string
    {
        $clean = $this->safeText($value, $limit);

        return $clean === '' ? 'Non renseigné' : $clean;
    }

    private function safeText(string $value, int $limit): string
    {
        $clean = Str::of($value)
            ->replaceMatches('/\s+/', ' ')
            ->replaceMatches('/[\x00-\x1F\x7F]/u', '')
            ->trim()
            ->toString();

        return Str::limit($clean, $limit, '');
    }

    private function disk(): string
    {
        return (string) config('services.arborisis_agent.memory_disk', 'local');
    }

    private function path(User $user, string $filename): string
    {
        $basePath = trim((string) config('services.arborisis_agent.memory_path', 'agent-memory'), '/');

        return "{$basePath}/users/{$user->id}/{$filename}";
    }
}
