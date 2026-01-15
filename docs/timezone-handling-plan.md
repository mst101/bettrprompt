# Timezone Handling Plan

## Context and Findings
- We store `timezone` on both `users` and `visitors`.
- Timezone is populated from GeoIP in the backend (see `App\Services\GeolocationService`).
- The Profile Location form also uses the browser timezone when the country changes and the timezone field is empty (see `resources/js/Pages/Profile/Partials/UpdateLocationForm.vue`, `Intl.DateTimeFormat().resolvedOptions().timeZone`).
- The Profile form and the Prompt Builder location modal both expose a short, hard-coded timezone list (about 15 entries), which is too limited for global coverage.

## Recommendation Summary
- Store canonical IANA timezone IDs (e.g., `America/New_York`) only.
- Continue defaulting from GeoIP; use browser timezone as a fallback hint.
- Replace the hard-coded short list with a searchable list of all IANA IDs plus a curated “Top timezones” shortlist.
- Do not create a database table for timezones unless we need admin-managed timezone metadata or analytics tied to a stable timezone dimension.

## Should We Add a Database Table?
### Recommendation: Not now
- A timezone table adds maintenance overhead (keeping IANA up to date, migrations, sync jobs) without immediate product value.
- We can provide a full IANA list from a static JSON file or a backend endpoint generated from PHP’s `DateTimeZone::listIdentifiers()`.
- UI needs flexibility (search, grouping, offsets) more than relational joins.

### When a DB table would be justified
- We need admin-curated labels/translations per timezone.
- We need analytics dimensions with human-friendly labels and offset snapshots.
- We want country-to-timezone mapping overrides stored in-app.

## Plan of Action
1. Confirm data sources and precedence.
   - Source order: user-set > visitor-set > browser timezone hint (explicit or empty-field fallback) > GeoIP > null.
   - Browser timezone should not override GeoIP silently; only use it when GeoIP is missing or the user explicitly chooses it.

2. Normalize timezone values.
   - Validate and store only valid IANA identifiers at the API boundary.
   - Reject non-IANA strings (offset-only values) or convert offsets to best-match IANA if provided.

3. Replace the timezone selector UI.
   - Provide a searchable list of all IANA timezones.
   - Include a curated “Top timezones” section (15–30) for quick selection.
   - Display current offset in labels to help users (e.g., “America/New_York (UTC-05:00)”).

4. Centralize timezone list generation.
   - Option A (frontend static): include a `resources/js/data/timezones.json` generated from IANA identifiers.
   - Option B (backend endpoint): expose `/timezones` from PHP using `DateTimeZone::listIdentifiers()`, and compute offsets server-side.
   - Decision: use Option A as the single source of truth for now, shared by all timezone selectors.

5. Improve browser timezone usage.
   - Use browser timezone as a suggestion if the field is empty (current behavior), but do not silently overwrite user-set values.
   - Consider surfacing a “Use browser timezone” CTA for explicit user choice (follow-up).

6. QA and migration considerations.
   - No data migration required if existing values are already IANA IDs.
   - Add validation tests to ensure invalid values are rejected.
   - Add UI tests for search + selecting common and uncommon timezones.

## Decisions
- Browser timezone is used only when GeoIP is missing or by explicit user action; no silent overrides.
- IANA label + UTC offset is sufficient for now; localized display names can be added later if needed.
- The Prompt Builder modal and Profile form should share the same timezone component to avoid drift.

## Execution Notes
- Implement a shared timezone selector component with search + curated top list.
- Provide a full IANA list via `resources/js/data/timezones.json`.
- Validate timezone values against IANA identifiers at the API boundary.
