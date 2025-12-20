export interface ClaudeModel {
    readonly id: string;
    readonly name: string;
    readonly tier: 'haiku' | 'sonnet' | 'opus';
    readonly version: number;
    readonly inputCostPerMtok: number;
    readonly outputCostPerMtok: number;
    readonly releaseDate: string | null;
    readonly active: boolean;
    readonly positioning: string | null;
    readonly contextWindowInput: number | null;
    readonly contextWindowOutput: number | null;
}

export interface ApiUsageData {
    model: string;
    inputTokens: number;
    outputTokens: number;
}

export interface CalculatedCost {
    inputCost: number;
    outputCost: number;
    totalCost: number;
}
