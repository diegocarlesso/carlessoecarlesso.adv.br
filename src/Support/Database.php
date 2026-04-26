<?php
declare(strict_types=1);

namespace Carlesso\Support;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Database — wrapper PDO singleton.
 *
 * Migrado de public_html/includes/db.php preservando 100% da API estática.
 * Admin pages legacy continuam chamando Database::fetchOne(), Database::insert(), etc.
 * via class_alias declarado em includes/db.php (shim).
 */
final class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host    = Env::get('DB_HOST', 'localhost');
            $dbname  = Env::get('DB_NAME');
            $user    = Env::get('DB_USER');
            $pass    = Env::get('DB_PASS');
            $charset = Env::get('DB_CHARSET', 'utf8mb4');

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

                if (defined('CARLESSO_INSTALL_MODE') || PHP_SAPI === 'cli') {
                    throw $e;
                }

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

    /**
     * Executa um callback dentro de uma transação.
     * Retorna o que o callback retornar; reverte em caso de exceção.
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::getInstance();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
