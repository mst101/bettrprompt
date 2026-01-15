import type { SelectOption } from '@/Types';
import timezones from '@/data/timezones.json';

const TOP_TIMEZONES = [
    'UTC',
    'Europe/London',
    'Europe/Paris',
    'Europe/Berlin',
    'America/New_York',
    'America/Chicago',
    'America/Denver',
    'America/Los_Angeles',
    'America/Sao_Paulo',
    'Africa/Johannesburg',
    'Asia/Dubai',
    'Asia/Kolkata',
    'Asia/Shanghai',
    'Asia/Tokyo',
    'Australia/Sydney',
    'Pacific/Auckland',
];

const timezoneIds = timezones as string[];
let cachedAllOptions: SelectOption[] | null = null;
let cachedTopOptions: SelectOption[] | null = null;

const formatOffset = (minutes: number): string => {
    const sign = minutes >= 0 ? '+' : '-';
    const absMinutes = Math.abs(minutes);
    const hours = Math.floor(absMinutes / 60);
    const mins = absMinutes % 60;

    return `${sign}${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
};

const getTimezoneOffsetMinutes = (timezone: string, date: Date): number => {
    const formatter = new Intl.DateTimeFormat('en-US', {
        timeZone: timezone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });

    const parts = formatter.formatToParts(date);
    const values: Record<string, string> = {};
    for (const part of parts) {
        if (part.type !== 'literal') {
            values[part.type] = part.value;
        }
    }

    const asUtc = Date.UTC(
        Number(values.year),
        Number(values.month) - 1,
        Number(values.day),
        Number(values.hour),
        Number(values.minute),
        Number(values.second),
    );

    return Math.round((asUtc - date.getTime()) / 60000);
};

const formatTimezoneLabel = (timezone: string, date: Date): string => {
    try {
        const offsetMinutes = getTimezoneOffsetMinutes(timezone, date);
        const offset = formatOffset(offsetMinutes);

        return `${timezone} (UTC${offset})`;
    } catch {
        return timezone;
    }
};

const buildOptions = (ids: string[], date: Date): SelectOption[] =>
    ids.map((timezone) => ({
        value: timezone,
        label: formatTimezoneLabel(timezone, date),
    }));

export const getTimezoneOptions = (): SelectOption[] => {
    if (!cachedAllOptions) {
        const snapshot = new Date();
        cachedAllOptions = buildOptions(timezoneIds, snapshot);
    }

    return cachedAllOptions;
};

export const getTopTimezoneOptions = (): SelectOption[] => {
    if (!cachedTopOptions) {
        const snapshot = new Date();
        cachedTopOptions = buildOptions(TOP_TIMEZONES, snapshot);
    }

    return cachedTopOptions;
};
