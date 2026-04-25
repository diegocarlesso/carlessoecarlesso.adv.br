<?php
require_once __DIR__ . '/config.php';

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host    = env('DB_HOST', 'localhost');
            $dbname  = env('DB_NAME');
            $user    = env('DB_USER');
            $pass    = env('DB_PASS');
            $charset = env('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_FOUND_ROWS   => true,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                error_log('DB Connection Error: ' . $e->getMessage());

                // Em modo install ou CLI: re-lança para handler superior
                if (defined('CARLESSO_INSTALL_MODE') || PHP_SAPI === 'cli') {
                    throw $e;
                }

                // Em produção (frontend/api): resposta amigável
                http_response_code(503);
                if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'Serviço temporariamente indisponível.']);
                } else {
                    header('Content-Type: text/html; charset=utf-8');
                    echo '<!DOCTYPE html><meta charset="utf-8"><title>Em manutenção</title>';
                    echo '<body style="font-family:system-ui;text-align:center;padding:80px 20px;color:#1a3554">';
                    echo '<h1>Site em manutenção</h1><p>Voltamos em breve.</p></body>';
                }
                exit;
            }
        }
        return self::$instance;
    }

    // Atalhos estáticos
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): int
    {
        $keys   = array_keys($data);
        $cols   = implode(', ', array_map(fn($k) => "`$k`", $keys));
        $places = implode(', ', array_fill(0, count($keys), '?'));
        $sql    = "INSERT INTO `$table` ($cols) VALUES ($places)";
        self::query($sql, array_values($data));
        return (int) self::getInstance()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $sets = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        $sql  = "UPDATE `$table` SET $sets WHERE $where";
        $stmt = self::query($sql, array_merge(array_values($data), $whereParams));
        return $stmt->rowCount();
    }

    public static function lastId(): int
    {
        return (int) self::getInstance()->lastInsertId();
    }
}
