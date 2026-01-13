@component('mail::message')
# Workflow Alert

A workflow failure has been detected in BettrPrompt.

**Error Code:** {{ $errorCode ?? 'UNKNOWN' }}
**Error Message:** {{ $errorMessage ?? 'No message provided' }}
**Triggered Count:** {{ $triggeredCount }} (within 15 minutes)
**Timestamp:** {{ $timestamp->format('Y-m-d H:i:s') }} UTC

## What to do

1. Visit the admin dashboard to view full details
2. Check the workflow processing logs for more information
3. Consider investigating the workflow configuration if this is recurring

@component('mail::button', ['url' => config('app.url') . '/admin/alerts'])
View Alert Dashboard
@endcomponent

Thanks,
BettrPrompt Team
@endcomponent
