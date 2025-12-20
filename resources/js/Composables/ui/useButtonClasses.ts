import { computed } from 'vue';

type ButtonVariant = 'primary' | 'secondary' | 'danger' | 'success';
type ButtonSize = 'sm' | 'md' | 'lg';

export interface ButtonStyleProps {
    variant?: ButtonVariant;
    size?: ButtonSize;
}

export function useButtonClasses(props: ButtonStyleProps) {
    return computed(() => {
        const base =
            'gap-2 tracking-wider uppercase border inline-flex items-center justify-center font-medium transition-colors duration-150 focus:ring-offset-2 focus:ring-offset-indigo-100 focus:ring-2 focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50';

        const variants = {
            primary:
                'border-transparent bg-indigo-600 text-white shadow-xs hover:bg-indigo-700 focus:ring-indigo-500',
            secondary:
                'border-indigo-100 bg-indigo-50 text-indigo-700 shadow-xs hover:bg-indigo-100 dark:bg-indigo-100 dark:text-indigo-900 dark:hover:bg-indigo-200 focus:ring-indigo-500',
            danger: 'uppercase border-transparent bg-red-600 text-white shadow-xs hover:bg-red-700 focus:ring-red-500',
            success:
                'border-transparent bg-green-500 text-white shadow-xs hover:bg-green-700 focus:ring-green-500',
        };

        const sizes = {
            sm: 'rounded-md px-3 py-1.5 text-xs',
            md: 'rounded-md px-4 py-2 text-xs',
            lg: 'rounded-md px-6 py-3 text-base',
        };

        const variant = props.variant ?? 'primary';
        const size = props.size ?? 'md';

        return [base, variants[variant], sizes[size]].join(' ');
    });
}
