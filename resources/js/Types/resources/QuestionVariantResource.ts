/**
 * TypeScript definition for QuestionVariantResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

export interface QuestionVariantResource {
    readonly id: number;
    readonly questionId: string;
    readonly personalityPattern: string | null;
    readonly phrasing: string;
    readonly isActive: boolean;
    readonly createdAt: string;
    readonly updatedAt: string;
}
