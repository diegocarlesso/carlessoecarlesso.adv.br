<?php
declare(strict_types=1);

namespace Carlesso\Http;

/**
 * Response — builder leve para respostas HTTP.
 *
 * Scaffold mínimo para Phase 1. Phase 2 vai usar para os endpoints
 * /api/v1/* dos controllers REST.
 */
final class Response
{
    public function __construct(
        public string $body = '',
        public int    $status = 200,
        public array  $headers = [],
    ) {}

    public static function json(mixed $data, int $status = 200): self
    {
        $r = new self((string) json_encode($data, JSON_UNESCAPED_UNICODE), $status);
        $r->headers['Content-Type'] = 'application/json; charset=utf-8';
        return $r;
    }

    public static function ok(mixed $data = null): self
    {
        return self::json(['ok' => true, 'data' => $data]);
    }

    public static function error(string $message, int $status = 400): self
    {
        return self::json(['ok' => false, 'error' => $message], $status);
    }

    public static function redirect(string $location, int $status = 302): self
    {
        $r = new self('', $status);
        $r->headers['Location'] = $location;
        return $r;
    }

    public static function html(string $html, int $status = 200): self
    {
        $r = new self($html, $status);
        $r->headers['Content-Type'] = 'text/html; charset=utf-8';
        return $r;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}
