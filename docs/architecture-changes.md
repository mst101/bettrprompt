# Architecture Changes - Standard Ports Configuration

## Overview

Reconfigured the development environment to use standard ports (80/443) through Caddy reverse proxy, matching production-like architecture.

## What Changed

### Before
```
Browser → Laravel container (port 80) - Direct access
Browser → n8n container (port 5678) - Direct access
Browser → Caddy (ports 8080/8443) - Secondary access with port numbers
```

**Problems:**
- Non-standard ports in URLs: `https://n8n.localhost:8443`
- Multiple ways to access services (confusing)
- OAuth URLs looked unprofessional with port numbers
- Didn't match production architecture

### After
```
Browser → Caddy (ports 80/443) → Routes to:
                                 ├─ app.localhost → Laravel (internal :80)
                                 └─ n8n.localhost → n8n (internal :5678)
```

**Benefits:**
- ✅ Clean URLs without port numbers
- ✅ Professional OAuth callback: `https://n8n.localhost/rest/oauth2-credential/callback`
- ✅ Single point of entry (Caddy)
- ✅ Production-like architecture
- ✅ Automatic HTTPS with self-signed certificates

## Files Modified

### 1. `compose.yaml`

**Laravel container:**
- Removed: `- '${APP_PORT:-80}:80'` (direct port 80 exposure)
- Added: `expose: ["80"]` (internal network only)
- Kept: `- '${VITE_PORT:-5173}:${VITE_PORT:-5173}'` (for HMR)

**Caddy container:**
- Changed from: `8080:80` and `8443:443`
- Changed to: `80:80` and `443:443` (standard ports)

### 2. `.env`

**Application URL:**
```diff
- APP_URL=http://localhost
+ APP_URL=http://app.localhost
```

**n8n URLs:**
```diff
- N8N_WEBHOOK_URL=https://n8n.localhost:8443/
+ N8N_WEBHOOK_URL=https://n8n.localhost/

- N8N_PUBLIC_URL=https://n8n.localhost:8443/
+ N8N_PUBLIC_URL=https://n8n.localhost/
```

### 3. `Caddyfile`

No changes needed - already configured for `.localhost` domains with automatic HTTPS.

### 4. Documentation

Updated:
- `docs/caddy-https-setup.md` - Removed all port number references
- `docs/QUICK_START.md` - Updated URLs to standard ports

## Access URLs

### Development URLs (via Caddy)

| Service | URL | Purpose |
|---------|-----|---------|
| Laravel | http://app.localhost | HTTP access |
| Laravel | https://app.localhost | HTTPS access (recommended) |
| n8n | http://n8n.localhost | HTTP access |
| n8n | https://n8n.localhost | HTTPS access (required for OAuth) |

### Direct Access (Bypassing Caddy)

| Service | URL | Purpose |
|---------|-----|---------|
| n8n | http://localhost:5678 | Direct access (if needed) |
| Vite HMR | http://localhost:5173 | Frontend hot module reload |

### Internal (Docker Network)

| Service | URL | Purpose |
|---------|-----|---------|
| Laravel | http://laravel.test:80 | Container-to-container |
| n8n | http://n8n:5678 | Used by Laravel to call n8n |

## OAuth Configuration

### Slack OAuth Redirect URL

**Old (non-standard port):**
```
https://n8n.localhost:8443/rest/oauth2-credential/callback
```

**New (clean, professional):**
```
https://n8n.localhost/rest/oauth2-credential/callback
```

This is now suitable for production (just change `n8n.localhost` to your production domain).

## Architecture Benefits

### Production Parity

This setup matches how it would work in production:
```
Production:
Browser → Caddy (80/443) → app.yourdomain.com
                         → n8n.yourdomain.com

Development:
Browser → Caddy (80/443) → app.localhost
                         → n8n.localhost
```

### Security

- Single TLS termination point (Caddy)
- Internal services not directly exposed
- Caddy handles certificate management

### Scalability

Easy to add more services:
```caddyfile
api.localhost {
    reverse_proxy api-service:3000
}
```

## Testing

All services tested and working:
- ✅ https://app.localhost - Laravel application
- ✅ https://n8n.localhost - n8n dashboard
- ✅ Auto-redirect HTTP → HTTPS
- ✅ Self-signed certificates generated
- ✅ OAuth-ready URLs (no ports)

## Migration Guide

If you're updating an existing installation:

1. **Stop containers:**
   ```bash
   ./vendor/bin/sail down
   ```

2. **Pull latest changes** (compose.yaml, .env, docs)

3. **Update your Slack OAuth redirect URL:**
   - Old: `https://n8n.localhost:8443/rest/oauth2-credential/callback`
   - New: `https://n8n.localhost/rest/oauth2-credential/callback`

4. **Start containers:**
   ```bash
   ./vendor/bin/sail up -d
   ```

5. **Access services:**
   - Laravel: https://app.localhost
   - n8n: https://n8n.localhost

6. **Accept self-signed certificate** in your browser (one-time)

## Notes

- HTTP still works but redirects to HTTPS
- Direct access to Laravel on port 80 is no longer available
- All access should go through Caddy for consistency
- Vite HMR (port 5173) still works for hot module reload
