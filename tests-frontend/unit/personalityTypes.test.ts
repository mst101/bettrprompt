import {
    getFullPersonalityType,
    getPersonalityTypeName,
} from '@/Utils/data/personalityTypes';
import { describe, expect, it } from 'vitest';

describe('personalityTypes utilities', () => {
    describe('getPersonalityTypeName', () => {
        it('should return display name for INTJ', () => {
            expect(getPersonalityTypeName('INTJ-A')).toBe('Architect');
        });

        it('should return display name for ENFP', () => {
            expect(getPersonalityTypeName('ENFP-T')).toBe('Campaigner');
        });

        it('should extract base type from code with assertiveness suffix', () => {
            expect(getPersonalityTypeName('ISTJ-A')).toBe('Logistician');
        });

        it('should extract base type from code with turbulent suffix', () => {
            expect(getPersonalityTypeName('ESFP-T')).toBe('Entertainer');
        });

        it('should work with all 16 personality types', () => {
            const allTypes = [
                ['INTJ', 'Architect'],
                ['INTP', 'Logician'],
                ['ENTJ', 'Commander'],
                ['ENTP', 'Debater'],
                ['INFJ', 'Advocate'],
                ['INFP', 'Mediator'],
                ['ENFJ', 'Protagonist'],
                ['ENFP', 'Campaigner'],
                ['ISTJ', 'Logistician'],
                ['ISFJ', 'Defender'],
                ['ESTJ', 'Executive'],
                ['ESFJ', 'Consul'],
                ['ISTP', 'Virtuoso'],
                ['ISFP', 'Adventurer'],
                ['ESTP', 'Entrepreneur'],
                ['ESFP', 'Entertainer'],
            ];

            allTypes.forEach(([code, name]) => {
                expect(getPersonalityTypeName(`${code}-A`)).toBe(name);
                expect(getPersonalityTypeName(`${code}-T`)).toBe(name);
            });
        });

        it('should return empty string for null', () => {
            expect(getPersonalityTypeName(null)).toBe('');
        });

        it('should return empty string for undefined', () => {
            expect(getPersonalityTypeName(undefined as any)).toBe('');
        });

        it('should return empty string for empty string', () => {
            expect(getPersonalityTypeName('')).toBe('');
        });

        it('should return empty string for invalid type', () => {
            expect(getPersonalityTypeName('INVALID-A')).toBe('');
        });

        it('should handle type without assertiveness suffix', () => {
            expect(getPersonalityTypeName('INTJ')).toBe('Architect');
        });
    });

    describe('getFullPersonalityType', () => {
        it('should return formatted name with code for INTJ-A', () => {
            expect(getFullPersonalityType('INTJ-A')).toBe('Architect (INTJ-A)');
        });

        it('should return formatted name with code for ENFP-T', () => {
            expect(getFullPersonalityType('ENFP-T')).toBe(
                'Campaigner (ENFP-T)',
            );
        });

        it('should return code only if name not found', () => {
            expect(getFullPersonalityType('INVALID-A')).toBe('INVALID-A');
        });

        it('should return empty string for null', () => {
            expect(getFullPersonalityType(null)).toBe('');
        });

        it('should return empty string for undefined', () => {
            expect(getFullPersonalityType(undefined as any)).toBe('');
        });

        it('should return empty string for empty string', () => {
            expect(getFullPersonalityType('')).toBe('');
        });

        it('should handle type without assertiveness suffix', () => {
            expect(getFullPersonalityType('ISTJ')).toBe('Logistician (ISTJ)');
        });

        it('should format all 16 personality types correctly', () => {
            const allTypes = [
                ['INTJ-A', 'Architect (INTJ-A)'],
                ['INTP-T', 'Logician (INTP-T)'],
                ['ENTJ-A', 'Commander (ENTJ-A)'],
                ['ENTP-T', 'Debater (ENTP-T)'],
                ['INFJ-A', 'Advocate (INFJ-A)'],
                ['INFP-T', 'Mediator (INFP-T)'],
                ['ENFJ-A', 'Protagonist (ENFJ-A)'],
                ['ENFP-T', 'Campaigner (ENFP-T)'],
            ];

            allTypes.forEach(([code, expected]) => {
                expect(getFullPersonalityType(code)).toBe(expected);
            });
        });
    });
});
