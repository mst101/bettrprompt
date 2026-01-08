/* eslint-disable @typescript-eslint/no-explicit-any */
import { onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

export function useAudioRecording() {
    const { t } = useI18n();
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
                error.value = t('audio.errors.accessDenied');
            } else if (err.name === 'NotFoundError') {
                error.value = t('audio.errors.notFound');
            } else {
                error.value = t('audio.errors.recordingFailed');
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
                reject(new Error(t('audio.errors.noActiveRecording')));
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
                    error.value = t('audio.errors.transcriptionFailed');

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
            const response = await fetch('/voice-transcription', {
                method: 'POST',
                headers: {
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                body: formData,
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(t('audio.errors.transcriptionRequestFailed'));
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
