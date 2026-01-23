import { getCsrfToken } from '@/Utils/cookies';
import { ref } from 'vue';

interface PreparePromptNode {
    name: string;
    passNumber: number;
    passInput: object | null;
    javascriptOld: string | null;
    javascriptNew: string | null;
    promptOld: object | null;
    promptNew: object | null;
}

export function useWorkflowOperations(
    workflowRoute: (
        name: string,
        parameters?: Record<string, unknown>,
    ) => string,
) {
    const isPreparingOld = ref(false);
    const isPreparingNew = ref(false);
    const isExecutingOld = ref(false);
    const isExecutingNew = ref(false);
    const isUploadingOld = ref(false);
    const isUploadingNew = ref(false);
    const isUploadingToLive = ref(false);
    const error = ref<string | null>(null);
    const saveMessage = ref<string | null>(null);

    const makeRequest = async (url: string, method: string, body?: unknown) => {
        const csrfToken = getCsrfToken();
        const headers: Record<string, string> = {
            'Content-Type': 'application/json',
        };
        if (csrfToken) {
            const decodedToken = decodeURIComponent(csrfToken);
            headers['X-CSRF-TOKEN'] = decodedToken;
            headers['X-XSRF-TOKEN'] = decodedToken;
        }

        const config: RequestInit = {
            method,
            headers,
            credentials: 'same-origin',
        };
        if (body) {
            config.body = JSON.stringify(body);
        }

        return fetch(url, config);
    };

    const reloadJavaScriptFromWorkflowAsOld = async (
        currentVariant: string,
        preparePromptNodes: PreparePromptNode[],
    ) => {
        try {
            const response = await makeRequest(
                workflowRoute('workflows.reload-javascript-old', {
                    variant: currentVariant,
                }),
                'POST',
            );

            const result = await response.json();
            if (!result.success) {
                error.value =
                    result.error || 'Failed to reload JavaScript from workflow';
            } else {
                if (
                    result.reloadedNodes &&
                    Array.isArray(result.reloadedNodes)
                ) {
                    result.reloadedNodes.forEach((reloadedNode) => {
                        const node = preparePromptNodes.find(
                            (n) => n.name === reloadedNode.nodeName,
                        );
                        if (node && reloadedNode.javascript) {
                            node.javascriptOld = reloadedNode.javascript;
                        }
                    });
                }
                saveMessage.value =
                    'JavaScript reloaded from workflow (not saved)';
                setTimeout(() => {
                    saveMessage.value = null;
                }, 3000);
                error.value = null;
            }
        } catch (err) {
            error.value = `Failed to reload JavaScript: ${err instanceof Error ? err.message : 'Unknown error'}`;
        }
    };

    const reloadJavaScriptFromWorkflowAsNew = async (
        currentVariant: string,
        preparePromptNodes: PreparePromptNode[],
    ) => {
        try {
            const response = await makeRequest(
                workflowRoute('workflows.reload-javascript-new', {
                    variant: currentVariant,
                }),
                'POST',
            );

            const result = await response.json();
            if (!result.success) {
                error.value =
                    result.error || 'Failed to reload JavaScript from workflow';
            } else {
                if (
                    result.reloadedNodes &&
                    Array.isArray(result.reloadedNodes)
                ) {
                    result.reloadedNodes.forEach((reloadedNode) => {
                        const node = preparePromptNodes.find(
                            (n) => n.name === reloadedNode.nodeName,
                        );
                        if (node && reloadedNode.javascript) {
                            node.javascriptNew = reloadedNode.javascript;
                        }
                    });
                }
                saveMessage.value =
                    'JavaScript reloaded from workflow (not saved)';
                setTimeout(() => {
                    saveMessage.value = null;
                }, 3000);
                error.value = null;
            }
        } catch (err) {
            error.value = `Failed to reload JavaScript: ${err instanceof Error ? err.message : 'Unknown error'}`;
        }
    };

    const executeWorkflowOld = async (
        inputJson: string,
        currentVariant: string,
        nodeName: string = 'Prepare Prompt',
    ) => {
        if (!inputJson) {
            error.value = 'Input data is required';
            return null;
        }

        isExecutingOld.value = true;
        error.value = null;

        try {
            let inputData;
            try {
                inputData = JSON.parse(inputJson);
            } catch {
                error.value = 'Invalid JSON in input data';
                return null;
            }

            const response = await makeRequest(
                workflowRoute('workflows.execute-workflow-old', {
                    variant: currentVariant,
                }),
                'POST',
                { input: inputData, nodeName },
            );

            if (!response.ok) {
                error.value = `Server error: ${response.status} ${response.statusText}`;
                return null;
            }

            const result = await response.json();

            if (!result.success) {
                error.value = result.error || 'Workflow execution failed';
                return null;
            }

            return result.output;
        } catch (err) {
            error.value = `Execution error: ${err instanceof Error ? err.message : 'Unknown error'}`;
            return null;
        } finally {
            isExecutingOld.value = false;
        }
    };

    const executeWorkflowNew = async (
        inputJson: string,
        currentVariant: string,
        nodeName: string = 'Prepare Prompt',
    ) => {
        if (!inputJson) {
            error.value = 'Input data is required';
            return null;
        }

        isExecutingNew.value = true;
        error.value = null;

        try {
            let inputData;
            try {
                inputData = JSON.parse(inputJson);
            } catch {
                error.value = 'Invalid JSON in input data';
                return null;
            }

            const response = await makeRequest(
                workflowRoute('workflows.execute-workflow-new', {
                    variant: currentVariant,
                }),
                'POST',
                { input: inputData, nodeName },
            );

            if (!response.ok) {
                error.value = `Server error: ${response.status} ${response.statusText}`;
                return null;
            }

            const result = await response.json();

            if (!result.success) {
                error.value = result.error || 'Workflow execution failed';
                return null;
            }

            return result.output;
        } catch (err) {
            error.value = `Execution error: ${err instanceof Error ? err.message : 'Unknown error'}`;
            return null;
        } finally {
            isExecutingNew.value = false;
        }
    };

    const uploadWorkflowOld = async (
        currentVariant: string,
        nodeName: string = 'Prepare Prompt',
    ) => {
        isUploadingOld.value = true;
        error.value = null;

        try {
            const response = await makeRequest(
                workflowRoute('workflows.upload-to-n8n-old', {
                    variant: currentVariant,
                }),
                'POST',
                { nodeName },
            );

            if (!response.ok) {
                error.value = `Server error: ${response.status} ${response.statusText}`;
                return false;
            }

            const result = await response.json();

            if (!result.success) {
                error.value =
                    result.error || 'Failed to upload workflow to n8n';
                return false;
            }

            error.value = null;
            return true;
        } catch (err) {
            error.value = `Upload error: ${err instanceof Error ? err.message : 'Unknown error'}`;
            return false;
        } finally {
            isUploadingOld.value = false;
        }
    };

    const uploadWorkflowNew = async (
        currentVariant: string,
        nodeName: string = 'Prepare Prompt',
    ) => {
        isUploadingNew.value = true;
        error.value = null;

        try {
            const response = await makeRequest(
                workflowRoute('workflows.upload-to-n8n-new', {
                    variant: currentVariant,
                }),
                'POST',
                { nodeName },
            );

            if (!response.ok) {
                error.value = `Server error: ${response.status} ${response.statusText}`;
                return false;
            }

            const result = await response.json();

            if (!result.success) {
                error.value =
                    result.error || 'Failed to upload workflow to n8n';
                return false;
            }

            error.value = null;
            return true;
        } catch (err) {
            error.value = `Upload error: ${err instanceof Error ? err.message : 'Unknown error'}`;
            return false;
        } finally {
            isUploadingNew.value = false;
        }
    };

    const uploadWorkflowToLive = async () => {
        isUploadingToLive.value = true;
        error.value = null;

        try {
            const response = await makeRequest(
                workflowRoute('workflows.upload-to-live'),
                'POST',
            );

            if (!response.ok) {
                error.value = `Server error: ${response.status} ${response.statusText}`;
                return false;
            }

            const result = await response.json();

            if (!result.success) {
                error.value =
                    result.error || 'Failed to upload workflow to live server';
                return false;
            }

            error.value = null;
            return true;
        } catch (err) {
            const errorMessage = `Upload error: ${err instanceof Error ? err.message : 'Unknown error'}`;
            error.value = errorMessage;
            return false;
        } finally {
            isUploadingToLive.value = false;
        }
    };

    const saveInputData = async (inputJson: string) => {
        try {
            let inputData;
            try {
                inputData = JSON.parse(inputJson);
            } catch {
                error.value = 'Invalid JSON in input data';
                return false;
            }

            const response = await makeRequest(
                workflowRoute('workflows.save-input'),
                'POST',
                Array.isArray(inputData) ? inputData : [inputData],
            );

            const result = await response.json();
            if (!result.success) {
                error.value = result.error || 'Failed to save input data';
                return false;
            } else {
                saveMessage.value = 'Input data saved successfully!';
                setTimeout(() => {
                    saveMessage.value = null;
                }, 3000);
                error.value = null;
                return true;
            }
        } catch (err) {
            error.value = `Failed to save input data: ${err instanceof Error ? err.message : 'Unknown error'}`;
            return false;
        }
    };

    const saveOldJavaScriptToFile = async (
        code: string,
        currentVariant: string,
        nodeName: string,
    ) => {
        try {
            const response = await makeRequest(
                workflowRoute('workflows.save-javascript-old'),
                'POST',
                { code, variant: currentVariant, nodeName },
            );

            if (!response.ok) {
                error.value = `Server error: ${response.status} ${response.statusText}`;
                return false;
            }

            const result = await response.json();
            if (!result.success) {
                error.value = result.error || 'Failed to save JavaScript code';
                return false;
            } else {
                saveMessage.value = 'Old JavaScript code saved successfully!';
                setTimeout(() => {
                    saveMessage.value = null;
                }, 3000);
                error.value = null;
                return true;
            }
        } catch (err) {
            error.value = `Failed to save JavaScript code: ${err instanceof Error ? err.message : 'Unknown error'}`;
            return false;
        }
    };

    const saveNewJavaScriptToFile = async (
        code: string,
        currentVariant: string,
        nodeName: string,
    ) => {
        try {
            const response = await makeRequest(
                workflowRoute('workflows.save-javascript-new'),
                'POST',
                { code, variant: currentVariant, nodeName },
            );

            if (!response.ok) {
                error.value = `Server error: ${response.status} ${response.statusText}`;
                return false;
            }

            const result = await response.json();
            if (!result.success) {
                error.value =
                    result.error || 'Failed to save new JavaScript code';
                return false;
            } else {
                saveMessage.value = 'New JavaScript code saved successfully!';
                setTimeout(() => {
                    saveMessage.value = null;
                }, 3000);
                error.value = null;
                return true;
            }
        } catch (err) {
            error.value = `Failed to save new JavaScript code: ${err instanceof Error ? err.message : 'Unknown error'}`;
            return false;
        }
    };

    const preparePromptOld = async (
        inputJson: string,
        currentVariant: string,
        preparePromptNodes: PreparePromptNode[],
        nodeName: string = 'Prepare Prompt',
    ) => {
        const node = preparePromptNodes.find((n) => n.name === nodeName);
        if (!node?.javascriptOld || !inputJson) {
            error.value = 'Both input and JavaScript code are required';
            return null;
        }

        isPreparingOld.value = true;
        error.value = null;

        try {
            let inputData;
            try {
                inputData = JSON.parse(inputJson);
            } catch {
                error.value = 'Invalid JSON in input data';
                return null;
            }

            if (Array.isArray(inputData) && inputData.length > 0) {
                inputData = inputData[0];
            }

            const currentPassIndex = preparePromptNodes.findIndex(
                (n) => n.name === nodeName,
            );
            if (currentPassIndex > 0) {
                const previousNode = preparePromptNodes[currentPassIndex - 1];
                if (!previousNode?.promptOld) {
                    error.value = `Pass ${currentPassIndex + 1} requires Pass ${currentPassIndex} to be run first. Please run "Prepare Prompt ${currentPassIndex === 1 ? '' : currentPassIndex}" and generate its output first.`;
                    return null;
                }

                inputData.classification =
                    previousNode.promptOld.classification;
                inputData.selected_questions =
                    previousNode.promptOld.selected_questions;
            }

            if (nodeName === 'Prepare Prompt') {
                await saveOldJavaScriptToFile(
                    node.javascriptOld,
                    currentVariant,
                    nodeName,
                );
            }

            const response = await makeRequest(
                workflowRoute('workflows.prepare-prompt-old'),
                'POST',
                { input: inputData, variant: currentVariant, nodeName },
            );

            if (!response.ok) {
                error.value = `Server error: ${response.status} ${response.statusText}`;
                return null;
            }

            const result = await response.json();

            if (!result.success) {
                error.value = result.error || 'Execution failed';
                return null;
            }

            let promptData = result.prompt;
            if (
                Array.isArray(promptData) &&
                promptData.length > 0 &&
                promptData[0] &&
                promptData[0].json
            ) {
                promptData = promptData[0].json;
            }
            node.promptOld = promptData;
            return promptData;
        } catch (err) {
            error.value = `Execution error: ${err instanceof Error ? err.message : 'Unknown error'}`;
            return null;
        } finally {
            isPreparingOld.value = false;
        }
    };

    const preparePromptNew = async (
        inputJson: string,
        currentVariant: string,
        preparePromptNodes: PreparePromptNode[],
        nodeName: string = 'Prepare Prompt',
    ) => {
        const node = preparePromptNodes.find((n) => n.name === nodeName);
        if (!node?.javascriptNew || !inputJson) {
            error.value = 'Both input and JavaScript code are required';
            return null;
        }

        isPreparingNew.value = true;
        error.value = null;

        try {
            let inputData;
            try {
                inputData = JSON.parse(inputJson);
            } catch {
                error.value = 'Invalid JSON in input data';
                return null;
            }

            if (Array.isArray(inputData) && inputData.length > 0) {
                inputData = inputData[0];
            }

            const currentPassIndex = preparePromptNodes.findIndex(
                (n) => n.name === nodeName,
            );
            if (currentPassIndex > 0) {
                const previousNode = preparePromptNodes[currentPassIndex - 1];
                if (!previousNode?.promptNew) {
                    error.value = `Pass ${currentPassIndex + 1} requires Pass ${currentPassIndex} to be run first. Please run "Prepare Prompt ${currentPassIndex === 1 ? '' : currentPassIndex}" and generate its output first.`;
                    return null;
                }

                inputData.classification =
                    previousNode.promptNew.classification;
                inputData.selected_questions =
                    previousNode.promptNew.selected_questions;
            }

            if (nodeName === 'Prepare Prompt') {
                await saveNewJavaScriptToFile(
                    node.javascriptNew,
                    currentVariant,
                    nodeName,
                );
            }

            const response = await makeRequest(
                workflowRoute('workflows.prepare-prompt-new'),
                'POST',
                { input: inputData, variant: currentVariant, nodeName },
            );

            if (!response.ok) {
                error.value = `Server error: ${response.status} ${response.statusText}`;
                return null;
            }

            const result = await response.json();

            if (!result.success) {
                error.value = result.error || 'Execution failed';
                return null;
            }

            let promptData = result.prompt;

            if (
                Array.isArray(promptData) &&
                promptData.length > 0 &&
                promptData[0] &&
                promptData[0].json
            ) {
                promptData = promptData[0].json;
            }

            node.promptNew = promptData;
            return promptData;
        } catch (err) {
            error.value = `Execution error: ${err instanceof Error ? err.message : 'Unknown error'}`;
            return null;
        } finally {
            isPreparingNew.value = false;
        }
    };

    const savePassInputData = async (
        nodeName: string,
        passNumber: number,
        passInputJson: string,
    ) => {
        try {
            let inputData;
            try {
                inputData = JSON.parse(passInputJson);
            } catch {
                error.value = 'Invalid JSON in pass input data';
                return false;
            }

            const response = await makeRequest(
                workflowRoute('workflows.save-pass-input', { passNumber }),
                'POST',
                Array.isArray(inputData) ? inputData : [inputData],
            );

            const result = await response.json();
            if (!result.success) {
                error.value = result.error || 'Failed to save pass input data';
                return false;
            } else {
                saveMessage.value = `Pass ${passNumber} input saved successfully!`;
                setTimeout(() => {
                    saveMessage.value = null;
                }, 3000);
                error.value = null;
                return true;
            }
        } catch (err) {
            error.value = `Failed to save pass input data: ${
                err instanceof Error ? err.message : 'Unknown error'
            }`;
            return false;
        }
    };

    return {
        isPreparingOld,
        isPreparingNew,
        isExecutingOld,
        isExecutingNew,
        isUploadingOld,
        isUploadingNew,
        isUploadingToLive,
        error,
        saveMessage,
        reloadJavaScriptFromWorkflowAsOld,
        reloadJavaScriptFromWorkflowAsNew,
        executeWorkflowOld,
        executeWorkflowNew,
        uploadWorkflowOld,
        uploadWorkflowNew,
        uploadWorkflowToLive,
        saveInputData,
        saveOldJavaScriptToFile,
        saveNewJavaScriptToFile,
        preparePromptOld,
        preparePromptNew,
        savePassInputData,
    };
}
