# Caddy HTTPS Setup for n8n OAuth

This guide explains how to set up HTTPS for local development to enable OAuth callbacks (required by Slack and other OAuth providers).

## What Was Configured

1. **Caddy reverse proxy** added to Docker Compose on standard ports (80/443)
2. **HTTPS enabled** with self-signed certificates for `.localhost` domains
3. **Automatic HTTP → HTTPS redirects** enabled (308 Permanent Redirect)
4. **All services accessed through Caddy** for production-like architecture
5. **Laravel no longer exposes port 80 directly** - access via Caddy
6. **n8n accessible via HTTPS** at `https://n8n.localhost` (no port numbers!)

## Steps to Apply Changes

### 1. Restart Docker Containers

```bash
# Stop existing containers
./vendor/bin/sail down

# Start with the new Caddy configuration
./vendor/bin/sail up -d
```

### 2. Trust the Self-Signed Certificate

Since Caddy generates self-signed certificates for local development, your browser will show a security warning. You need to accept/trust the certificate:

**Chrome/Edge:**
1. Visit `https://n8n.localhost`
2. Click "Advanced"
3. Click "Proceed to n8n.localhost (unsafe)"

**Firefox:**
1. Visit `https://n8n.localhost`
2. Click "Advanced"
3. Click "Accept the Risk and Continue"

**Safari:**
1. Visit `https://n8n.localhost`
2. Click "Show Details"
3. Click "visit this website"

### 3. Access Services via Caddy

All services now use standard ports through Caddy:

- **n8n HTTPS**: https://n8n.localhost
- **n8n HTTP**: http://n8n.localhost
- **Laravel HTTPS**: https://app.localhost
- **Laravel HTTP**: http://app.localhost
- **Login (n8n)**: admin / password

**Note**: No port numbers needed! Caddy handles routing on standard ports 80 and 443.

### 4. Configure Slack OAuth

Now you can use the HTTPS callback URL in your Slack app:

**OAuth Redirect URL:**
```
https://n8n.localhost/rest/oauth2-credential/callback
```

**Steps:**
1. Go to https://api.slack.com/apps
2. Select your app (or create one)
3. Go to "OAuth & Permissions"
4. Under "Redirect URLs", add:
   ```
   https://n8n.localhost/rest/oauth2-credential/callback
   ```
5. Click "Save URLs"

**Note**: Clean URL with no port numbers - looks professional!

### 5. Set Up Slack Credential in n8n

1. In n8n, go to **Credentials** (left sidebar)
2. Click **"+ Add Credential"**
3. Search for "Slack OAuth2 API"
4. Fill in:
   - **Client ID**: From your Slack app settings
   - **Client Secret**: From your Slack app settings
   - **OAuth Redirect URL**: `https://n8n.localhost/rest/oauth2-credential/callback`
5. Click **"Connect my account"**
6. Authorise the Slack app

## URLs Reference

| Service | HTTP | HTTPS | Purpose |
|---------|------|-------|---------|
| Laravel | http://app.localhost | https://app.localhost | Main application |
| n8n | http://n8n.localhost | https://n8n.localhost | n8n dashboard & OAuth |
| n8n (direct) | http://localhost:5678 | - | Direct access (bypass Caddy) |
| n8n (internal) | http://n8n:5678 | - | For Laravel→n8n calls |
| Vite HMR | http://localhost:5173 | - | Frontend dev server |

**Architecture:**
```
Browser → Caddy (80/443) → Laravel container (internal :80)
                         → n8n container (internal :5678)
```

## Troubleshooting

### Port Conflicts

If you get "port already in use" errors:

```bash
# Check what's using port 80/443
sudo lsof -i :80
sudo lsof -i :443

# Stop conflicting services (example: Apache)
sudo systemctl stop apache2
```

### HTTP Not Working ("This site can't be reached")

If `http://app.localhost` gives "This site can't be reached":

**Problem:** The Caddyfile has `auto_https disable_redirects` which prevents HTTP from working.

**Solution:**
1. Edit `Caddyfile` and remove `auto_https disable_redirects` from the global options
2. Restart Caddy:
   ```bash
   ./vendor/bin/sail restart caddy
   ```
3. Test HTTP redirect:
   ```bash
   curl -I http://app.localhost
   # Should see: HTTP/1.1 308 Permanent Redirect
   # Location: https://app.localhost/
   ```

### Certificate Issues

If the browser still shows warnings after accepting:

1. Clear browser cache and certificates
2. Restart the Caddy container:
   ```bash
   ./vendor/bin/sail restart caddy
   ```

### n8n Can't Be Reached

Check that Caddy is running:
```bash
./vendor/bin/sail ps
```

Check Caddy logs:
```bash
./vendor/bin/sail logs caddy
```

### OAuth Still Not Working

1. Verify the callback URL in Slack exactly matches:
   ```
   https://n8n.localhost/rest/oauth2-credential/callback
   ```
2. Make sure n8n's environment variables are set correctly (check `.env`)
3. Restart n8n:
   ```bash
   ./vendor/bin/sail restart n8n
   ```

## Production Setup

For production, you would:

1. Use a real domain (e.g., `n8n.yourdomain.com`)
2. Point DNS to your server
3. Caddy will automatically get a real certificate from Let's Encrypt
4. Update the OAuth redirect URL to use your production domain

Example production Caddyfile:
```
n8n.yourdomain.com {
    reverse_proxy n8n:5678
}
```

Caddy handles everything automatically - no certificate configuration needed!
