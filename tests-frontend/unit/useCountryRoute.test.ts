import { useCountryRoute } from '@/Composables/useCountryRoute';
import { usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(),
}));

describe('useCountryRoute', () => {
    let mockUsePage: any;

    beforeEach(() => {
        mockUsePage = usePage as any;
        (global as any).route = vi.fn((name, params) => {
            return `/${params?.country || 'gb'}/${name}`;
        });
        vi.clearAllMocks();
    });

    describe('Extracting country from props', () => {
        it('should return gb as default country', () => {
            mockUsePage.mockReturnValue({ props: {} });

            const { currentCountry } = useCountryRoute();

            expect(currentCountry.value).toBe('gb');
        });

        it('should extract country from page props', () => {
            mockUsePage.mockReturnValue({
                props: { country: 'us' },
            });

            const { currentCountry } = useCountryRoute();

            expect(currentCountry.value).toBe('us');
        });

        it('should support various country codes', () => {
            const countries = ['us', 'de', 'fr', 'mx', 'sg', 'au'];

            for (const country of countries) {
                mockUsePage.mockReturnValue({
                    props: { country },
                });

                const { currentCountry } = useCountryRoute();
                expect(currentCountry.value).toBe(country);
            }
        });

        it('should fallback to gb when country is not provided', () => {
            mockUsePage.mockReturnValue({
                props: { country: undefined },
            });

            const { currentCountry } = useCountryRoute();

            expect(currentCountry.value).toBe('gb');
        });

        it('should fallback to gb for empty string', () => {
            mockUsePage.mockReturnValue({
                props: { country: '' },
            });

            const { currentCountry } = useCountryRoute();

            expect(currentCountry.value).toBe('gb');
        });
    });

    describe('Extracting locale from props', () => {
        it('should return en-GB as default locale', () => {
            mockUsePage.mockReturnValue({ props: {} });

            const { currentLocale } = useCountryRoute();

            expect(currentLocale.value).toBe('en-GB');
        });

        it('should extract locale from page props', () => {
            mockUsePage.mockReturnValue({
                props: { locale: 'es-MX' },
            });

            const { currentLocale } = useCountryRoute();

            expect(currentLocale.value).toBe('es-MX');
        });

        it('should support various locale formats', () => {
            const locales = ['en-GB', 'de-DE', 'es-ES', 'es-MX', 'fr-FR'];

            for (const locale of locales) {
                mockUsePage.mockReturnValue({
                    props: { locale },
                });

                const { currentLocale } = useCountryRoute();
                expect(currentLocale.value).toBe(locale);
            }
        });
    });

    describe('Extracting currency from props', () => {
        it('should return GBP as default currency', () => {
            mockUsePage.mockReturnValue({ props: {} });

            const { currentCurrency } = useCountryRoute();

            expect(currentCurrency.value).toBe('GBP');
        });

        it('should extract currency from page props', () => {
            mockUsePage.mockReturnValue({
                props: { currency: 'USD' },
            });

            const { currentCurrency } = useCountryRoute();

            expect(currentCurrency.value).toBe('USD');
        });

        it('should support various currency codes', () => {
            const currencies = ['USD', 'EUR', 'GBP', 'JPY', 'AUD'];

            for (const currency of currencies) {
                mockUsePage.mockReturnValue({
                    props: { currency },
                });

                const { currentCurrency } = useCountryRoute();
                expect(currentCurrency.value).toBe(currency);
            }
        });
    });

    describe('countryRoute function', () => {
        it('should generate route with country parameter', () => {
            mockUsePage.mockReturnValue({
                props: { country: 'us' },
            });

            const { countryRoute } = useCountryRoute();

            countryRoute('pricing');

            expect((global as any).route).toHaveBeenCalledWith('pricing', {
                country: 'us',
            });
        });

        it('should merge additional parameters with country', () => {
            mockUsePage.mockReturnValue({
                props: { country: 'de' },
            });

            const { countryRoute } = useCountryRoute();

            countryRoute('product.show', { id: 123 });

            expect((global as any).route).toHaveBeenCalledWith('product.show', {
                country: 'de',
                id: 123,
            });
        });

        it('should use default gb when no country prop', () => {
            mockUsePage.mockReturnValue({
                props: {},
            });

            const { countryRoute } = useCountryRoute();

            countryRoute('home');

            expect((global as any).route).toHaveBeenCalledWith('home', {
                country: 'gb',
            });
        });

        it('should work with no additional parameters', () => {
            mockUsePage.mockReturnValue({
                props: { country: 'gb' },
            });

            const { countryRoute } = useCountryRoute();

            countryRoute('home');

            expect((global as any).route).toHaveBeenCalledWith('home', {
                country: 'gb',
            });
        });
    });

    describe('Real-world usage scenarios', () => {
        it('should support UK setup', () => {
            mockUsePage.mockReturnValue({
                props: {
                    country: 'gb',
                    locale: 'en-GB',
                    currency: 'GBP',
                },
            });

            const { currentCountry, currentLocale, currentCurrency } =
                useCountryRoute();

            expect(currentCountry.value).toBe('gb');
            expect(currentLocale.value).toBe('en-GB');
            expect(currentCurrency.value).toBe('GBP');
        });

        it('should support US setup', () => {
            mockUsePage.mockReturnValue({
                props: {
                    country: 'us',
                    locale: 'en-US',
                    currency: 'USD',
                },
            });

            const { currentCountry, currentLocale, currentCurrency } =
                useCountryRoute();

            expect(currentCountry.value).toBe('us');
            expect(currentLocale.value).toBe('en-US');
            expect(currentCurrency.value).toBe('USD');
        });

        it('should support Mexico with Spanish', () => {
            mockUsePage.mockReturnValue({
                props: {
                    country: 'mx',
                    locale: 'es-MX',
                    currency: 'USD',
                },
            });

            const { currentCountry, currentLocale, currentCurrency } =
                useCountryRoute();

            expect(currentCountry.value).toBe('mx');
            expect(currentLocale.value).toBe('es-MX');
            expect(currentCurrency.value).toBe('USD');
        });

        it('should generate correct URL for country-prefixed routes', () => {
            mockUsePage.mockReturnValue({
                props: { country: 'de' },
            });

            const { countryRoute } = useCountryRoute();

            countryRoute('pricing');

            const callArgs = (global as any).route.mock.calls[0];
            expect(callArgs[0]).toBe('pricing');
            expect(callArgs[1].country).toBe('de');
        });
    });
});
