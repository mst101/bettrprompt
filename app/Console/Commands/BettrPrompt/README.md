# BettrPrompt Artisan Commands

Custom Artisan commands for the BettrPrompt application.

## Command Structure

```
app/Console/Commands/BettrPrompt/
├── Core/                           # System and infrastructure commands
│   ├── FreshCommand.php            # bp:fresh
│   └── ClearLogsCommand.php        # bp:logs:clear
└── TypeScript/                     # TypeScript generation
    └── GenerateTypesCommand.php    # bp:types:generate
```

## Available Commands

### Core Commands

| Command | Description | Usage |
|---------|-------------|-------|
| `bp:fresh` | Reset database with migrations and seeders, flush Redis stores, clear logs | `./vendor/bin/sail artisan bp:fresh` |
| `bp:logs:clear` | Clear log files (laravel, horizon, reverb) | `./vendor/bin/sail artisan bp:logs:clear` |

### TypeScript Commands

| Command | Description | Usage |
|---------|-------------|-------|
| `bp:types:generate` | Generate TypeScript definitions from Resource docblocks | `./vendor/bin/sail artisan bp:types:generate` |

## Command Examples

### bp:fresh

```bash
# Reset everything (database, all Redis stores, all logs) with confirmation
./vendor/bin/sail artisan bp:fresh

# Reset specific Redis stores only
./vendor/bin/sail artisan bp:fresh cache analytics

# Force in production (requires --force flag)
./vendor/bin/sail artisan bp:fresh --force

# Skip confirmation prompt (useful for scripts)
./vendor/bin/sail artisan bp:fresh --no-interaction
```

**What it does:**
1. Drops all tables and re-runs migrations with seeders
2. Clears specified Redis stores (or all if none specified)
3. Clears all log files

**Production safety:** Requires `--force` flag when `APP_ENV=production`

### bp:logs:clear

```bash
# Clear all logs (laravel, horizon, reverb)
./vendor/bin/sail artisan bp:logs:clear

# Clear specific logs only
./vendor/bin/sail artisan bp:logs:clear laravel
./vendor/bin/sail artisan bp:logs:clear laravel horizon
```

**Available log types:**
- `laravel` - Main application log (laravel.log)
- `horizon` - Horizon queue log (horizon.log)
- `reverb` - Reverb WebSocket log (reverb.log)

### bp:types:generate

```bash
# Generate TypeScript for all resources
./vendor/bin/sail artisan bp:types:generate

# Generate TypeScript for a specific resource
./vendor/bin/sail artisan bp:types:generate UserResource
./vendor/bin/sail artisan bp:types:generate PromptRun  # '.Resource' suffix is optional
```

**How it works:**
1. Reads TypeScript interfaces from Resource class docblocks
2. Extracts interface code from ` ```typescript ... ``` ` blocks
3. Generates `.ts` files in `resources/js/Types/resources/`
4. Creates `index.ts` that exports all resource types

**Example Resource docblock:**
```php
/**
 * @see \App\Models\User
 *
 * TypeScript interface:
 * ```typescript
 * interface User {
 *   readonly id: number;
 *   readonly name: string;
 *   readonly email: string;
 * }
 * ```
 */
class UserResource extends JsonResource
{
    // ...
}
```

## Best Practices

### When writing Resource classes:

1. **Always include TypeScript interface in docblock**
   - Use ` ```typescript ... ``` ` code blocks
   - Match the interface to your `toArray()` method
   - Use camelCase for property names (matches your frontend)

2. **Run `bp:types:generate` after updating Resources**
   ```bash
   ./vendor/bin/sail artisan bp:types:generate
   ```

3. **Keep interfaces readonly**
   - Frontend should treat resource data as immutable
   - Use `readonly` modifier for all properties

### Development workflow:

```bash
# After database schema changes or seeder updates:
./vendor/bin/sail artisan bp:fresh

# After modifying a Resource class:
./vendor/bin/sail artisan bp:types:generate

# Clear logs when debugging:
./vendor/bin/sail artisan bp:logs:clear laravel
```

## Command Naming Convention

All BettrPrompt commands use the `bp:` prefix to distinguish them from:
- Laravel core commands (`migrate:`, `cache:`, etc.)
- Package commands (`horizon:`, `telescope:`, etc.)

Pattern: `bp:{category}:{action}`

Examples:
- `bp:fresh` (no category for common commands)
- `bp:logs:clear`
- `bp:types:generate`
