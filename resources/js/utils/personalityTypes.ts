/**
 * Personality type utilities for formatting and displaying personality data
 */

import { PERSONALITY_TYPE_NAMES } from '@/constants/workflow';

/**
 * Get the display name for a personality type code
 *
 * @param type - Personality type code (e.g., "INTJ-A", "ENFP-T")
 * @returns Display name (e.g., "Architect") or empty string if invalid
 *
 * @example
 * getPersonalityTypeName('INTJ-A')
 * // Returns: "Architect"
 *
 * getPersonalityTypeName('ENFP-T')
 * // Returns: "Campaigner"
 *
 * getPersonalityTypeName(null)
 * // Returns: ""
 */
export function getPersonalityTypeName(type: string | null): string {
    if (!type) return '';
    const baseType = type.split('-')[0] as keyof typeof PERSONALITY_TYPE_NAMES;
    return PERSONALITY_TYPE_NAMES[baseType] || '';
}

/**
 * Get the full personality type display string
 *
 * @param type - Personality type code (e.g., "INTJ-A", "ENFP-T")
 * @returns Full display string with name and code, or just the code if name not found
 *
 * @example
 * getFullPersonalityType('INTJ-A')
 * // Returns: "Architect (INTJ-A)"
 *
 * getFullPersonalityType('ENFP-T')
 * // Returns: "Campaigner (ENFP-T)"
 *
 * getFullPersonalityType(null)
 * // Returns: ""
 */
export function getFullPersonalityType(type: string | null): string {
    if (!type) return '';
    const name = getPersonalityTypeName(type);
    return name ? `${name} (${type})` : type;
}
