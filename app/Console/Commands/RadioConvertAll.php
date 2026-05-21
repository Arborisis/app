<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SoundFile;
use App\Services\Radio\RadioConversionService;
use Illuminate\Console\Command;

class RadioConvertAll extends Command
{
    protected $signature = 'radio:convert-all
                            {--force : Reconvert even if radio file already exists}';

    protected $description = 'Convert all sound files to MP3 for web radio streaming';

    public function handle(RadioConversionService $converter): int
    {
        $query = SoundFile::query();

        if (!$this->option('force')) {
            $query->whereNull('radio_path');
        }

        $files = $query->get();
        $total = $files->count();

        if ($total === 0) {
            $this->info('No files to convert.');

            return self::SUCCESS;
        }

        $this->info("Converting {$total} file(s)...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $success = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($files as $file) {
            if (!$converter->isConvertible($file)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if (!$this->option('force') && $file->radio_path && \Illuminate\Support\Facades\Storage::disk($file->disk)->exists($file->radio_path)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if ($this->option('force') && $file->radio_path) {
                \Illuminate\Support\Facades\Storage::disk($file->disk)->delete($file->radio_path);
                $file->update([
                    'radio_path' => null,
                    'radio_mime_type' => null,
                    'radio_size_bytes' => null,
                    'radio_converted_at' => null,
                ]);
                $file->refresh();
            }

            if ($converter->convert($file)) {
                $success++;
            } else {
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done: {$success} converted, {$failed} failed, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
