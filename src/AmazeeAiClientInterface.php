<?php

declare(strict_types=1);

namespace AmazeeIo\AmazeeAiConfigure;

/**
 * Interface for Amazee AI API Client.
 */
interface AmazeeAiClientInterface
{
    /**
     * The default API endpoint host.
     */
    public const AMAZEE_API_HOST = 'api.amazee.ai';

    /**
     * Set the auth token to use for future requests.
     */
    public function setToken(string $token): void;

    /**
     * Get the current auth token.
     */
    public function getToken(): string;

    /**
     * Set the host domain for API requests.
     */
    public function setHost(string $host): void;

    /**
     * Get the current host domain.
     */
    public function getHost(): string;

    /**
     * Get the team ID for the current user.
     */
    public function getTeamId(): int;

    /**
     * Attempt to log in to the Amazee API with username and password.
     *
     * @return string the access token or an empty string on failure
     */
    public function login(string $username, string $password): string;

    /**
     * Attempt to log out from the Amazee API.
     *
     * @return bool whether the operation was successful
     */
    public function logout(): bool;

    /**
     * Request a validation code for a given email address.
     * This triggers an email with a PIN code to the user.
     *
     * @throws Exception\AmazeeAiApiException when the request fails
     */
    public function requestCode(string $email): void;

    /**
     * Validate an email validation code and get an access token.
     *
     * @return string|null the access token or null if validation failed
     *
     * @throws Exception\AmazeeAiApiException when the request fails unexpectedly
     */
    public function validateCode(string $email, string $code): ?string;

    /**
     * Attempt to register and log in to the Amazee API.
     *
     * @return string the access token or an empty string on failure
     */
    public function register(string $email, string $password): string;

    /**
     * Check if the client is authorized and fetch user info.
     * Also populates the team_id.
     *
     * @return bool whether the client has authorized access
     */
    public function authorized(): bool;

    /**
     * Get a list of available regions from the API.
     *
     * @return array<string, string> array of region names, keyed by ID
     *
     * @throws Exception\AmazeeAiApiException when the request fails
     */
    public function getRegions(): array;

    /**
     * Create a Private AI key from the API.
     *
     * @param string   $regionId the region for the key
     * @param string   $name     the name for the key
     * @param int|null $teamId   the team ID to associate the key with
     *
     * @return array{
     *     litellm_token: string,
     *     litellm_api_url: string,
     *     database_host: string|null,
     *     database_port: int,
     *     database_name: string|null,
     *     database_username: string|null,
     *     database_password: string|null
     * } Info about the created Private AI key
     *
     * @throws Exception\AmazeeAiApiException when the request fails
     */
    public function createPrivateAiKey(string $regionId, string $name, ?int $teamId = null): array;

    /**
     * Get the private keys for the authorized user from the API.
     *
     * @return array<\stdClass> an array of the available keys
     *
     * @throws Exception\AmazeeAiApiException when the request fails
     */
    public function getPrivateApiKeys(): array;

    /**
     * Get info about a specific API key by its token.
     *
     * @return \stdClass|null the API key object or null if not found
     */
    public function getPrivateApiKey(string $apiKey): ?\stdClass;

    /**
     * Get available models from the API.
     *
     * @return array<string, Dto\Model> array of models keyed by model name
     *
     * @throws Exception\AmazeeAiApiException when the request fails
     */
    public function getModels(): array;
}
