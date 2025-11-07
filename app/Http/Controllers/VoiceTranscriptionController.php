<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

        try {
            $audioFile = $request->file('audio');

            // Initialize OpenAI client
            $client = OpenAI::client(config('services.openai.api_key'));

            // Call Whisper API for transcription
            $response = $client->audio()->transcribe([
                'model' => 'whisper-1',
                'file' => fopen($audioFile->getRealPath(), 'r'),
                'language' => 'en', // English transcription
                'response_format' => 'json',
            ]);

            return response()->json([
                'success' => true,
                'transcript' => $response->text,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to transcribe audio. Please try again.',
            ], 500);
        }
    }
}
