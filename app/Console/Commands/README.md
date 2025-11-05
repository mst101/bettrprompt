# Hidden Gambia Artisan Commands

This directory contains all custom Artisan commands for the Hidden Gambia application.

## Command Structure

We use a standardized structure for our commands with clear namespaces to distinguish our custom commands from Laravel's
built-in ones.

### Directory Structure

```
app/Console/Commands/
├── HiddenGambia/               # All custom Hidden Gambia commands
│   ├── App/                    # Application generation commands
│   ├── Core/                   # System/infrastructure commands
│   ├── Resources/              # Resource generation commands
│   └── TypeScript/             # TypeScript generation commands
│       └── Utils/              # TypeScript generation utilities
├── Shared/                     # Shared utilities and classes
└── README.md                   # This file
```

## Command Naming Convention

### Command Signatures

Hidden Gambia commands follow this pattern:

```
hg:{category}:{action}
```

Examples:

- `hg:make:resources` - Generate Laravel resources
- `hg:make:typescript` - Generate TypeScript definitions
- `hg:make:app` - Generate both resources and TypeScript definitions

Other commands follow this pattern:

```
{category}:{action}
```

### Command Classes

Command classes follow these conventions:

- Located in the appropriate category directory
- Named using PascalCase with a `Command` suffix
- Namespace matches the directory structure

## Available Commands

### Generation Commands

| Command              | Description                                                      | Usage                                                                                        |
|----------------------|------------------------------------------------------------------|----------------------------------------------------------------------------------------------|
| `hg:make:app`        | Generate both resources and TypeScript definitions (recommended) | `./vendor/bin/sail artisan hg:make:app` or `./vendor/bin/sail artisan hg:make:app User Team` |
| `hg:make:resources`  | Generate Laravel resources for models                            | `./vendor/bin/sail artisan hg:make:resources User Post`                                      |
| `hg:make:typescript` | Generate TypeScript definitions                                  | `./vendor/bin/sail artisan hg:make:typescript User Post`                                     |

### Core Commands

| Command                        | Description              | Usage                                                    |
|--------------------------------|--------------------------|----------------------------------------------------------|
| `hg:core:clear-settings-cache` | Clear the settings cache | `./vendor/bin/sail artisan hg:core:clear-settings-cache` |

## Generation Command Examples

```bash
# Generate both resources and TypeScript for all models (recommended)
./vendor/bin/sail artisan hg:make:app

# Generate for specific models
./vendor/bin/sail artisan hg:make:app User Team Itinerary

# Generate only resources for specific models
./vendor/bin/sail artisan hg:make:resources User Team

# Generate only TypeScript for specific models
./vendor/bin/sail artisan hg:make:typescript User Team

# Generate TypeScript for all models and resources
./vendor/bin/sail artisan hg:make:typescript

# Generate TypeScript for a specific resource
./vendor/bin/sail artisan hg:make:typescript --resource=UserResource
```

## Best Practices

When creating new commands, follow these guidelines:

1. **Namespace**: Place commands in the appropriate category directory
2. **Naming**: Follow the appropriate namespace pattern (`hg:` for Hidden Gambia commands)
3. **Organisation**: Place commands in the appropriate category directory
4. **Reusability**: Extract common functionality to shared classes
5. **Testing**: Write tests for all commands
6. **Documentation**: Add comprehensive help text and docblocks
7. **Laravel Sail**: Always use Laravel Sail to run commands in the documentation (`./vendor/bin/sail artisan`)
