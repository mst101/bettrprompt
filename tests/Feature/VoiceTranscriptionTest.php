<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('transcription is accessible without authentication', function () {
    auth()->logout();

    // Voice transcription doesn't require auth
    // Test that guests can make requests (will fail validation, but that's expected)
    $audioFile = UploadedFile::fake()->create('audio.webm', 100, 'audio/webm');

    // This will likely fail due to missing API key or other reasons,
    // but the important thing is it's not a 401 Unauthorized
    $response = $this->postJsonLocale('/voice-transcription', [
        'audio' => $audioFile,
    ]);

    // As long as it's not 401, the endpoint is accessible
    expect($response->status())->not->toBe(401);
});

test('transcription validates audio file presence', function () {
    $response = $this->postJsonLocale('/voice-transcription', []);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
    ]);
    $response->assertJsonStructure(['error']);
});

test('transcription validates invalid audio files', function (string $filename, int $size, string $mimeType) {
    $invalidFile = UploadedFile::fake()->create($filename, $size, $mimeType);

    $response = $this->postJsonLocale('/voice-transcription', [
        'audio' => $invalidFile,
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
    ]);
})->with([
    ['document.pdf', 100, 'application/pdf'],           // Wrong file type
    ['audio.webm', 26000, 'audio/webm'],               // File too large (26MB > 25MB limit)
    ['audio.webm', 0, 'audio/webm'],                   // Empty file
]);

test('transcription requires file not string', function () {
    $response = $this->postJsonLocale('/voice-transcription', [
        'audio' => 'not-a-file',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
    ]);
});
