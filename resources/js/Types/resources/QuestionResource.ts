/**
 * TypeScript definition for QuestionResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

import type { QuestionVariantResource } from '@/Types';

export interface QuestionResource {
    readonly id: string;
    readonly questionText: string;
    readonly purpose: string | null;
    readonly priority: string | null;
    readonly taskCategoryCode: string | null;
    readonly frameworkCode: string | null;
    readonly isUniversal: boolean;
    readonly isConditional: boolean;
    readonly conditionText: string | null;
    readonly displayOrder: number;
    readonly isActive: boolean;
    readonly variantsCount: number;
    readonly variants?: QuestionVariantResource[];
    readonly createdAt: string;
    readonly updatedAt: string;
}
