<?php

declare(strict_types=1);

namespace Laenutus;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $headers,
        public readonly string $requestId,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = rtrim($uri, '/') ?: '/';

        $headers = [];

        if (function_exists('getallheaders')) {
            $allHeaders = getallheaders();
            if (is_array($allHeaders)) {
                foreach ($allHeaders as $name => $value) {
                    $headers[(string) $name] = (string) $value;
                    $normalized = str_replace(' ', '-', ucwords(strtolower(str_replace(['_', '-'], ' ', (string) $name))));
                    $headers[$normalized] = (string) $value;
                }
            }
        }

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = (string) $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers['Authorization'] = (string) $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['Authorization'] = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        $rawBody = file_get_contents('php://input') ?: '';
        $body = [];
        $contentType = $headers['Content-Type'] ?? '';
        if ($rawBody !== '' && str_contains($contentType, 'application/json')) {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        $requestId = $headers['X-Request-Id']
            ?? ($headers['x-request-id'] ?? null)
            ?? ('req-' . bin2hex(random_bytes(4)));

        return new self(
            method: $method,
            path: $path,
            query: $_GET,
            body: $body,
            headers: $headers,
            requestId: $requestId,
        );
    }

    public function header(string $name, ?string $default = null): ?string
    {
        foreach ($this->headers as $key => $val) {
            if (strcasecmp((string) $key, $name) === 0) {
                return (string) $val;
            }
        }

        return $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization') ?? $this->headers['Authorization'] ?? null;
        if ($auth === null || !str_starts_with($auth, 'Bearer ')) {
            return null;
        }

        return trim(substr($auth, 7));
    }
}
