export interface Paginated<T> {
    data: T[];
    meta: {
        currentPage: number;
        lastPage: number;
        from: number | null;
        to: number | null;
        perPage: number;
        path: string;
        total: number;
        hasMorePages: boolean;
        nextPageUrl: string | null;
        prevPageUrl: string | null;
    };
}
