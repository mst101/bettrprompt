/**
 * TypeScript definition for ClaudeModelResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

export interface ClaudeModelResource {
    readonly id: string;
    readonly name: string;
    readonly tier: 'haiku' | 'sonnet' | 'opus';
    readonly version: number;
    readonly inputCostPerMtok: string;
    readonly outputCostPerMtok: string;
    readonly releaseDate: string | null;
    readonly active: boolean;
    readonly positioning: string | null;
    readonly contextWindowInput: number | null;
    readonly contextWindowOutput: number | null;
}
