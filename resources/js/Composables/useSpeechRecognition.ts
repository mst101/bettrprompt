import { onUnmounted, ref } from 'vue';

export interface SpeechRecognitionOptions {
    lang?: string;
    continuous?: boolean;
    interimResults?: boolean;
    maxAlternatives?: number;
}

export function useSpeechRecognition(options: SpeechRecognitionOptions = {}) {
    // Check browser support
    const SpeechRecognition =
        (window as any).SpeechRecognition ||
        (window as any).webkitSpeechRecognition;

    const isSupported = ref(!!SpeechRecognition);
    const isListening = ref(false);
    const transcript = ref('');
    const interimTranscript = ref('');
    const error = ref<string | null>(null);

    let recognition: any = null;

    if (isSupported.value) {
        recognition = new SpeechRecognition();
        recognition.lang = options.lang || 'en-GB';
        recognition.continuous = options.continuous ?? true;
        recognition.interimResults = options.interimResults ?? true;
        recognition.maxAlternatives = options.maxAlternatives || 1;

        recognition.onstart = () => {
            isListening.value = true;
            error.value = null;
        };

        recognition.onresult = (event: any) => {
            let interim = '';
            let final = '';

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcriptPart = event.results[i][0].transcript;

                if (event.results[i].isFinal) {
                    final += transcriptPart + ' ';
                } else {
                    interim += transcriptPart;
                }
            }

            if (final) {
                transcript.value += final;
            }
            interimTranscript.value = interim;
        };

        recognition.onerror = (event: any) => {
            isListening.value = false;

            switch (event.error) {
                case 'no-speech':
                    error.value = 'No speech detected. Please try again.';
                    break;
                case 'audio-capture':
                    error.value =
                        'No microphone found. Please check your device.';
                    break;
                case 'not-allowed':
                    error.value =
                        'Microphone access denied. Please enable microphone permissions.';
                    break;
                case 'network':
                    error.value =
                        'Network error occurred. Please check your connection.';
                    break;
                default:
                    error.value = `Speech recognition error: ${event.error}`;
            }

            // Auto-dismiss error after 5 seconds
            setTimeout(() => {
                error.value = null;
            }, 5000);
        };

        recognition.onend = () => {
            isListening.value = false;
        };
    }

    const start = () => {
        if (!isSupported.value) {
            error.value =
                'Speech recognition is not supported in this browser.';
            setTimeout(() => {
                error.value = null;
            }, 5000);
            return;
        }

        if (isListening.value) {
            return;
        }

        transcript.value = '';
        interimTranscript.value = '';
        error.value = null;

        try {
            recognition.start();
        } catch (err) {
            error.value = 'Failed to start speech recognition.';
            setTimeout(() => {
                error.value = null;
            }, 5000);
            console.error('Speech recognition error:', err);
        }
    };

    const stop = () => {
        if (!isSupported.value || !isListening.value) {
            return;
        }

        try {
            recognition.stop();
        } catch (err) {
            console.error('Error stopping speech recognition:', err);
        }
    };

    const reset = () => {
        transcript.value = '';
        interimTranscript.value = '';
        error.value = null;
    };

    // Cleanup on component unmount
    onUnmounted(() => {
        if (recognition && isListening.value) {
            recognition.stop();
        }
    });

    return {
        isSupported,
        isListening,
        transcript,
        interimTranscript,
        error,
        start,
        stop,
        reset,
    };
}
