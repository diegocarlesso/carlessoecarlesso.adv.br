<?php
/**
 * admin/permissoes.php — Matriz de permissões por role.
 *
 * Permite ligar/desligar cada uma das 11 capabilities para os 3 roles
 * (admin/editor/author). Admin sempre mantém TODAS — checkboxes do admin
 * aparecem disabled e marcadas (proteção contra lock-out).
 *
 * Requer users.manage.
 */
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

use Carlesso\Services\Audit\AuditLog;
use Carlesso\Services\Auth\Permissions;

Auth::requireCan('users.manage');

$roles = ['editor', 'author']; // admin é fixo (todas)
$me    = Auth::user();

// ═══════════════════════════════════════════════════════════════════════════
// Save
// ═══════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();
    $submitted = $_POST['permissions'] ?? [];

    $changes = [];
    Database::transaction(function () use ($submitted, $roles, &$changes) {
        foreach ($roles as $role) {
            $current = Permissions::forRole($role);
            $newSet  = array_values(array_intersect(
                Permissions::ALL_PERMISSIONS,
                array_keys($submitted[$role] ?? [])
            ));

            $added   = array_diff($newSet, $current);
            $removed = array_diff($current, $newSet);

            if (!$added && !$removed) continue;

            // Apaga e re-insere (transação garante atomicidade)
            Database::query('DELETE FROM role_permissions WHERE role = ?', [$role]);
            foreach ($newSet as $perm) {
                Database::insert('role_permissions', ['role' => $role, 'perm_key' => $perm]);
            }

            $changes[$role] = ['added' => array_values($added), 'removed' => array_values($removed)];
        }
    });

    Permissions::flushCache();

    if ($changes) {
        AuditLog::record('permissions.updated', 'role', null, $changes);
        flash('success', 'Permissões atualizadas para ' . count($changes) . ' perfil(is).');
    } else {
        flash('success', 'Nenhuma mudança aplicada.');
    }

    header('Location: /admin/permissoes.php');
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// Data
// ═══════════════════════════════════════════════════════════════════════════
$allPerms = Database::fetchAll('SELECT * FROM permissions ORDER BY perm_key');
if (!$allPerms) {
    // Fallback: gera a partir do código (Phase 1 seed pode não ter rodado)
    $allPerms = array_map(
        fn($k) => ['perm_key' => $k, 'label' => $k, 'description' => ''],
        Permissions::ALL_PERMISSIONS
    );
}

// Mapeia categoria via prefixo do perm_key
$categories = [];
foreach ($allPerms as $p) {
    $cat = explode('.', $p['perm_key'])[0];
    $categories[$cat][] = $p;
}

$matrix = [
    'admin'  => Permissions::ALL_PERMISSIONS, // sempre todas
    'editor' => Permissions::forRole('editor'),
    'author' => Permissions::forRole('author'),
];

$pageTitle  = 'Permissões por Perfil';
$breadcrumb = ['Usuários' => '/admin/usuarios.php', 'Permissões' => ''];

include __DIR__ . '/includes/header.php';
?>

<form method="POST" action="/admin/permissoes.php">
  <?= CSRF::field() ?>
  <?php showFlash('success'); showFlash('error'); ?>

  <div class="card" style="max-width:1100px;margin:0 auto">
    <div class="card-header" style="flex-wrap:wrap;gap:10px">
      <div>
        <h3>Matriz de Permissões</h3>
        <div style="font-size:.78rem;color:var(--text-muted);margin-top:4px">
          Cada checkbox controla uma capability por perfil. As mudanças se aplicam a TODOS os usuários daquele perfil imediatamente.
        </div>
      </div>
      <div style="display:flex;gap:8px">
        <a href="/admin/usuarios.php" class="topbar-btn outline">Voltar</a>
        <button type="submit" class="topbar-btn success">
          <span class="i i-check-circle"></span> Salvar mudanças
        </button>
      </div>
    </div>

    <div class="card-body" style="padding:0">
      <div style="overflow-x:auto">
      <table class="data-table perm-matrix">
        <thead>
          <tr>
            <th style="text-align:left;min-width:280px">Capability</th>
            <th style="text-align:center;background:#fef2f2">
              <span class="badge badge-red">Admin</span>
              <div style="font-size:.7rem;color:var(--text-muted);font-weight:400;margin-top:3px">sempre todas</div>
            </th>
            <th style="text-align:center;background:#eff6ff">
              <span class="badge badge-blue">Editor</span>
            </th>
            <th style="text-align:center;background:#f9fafb">
              <span class="badge badge-gray">Autor</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $cat => $perms):
              $catLabel = match ($cat) {
                  'pages'      => '📄 Páginas',
                  'posts'      => '📝 Produções',
                  'media'      => '🖼️ Mídia',
                  'users'      => '👥 Usuários',
                  'settings'   => '⚙️ Configurações',
                  'appearance' => '🎨 Aparência',
                  default      => ucfirst($cat),
              };
          ?>
            <tr class="cat-row">
              <td colspan="4" style="background:#f9fafb;font-weight:600;color:var(--navy);padding:12px 16px">
                <?= $catLabel ?>
              </td>
            </tr>
            <?php foreach ($perms as $p):
                $key = $p['perm_key'];
            ?>
              <tr>
                <td>
                  <div style="font-weight:500"><?= e($p['label']) ?></div>
                  <code style="font-size:.7rem;color:#9ca3af"><?= e($key) ?></code>
                  <?php if (!empty($p['description'])): ?>
                    <div style="font-size:.78rem;color:var(--text-muted);margin-top:3px"><?= e($p['description']) ?></div>
                  <?php endif; ?>
                </td>
                <td style="text-align:center;background:#fef2f2">
                  <input type="checkbox" checked disabled title="Admin sempre tem todas as permissões">
                  <input type="hidden" name="permissions[admin][<?= e($key) ?>]" value="1">
                </td>
                <td style="text-align:center;background:#eff6ff">
                  <label style="display:inline-block;padding:8px;cursor:pointer">
                    <input type="checkbox" name="permissions[editor][<?= e($key) ?>]" value="1"
                           <?= in_array($key, $matrix['editor'], true) ? 'checked' : '' ?>
                           style="width:18px;height:18px;cursor:pointer">
                  </label>
                </td>
                <td style="text-align:center;background:#f9fafb">
                  <label style="display:inline-block;padding:8px;cursor:pointer">
                    <input type="checkbox" name="permissions[author][<?= e($key) ?>]" value="1"
                           <?= in_array($key, $matrix['author'], true) ? 'checked' : '' ?>
                           style="width:18px;height:18px;cursor:pointer">
                  </label>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>

    <div class="card-footer" style="justify-content:space-between">
      <div style="font-size:.78rem;color:var(--text-muted)">
        💡 <strong>Dica:</strong> use a matriz para delegar. Ex.: deixar Autor publicar diretamente (marque <code>posts.publish</code>) ou habilitar Editor a gerenciar usuários (marque <code>users.manage</code>).
      </div>
      <button type="submit" class="topbar-btn success">
        <span class="i i-check-circle"></span> Salvar mudanças
      </button>
    </div>
  </div>
</form>

<style>
.perm-matrix th, .perm-matrix td { padding:10px 14px; }
.perm-matrix tbody tr:not(.cat-row):hover { background:rgba(82,112,149,.04); }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
