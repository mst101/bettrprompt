<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import Modal from '@/Components/Base/Modal/Modal.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    show: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
    close: [];
}>();

const selectedPlan = ref<'monthly' | 'yearly'>('yearly');
const isLoading = ref(false);

function subscribe() {
    isLoading.value = true;
    router.post(
        route('subscription.checkout'),
        { plan: selectedPlan.value },
        {
            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
}
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="p-6">
            <h2 class="mb-2 text-xl font-bold text-indigo-900">
                You've reached your monthly limit
            </h2>
            <p class="mb-6 text-indigo-600">
                Free accounts are limited to 10 prompts per month. Upgrade to
                Pro for unlimited access.
            </p>

            <div class="mb-6 grid grid-cols-2 gap-4">
                <button
                    type="button"
                    data-testid="monthly-option"
                    :class="[
                        'rounded-lg border-2 p-4 text-left transition',
                        selectedPlan === 'monthly'
                            ? 'border-indigo-500 bg-indigo-50'
                            : 'border-indigo-200 hover:border-indigo-300',
                    ]"
                    @click="selectedPlan = 'monthly'"
                >
                    <div class="font-semibold text-indigo-900">Monthly</div>
                    <div class="text-2xl font-bold text-indigo-900">
                        &pound;12<span class="text-sm font-normal">/mo</span>
                    </div>
                </button>

                <button
                    type="button"
                    data-testid="yearly-option"
                    :class="[
                        'relative rounded-lg border-2 p-4 text-left transition',
                        selectedPlan === 'yearly'
                            ? 'border-indigo-500 bg-indigo-50'
                            : 'border-indigo-200 hover:border-indigo-300',
                    ]"
                    @click="selectedPlan = 'yearly'"
                >
                    <div
                        class="absolute -top-2 -right-2 rounded-full bg-green-500 px-2 py-0.5 text-xs text-white"
                    >
                        Save 18%
                    </div>
                    <div class="font-semibold text-indigo-900">Annual</div>
                    <div class="text-2xl font-bold text-indigo-900">
                        &pound;99<span class="text-sm font-normal">/yr</span>
                    </div>
                    <div class="text-sm text-indigo-500">&pound;8.25/month</div>
                </button>
            </div>

            <div class="flex gap-4">
                <ButtonSecondary class="flex-1" @click="emit('close')">
                    Maybe Later
                </ButtonSecondary>
                <ButtonPrimary
                    class="flex-1"
                    data-testid="upgrade-now-button"
                    :disabled="isLoading"
                    :loading="isLoading"
                    @click="subscribe"
                >
                    {{ isLoading ? 'Processing...' : 'Upgrade Now' }}
                </ButtonPrimary>
            </div>
        </div>
    </Modal>
</template>
