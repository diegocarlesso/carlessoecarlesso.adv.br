<?php
declare(strict_types=1);

namespace Carlesso\Http;

/**
 * Router — tabela de rotas → Controller@method.
 *
 * Scaffold mínimo para Phase 1. O frontend e admin pages legacy continuam
 * sendo servidos pelos arquivos PHP individuais (index.php, /admin/*.php).
 *
 * Phase 2/3 vai migrar gradualmente:
 *  - /api/v1/* → este router
 *  - /admin/* → eventualmente este router
 *  - / (frontend público) → eventualmente este router (com URL shape preservado)
 */
final class Router
{
    /** @var array<int, array{method:string,pattern:string,handler:callable|array}> */
    private array $routes = [];

    public function get(string $pattern, callable|array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable|array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable|array $handler): void
    {
        $this->add('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, callable|array $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable|array $handler): void
    {
        $this->routes[] = compact('method', 'pattern', 'handler');
    }

    /**
     * Tenta despachar uma Request. Retorna a Response do handler casado,
     * ou null se nenhum casou (chamador decide o 404).
     */
    public function dispatch(Request $req): ?Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $req->method) {
                continue;
            }
            $params = self::match($route['pattern'], $req->path);
            if ($params === null) {
                continue;
            }
            $handler = $route['handler'];
            if (is_array($handler) && count($handler) === 2) {
                [$class, $method] = $handler;
                $instance = is_object($class) ? $class : new $class();
                $result = $instance->$method($req, ...array_values($params));
            } else {
                $result = $handler($req, ...array_values($params));
            }
            return $result instanceof Response ? $result : Response::html((string) $result);
        }
        return null;
    }

    /**
     * Casa um pattern com placeholders {name} contra um path real.
     * Retorna array de params ou null se não casa.
     */
    private static function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '/?$#';
        if (!preg_match($regex, $path, $m)) {
            return null;
        }
        return array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
    }
}
