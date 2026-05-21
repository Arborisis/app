<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Models\SoundFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class RadioConversionService
{
    private const VBR_QUALITY = '2';

    public function convert(SoundFile $soundFile): bool
    {
        if (!$this->isConvertible($soundFile)) {
            Log::info('Radio conversion skipped: not convertible', ['sound_file_id' => $soundFile->id]);

            return false;
        }

        if ($soundFile->radio_path && Storage::disk($soundFile->disk)->exists($soundFile->radio_path)) {
            Log::info('Radio conversion skipped: already exists', ['sound_file_id' => $soundFile->id]);

            return true;
        }

        $disk = $soundFile->disk;
        $originalPath = $soundFile->path;
        $tempOriginal = sys_get_temp_dir() . '/' . Str::uuid() . '_' . basename($originalPath);
        $tempConverted = sys_get_temp_dir() . '/' . Str::uuid() . '_radio.mp3';

        try {
            $stream = Storage::disk($disk)->readStream($originalPath);
            if (!$stream) {
                throw new \RuntimeException('Cannot open original file stream');
            }

            $written = file_put_contents($tempOriginal, $stream);
            fclose($stream);

            if ($written === false) {
                throw new \RuntimeException('Failed to write original file to temp path');
            }

            if ($soundFile->size_bytes > 0 && $written !== $soundFile->size_bytes) {
                Log::warning('Radio conversion: temp file size mismatch', [
                    'sound_file_id' => $soundFile->id,
                    'expected' => $soundFile->size_bytes,
                    'actual' => $written,
                ]);
            }

            $process = new Process([
                'ffmpeg',
                '-y',
                '-i', $tempOriginal,
                '-codec:a', 'libmp3lame',
                '-b:a', '192k',
                '-ar', '44100',
                '-ac', '2',
                '-map_metadata', '-1',
                '-id3v2_version', '0',
                '-write_id3v1', '0',
                '-af', 'highpass=f=10',
                $tempConverted,
            ]);

            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('FFmpeg radio conversion failed', [
                    'sound_file_id' => $soundFile->id,
                    'error' => $process->getErrorOutput(),
                ]);

                return false;
            }

            $radioPath = $this->generateRadioPath($soundFile);
            $stream = fopen($tempConverted, 'r');
            Storage::disk($disk)->put($radioPath, $stream);
            fclose($stream);

            $soundFile->update([
                'radio_path' => $radioPath,
                'radio_mime_type' => 'audio/mpeg',
                'radio_size_bytes' => filesize($tempConverted),
                'radio_converted_at' => now(),
            ]);

            Log::info('Radio conversion successful', [
                'sound_file_id' => $soundFile->id,
                'radio_path' => $radioPath,
                'size' => $soundFile->radio_size_bytes,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Radio conversion exception', [
                'sound_file_id' => $soundFile->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        } finally {
            @unlink($tempOriginal);
            @unlink($tempConverted);
        }
    }

    public function isConvertible(SoundFile $soundFile): bool
    {
        return $soundFile->mime_type === 'audio/x-wav'
            || $soundFile->mime_type === 'audio/wav'
            || str_starts_with($soundFile->mime_type, 'audio/');
    }

    private function generateRadioPath(SoundFile $soundFile): string
    {
        $dir = dirname($soundFile->path);
        $uuid = Str::uuid()->toString();

        return $dir . '/' . $uuid . '_radio.mp3';
    }
}
