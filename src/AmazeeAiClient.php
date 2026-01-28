<?php

declare(strict_types=1);

namespace AmazeeIO\AmazeeAIConfigure;

use AmazeeIO\AmazeeAIConfigure\Dto\Model;
use AmazeeIO\AmazeeAIConfigure\Exception\AmazeeAiApiException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client for Amazee AI API interactions.
 */
final class AmazeeAiClient implements AmazeeAiClientInterface
{
    private string $authToken = '';
    private string $host;
    private int $teamId = 0;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        ?string $host = null,
    ) {
        $this->host = $host ?? self::AMAZEE_API_HOST;
    }

    public function setToken(string $token): void
    {
        $this->authToken = $token;
    }

    public function getToken(): string
    {
        return $this->authToken;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getTeamId(): int
    {
        return $this->teamId;
    }

    public function login(string $username, string $password): string
    {
        try {
            $response = $this->makeRequest('POST', '/auth/login', [
                'username' => $username,
                'password' => $password,
            ]);

            $data = json_decode($response);

            if (empty($data->access_token)) {
                $this->logger->error('Login returned success with empty access token.');

                return '';
            }

            return $data->access_token;
        } catch (\Exception $e) {
            $this->logger->error('Failed to login to amazee.ai: {error}', ['error' => $e->getMessage()]);

            return '';
        }
    }

    public function logout(): bool
    {
        try {
            $this->makeRequest('POST', '/auth/logout');

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to log out of amazee.ai: {error}', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function requestCode(string $email): void
    {
        try {
            $this->makeRequest('POST', '/auth/validate-email', ['email' => $email]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to request validation code: {error}', ['error' => $e->getMessage()]);
            throw new AmazeeAiApiException('Failed to request validation code: '.$e->getMessage(), 0, $e);
        }
    }

    public function validateCode(string $email, string $code): ?string
    {
        try {
            $response = $this->makeRequest('POST', '/auth/sign-in', [
                'username' => $email,
                'verification_code' => $code,
            ]);

            $data = json_decode($response, true);

            return $data['access_token'] ?? null;
        } catch (ClientExceptionInterface $e) {
            // 401/403 means invalid code - return null instead of throwing.
            $statusCode = $e->getResponse()->getStatusCode();
            if (\in_array($statusCode, [401, 403], true)) {
                $this->logger->info('Invalid verification code for email: {email}', ['email' => $email]);

                return null;
            }

            $this->logger->error('Failed to validate code: {error}', ['error' => $e->getMessage()]);
            throw new AmazeeAiApiException('Failed to validate verification code: '.$e->getMessage(), 0, $e, $statusCode);
        } catch (\Exception $e) {
            $this->logger->error('Failed to validate code: {error}', ['error' => $e->getMessage()]);
            throw new AmazeeAiApiException('Failed to validate verification code: '.$e->getMessage(), 0, $e);
        }
    }

    public function register(string $email, string $password): string
    {
        try {
            $this->makeRequest('POST', '/auth/register', [
                'email' => $email,
                'password' => $password,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to register with amazee.ai: {error}', ['error' => $e->getMessage()]);

            return '';
        }

        // After successful registration, login to get the token.
        return $this->login($email, $password);
    }

    public function authorized(): bool
    {
        try {
            $response = $this->makeRequest('GET', '/auth/me');
            $data = json_decode($response);

            if (isset($data->team_id)) {
                $this->teamId = (int) $data->team_id;

                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->debug('Authorization check failed: {error}', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function getRegions(): array
    {
        try {
            $response = $this->makeRequest('GET', '/regions');
            /** @var list<array{id: string, name: string, is_active: bool}>|null $regionResponse */
            $regionResponse = json_decode($response, true);

            $regions = [];
            if (\is_array($regionResponse)) {
                foreach ($regionResponse as $region) {
                    if (!empty($region['is_active'])) {
                        $regions[$region['id']] = $region['name'];
                    }
                }
            }

            return $regions;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get regions: {error}', ['error' => $e->getMessage()]);
            throw new AmazeeAiApiException('Failed to retrieve available regions', 0, $e);
        }
    }

    public function createPrivateAiKey(string $regionId, string $name, ?int $teamId = null): array
    {
        try {
            // If no team_id provided, fetch it from /auth/me.
            if (null === $teamId) {
                $this->logger->info('No team_id provided, fetching from /auth/me');
                if ($this->authorized()) {
                    $teamId = $this->getTeamId();
                } else {
                    throw new AmazeeAiApiException('Unable to determine team_id - not authorized');
                }
            }

            $response = $this->makeRequest('POST', '/private-ai-keys', [
                'region_id' => $regionId,
                'name' => $name,
                'team_id' => $teamId,
            ]);

            $data = json_decode($response, true);

            return [
                'litellm_token' => $data['litellm_token'] ?? '',
                'litellm_api_url' => $data['litellm_api_url'] ?? '',
                'database_host' => $data['database_host'] ?? null,
                'database_port' => $data['database_port'] ?? AmazeeAiConfigurationInterface::VDB_PORT_DEFAULT,
                'database_name' => $data['database_name'] ?? null,
                'database_username' => $data['database_username'] ?? null,
                'database_password' => $data['database_password'] ?? null,
            ];
        } catch (AmazeeAiApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create private AI key: {error}', ['error' => $e->getMessage()]);
            throw new AmazeeAiApiException('Failed to create private AI key', 0, $e);
        }
    }

    public function getPrivateApiKeys(): array
    {
        try {
            // Ensure host is set to main API endpoint for this call.
            $this->setHost(self::AMAZEE_API_HOST);
            $response = $this->makeRequest('GET', '/private-ai-keys');

            // @todo - from Drupal provider: create DTO for API key responses.
            $responseBody = json_decode($response);

            $keys = [];
            if (\is_array($responseBody)) {
                foreach ($responseBody as $key) {
                    // Filter out demo keys.
                    if (($key->litellm_api_url ?? '') !== 'https://demo.litellm.ai') {
                        $keys[] = $key;
                    }
                }
            }

            return $keys;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get private API keys: {error}', ['error' => $e->getMessage()]);
            throw new AmazeeAiApiException('Failed to retrieve private API keys', 0, $e);
        }
    }

    public function getPrivateApiKey(string $apiKey): ?\stdClass
    {
        try {
            foreach ($this->getPrivateApiKeys() as $privateApiKey) {
                if (($privateApiKey->litellm_token ?? '') === $apiKey) {
                    return $privateApiKey;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to get private API key: {error}', ['error' => $e->getMessage()]);

            return null;
        }

        return null;
    }

    public function getModels(): array
    {
        try {
            $response = $this->makeRequest('GET', '/model/info');
            $decodedResponse = json_decode($response);

            $models = [];
            if (isset($decodedResponse->data) && \is_array($decodedResponse->data)) {
                foreach ($decodedResponse->data as $modelInfo) {
                    $model = Model::createFromResponse($modelInfo);
                    $models[$model->name] = $model;
                }
            }

            return $models;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get models: {error}', ['error' => $e->getMessage()]);
            throw new AmazeeAiApiException('Failed to retrieve available models', 0, $e);
        }
    }

    /**
     * Make an HTTP request to the Amazee API.
     *
     * @param array<string, mixed>|null $body    request body data
     * @param array<string, string>     $headers additional headers
     *
     * @return string response body content
     *
     * @throws \Exception when the request fails
     */
    private function makeRequest(string $method, string $endpoint, ?array $body = null, array $headers = []): string
    {
        if (empty($this->getHost())) {
            throw new \InvalidArgumentException('API host is not configured');
        }

        $requestHeaders = array_merge([
            'Content-Type' => 'application/json',
        ], $headers);

        if ('' !== $this->getToken()) {
            $requestHeaders['Authorization'] = 'Bearer '.$this->authToken;
        }

        $options = ['headers' => $requestHeaders];

        if (null !== $body) {
            $options['json'] = $body;
        }

        try {
            $response = $this->httpClient->request($method, 'https://'.$this->getHost().$endpoint, $options);

            return $response->getContent();
        } catch (ClientExceptionInterface $e) {
            // Re-throw to allow handling at method level.
            throw $e;
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Network error during API request: {error}', ['error' => $e->getMessage()]);
            throw new AmazeeAiApiException('Network error: '.$e->getMessage(), 0, $e);
        }
    }
}
