<?php

use App\Services\Radio\RadioAudioNormalizationService;

beforeEach(function () {
    $this->service = new RadioAudioNormalizationService();
});

it('normalizes mp3 to 44100 stereo 192k', function () {
    $input = '/tmp/test_audio_raw.mp3';
    $output = '/tmp/test_audio_normalized.mp3';

    expect(file_exists($input))->toBeTrue();

    $result = $this->service->normalize($input, $output);

    expect($result)->toBeTrue()
        ->and(file_exists($output))->toBeTrue();

    // Verify with ffprobe
    $process = new Symfony\Component\Process\Process([
        'ffprobe', '-v', 'error', '-select_streams', 'a:0',
        '-show_entries', 'stream=sample_rate,channels,bit_rate',
        '-of', 'json', $output,
    ]);
    $process->run();
    $info = json_decode($process->getOutput(), true);

    expect((int) $info['streams'][0]['sample_rate'])->toBe(44100)
        ->and((int) $info['streams'][0]['channels'])->toBe(2)
        ->and((int) $info['streams'][0]['bit_rate'])->toBeBetween(180000, 210000);

    @unlink($output);
});

it('probes duration correctly', function () {
    $input = '/tmp/test_audio_raw.mp3';
    $duration = $this->service->probeDuration($input);

    expect($duration)->toBeFloat()->toBeGreaterThan(0.9)->toBeLessThanOrEqual(1.1);
});

it('returns null for missing file probe', function () {
    expect($this->service->probeDuration('/tmp/nonexistent.mp3'))->toBeNull();
});

it('detects already normalized file', function () {
    $normalized = '/tmp/test_audio_norm.mp3';

    expect($this->service->isAlreadyNormalized($normalized))->toBeTrue();
});

it('detects non-normalized file', function () {
    $raw = '/tmp/test_audio_raw.mp3';

    expect($this->service->isAlreadyNormalized($raw))->toBeFalse();
});

it('returns false for missing file normalization check', function () {
    expect($this->service->isAlreadyNormalized('/tmp/nonexistent.mp3'))->toBeFalse();
});
