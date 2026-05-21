<?php

declare(strict_types=1);

namespace App\Services\Radio;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class RadioAudioNormalizationService
{
    private const TARGET_SAMPLE_RATE = 44100;

    private const TARGET_CHANNELS = 2;

    private const TARGET_BITRATE = '192k';

    public function normalize(string $inputPath, string $outputPath): bool
    {
        if (! file_exists($inputPath)) {
            Log::warning('Radio normalization: input file does not exist', ['path' => $inputPath]);

            return false;
        }

        $process = new Process([
            'ffmpeg',
            '-y',
            '-i', $inputPath,
            '-codec:a', 'libmp3lame',
            '-b:a', self::TARGET_BITRATE,
            '-ar', (string) self::TARGET_SAMPLE_RATE,
            '-ac', (string) self::TARGET_CHANNELS,
            '-map_metadata', '-1',
            '-id3v2_version', '0',
            '-write_id3v1', '0',
            '-af', 'highpass=f=10',
            $outputPath,
        ]);

        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('Radio normalization: ffmpeg failed', [
                'input' => $inputPath,
                'error' => $process->getErrorOutput(),
            ]);

            return false;
        }

        return file_exists($outputPath) && filesize($outputPath) > 0;
    }

    public function probeDuration(string $path): ?float
    {
        if (! file_exists($path)) {
            return null;
        }

        $process = new Process([
            'ffprobe',
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'csv=p=0',
            $path,
        ]);

        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $output = trim($process->getOutput());

        return is_numeric($output) ? (float) $output : null;
    }

    /**
     * Concatenates multiple audio files into one via the FFmpeg concat demuxer.
     *
     * @param  list<string>  $filePaths  Local temp paths — NOT deleted by this method
     * @return string|null Path to the concatenated temp file, or null on failure
     */
    public function concatAudioFiles(array $filePaths): ?string
    {
        $filePaths = array_values(array_filter($filePaths, 'file_exists'));

        if (empty($filePaths)) {
            return null;
        }

        if (count($filePaths) === 1) {
            return $filePaths[0];
        }

        $listPath = sys_get_temp_dir().'/'.Str::uuid().'_concat_list.txt';
        $content = '';
        foreach ($filePaths as $path) {
            $content .= "file '".str_replace("'", "'\\''", $path)."'\n";
        }
        file_put_contents($listPath, $content);

        $output = sys_get_temp_dir().'/'.Str::uuid().'_concat.mp3';
        $process = new Process([
            'ffmpeg', '-y',
            '-f', 'concat', '-safe', '0',
            '-i', $listPath,
            '-codec:a', 'libmp3lame',
            '-b:a', self::TARGET_BITRATE,
            '-ar', (string) self::TARGET_SAMPLE_RATE,
            '-ac', (string) self::TARGET_CHANNELS,
            $output,
        ]);
        $process->setTimeout(300);
        $process->run();
        @unlink($listPath);

        if (! $process->isSuccessful() || ! file_exists($output)) {
            Log::error('Radio concat: ffmpeg failed', ['error' => $process->getErrorOutput()]);

            return null;
        }

        return $output;
    }

    public function isAlreadyNormalized(string $path): bool
    {
        if (! file_exists($path)) {
            return false;
        }

        $process = new Process([
            'ffprobe',
            '-v', 'error',
            '-select_streams', 'a:0',
            '-show_entries', 'stream=sample_rate,channels,bit_rate:stream_disposition=:format=format_name',
            '-of', 'json',
            $path,
        ]);

        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            return false;
        }

        $info = json_decode($process->getOutput(), true);

        if (! is_array($info)) {
            return false;
        }

        $stream = $info['streams'][0] ?? [];
        $format = $info['format'] ?? [];

        $sampleRate = (int) ($stream['sample_rate'] ?? 0);
        $channels = (int) ($stream['channels'] ?? 0);
        $bitRate = (int) ($stream['bit_rate'] ?? 0);
        $formatNames = explode(',', $format['format_name'] ?? '');

        return in_array('mp3', $formatNames, true)
            && $sampleRate === self::TARGET_SAMPLE_RATE
            && $channels === self::TARGET_CHANNELS
            && $bitRate >= 180000 && $bitRate <= 210000;
    }
}
