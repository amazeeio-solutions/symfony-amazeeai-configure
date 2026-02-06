# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Symfony bundle that provides a console command to configure amazee.ai LLM and VectorDB providers via email-based PIN authentication. It stores credentials in `.env.local` (dev) or Symfony secrets (prod).

## Commands

### Development
```bash
composer install                          # Install dependencies
vendor/bin/phpunit                        # Run all tests
vendor/bin/phpunit tests/path/Test.php    # Run single test file
vendor/bin/phpunit --filter testMethod    # Run specific test method
vendor/bin/phpstan analyse                # Static analysis (level 8)
vendor/bin/php-cs-fixer fix               # Fix code style
vendor/bin/php-cs-fixer fix --dry-run --diff  # Check code style without fixing
vendor/bin/captainhook install            # Install git hooks (automatic on composer install)
```

### Using the Bundle
```bash
php bin/console ai:amazee:configure user@example.com                    # Configure with prod API
php bin/console ai:amazee:configure user@example.com -a backend.dev.amazeeai.us2.amazee.io  # Use dev API
```

## Architecture

### Core Flow
`AmazeeAiConfigureCommand` orchestrates a 5-step configuration wizard:
1. Email verification (sends PIN via `AmazeeAiClient::requestCode`)
2. PIN entry and validation (`AmazeeAiClient::validateCode`)
3. Team ID fetch (`AmazeeAiClient::authorized`)
4. API key resolution - finds existing or creates new via region selection
5. Credential persistence to `.env.local` or Symfony secrets

### Key Components
- **AmazeeAiClient** (`src/AmazeeAiClient.php`): HTTP client for amazee.ai API. Manages auth token state and all API calls.
- **AmazeeAiConfiguration** (`src/AmazeeAiConfiguration.php`): Business logic layer coordinating the client and persistence. Handles interactive prompts.
- **EnvFileWriter** (`src/EnvFileWriter.php`): Writes env vars to `.env.local`, manages `###> amazee.ai ###` block.
- **SecretsWriter** (`src/SecretsWriter.php`): Stores sensitive values via `secrets:set` command.

### Service Wiring
Services are configured in `config/services.php` using PHP-based configuration with autowiring.

### Environment Variables Set
```
AMAZEEAI_LLM_KEY, AMAZEEAI_LLM_API_URL
AMAZEEAI_VDB_HOST, AMAZEEAI_VDB_PORT, AMAZEEAI_VDB_NAME, AMAZEEAI_VDB_USER, AMAZEEAI_VDB_PASSWORD, AMAZEEAI_VDB_DSN
```

## Code Style

Uses PHP-CS-Fixer with `@Symfony` ruleset plus strict rules:
- `declare(strict_types=1)` required
- Strict comparisons (`===`)
- All classes are final by default
- PHPStan level 8

## Git Hooks & Commit Convention

This project uses CaptainHook to enforce code quality standards via git hooks:

### Conventional Commits
All commit messages must follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

```
<type>[optional scope][!]: <description>

[optional body]

[optional footer(s)]
```

**Allowed types:** `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`, `revert`

**Examples:**
- `feat: add user authentication`
- `fix(api): resolve null pointer exception`
- `feat!: breaking change in API`
- `docs: update installation instructions`

The `commit-msg` hook will automatically validate your commit messages and reject commits that don't follow this format.

## Testing

Tests use PHPUnit 12.5. Mock API responses are in `tests/Mock/AmazeeApiMockResponses.php`.
