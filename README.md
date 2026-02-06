# Symfony AI | amazee.ai configure

[![CI](https://github.com/amazeeio-solutions/symfony-amazeeai-configure/actions/workflows/ci.yml/badge.svg)](https://github.com/amazeeio-solutions/symfony-amazeeai-configure/actions/workflows/ci.yml)

Symfony command to configure the amazee.ai provider via email-based authentication.

## Installation

Install the bundle via Composer

This package is a pre-release that will be published on Packagist,
for now configure this repository in `composer.json`

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/amazeeio-solutions/symfony-amazeeai-configure.git"
        }
    ]
}
```

```bash
composer require amazeeio/symfony-amazeeai-configure
```

If you're using Symfony Flex, the bundle will be automatically registered. Otherwise, add it to your `config/bundles.php`:

```php
return [
    // ...
    AmazeeIo\AmazeeAiConfigure\AmazeeAiConfigureBundle::class => ['all' => true],
];
```

## Configure

A Symfony Console command is used to configure amazee.ai LLM and Vector Database providers with
the [Private AI Keys as a Service](https://api.amazee.ai).

```bash
php bin/console ai:amazee:configure user@example.com
```

or `symfony console ai:amazee:configure user@example.com` with the Symfony CLI.

This will send a PIN code to the provided email that can be exchanged for a LLM API key and VDB credentials.

On development environments, we will store the following in `.env.local`

```dotenv
AMAZEEAI_LLM_KEY=sk-...
AMAZEEAI_LLM_API_URL=https://llm.[region].amazee.ai
AMAZEEAI_VDB_HOST=vectordb1.[region].amazee.ai
AMAZEEAI_VDB_PORT=5432
AMAZEEAI_VDB_NAME=db_abcd1234
AMAZEEAI_VDB_USER=user_abcd1234
AMAZEEAI_VDB_PASSWORD=...
AMAZEEAI_VDB_DSN="pgsql:host=${AMAZEEAI_VDB_HOST};port=${AMAZEEAI_VDB_PORT};dbname=${AMAZEEAI_VDB_NAME}"
```

Sensitive env vars are `AMAZEEAI_LLM_KEY` and `AMAZEEAI_VDB_PASSWORD`,
these should be stored with [Symfony secrets](https://symfony.com/doc/current/configuration/secrets.html).

## Development

By default, API requests are using the `prod` environment: `api.amazee.ai`.

Use the `-a` option to redirect API requests to `dev` or `stage` API environments.

```bash
symfony console ai:amazee:configure user@example.com -a dev.api.example.com
```

### Contributing

For contribution in general, refer to [Symfony Contributing](https://symfony.com/doc/current/contributing/index.html).

This project uses git hooks to enforce conventional commits.

The CI will trigger
- PHPStan `vendor/bin/phpstan analyse`
- PHPUnit `vendor/bin/phpunit`
- PHPCS fixer `vendor/bin/php-cs-fixer fix`

#### Conventional Commits

All commit messages must follow the [Conventional Commits](https://www.conventionalcommits.org/) specification. The format is:

```
<type>[optional scope][!]: <description>
```

**Allowed types:** `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`, `revert`

**Examples:**
```
feat: add user authentication
fix(api): resolve null pointer exception
feat!: breaking change in API
docs: update installation instructions
```

The git `commit-msg` hook will automatically validate your commit messages using [CaptainHook](https://github.com/captainhookphp/captainhook).
