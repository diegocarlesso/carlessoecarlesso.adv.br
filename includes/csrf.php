<?php
/**
 * csrf.php — Shim de compatibilidade.
 *
 * A classe Csrf real vive em src/Support/Csrf.php (namespace Carlesso\Support).
 * Mantém o nome global CSRF (maiúsculas) usado por todos os admin pages.
 */

require_once __DIR__ . '/config.php';

if (!class_exists('CSRF', false)) {
    class_exists(\Carlesso\Support\Csrf::class, true);
    class_alias(\Carlesso\Support\Csrf::class, 'CSRF');
}
