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
    $response = $this->postJson('/voice-transcription', [
        'audio' => $audioFile,
    ]);

    // As long as it's not 401, the endpoint is accessible
    expect($response->status())->not->toBe(401);
});

test('transcription validates audio file presence', function () {
    $response = $this->postJson('/voice-transcription', []);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
    ]);
    $response->assertJsonStructure(['error']);
});

test('transcription validates audio file type', function () {
    $invalidFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $response = $this->postJson('/voice-transcription', [
        'audio' => $invalidFile,
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
    ]);
});

test('transcription validates audio file size', function () {
    $oversizedFile = UploadedFile::fake()->create('audio.webm', 26000, 'audio/webm'); // Over 25MB

    $response = $this->postJson('/voice-transcription', [
        'audio' => $oversizedFile,
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
    ]);
});

test('transcription handles empty audio file', function () {
    $emptyFile = UploadedFile::fake()->create('audio.webm', 0, 'audio/webm');

    $response = $this->postJson('/voice-transcription', [
        'audio' => $emptyFile,
    ]);

    // Should fail validation or API error
    expect($response->status())->toBeIn([422, 500]);

    $response->assertJson([
        'success' => false,
    ]);
});

test('transcription requires file not string', function () {
    $response = $this->postJson('/voice-transcription', [
        'audio' => 'not-a-file',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
    ]);
});
