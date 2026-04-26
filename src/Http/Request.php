<?php
declare(strict_types=1);

namespace Carlesso\Http;

/**
 * Request — wrapper imutável sobre $_SERVER/$_GET/$_POST/$_FILES.
 *
 * Scaffold mínimo para Phase 1; Phase 2/3 vai expandir (json body parsing,
 * validação, etc.) conforme os controllers REST forem nascendo.
 */
final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array  $query   = [],
        public readonly array  $body    = [],
        public readonly array  $headers = [],
        public readonly array  $files   = [],
    ) {}

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $path   = parse_url($uri, PHP_URL_PATH) ?: '/';

        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($k, 5)));
                $headers[$name] = $v;
            }
        }

        $body = $_POST;
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && str_starts_with($headers['content-type'] ?? '', 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        return new self(
            method:  $method,
            path:    $path,
            query:   $_GET,
            body:    $body,
            headers: $headers,
            files:   $_FILES,
        );
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function isJson(): bool
    {
        return str_contains($this->header('content-type') ?? '', 'application/json');
    }
}
