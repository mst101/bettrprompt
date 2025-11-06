export type Nullable<T> = T | null;
export type DateTime = string;
export type ButtonType = 'button' | 'submit' | 'reset' | undefined;

export interface WithTimestamps {
    createdAt: DateTime;
    updatedAt: DateTime;
}

export interface WithSoftDeletes extends WithTimestamps {
    deletedAt: Nullable<DateTime>;
}

// JSON data structure for common fields
export interface JsonData {
    [key: string]: any;
}
