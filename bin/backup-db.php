<?php
declare(strict_types=1);

/**
 * bin/backup-db.php — Backup do banco MySQL.
 *
 * Uso:
 *   php bin/backup-db.php
 *
 * Tenta mysqldump primeiro (mais rápido e fidedigno). Se mysqldump não está
 * disponível (hosts compartilhados frequentemente bloqueiam exec), faz
 * fallback puro PHP via PDO.
 *
 * Output em storage/backups/db-YYYY-MM-DD-HHMMSS.sql.gz
 *
 * Configuração via .env:
 *   BACKUP_RETENTION_DAYS=14   (default)
 *   BACKUP_DISABLE_EXEC=0      (force fallback PHP mesmo com exec disponível)
 *
 * Exit codes:
 *   0 — sucesso
 *   1 — erro (config, lock, dump)
 *   2 — outro processo rodando (lock file presente)
 *
 * Phase 4 vai expor um botão admin que chama este mesmo script.
 */

// ── Bootstrap ──────────────────────────────────────────────────────────────
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script deve ser rodado via CLI (php bin/backup-db.php).\n");
    exit(1);
}

// Auto-detecta o layout para localizar includes/config.php:
//   PARENT   : bin/ é irmão de public_html/    → dirname(__DIR__) + /public_html/includes/config.php
//   HOSTINGER: bin/ está dentro de public_html/ → dirname(__DIR__) + /includes/config.php
$baseDir = dirname(__DIR__);
$configCandidates = [
    $baseDir . '/public_html/includes/config.php',
    $baseDir . '/includes/config.php',
];
$configFile = null;
foreach ($configCandidates as $c) {
    if (is_file($c)) {
        $configFile = $c;
        break;
    }
}
if (!$configFile) {
    fwrite(STDERR, "Não foi possível localizar includes/config.php em:\n  " . implode("\n  ", $configCandidates) . "\n");
    exit(1);
}

define('CARLESSO_CMS', true);
require_once $configFile;

if (!defined('STORAGE_PATH')) {
    fwrite(STDERR, "STORAGE_PATH não definido. Verifique se Kernel::boot() rodou.\n");
    exit(1);
}

// ── Config ────────────────────────────────────────────────────────────────
$dbHost      = env('DB_HOST', 'localhost');
$dbName      = env('DB_NAME');
$dbUser      = env('DB_USER');
$dbPass      = env('DB_PASS');
$retainDays  = (int) (env('BACKUP_RETENTION_DAYS', 14));
$forceFallback = (int) (env('BACKUP_DISABLE_EXEC', 0)) === 1;

if (!$dbName || !$dbUser) {
    fwrite(STDERR, "DB_NAME ou DB_USER ausentes no .env.\n");
    exit(1);
}

$backupDir = STORAGE_PATH . '/backups';
$lockFile  = $backupDir . '/.lock';

if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true)) {
    fwrite(STDERR, "Não foi possível criar $backupDir\n");
    exit(1);
}

// ── Lock para evitar overlap ──────────────────────────────────────────────
$lock = @fopen($lockFile, 'c');
if (!$lock) {
    fwrite(STDERR, "Não foi possível abrir lock file $lockFile\n");
    exit(1);
}
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Outro backup já está rodando (lock ativo).\n");
    exit(2);
}
register_shutdown_function(static function () use ($lock, $lockFile): void {
    @flock($lock, LOCK_UN);
    @fclose($lock);
    @unlink($lockFile);
});

// ── Nome do arquivo ───────────────────────────────────────────────────────
$timestamp = date('Y-m-d-His');
$outFile   = $backupDir . "/db-{$timestamp}.sql";
$outFileGz = $outFile . '.gz';

echo "[backup] Iniciando backup de '$dbName' → $outFileGz\n";

// ── Tenta mysqldump ───────────────────────────────────────────────────────
$success = false;
if (!$forceFallback && function_exists('exec')) {
    $disabled = explode(',', (string) ini_get('disable_functions'));
    if (!in_array('exec', array_map('trim', $disabled), true)) {
        $success = runMysqldump($dbHost, $dbUser, $dbPass, $dbName, $outFile);
    }
}

// ── Fallback PHP via PDO ──────────────────────────────────────────────────
if (!$success) {
    echo "[backup] mysqldump indisponível; usando fallback PHP.\n";
    $success = runPhpDump($dbHost, $dbUser, $dbPass, $dbName, $outFile);
}

if (!$success) {
    fwrite(STDERR, "[backup] Falhou.\n");
    @unlink($outFile);
    exit(1);
}

// ── Comprime ──────────────────────────────────────────────────────────────
if (!compressFile($outFile, $outFileGz)) {
    fwrite(STDERR, "[backup] Falha ao comprimir.\n");
    exit(1);
}
@unlink($outFile);

$size = filesize($outFileGz);
echo "[backup] OK — " . number_format($size / 1024, 1) . " KB\n";

// ── Retenção ──────────────────────────────────────────────────────────────
$pruned = pruneOldBackups($backupDir, $retainDays);
if ($pruned > 0) {
    echo "[backup] Removidos $pruned backup(s) com mais de $retainDays dias.\n";
}

exit(0);

// ═══════════════════════════════════════════════════════════════════════════
// Helpers
// ═══════════════════════════════════════════════════════════════════════════

function runMysqldump(string $host, string $user, string $pass, string $db, string $outFile): bool
{
    $cmd = sprintf(
        'mysqldump --host=%s --user=%s --password=%s --single-transaction --quick --skip-lock-tables %s 2>&1',
        escapeshellarg($host),
        escapeshellarg($user),
        escapeshellarg($pass),
        escapeshellarg($db)
    );
    $fp = @popen($cmd, 'r');
    if (!$fp) {
        return false;
    }
    $out = @fopen($outFile, 'w');
    if (!$out) {
        pclose($fp);
        return false;
    }
    while (!feof($fp)) {
        $chunk = fread($fp, 65536);
        if ($chunk === false) {
            break;
        }
        fwrite($out, $chunk);
    }
    fclose($out);
    $code = pclose($fp);
    if ($code !== 0 || filesize($outFile) === 0) {
        return false;
    }
    // Verificação básica: arquivo deve conter "CREATE TABLE" ou "INSERT INTO"
    $head = file_get_contents($outFile, false, null, 0, 4096);
    if ($head === false || (!str_contains($head, 'CREATE TABLE') && !str_contains($head, 'INSERT INTO'))) {
        return false;
    }
    return true;
}

function runPhpDump(string $host, string $user, string $pass, string $db, string $outFile): bool
{
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (PDOException $e) {
        fwrite(STDERR, "PDO connect: " . $e->getMessage() . "\n");
        return false;
    }

    $out = @fopen($outFile, 'w');
    if (!$out) {
        return false;
    }

    fwrite($out, "-- Carlesso CMS backup\n-- Generated: " . date('c') . "\n");
    fwrite($out, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        fwrite($out, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($out, $create['Create Table'] . ";\n\n");

        $rows = $pdo->query("SELECT * FROM `$table`");
        while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
            $cols = array_map(fn($c) => "`$c`", array_keys($row));
            $vals = array_map(static function ($v) use ($pdo) {
                if ($v === null) return 'NULL';
                if (is_int($v) || is_float($v)) return (string) $v;
                return $pdo->quote((string) $v);
            }, array_values($row));
            fwrite($out, "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n");
        }
        fwrite($out, "\n");
    }

    fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n");
    fclose($out);
    return true;
}

function compressFile(string $in, string $out): bool
{
    $src = @fopen($in, 'rb');
    $dst = @gzopen($out, 'wb9');
    if (!$src || !$dst) {
        if ($src) fclose($src);
        if ($dst) gzclose($dst);
        return false;
    }
    while (!feof($src)) {
        $chunk = fread($src, 65536);
        if ($chunk === false) break;
        gzwrite($dst, $chunk);
    }
    fclose($src);
    gzclose($dst);
    return true;
}

function pruneOldBackups(string $dir, int $retainDays): int
{
    $threshold = time() - ($retainDays * 86400);
    $count = 0;
    foreach (glob($dir . '/db-*.sql.gz') ?: [] as $file) {
        if (filemtime($file) < $threshold) {
            if (@unlink($file)) {
                $count++;
            }
        }
    }
    return $count;
}
