export interface VisitorResource {
    id: string;
    user_id: number | null;
    utm_source: string | null;
    utm_medium: string | null;
    utm_campaign: string | null;
    utm_term: string | null;
    utm_content: string | null;
    referrer: string | null;
    landing_page: string | null;
    user_agent: string | null;
    ip_address: string | null;
    first_visit_at: string;
    last_visit_at: string;
    visit_count: number;
    converted_at: string | null;
    created_at: string;
    updated_at: string;
}
