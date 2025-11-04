# Laravel Reverb Setup Guide

**Date**: 2025-11-04
**Status**: ✅ Completed
**Purpose**: Real-time WebSocket broadcasting for AI Buddy

## Overview

Laravel Reverb is installed and configured to provide real-time WebSocket functionality for the prompt optimisation feature. This enables instant notifications when framework selection completes and when the final optimised prompt is generated.

## What Was Installed

### Backend Packages
- **laravel/reverb** (v1.6.0) - Laravel's first-party WebSocket server
- Dependencies: React PHP, Ratchet, Pusher PHP Server, and Redis Protocol

### Frontend Packages
- **laravel-echo** (v2.2.4) - JavaScript client for Laravel broadcasting
- **pusher-js** (v8.4.0) - Pusher Protocol implementation

## Configuration

### Environment Variables

Added to `.env`:
```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=207502
REVERB_APP_KEY=ovypfq0efeetujdvtpkg
REVERB_APP_SECRET=kd4nhde6jj4m9zdofubh
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Docker Service

Added to `compose.yaml`:
```yaml
reverb:
    build:
        context: './vendor/laravel/sail/runtimes/8.4'
        dockerfile: Dockerfile
        args:
            WWWGROUP: '${WWWGROUP}'
    image: 'sail-8.4/app'
    extra_hosts:
        - 'host.docker.internal:host-gateway'
    ports:
        - '${REVERB_PORT:-8080}:8080'
    environment:
        WWWUSER: '${WWWUSER}'
        LARAVEL_SAIL: 1
        XDEBUG_MODE: '${SAIL_XDEBUG_MODE:-off}'
        XDEBUG_CONFIG: '${SAIL_XDEBUG_CONFIG:-client_host=host.docker.internal}'
    volumes:
        - '.:/var/www/html'
    networks:
        - sail
    depends_on:
        - pgsql
        - redis
    command: 'php artisan reverb:start --host=0.0.0.0 --port=8080 --hostname=localhost'
```

### Laravel Configuration Files

**Created by `php artisan reverb:install`:**
- `config/broadcasting.php` - Broadcasting configuration
- `routes/channels.php` - WebSocket channel authorisation

### Frontend Configuration

**Updated `resources/js/bootstrap.ts`:**
```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

**Updated `resources/js/types/global.d.ts`:**
```typescript
import Echo from 'laravel-echo';

declare global {
    interface Window {
        axios: AxiosInstance;
        Echo: Echo;
        Pusher: any;
    }
}
```

## Architecture

### Service Communication

```
┌─────────────┐
│   Browser   │
│             │
│ Laravel Echo│
└──────┬──────┘
       │ WebSocket (ws://localhost:8080)
       │
       ▼
┌─────────────┐
│   Reverb    │ ← Redis for scaling
│  (Port 8080)│
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Laravel   │
│    Events   │
└─────────────┘
```

### Connection Details

- **Internal (Docker)**: `reverb:8080`
- **External (Browser)**: `localhost:8080` or `ws://localhost:8080`
- **Protocol**: WebSocket (ws://) in development
- **Redis**: Used by Reverb for message queue and scaling

## Usage

### Starting the Services

```bash
# Start all Docker services (including Reverb)
./vendor/bin/sail up -d

# Check Reverb is running
./vendor/bin/sail ps
# Should show: personality-reverb-1 ... Up ... 0.0.0.0:8080->8080/tcp

# View Reverb logs
./vendor/bin/sail logs reverb

# View live logs
./vendor/bin/sail logs -f reverb
```

### Testing WebSocket Connection

**In Browser Console:**
```javascript
// Check Echo is loaded
console.log(window.Echo); // Should show Echo instance

// Test connection
window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('✅ WebSocket connected!');
});

window.Echo.connector.pusher.connection.bind('error', (err) => {
    console.error('❌ WebSocket error:', err);
});
```

### Creating a Broadcast Event

```php
// app/Events/PromptOptimizationCompleted.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\PromptRun;

class PromptOptimizationCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PromptRun $promptRun
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('prompt-run.' . $this->promptRun->id);
    }
}
```

### Listening for Events in Vue

```vue
<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';

const props = defineProps<{
    promptRunId: number;
}>();

onMounted(() => {
    window.Echo.channel(`prompt-run.${props.promptRunId}`)
        .listen('PromptOptimizationCompleted', (e) => {
            console.log('Prompt completed!', e.promptRun);
            // Redirect or update UI
        });
});

onUnmounted(() => {
    window.Echo.leave(`prompt-run.${props.promptRunId}`);
});
</script>
```

## Verification Checklist

- ✅ Composer package installed (`laravel/reverb`)
- ✅ Frontend packages installed (`laravel-echo`, `pusher-js`)
- ✅ Environment variables configured
- ✅ Docker service added and running
- ✅ Echo configured in `bootstrap.ts`
- ✅ TypeScript types added
- ✅ Broadcasting config exists (`config/broadcasting.php`)
- ✅ Channels routes exists (`routes/channels.php`)
- ✅ Reverb service starts without errors
- ✅ Port 8080 is accessible

## Current Status

**Reverb Server**: ✅ Running on port 8080
```
INFO  Starting server on 0.0.0.0:8080 (localhost).
```

**Docker Container**: ✅ `personality-reverb-1` is UP

**Next Steps**: Ready to create broadcast events for prompt optimization feature.

## Troubleshooting

### Connection Refused Error

If you see `ERR_CONNECTION_REFUSED` in the browser:

1. Check Reverb is running:
   ```bash
   ./vendor/bin/sail ps | grep reverb
   ```

2. Check Reverb logs for errors:
   ```bash
   ./vendor/bin/sail logs reverb
   ```

3. Verify port 8080 is exposed:
   ```bash
   docker port personality-reverb-1
   ```

### Authentication Errors

If events aren't reaching the client:

1. Check channel authorization in `routes/channels.php`
2. Ensure user is authenticated for private/presence channels
3. Use public channels for testing:
   ```php
   public function broadcastOn(): Channel
   {
       return new Channel('public-test');
   }
   ```

### Environment Variables Not Found

If `import.meta.env.VITE_REVERB_*` is undefined:

1. Restart Vite dev server:
   ```bash
   pnpm run dev
   ```

2. Check variables are in `.env`:
   ```bash
   grep VITE_REVERB .env
   ```

### Redis Connection Issues

If Reverb can't connect to Redis:

1. Check Redis is running:
   ```bash
   ./vendor/bin/sail ps | grep redis
   ```

2. Test Redis connection:
   ```bash
   ./vendor/bin/sail redis redis-cli ping
   ```

## Security Considerations

### Development vs Production

**Current (Development)**:
- Using `http://localhost:8080`
- Self-signed certificates for HTTPS endpoints
- Basic Redis configuration

**Production TODO**:
- Use proper SSL/TLS (`wss://`)
- Configure Redis password
- Set up Redis persistence
- Use environment-specific Reverb credentials
- Consider using managed Redis (e.g., AWS ElastiCache)

### Channel Authorization

Always authorize private channels in `routes/channels.php`:

```php
use App\Models\PromptRun;

Broadcast::channel('prompt-run.{promptRunId}', function ($user, $promptRunId) {
    return $user->id === PromptRun::find($promptRunId)?->user_id;
});
```

## Performance

### Current Setup

- **Redis**: Used for message queue (already running in Docker)
- **Scaling**: Single Reverb instance (sufficient for development)
- **Connections**: Handles thousands of concurrent connections

### Production Scaling

For production with high traffic:

1. **Multiple Reverb Instances**: Run multiple containers
2. **Load Balancer**: Distribute WebSocket connections
3. **Redis Cluster**: For horizontal scaling
4. **Monitoring**: Track connection counts, message throughput

## Resources

- [Laravel Reverb Documentation](https://laravel.com/docs/11.x/reverb)
- [Laravel Broadcasting Documentation](https://laravel.com/docs/11.x/broadcasting)
- [Laravel Echo Documentation](https://laravel.com/docs/11.x/broadcasting#client-side-installation)

## Related Files

- `docs/features/interactive-prompt-optimization-plan.md` - Feature plan using Reverb
- `config/broadcasting.php` - Broadcasting configuration
- `routes/channels.php` - Channel authorization
- `resources/js/bootstrap.ts` - Echo configuration
- `resources/js/types/global.d.ts` - TypeScript types
- `compose.yaml` - Docker service definition

---

**Setup Completed**: 2025-11-04
**Ready For**: Phase 1 - Interactive Prompt Optimization Implementation
