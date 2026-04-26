<?php
declare(strict_types=1);

/**
 * bin/migrate-blocks-v2.php — converte blocos V1 (flat array) em V2 (tree).
 *
 * Para cada linha de paginas com blocos V1:
 *   1. Salva o JSON original em paginas.blocks_v1 (se ainda vazio)
 *   2. Roda BlockTransformer::normalize() → árvore V2 com section/column wrap
 *   3. Atualiza paginas.blocos com o novo JSON
 *   4. Marca paginas.blocks_version = 2
 *
 * Idempotente: linhas com blocks_version >= 2 são puladas.
 * Reversível: blocks_v1 mantém o original; restore manual via SQL.
 *
 * Uso:
 *   php bin/migrate-blocks-v2.php              → executa
 *   php bin/migrate-blocks-v2.php --dry-run    → só mostra o que faria
 *   php bin/migrate-blocks-v2.php --rollback   → restaura blocos a partir de blocks_v1
 *
 * Phase 2 cutover típico:
 *   1. composer install local + upload
 *   2. update-v1.3.sql (Phase 1)
 *   3. update-v1.4.sql (Phase 2)
 *   4. php bin/migrate-blocks-v2.php --dry-run    ← verificar antes
 *   5. php bin/backup-db.php                       ← snapshot
 *   6. php bin/migrate-blocks-v2.php               ← migrar
 *   7. setar USE_RENDERER_V2=1 no .env
 *   8. testar páginas no front; se ok, manter
 *   9. Em 30 dias, dropar a coluna blocks_v1
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script deve ser rodado via CLI.\n");
    exit(1);
}

// ── Bootstrap ──────────────────────────────────────────────────────────────
$baseDir = dirname(__DIR__);
$configCandidates = [
    $baseDir . '/public_html/includes/config.php',
    $baseDir . '/includes/config.php',
];
$configFile = null;
foreach ($configCandidates as $c) {
    if (is_file($c)) { $configFile = $c; break; }
}
if (!$configFile) {
    fwrite(STDERR, "Não foi possível localizar includes/config.php\n");
    exit(1);
}

define('CARLESSO_CMS', true);
require_once $configFile;
require_once dirname($configFile) . '/db.php';

// ── Args ──────────────────────────────────────────────────────────────────
$dryRun   = in_array('--dry-run', $argv, true);
$rollback = in_array('--rollback', $argv, true);

if ($rollback) {
    rollback();
    exit(0);
}

migrate($dryRun);
exit(0);

// ═══════════════════════════════════════════════════════════════════════════

function migrate(bool $dryRun): void
{
    $transformer = new \Carlesso\Services\Blocks\BlockTransformer();

    // Pré-checagem das colunas
    $checkV1  = Database::fetchOne("SHOW COLUMNS FROM paginas LIKE 'blocks_v1'");
    $checkVer = Database::fetchOne("SHOW COLUMNS FROM paginas LIKE 'blocks_version'");
    if (!$checkV1 || !$checkVer) {
        fwrite(STDERR, "ERRO: colunas paginas.blocks_v1 e/ou blocks_version ausentes.\n");
        fwrite(STDERR, "      Rode update-v1.4.sql primeiro.\n");
        exit(1);
    }

    $rows = Database::fetchAll(
        'SELECT id, titulo, slug, blocos, blocks_v1, blocks_version
         FROM paginas
         WHERE blocks_version < 2'
    );

    if (!$rows) {
        echo "[migrate] Nenhuma página pendente. Tudo já está em V2.\n";
        return;
    }

    echo sprintf("[migrate] %d página(s) pendente(s)%s.\n",
        count($rows), $dryRun ? ' (DRY RUN)' : ''
    );

    $migrated = 0;
    $skipped  = 0;
    foreach ($rows as $row) {
        $id    = (int) $row['id'];
        $slug  = (string) $row['slug'];
        $title = (string) $row['titulo'];
        $blocos = (string) ($row['blocos'] ?? '');

        if (trim($blocos) === '') {
            // Sem blocos — só marca version=2 e segue
            if (!$dryRun) {
                Database::update('paginas', ['blocks_version' => 2], 'id = ?', [$id]);
            }
            $skipped++;
            echo "  [skip] #$id $slug — sem blocos, marcado como V2\n";
            continue;
        }

        // Já é V2? (defensivo — em teoria a query já filtra)
        if (\Carlesso\Services\Blocks\BlockTransformer::isV2($blocos)) {
            if (!$dryRun) {
                Database::update('paginas', ['blocks_version' => 2], 'id = ?', [$id]);
            }
            $skipped++;
            echo "  [skip] #$id $slug — já era V2 sem flag, atualizado\n";
            continue;
        }

        // Backup do V1 (se ainda vazio)
        $v1Backup = (string) ($row['blocks_v1'] ?? '');
        if ($v1Backup === '') {
            $v1Backup = $blocos;
        }

        // Transforma
        $v2 = $transformer->normalize($blocos);
        $v2Json = json_encode($v2, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($v2Json === false) {
            echo "  [erro] #$id $slug — json_encode falhou\n";
            continue;
        }

        echo "  [ok]   #$id $slug — V1 ({" . strlen($blocos) . " bytes}) → V2 ({" . strlen($v2Json) . " bytes})\n";

        if (!$dryRun) {
            Database::update('paginas', [
                'blocks_v1'      => $v1Backup,
                'blocos'         => $v2Json,
                'blocks_version' => 2,
            ], 'id = ?', [$id]);
        }

        $migrated++;
    }

    echo "\n[migrate] Resumo: $migrated migrada(s), $skipped pulada(s).";
    if ($dryRun) {
        echo "  (dry-run — nada foi gravado)";
    }
    echo "\n";
}

function rollback(): void
{
    $rows = Database::fetchAll(
        'SELECT id, titulo, slug, blocks_v1
         FROM paginas
         WHERE blocks_v1 IS NOT NULL AND blocks_v1 != ""'
    );

    if (!$rows) {
        echo "[rollback] Nenhum backup V1 encontrado.\n";
        return;
    }

    echo "[rollback] Restaurando " . count($rows) . " página(s) para V1.\n";

    foreach ($rows as $row) {
        $id = (int) $row['id'];
        Database::update('paginas', [
            'blocos'         => $row['blocks_v1'],
            'blocks_version' => 1,
        ], 'id = ?', [$id]);
        echo "  [restore] #$id " . $row['slug'] . "\n";
    }

    echo "\n[rollback] Pronto. Lembre de setar USE_RENDERER_V2=0 no .env.\n";
}
