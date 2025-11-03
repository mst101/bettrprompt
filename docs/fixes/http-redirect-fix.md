# Fix: HTTP Not Redirecting to HTTPS

## Issue

When visiting `http://app.localhost` or `http://n8n.localhost`, users got:
```
This site can't be reached
```

HTTP requests were failing instead of redirecting to HTTPS.

## Root Cause

The `Caddyfile` had `auto_https disable_redirects` in the global options block:

```caddyfile
{
    auto_https disable_redirects  # ❌ This prevented HTTP→HTTPS redirects
    local_certs
}
```

This directive **disabled** Caddy's automatic HTTP → HTTPS redirect feature, causing:
- Port 80 (HTTP) to not respond
- No redirect to HTTPS
- "Site can't be reached" errors

## Solution

Removed the `auto_https disable_redirects` directive:

```caddyfile
{
    # Enable automatic HTTPS with self-signed certificates
    # Caddy will automatically redirect HTTP → HTTPS
    local_certs
}
```

## What Changed

### Before
- `http://app.localhost` → ❌ Site can't be reached
- `http://n8n.localhost` → ❌ Site can't be reached
- Users had to remember to use `https://`

### After
- `http://app.localhost` → ✅ 308 Permanent Redirect → `https://app.localhost`
- `http://n8n.localhost` → ✅ 308 Permanent Redirect → `https://n8n.localhost`
- HTTP automatically redirects to HTTPS

## Files Modified

1. **`Caddyfile`**
   - Removed `auto_https disable_redirects`
   - Updated comments to clarify HTTP/HTTPS handling

2. **`docs/caddy-https-setup.md`**
   - Added troubleshooting section for this issue
   - Updated "What Was Configured" to mention auto-redirects

## Verification

Test that HTTP redirects to HTTPS:

```bash
# Test Laravel
curl -I http://app.localhost
# Expected: HTTP/1.1 308 Permanent Redirect
# Location: https://app.localhost/

# Test n8n
curl -I http://n8n.localhost
# Expected: HTTP/1.1 308 Permanent Redirect
# Location: https://n8n.localhost/
```

Test in browser:
1. Visit `http://app.localhost` (without the 's')
2. Browser should automatically redirect to `https://app.localhost`
3. Address bar should show `https://`

## Why 308 Permanent Redirect?

Caddy uses **308 Permanent Redirect** instead of 301 because:
- **308** preserves the HTTP method (POST stays POST)
- **301** can change POST to GET (bad for forms)
- **308** is better for modern web applications

## Caddy's Default Behavior

By default, Caddy automatically:
1. Generates HTTPS certificates for all configured domains
2. Listens on both port 80 (HTTP) and 443 (HTTPS)
3. Redirects all HTTP traffic to HTTPS
4. Uses HTTP/2 and HTTP/3 when possible

The `auto_https disable_redirects` directive **disabled** step 3, which broke HTTP access.

## Related Caddy Documentation

- [Automatic HTTPS](https://caddyserver.com/docs/automatic-https)
- [HTTPS Directives](https://caddyserver.com/docs/caddyfile/options#https)
- [auto_https](https://caddyserver.com/docs/caddyfile/options#auto-https)

## Best Practices

### Development (Local)
```caddyfile
{
    local_certs  # ✅ Generate local certificates
    # No disable_redirects - let HTTP redirect to HTTPS
}
```

### Production
```caddyfile
# Usually no global options needed
# Caddy gets real certificates from Let's Encrypt automatically
app.yourdomain.com {
    reverse_proxy backend:8080
}
```

Caddy handles everything automatically in production!

## Troubleshooting

### Still Getting "Site Can't be Reached"?

1. **Check Caddy is running:**
   ```bash
   ./vendor/bin/sail ps
   ```

2. **Check Caddy logs:**
   ```bash
   ./vendor/bin/sail logs caddy
   ```

3. **Verify Caddyfile syntax:**
   ```bash
   ./vendor/bin/sail exec caddy caddy validate --config /etc/caddy/Caddyfile
   ```

4. **Restart Caddy:**
   ```bash
   ./vendor/bin/sail restart caddy
   ```

### Port 80 Already in Use

If another service is using port 80:
```bash
# Find what's using it
sudo lsof -i :80

# Stop the conflicting service
sudo systemctl stop apache2  # or nginx, etc.
```

## Summary

The fix was simple: **remove the line that disabled redirects**. Caddy's default behavior (auto-redirecting HTTP → HTTPS) is exactly what we want for local development with HTTPS.

Date Fixed: 2025-11-04
