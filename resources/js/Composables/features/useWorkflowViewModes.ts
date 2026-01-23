import { ref } from 'vue';

export function useWorkflowViewModes() {
    // Modal state for maximized views
    const expandedView = ref<string | null>(null);

    // Track raw vs formatted mode for expanded views
    const rawModeMessagesOld = ref(false);
    const rawModeMessagesNew = ref(false);
    const rawModeSystemOld = ref(false);
    const rawModeSystemNew = ref(false);
    const rawModeWorkflowMessagesOld = ref(false);
    const rawModeWorkflowMessagesNew = ref(false);
    const rawModeWorkflowSystemOld = ref(false);
    const rawModeWorkflowSystemNew = ref(false);

    const handleRawModeUpdate = (
        section:
            | 'messages-old'
            | 'messages-new'
            | 'system-old'
            | 'system-new'
            | 'workflow-messages-old'
            | 'workflow-messages-new'
            | 'workflow-system-old'
            | 'workflow-system-new',
        isRaw: boolean,
    ) => {
        if (section === 'system-old') {
            rawModeSystemOld.value = isRaw;
        } else if (section === 'system-new') {
            rawModeSystemNew.value = isRaw;
        } else if (section === 'messages-old') {
            rawModeMessagesOld.value = isRaw;
        } else if (section === 'messages-new') {
            rawModeMessagesNew.value = isRaw;
        } else if (section === 'workflow-messages-old') {
            rawModeWorkflowMessagesOld.value = isRaw;
        } else if (section === 'workflow-messages-new') {
            rawModeWorkflowMessagesNew.value = isRaw;
        } else if (section === 'workflow-system-old') {
            rawModeWorkflowSystemOld.value = isRaw;
        } else if (section === 'workflow-system-new') {
            rawModeWorkflowSystemNew.value = isRaw;
        }
    };

    return {
        expandedView,
        rawModeMessagesOld,
        rawModeMessagesNew,
        rawModeSystemOld,
        rawModeSystemNew,
        rawModeWorkflowMessagesOld,
        rawModeWorkflowMessagesNew,
        rawModeWorkflowSystemOld,
        rawModeWorkflowSystemNew,
        handleRawModeUpdate,
    };
}
