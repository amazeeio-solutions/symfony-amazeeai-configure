# Symfony AI | amazee.ai configure

Symfony command to configure the amazee.ai provider via email-based authentication.

## Installation

Install the bundle via Composer:

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
```

Sensitive env vars are `AMAZEEAI_LLM_KEY` and `AMAZEEAI_VDB_PASSWORD`,
these should be stored with [Symfony secrets](https://symfony.com/doc/current/configuration/secrets.html).

## Development

Use the test mode, that will redirect API requests to `dev` or `stage` API environments.
In this case, the test API hostname has to be provided.

- `dev` https://backend.dev.amazeeai.us2.amazee.io
- `stage` https://backend.main.amazeeai.us2.amazee.io
- `prod` https://api.amazee.ai

Example, for `dev`
```dotenv
symfony console ai:amazee:configure user@example.com -t backend.dev.amazeeai.us2.amazee.io
```
