<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('transcription requires authentication', function () {
    auth()->logout();

    $audioFile = UploadedFile::fake()->create('audio.webm', 100, 'audio/webm');

    $response = $this->postJson('/voice-transcription', [
        'audio' => $audioFile,
    ]);

    $response->assertStatus(401);
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
