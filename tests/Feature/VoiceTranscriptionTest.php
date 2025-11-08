<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VoiceTranscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_transcription_requires_authentication(): void
    {
        auth()->logout();

        $audioFile = UploadedFile::fake()->create('audio.webm', 100, 'audio/webm');

        $response = $this->postJson('/voice-transcription', [
            'audio' => $audioFile,
        ]);

        $response->assertStatus(401);
    }

    public function test_transcription_validates_audio_file_presence(): void
    {
        $response = $this->postJson('/voice-transcription', []);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonStructure(['error']);
    }

    public function test_transcription_validates_audio_file_type(): void
    {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->postJson('/voice-transcription', [
            'audio' => $invalidFile,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }


    public function test_transcription_validates_audio_file_size(): void
    {
        $oversizedFile = UploadedFile::fake()->create('audio.webm', 26000, 'audio/webm'); // Over 25MB

        $response = $this->postJson('/voice-transcription', [
            'audio' => $oversizedFile,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }


    public function test_transcription_handles_empty_audio_file(): void
    {
        $emptyFile = UploadedFile::fake()->create('audio.webm', 0, 'audio/webm');

        $response = $this->postJson('/voice-transcription', [
            'audio' => $emptyFile,
        ]);

        // Should fail validation or API error
        $this->assertThat(
            $response->status(),
            $this->logicalOr(
                $this->equalTo(422),
                $this->equalTo(500)
            )
        );

        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_transcription_requires_file_not_string(): void
    {
        $response = $this->postJson('/voice-transcription', [
            'audio' => 'not-a-file',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

}
