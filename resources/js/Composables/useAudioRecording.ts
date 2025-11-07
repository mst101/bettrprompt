import { onUnmounted, ref } from 'vue';

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
        } catch (err: any) {
            if (err.name === 'NotAllowedError') {
                error.value =
                    'Microphone access denied. Please enable microphone permissions.';
            } else if (err.name === 'NotFoundError') {
                error.value = 'No microphone found. Please check your device.';
            } else {
                error.value = 'Failed to start recording. Please try again.';
            }
            console.error('Audio recording error:', err);

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
                } catch (err: any) {
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
        const formData = new FormData();
        formData.append('audio', audioBlob, 'recording.webm');

        try {
            const response = await fetch('/voice-transcription', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content,
                },
                body: formData,
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Transcription request failed');
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Transcription failed');
            }

            return data.transcript;
        } catch (err) {
            console.error('Upload error:', err);
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
