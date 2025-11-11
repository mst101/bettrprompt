/**
 * Base props shared across all form field components
 */
export interface BaseFormFieldProps {
    /** Unique identifier for the form field */
    id: string;

    /** Label text displayed for the field */
    label: string;

    /** Error message to display */
    error?: string;

    /** Whether the field is required */
    required?: boolean;

    /** Placeholder text */
    placeholder?: string;

    /** Whether the field is disabled */
    disabled?: boolean;

    /** Help text displayed below the field */
    helpText?: string;

    /** Additional CSS classes to apply to the input element */
    customClass?: string;

    /** Whether to auto-focus the field on mount */
    autofocus?: boolean;
}

/**
 * Props for FormInput component
 */
export interface FormInputProps extends BaseFormFieldProps {
    /** Current input value */
    modelValue?: string | number | null;

    /** Input type (text, email, password, number, etc.) */
    type?: string;

    /** Autocomplete attribute value */
    autocomplete?: string;

    /** Minimum value (for number inputs) */
    min?: number | string;

    /** Maximum value (for number inputs) */
    max?: number | string;
}

/**
 * Option structure for select dropdowns
 */
export interface SelectOption {
    value: string | number;
    label: string;
}

/**
 * Props for FormSelect component
 */
export interface FormSelectProps extends BaseFormFieldProps {
    /** Current selected value */
    modelValue?: string | number;

    /** Array of options to display */
    options: SelectOption[];

    /** Whether to show the placeholder option */
    showPlaceholder?: boolean;
}

/**
 * Props for FormTextarea component
 */
export interface FormTextareaProps extends BaseFormFieldProps {
    /** Current textarea value */
    modelValue?: string;

    /** Number of visible text lines */
    rows?: number;

    /** Maximum character length */
    maxlength?: number;
}
