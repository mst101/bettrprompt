import { useCountryRoute } from '@/Composables/useCountryRoute';
import { logger } from '@/Utils/logger';
import { onUnmounted, ref } from 'vue';

type AudioRecordingError = DOMException | Error;

export function useAudioRecording() {
    const isRecording = ref(false);
    const isProcessing = ref(false);
    const error = ref<string | null>(null);

    let mediaRecorder: MediaRecorder | null = null;
    let audioChunks: Blob[] = [];

    const startRecording = async () => {
        try {
            error.value = null;
            audioChunks = [];

            // Request microphone access
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: true,
            });

            // Create MediaRecorder with webm format (widely supported)
            mediaRecorder = new MediaRecorder(stream, {
                mimeType: 'audio/webm',
            });

            mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    audioChunks.push(event.data);
                }
            };

            mediaRecorder.start();
            isRecording.value = true;
        } catch (err: unknown) {
            const audioError = err as AudioRecordingError;
            if (audioError.name === 'NotAllowedError') {
                error.value =
                    'Microphone access denied. Please enable microphone permissions.';
            } else if (audioError.name === 'NotFoundError') {
                error.value = 'No microphone found. Please check your device.';
            } else {
                error.value = 'Failed to start recording. Please try again.';
            }
            logger.error('Audio recording error:', err);

            // Auto-dismiss error after 5 seconds
            setTimeout(() => {
                error.value = null;
            }, 5000);
        }
    };

    const stopRecording = (): Promise<string> => {
        return new Promise((resolve, reject) => {
            if (!mediaRecorder || !isRecording.value) {
                reject(new Error('No active recording'));
                return;
            }

            mediaRecorder.onstop = async () => {
                isRecording.value = false;
                isProcessing.value = true;

                // Stop all audio tracks
                mediaRecorder?.stream
                    .getTracks()
                    .forEach((track) => track.stop());

                try {
                    // Create audio blob from chunks
                    const audioBlob = new Blob(audioChunks, {
                        type: 'audio/webm',
                    });

                    // Upload to backend for transcription
                    const transcript = await uploadAndTranscribe(audioBlob);

                    isProcessing.value = false;
                    resolve(transcript);
                } catch (err: unknown) {
                    isProcessing.value = false;
                    error.value =
                        'Failed to transcribe audio. Please try again.';

                    // Auto-dismiss error after 5 seconds
                    setTimeout(() => {
                        error.value = null;
                    }, 5000);

                    reject(err);
                }
            };

            mediaRecorder.stop();
        });
    };

    const uploadAndTranscribe = async (audioBlob: Blob): Promise<string> => {
        const { countryRoute } = useCountryRoute();
        const formData = new FormData();
        formData.append('audio', audioBlob, 'recording.webm');

        // Get CSRF token from cookie (Laravel sets XSRF-TOKEN automatically)
        const getCsrfToken = () => {
            const name = 'XSRF-TOKEN=';
            const decodedCookie = decodeURIComponent(document.cookie);
            const ca = decodedCookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) === 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return '';
        };

        try {
            const response = await fetch(
                countryRoute('voice-transcription.transcribe'),
                {
                    method: 'POST',
                    headers: {
                        'X-XSRF-TOKEN': getCsrfToken(),
                    },
                    body: formData,
                    credentials: 'same-origin',
                },
            );

            if (!response.ok) {
                throw new Error('Transcription request failed');
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Transcription failed');
            }

            return data.transcript;
        } catch (err) {
            logger.error('Upload error:', err);
            throw err;
        }
    };

    const cleanup = () => {
        if (mediaRecorder && isRecording.value) {
            mediaRecorder.stream.getTracks().forEach((track) => track.stop());
            isRecording.value = false;
        }
    };

    // Cleanup on component unmount
    onUnmounted(cleanup);

    return {
        isRecording,
        isProcessing,
        error,
        startRecording,
        stopRecording,
    };
}
