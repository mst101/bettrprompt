# BettrPrompt - Personalised Prompt Generator

An intelligent prompt generation system that creates optimised AI prompts customised to your task requirements,
personality type, and professional context.

## Features

- **Framework Selection**: Choose from 64+ prompt engineering frameworks (CRISPE, Chain of Thought, etc.)
- **Intelligent Analysis**: AI-powered pre-analysis and task understanding via n8n workflows
- **Personality-Based Optimisation**: Tailors prompts to your MBTI personality type (based on 16personalities.com)
- **Real-Time Updates**: WebSocket-powered progress tracking with Laravel Reverb
- **Comprehensive History**: Track and manage all your generated prompts
- **SSR Support**: Server-side rendering for improved performance and SEO

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Inertia.js + Vue 3 + TypeScript
- **Database**: PostgreSQL
- **Cache/Queue**: Redis + Laravel Horizon
- **WebSockets**: Laravel Reverb
- **Workflow Automation**: n8n
- **Styling**: Tailwind CSS v4
- **Testing**: Pest (PHPUnit) + Vitest
- **Dev Environment**: Laravel Sail (Docker)
- **Reverse Proxy**: Caddy

## Quick Start

### Prerequisites

- Docker Desktop
- Composer
- Node.js 18+ and pnpm

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd bettrprompt
   ```

2. **Install dependencies and set up environment**
   ```bash
   composer setup
   ```

   This runs:
    - `composer install`
    - Creates `.env` from `.env.example`
    - Generates application key
    - Runs database migrations
    - `npm install`
    - Builds frontend assets

3. **Start the development environment**
   ```bash
   ./vendor/bin/sail up -d
   ```

4. **Run the development stack**
   ```bash
   composer dev
   ```

   This starts:
    - Laravel development server (port 80)
    - Queue worker (Horizon)
    - WebSocket server (Reverb)
    - Log viewer (Pail)
    - Vite dev server with HMR

5. **Fix HTTPS certificate warnings** (first time only)

   **For Chrome on Linux:**
   ```bash
   # Install NSS tools
   sudo apt install libnss3-tools

   # Install Caddy's certificate to Chrome
   ./scripts/install-chrome-ca.sh

   # Completely close Chrome
   pkill -f chrome
   ```

   **For macOS or other browsers:**
   ```bash
   ./scripts/install-local-ca.sh
   ```

   Then **wait a few seconds and restart Chrome**.

6. **Access the application**
    - **Main app**: https://app.localhost
    - **n8n dashboard**: https://n8n.localhost
    - **Horizon dashboard**: https://app.localhost/horizon

## Development

### Running Tests

```bash
# Backend tests (Pest)
./vendor/bin/sail test

# Frontend tests (Vitest)
pnpm test:unit

# Run specific test file
./vendor/bin/sail test tests/Feature/ExampleTest.php
```

**Important**: Always use `./vendor/bin/sail` for Laravel commands to ensure consistency with the Docker environment.

### Building for Production

```bash
# Build frontend assets (includes SSR)
npm run build

# Check code style
./vendor/bin/pint
npm run lint
```

### Development Commands

```bash
# Start Docker containers
./vendor/bin/sail up -d

# Stop Docker containers
./vendor/bin/sail down

# View logs
./vendor/bin/sail logs -f

# Run artisan commands
./vendor/bin/sail artisan <command>

# Access database
./vendor/bin/sail psql

# Run migrations
./vendor/bin/sail artisan migrate

# Fresh database with seeders
./vendor/bin/sail artisan migrate:fresh --seed
```

## Architecture

### Frontend (Inertia + Vue)

- **Entry Point**: `resources/js/app.ts`
- **SSR Entry**: `resources/js/ssr.ts`
- **Pages**: `resources/js/Pages/` (Inertia auto-resolves)
- **Layouts**: `resources/js/Layouts/`
- **Components**: `resources/js/Components/`
- **Routing**: Uses Ziggy for Laravel route helpers

The application uses Inertia.js to create a single-page app experience whilst maintaining Laravel's routing and
controllers.

### Backend (Laravel)

- **Controllers**: `app/Http/Controllers/`
- **Services**: `app/Services/`
    - `N8nClient` - Handles n8n workflow integration
- **Jobs**: `app/Jobs/` (processed by Horizon)
- **Resources**: `app/Http/Resources/` (API transformations)
- **Routes**:
    - `routes/web.php` - Inertia page routes
    - `routes/api.php` - API endpoints + n8n webhooks
    - `routes/auth.php` - Laravel Breeze authentication

### n8n Workflow Integration

The application integrates with n8n for AI-powered prompt generation:

1. **Workflow 0**: Pre-analysis question generation
2. **Workflow 1**: Task analysis and framework selection
3. **Workflow 2**: Final prompt generation with metadata

**Configuration** (in `.env`):

```env
N8N_URL=http://n8n:5678
N8N_USERNAME=admin
N8N_PASSWORD=password
N8N_WEBHOOK_SECRET=your-secret-here
```

**Webhook Receiver**: POST `/api/n8n/webhook`

- Secured with `X-N8N-SECRET` header verification
- Processes workflow results and updates database

## Database

Uses PostgreSQL with migrations in `database/migrations/`.

Key tables:

- `users` - User accounts and personality profiles
- `prompt_runs` - Generated prompts and workflow state
- `jobs` - Queue jobs (processed by Horizon)

A separate `testing` database is automatically created for tests.

## Local Development with Caddy

Caddy serves as a reverse proxy providing:

- **HTTPS for local domains** (self-signed certificates)
- **WebSocket support** for Laravel Reverb
- **Automatic HTTP → HTTPS redirects**
- **Gzip compression** (excluding WebSocket traffic)

### Domains

- `app.localhost` → Laravel application (port 80)
- `n8n.localhost` → n8n dashboard (port 5678, basic auth protected)

### Fixing Certificate Warnings

Run the installation script to trust Caddy's CA certificate:

```bash
./scripts/install-local-ca.sh
```

Then **completely close and restart Chrome**.

See [CLAUDE.md](./CLAUDE.md) for manual installation steps if needed.

## Coding Conventions

### Language

- **Always use British English** (except for third-party APIs, CSS classes, HTML attributes)

### Naming Conventions

- **Frontend (Vue/TypeScript)**: camelCase for variables, props, data fields
- **Backend (Laravel)**: snake_case for database columns, method names
- **HTML attributes**: kebab-case for `id` and `data-testid`

**Example**:

```typescript
// Frontend (Vue) - camelCase
const form = reactive({
    uiComplexity: 'advanced',
    personalityType: 'INTJ'
});

// Laravel Resource - converts to snake_case
'uiComplexity'
=>
$this->ui_complexity,
    'personalityType'
=>
$this->personality_type,

// HTML - kebab-case
    <input id = "user-name"
data - testid = "submit-button" / >
```

See [CLAUDE.md](./CLAUDE.md) for detailed coding conventions and architecture documentation.

## Project Structure

```
.
├── app/                    # Laravel application code
│   ├── Http/
│   │   ├── Controllers/   # Request handlers
│   │   └── Resources/     # API transformations
│   ├── Jobs/              # Queue jobs
│   ├── Models/            # Eloquent models
│   └── Services/          # Business logic
├── database/
│   ├── migrations/        # Database migrations
│   └── seeders/          # Database seeders
├── n8n/                   # n8n workflow JSON files
├── resources/
│   ├── js/               # Vue.js frontend
│   │   ├── Components/   # Reusable Vue components
│   │   ├── Layouts/      # Page layouts
│   │   └── Pages/        # Inertia pages
│   ├── reference_documents/  # Framework templates
│   └── views/            # Blade templates (minimal, for Inertia)
├── routes/               # Route definitions
├── scripts/              # Helper scripts
├── tests/                # Backend tests (Pest)
└── tests-frontend/       # Frontend tests (Vitest)
```

## Contributing

1. Follow the coding conventions in [CLAUDE.md](./CLAUDE.md)
2. Write tests for new features
3. Run tests before committing: `./vendor/bin/sail test && pnpm test:unit`
4. Run linters: `./vendor/bin/pint && npm run lint`
5. Use conventional commits

## Troubleshooting

### "Your connection is not private" in Chrome

**On Linux:**

```bash
sudo apt install libnss3-tools
./scripts/install-chrome-ca.sh
pkill -f chrome  # Close all Chrome windows
```

**On macOS:**

```bash
./scripts/install-local-ca.sh
```

Then completely restart Chrome (wait a few seconds after closing).

### Port already in use

Stop conflicting services:

```bash
sudo lsof -ti:80 | xargs kill -9
sudo lsof -ti:5678 | xargs kill -9
```

### Database connection failed

Ensure PostgreSQL container is running:

```bash
./vendor/bin/sail up -d postgres
```

### Queue jobs not processing

Check Horizon is running:

```bash
./vendor/bin/sail artisan horizon:status
```

Or restart it:

```bash
composer dev
```

## Licence

[Add your licence here]
