<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Log;
use OpenAI;

class VoiceTranscriptionController extends Controller
{
    /**
     * Transcribe audio using OpenAI Whisper API
     */
    public function transcribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'audio' => [
                'required',
                'file',
                'mimes:webm,mp3,mp4,mpeg,mpga,m4a,wav,ogg',
                'max:25600', // 25MB max (Whisper API limit)
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first(),
            ], 422);
        }

        $tempPath = null;

        try {
            $audioFile = $request->file('audio');

            // Save temporarily with proper extension (Whisper needs to detect format)
            $tempPath = sys_get_temp_dir().'/'.uniqid('audio_', true).'.webm';
            $audioFile->move(dirname($tempPath), basename($tempPath));

            // Initialize OpenAI client
            $client = OpenAI::client(config('services.openai.api_key'));

            // Call Whisper API for transcription
            $response = $client->audio()->transcribe([
                'model' => 'whisper-1',
                'file' => fopen($tempPath, 'r'),
                'language' => 'en', // English transcription
                'response_format' => 'json',
            ]);

            // Clean up temporary file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return response()->json([
                'success' => true,
                'transcript' => $response->text,
            ]);
        } catch (Exception $e) {
            // Clean up temporary file on error
            if ($tempPath && file_exists($tempPath)) {
                unlink($tempPath);
            }
            Log::error('Voice transcription failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : __('messages.voice.transcription_failed'),
            ], 500);
        }
    }
}
