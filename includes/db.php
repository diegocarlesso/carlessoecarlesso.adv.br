<?php
/**
 * db.php — Shim de compatibilidade.
 *
 * O Database class real vive em src/Support/Database.php (namespace Carlesso\Support).
 * Este arquivo apenas garante que o Kernel está bootado e cria um alias
 * global `Database` apontando para a classe namespaced, de modo que
 * todo código legacy (`Database::fetchOne`, `Database::insert`, etc.) continua funcionando.
 */

require_once __DIR__ . '/config.php';

if (!class_exists('Database', false)) {
    // Força o autoload da classe real antes de aliasar.
    class_exists(\Carlesso\Support\Database::class, true);
    class_alias(\Carlesso\Support\Database::class, 'Database');
}
