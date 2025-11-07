# Troubleshooting: HTTPS Mixed Content Errors

## Problem

When accessing `https://app.localhost`, you may see:

- Blank page
- Console errors like:
  ```
  GET http://app.localhost/build/assets/app-xxx.css net::ERR_SOCKET_NOT_CONNECTED
  GET http://app.localhost/build/assets/app-xxx.js net::ERR_CONNECTION_RESET
  ```

Notice the URLs are `http://` instead of `https://`.

## Root Cause

**Mixed Content Issue:**

1. Browser loads page via HTTPS: `https://app.localhost`
2. Laravel generates asset URLs as HTTP: `http://app.localhost/build/assets/...`
3. Browser blocks loading HTTP resources on HTTPS pages (security policy)

**Why Laravel Generates HTTP URLs:**

- Caddy (HTTPS) → Laravel (HTTP internal communication)
- Laravel sees the internal HTTP request
- Without proxy configuration, Laravel thinks all requests are HTTP
- Generates HTTP asset URLs

## Solution Applied

Two changes were made to fix this:

### 1. Trust Proxy Headers

**File:** `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware): void {
    // ... existing middleware ...

    // Trust all proxies for local development (Caddy reverse proxy)
    $middleware->trustProxies(at: '*');
})
```

**What this does:**

- Tells Laravel to trust the `X-Forwarded-Proto` header from Caddy
- Caddy sets `X-Forwarded-Proto: https` when it proxies HTTPS requests
- Laravel now knows the original request was HTTPS

### 2. Force HTTPS URL Generation

**File:** `app/Providers/AppServiceProvider.php`

```php
public function boot(): void
{
    Vite::prefetch(concurrency: 3);

    // Force HTTPS URLs when behind reverse proxy (Caddy)
    if ($this->app->environment('local')) {
        \Illuminate\Support\Facades\URL::forceScheme('https');
    }
}
```

**What this does:**

- Forces all URL generation helpers to use HTTPS
- Applies only in `local` environment
- Ensures consistency even if proxy headers are missing

## Architecture Context

```
┌──────────┐
│  Browser │ HTTPS (443)
└─────┬────┘
      │
      ▼
┌──────────────┐
│    Caddy     │ Sets: X-Forwarded-Proto: https
└─────┬────────┘
      │ HTTP (80) - internal network
      ▼
┌──────────────┐
│   Laravel    │ Reads X-Forwarded-Proto
└──────────────┘ Generates: https:// URLs
```

## Testing

After applying the fix:

```bash
# Clear Laravel caches
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear

# Test asset URLs are HTTPS
curl -k -s https://app.localhost | grep -o 'https://[^"]*\.js'
```

Should output:

```
https://app.localhost/build/assets/app-xxx.js
https://app.localhost/build/assets/Home-xxx.js
```

## Why Both Changes?

| Change           | Purpose                           | When It Helps                                        |
|------------------|-----------------------------------|------------------------------------------------------|
| `trustProxies()` | Read forwarded headers from Caddy | Laravel detects HTTPS from proxy headers             |
| `forceScheme()`  | Guarantee HTTPS URL generation    | Fallback if headers are missing; ensures consistency |

Both together provide:

- ✅ Proper protocol detection
- ✅ Guaranteed HTTPS URLs
- ✅ Works with all Laravel URL helpers (`route()`, `asset()`, `url()`)

## Production Considerations

In production, you should:

1. **Trust specific proxies only:**
   ```php
   // Instead of '*', use your load balancer's IP
   $middleware->trustProxies(at: ['10.0.0.0/8']);
   ```

2. **Remove environment check:**
   ```php
   // Force HTTPS in production too
   \Illuminate\Support\Facades\URL::forceScheme('https');
   ```

3. **Or use conditional:**
   ```php
   if (config('app.env') !== 'testing') {
       \Illuminate\Support\Facades\URL::forceScheme('https');
   }
   ```

## Related Laravel Documentation

- [TrustProxies Middleware](https://laravel.com/docs/11.x/requests#configuring-trusted-proxies)
- [URL Generation](https://laravel.com/docs/11.x/urls#forcing-https-on-generated-urls)
- [Reverse Proxy Configuration](https://laravel.com/docs/11.x/deployment#nginx)

## Common Pitfalls

### Forgot to Clear Cache

```bash
# Always clear after config changes
./vendor/bin/sail artisan config:clear
```

### Mixed HTTP/HTTPS in .env

```env
# ❌ Wrong - mismatched protocols
APP_URL=http://app.localhost
# But accessing via https://app.localhost

# ✅ Correct - match your primary access method
APP_URL=https://app.localhost
```

### Trusting All Proxies in Production

```php
// ❌ Security risk in production
$middleware->trustProxies(at: '*');

// ✅ Trust specific IPs only
$middleware->trustProxies(at: ['10.0.0.1', '10.0.0.2']);
```

## Verification Checklist

After applying the fix:

- [ ] Visit `https://app.localhost` - page loads without errors
- [ ] Check browser console - no mixed content warnings
- [ ] Check Network tab - all assets load via HTTPS
- [ ] Test `route()` helper - generates HTTPS URLs
- [ ] Test `asset()` helper - generates HTTPS URLs
- [ ] Verify Vite HMR still works (http://localhost:5173)

## Still Having Issues?

1. **Clear browser cache:**
    - Chrome: Cmd/Ctrl + Shift + Delete
    - Hard refresh: Cmd/Ctrl + Shift + R

2. **Check Caddy logs:**
   ```bash
   ./vendor/bin/sail logs caddy | grep "X-Forwarded"
   ```

3. **Verify Caddy is setting headers:**
   ```bash
   curl -k -I https://app.localhost -v 2>&1 | grep -i forward
   ```

4. **Test without cache:**
   ```bash
   curl -k -H "Cache-Control: no-cache" https://app.localhost
   ```
