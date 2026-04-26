<?php
/**
 * admin/usuarios.php — CRUD de usuários (rev v1.5).
 *
 * Permissão: users.manage (admin only por default; pode delegar via
 * /admin/permissoes.php).
 *
 * Recursos:
 *  - Lista com filtro (todos / ativos / inativos)
 *  - Criar/editar/remover (com proteção contra auto-remoção)
 *  - Toggle ativo/inativo (login bloqueado se enabled=0)
 *  - Reset de senha pelo admin
 *  - Forçar troca de senha no próximo login
 *  - Visualização de permissões efetivas por role
 *  - Auditoria de todas as ações
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

$action = $_GET['action'] ?? 'list';
$id     = (int) ($_GET['id'] ?? 0);
$me     = Auth::user();
$myId   = (int) $me['id'];

// ═══════════════════════════════════════════════════════════════════════════
// Toggle enabled
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'toggle' && $id) {
    CSRF::check();
    if ($id === $myId) {
        flash('error', 'Você não pode desativar a si mesmo.', 'error');
    } else {
        $u = Database::fetchOne('SELECT enabled, username FROM usuarios WHERE id = ?', [$id]);
        if ($u) {
            $newState = (int) (!$u['enabled']);
            Database::update('usuarios', ['enabled' => $newState], 'id = ?', [$id]);
            AuditLog::record($newState ? 'user.enabled' : 'user.disabled', 'user', $id, ['username' => $u['username']]);
            flash('success', $newState ? "Usuário {$u['username']} ativado." : "Usuário {$u['username']} desativado.");
        }
    }
    header('Location: /admin/usuarios.php');
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// Force password reset
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'force_reset' && $id) {
    CSRF::check();
    $u = Database::fetchOne('SELECT username FROM usuarios WHERE id = ?', [$id]);
    if ($u) {
        Database::update('usuarios', ['must_change_password' => 1], 'id = ?', [$id]);
        AuditLog::record('user.force_reset', 'user', $id, ['username' => $u['username']]);
        flash('success', "{$u['username']} terá que trocar a senha no próximo login.");
    }
    header('Location: /admin/usuarios.php');
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// Save (create / update)
// ═══════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['new', 'edit'], true)) {
    CSRF::check();
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $role     = in_array($_POST['role'] ?? '', ['admin','editor','author'], true) ? $_POST['role'] : 'editor';
    $bio      = trim($_POST['bio'] ?? '');
    $password = $_POST['password'] ?? '';
    $passConf = $_POST['password_confirm'] ?? '';
    $enabled  = isset($_POST['enabled']) ? 1 : 0;
    $forceReset = isset($_POST['must_change_password']) ? 1 : 0;

    $errors = [];
    if ($username === '' || $email === '') {
        $errors[] = 'Usuário e e-mail são obrigatórios.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'E-mail inválido.';
    }
    if ($password !== '' && $password !== $passConf) {
        $errors[] = 'As senhas não conferem.';
    }
    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Senha deve ter ao menos 8 caracteres.';
    }
    if (!$id && $password === '') {
        $errors[] = 'Senha é obrigatória ao criar um novo usuário.';
    }
    // Auto-proteção: não pode tirar próprio admin nem desativar a si
    if ($id === $myId) {
        if ($role !== $me['role'] && $me['role'] === 'admin') {
            $errors[] = 'Você não pode mudar seu próprio perfil.';
            $role = $me['role'];
        }
        if (!$enabled) {
            $errors[] = 'Você não pode desativar a si mesmo.';
            $enabled = 1;
        }
    }
    // Username/email únicos
    $dup = Database::fetchOne(
        'SELECT id FROM usuarios WHERE (username = ? OR email = ?) AND id != ? LIMIT 1',
        [$username, $email, $id ?: 0]
    );
    if ($dup) {
        $errors[] = 'Já existe outro usuário com esse username ou e-mail.';
    }

    if ($errors) {
        flash('error', implode(' • ', $errors), 'error');
    } else {
        $data = [
            'username'             => $username,
            'email'                => $email,
            'full_name'            => $fullName,
            'role'                 => $role,
            'bio'                  => $bio,
            'enabled'              => $enabled,
            'must_change_password' => $forceReset,
        ];
        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }
        try {
            if ($id) {
                Database::update('usuarios', $data, 'id = ?', [$id]);
                AuditLog::record('user.updated', 'user', $id, ['username' => $username, 'role' => $role]);
                flash('success', "Usuário {$username} atualizado.");
            } else {
                $id = Database::insert('usuarios', $data);
                AuditLog::record('user.created', 'user', $id, ['username' => $username, 'role' => $role]);
                flash('success', "Usuário {$username} criado.");
            }
            header('Location: /admin/usuarios.php');
            exit;
        } catch (\Throwable $e) {
            flash('error', 'Erro ao salvar: ' . $e->getMessage(), 'error');
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// Delete
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'delete' && $id) {
    CSRF::check();
    if ($id === $myId) {
        flash('error', 'Você não pode remover a si mesmo.', 'error');
    } else {
        $u = Database::fetchOne('SELECT username, role FROM usuarios WHERE id = ?', [$id]);
        if (!$u) {
            flash('error', 'Usuário não encontrado.', 'error');
        } else {
            // Não pode remover o último admin
            if ($u['role'] === 'admin') {
                $admins = (int) (Database::fetchOne('SELECT COUNT(*) AS c FROM usuarios WHERE role = "admin" AND enabled = 1')['c'] ?? 0);
                if ($admins <= 1) {
                    flash('error', 'Impossível remover o último administrador ativo.', 'error');
                    header('Location: /admin/usuarios.php');
                    exit;
                }
            }
            Database::query('DELETE FROM usuarios WHERE id = ?', [$id]);
            AuditLog::record('user.deleted', 'user', $id, ['username' => $u['username']]);
            flash('success', "Usuário {$u['username']} removido.");
        }
    }
    header('Location: /admin/usuarios.php');
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// Data
// ═══════════════════════════════════════════════════════════════════════════
$user  = $id ? Database::fetchOne('SELECT * FROM usuarios WHERE id = ?', [$id]) : null;

$filterEnabled = $_GET['enabled'] ?? '';
$where = '';
if ($filterEnabled === '1') $where = 'WHERE enabled = 1';
elseif ($filterEnabled === '0') $where = 'WHERE enabled = 0';
$users = Database::fetchAll("SELECT * FROM usuarios $where ORDER BY enabled DESC, role DESC, full_name ASC");

$pageTitle  = $action === 'new' ? 'Novo Usuário' : ($action === 'edit' ? 'Editar Usuário' : 'Usuários');
$breadcrumb = ['Usuários' => '/admin/usuarios.php'];
if ($action !== 'list') $breadcrumb[$pageTitle] = '';

include __DIR__ . '/includes/header.php';

$roleLabels = ['admin' => 'Administrador', 'editor' => 'Editor', 'author' => 'Autor'];
$roleColors = ['admin' => 'badge-red', 'editor' => 'badge-blue', 'author' => 'badge-gray'];

// ═══════════════════════════════════════════════════════════════════════════
// LIST
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'list'): ?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:10px">
    <h3>Usuários <span class="badge badge-blue"><?= count($users) ?></span></h3>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <div class="filter-pills">
        <a href="/admin/usuarios.php" class="pill <?= $filterEnabled === '' ? 'active' : '' ?>">Todos</a>
        <a href="/admin/usuarios.php?enabled=1" class="pill <?= $filterEnabled === '1' ? 'active' : '' ?>">Ativos</a>
        <a href="/admin/usuarios.php?enabled=0" class="pill <?= $filterEnabled === '0' ? 'active' : '' ?>">Inativos</a>
      </div>
      <a href="/admin/permissoes.php" class="topbar-btn outline">
        <span class="i i-shield"></span> Gerir permissões
      </a>
      <a href="/admin/usuarios.php?action=new" class="topbar-btn primary">
        <span class="i i-person-plus"></span> Novo Usuário
      </a>
    </div>
  </div>
  <?php showFlash('success'); showFlash('error'); ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Nome</th><th>Usuário</th><th>E-mail</th><th>Perfil</th>
          <th>Status</th><th>Último acesso</th><th>Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr style="<?= !$u['enabled'] ? 'opacity:0.55' : '' ?>">
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div style="width:32px;height:32px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:#fff;flex-shrink:0">
                <?= strtoupper(substr($u['full_name'] ?: $u['username'], 0, 1)) ?>
              </div>
              <div>
                <div style="font-weight:500"><?= e($u['full_name'] ?: '—') ?></div>
                <?php if ($u['id'] === $myId): ?>
                  <div style="font-size:.7rem;color:var(--accent)">você</div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td><code style="font-size:.8rem"><?= e($u['username']) ?></code></td>
          <td style="color:var(--text-muted);font-size:.85rem"><?= e($u['email']) ?></td>
          <td>
            <span class="badge <?= $roleColors[$u['role']] ?? 'badge-gray' ?>">
              <?= e($roleLabels[$u['role']] ?? $u['role']) ?>
            </span>
            <div style="font-size:.7rem;color:#9ca3af;margin-top:3px">
              <?= count(Permissions::forRole($u['role'])) ?> permissões
            </div>
          </td>
          <td>
            <?php if ($u['enabled']): ?>
              <span class="badge badge-green">✓ ativo</span>
            <?php else: ?>
              <span class="badge badge-gray">⊘ inativo</span>
            <?php endif; ?>
            <?php if (!empty($u['must_change_password'])): ?>
              <div style="font-size:.7rem;color:#a16207;margin-top:3px">⚠ senha pendente</div>
            <?php endif; ?>
          </td>
          <td style="color:var(--text-muted);font-size:.78rem">
            <?= $u['last_login'] ? dateFormat($u['last_login'], 'd/m/Y H:i') : '<em>Nunca</em>' ?>
          </td>
          <td>
            <div style="display:flex;gap:4px">
              <a href="/admin/usuarios.php?action=edit&id=<?= $u['id'] ?>" class="topbar-btn outline" style="padding:4px 8px;font-size:.72rem" title="Editar">
                <span class="i i-pencil"></span>
              </a>
              <?php if ($u['id'] !== $myId): ?>
                <form method="POST" action="/admin/usuarios.php?action=toggle&id=<?= $u['id'] ?>" style="display:inline">
                  <?= CSRF::field() ?>
                  <button type="submit" class="topbar-btn outline" style="padding:4px 8px;font-size:.72rem"
                          title="<?= $u['enabled'] ? 'Desativar' : 'Ativar' ?>">
                    <?= $u['enabled'] ? '⊘' : '✓' ?>
                  </button>
                </form>
                <form method="POST" action="/admin/usuarios.php?action=force_reset&id=<?= $u['id'] ?>" style="display:inline">
                  <?= CSRF::field() ?>
                  <button type="submit" class="topbar-btn outline" style="padding:4px 8px;font-size:.72rem"
                          title="Forçar troca de senha"
                          data-confirm="Forçar &lsquo;<?= e($u['username']) ?>&rsquo; a trocar a senha no próximo login?">
                    🔑
                  </button>
                </form>
                <form method="POST" action="/admin/usuarios.php?action=delete&id=<?= $u['id'] ?>" style="display:inline">
                  <?= CSRF::field() ?>
                  <button type="submit" class="topbar-btn danger" style="padding:4px 8px;font-size:.72rem"
                          title="Remover"
                          data-confirm="Remover &lsquo;<?= e($u['username']) ?>&rsquo; permanentemente?">
                    <span class="i i-trash"></span>
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
.filter-pills { display:flex; gap:4px; }
.filter-pills .pill { padding:6px 12px; border-radius:14px; font-size:.78rem; color:var(--text-muted); text-decoration:none; background:#f3f4f6; }
.filter-pills .pill.active { background:var(--primary); color:#fff; }
.filter-pills .pill:hover { background:#e5e7eb; }
.filter-pills .pill.active:hover { background:var(--navy); }
</style>
<script>
document.querySelectorAll('[data-confirm]').forEach(b => b.addEventListener('click', e => { if (!confirm(b.dataset.confirm)) e.preventDefault(); }));
</script>

<?php // ═════════════════════════════════════════════════════════════════════
elseif (in_array($action, ['new', 'edit'])): ?>

<form method="POST" action="/admin/usuarios.php?action=<?= $action ?>&id=<?= $id ?>">
  <?= CSRF::field() ?>
  <?php showFlash('success'); showFlash('error'); ?>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;max-width:1100px;margin:0 auto">

    <!-- Coluna principal -->
    <div style="display:flex;flex-direction:column;gap:20px">

      <div class="card">
        <div class="card-header"><h3><?= e($pageTitle) ?></h3></div>
        <div class="card-body form-grid cols-2">
          <div class="form-group">
            <label class="form-label">Nome completo</label>
            <input class="form-input" type="text" name="full_name" value="<?= e($user['full_name'] ?? '') ?>" autofocus>
          </div>
          <div class="form-group">
            <label class="form-label">Perfil <span class="req">*</span></label>
            <select class="form-select" name="role" <?= $id === $myId ? 'disabled' : '' ?>>
              <?php foreach ($roleLabels as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($user['role'] ?? 'editor') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($id === $myId): ?>
              <input type="hidden" name="role" value="<?= e($user['role']) ?>">
              <div class="form-hint">Não é possível mudar o próprio perfil.</div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label">Username (login) <span class="req">*</span></label>
            <input class="form-input" type="text" name="username" value="<?= e($user['username'] ?? '') ?>" required pattern="[a-zA-Z0-9_.-]+" minlength="3">
            <div class="form-hint">Letras, números, ponto, underline, hífen.</div>
          </div>
          <div class="form-group">
            <label class="form-label">E-mail <span class="req">*</span></label>
            <input class="form-input" type="email" name="email" value="<?= e($user['email'] ?? '') ?>" required>
          </div>
          <div class="form-group full" style="grid-column:1/-1">
            <label class="form-label">Bio (opcional)</label>
            <textarea class="form-textarea" name="bio" rows="3" placeholder="Aparece junto às produções publicadas pelo usuário."><?= e($user['bio'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h3>Senha</h3></div>
        <div class="card-body form-grid cols-2">
          <div class="form-group">
            <label class="form-label">
              <?= $id ? 'Nova senha (deixe em branco para manter)' : 'Senha' ?>
              <?= $id ? '' : '<span class="req">*</span>' ?>
            </label>
            <input class="form-input" type="password" name="password" minlength="8" autocomplete="new-password">
            <div class="form-hint">Mínimo 8 caracteres.</div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirmar senha</label>
            <input class="form-input" type="password" name="password_confirm" autocomplete="new-password">
          </div>
        </div>
      </div>

    </div>

    <!-- Sidebar -->
    <div style="display:flex;flex-direction:column;gap:20px">

      <div class="card">
        <div class="card-header"><h3>Status</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
            <input type="checkbox" name="enabled" <?= ($user['enabled'] ?? 1) ? 'checked' : '' ?> <?= $id === $myId ? 'disabled' : '' ?>>
            <span><strong>Ativo</strong> — pode fazer login</span>
          </label>
          <?php if ($id === $myId): ?>
            <input type="hidden" name="enabled" value="1">
          <?php endif; ?>

          <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
            <input type="checkbox" name="must_change_password" <?= !empty($user['must_change_password']) ? 'checked' : '' ?>>
            <span>Forçar troca de senha no próximo login</span>
          </label>

          <?php if ($id && !empty($user['last_login'])): ?>
            <div style="font-size:.78rem;color:var(--text-muted);padding-top:6px;border-top:1px solid #e5e9ef">
              <strong>Último acesso:</strong><br>
              <?= dateFormat($user['last_login'], 'd/m/Y H:i') ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="card-footer">
          <a href="/admin/usuarios.php" class="topbar-btn outline">Cancelar</a>
          <button type="submit" class="topbar-btn success">
            <span class="i i-check-circle"></span> <?= $id ? 'Atualizar' : 'Criar' ?>
          </button>
        </div>
      </div>

      <?php if ($id && !empty($user['role'])): ?>
      <div class="card">
        <div class="card-header"><h3>Permissões do perfil</h3></div>
        <div class="card-body">
          <div style="font-size:.85rem;color:var(--text-muted);margin-bottom:10px">
            Capabilities herdadas do perfil <strong><?= e($roleLabels[$user['role']]) ?></strong>:
          </div>
          <ul style="list-style:none;padding:0;margin:0;font-size:.82rem">
            <?php foreach (Permissions::forRole($user['role']) as $perm): ?>
              <li style="padding:4px 0;color:#16a34a">✓ <code><?= e($perm) ?></code></li>
            <?php endforeach; ?>
          </ul>
          <a href="/admin/permissoes.php" style="display:inline-block;margin-top:10px;font-size:.82rem;color:var(--primary)">
            Editar permissões do perfil →
          </a>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</form>

<?php endif;
include __DIR__ . '/includes/footer.php'; ?>
