<?php
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireRole('admin'); // Apenas admin

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$self   = Auth::user();

// ── Save ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $fullName  = trim($_POST['full_name'] ?? '');
    $role      = in_array($_POST['role'] ?? '', ['admin','editor','author']) ? $_POST['role'] : 'editor';
    $password  = $_POST['password'] ?? '';
    $passConf  = $_POST['password_confirm'] ?? '';

    if (!$username || !$email) {
        flash('error', 'Usuário e e-mail são obrigatórios.', 'error');
    } elseif ($password && $password !== $passConf) {
        flash('error', 'Senhas não conferem.', 'error');
    } elseif ($password && strlen($password) < 8) {
        flash('error', 'A senha deve ter no mínimo 8 caracteres.', 'error');
    } else {
        $data = ['username' => $username, 'email' => $email, 'full_name' => $fullName, 'role' => $role];
        if ($password) $data['password'] = password_hash($password, PASSWORD_BCRYPT);

        if ($id) {
            Database::update('usuarios', $data, 'id = ?', [$id]);
            flash('success', 'Usuário atualizado.');
        } else {
            if (!$password) {
                flash('error', 'Senha obrigatória para novo usuário.', 'error');
                goto render;
            }
            Database::insert('usuarios', $data);
            flash('success', 'Usuário criado.');
        }
        header('Location: /admin/usuarios.php');
        exit;
    }
}

// ── Delete ─────────────────────────────────────────────────
if ($action === 'delete' && $id && $id !== $self['id']) {
    CSRF::check();
    Database::query('DELETE FROM usuarios WHERE id = ?', [$id]);
    flash('success', 'Usuário removido.');
    header('Location: /admin/usuarios.php');
    exit;
}

render:
$user  = $id ? Database::fetchOne('SELECT * FROM usuarios WHERE id = ?', [$id]) : null;
$users = Database::fetchAll('SELECT * FROM usuarios ORDER BY created_at DESC');

$pageTitle  = $action === 'new' ? 'Novo Usuário' : ($action === 'edit' ? 'Editar Usuário' : 'Usuários');
$breadcrumb = ['Usuários' => '/admin/usuarios.php'];
if ($action !== 'list') $breadcrumb[$pageTitle] = '';

include __DIR__ . '/includes/header.php';

if ($action === 'list'): ?>

<div class="card">
  <div class="card-header">
    <h3>Usuários <span class="badge badge-blue"><?= count($users) ?></span></h3>
    <a href="/admin/usuarios.php?action=new" class="topbar-btn primary">
      <span class="i i-person-plus"></span> Novo Usuário
    </a>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Nome</th><th>Usuário</th><th>E-mail</th><th>Perfil</th><th>Último Acesso</th><th>Ações</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div style="width:32px;height:32px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:#fff;flex-shrink:0">
                <?= strtoupper(substr($u['full_name'] ?: $u['username'], 0, 1)) ?>
              </div>
              <span style="font-weight:500"><?= e($u['full_name'] ?: '—') ?></span>
            </div>
          </td>
          <td><code style="font-size:.8rem"><?= e($u['username']) ?></code></td>
          <td style="color:var(--text-muted);font-size:.85rem"><?= e($u['email']) ?></td>
          <td>
            <span class="badge <?= $u['role'] === 'admin' ? 'badge-red' : ($u['role'] === 'editor' ? 'badge-blue' : 'badge-gray') ?>">
              <?= e($u['role']) ?>
            </span>
          </td>
          <td style="color:var(--text-muted);font-size:.8rem">
            <?= $u['last_login'] ? dateFormat($u['last_login'], 'd/m/Y H:i') : 'Nunca' ?>
          </td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="/admin/usuarios.php?action=edit&id=<?= $u['id'] ?>" class="topbar-btn outline" style="padding:4px 8px;font-size:.72rem"><span class="i i-pencil"></span></a>
              <?php if ($u['id'] !== $self['id']): ?>
              <form method="POST" action="/admin/usuarios.php?action=delete&id=<?= $u['id'] ?>" style="display:inline">
                <?= CSRF::field() ?>
                <button type="submit" class="topbar-btn danger" style="padding:4px 8px;font-size:.72rem"
                        data-confirm="Remover o usuário '<?= e($u['username']) ?>'?"><span class="i i-trash"></span></button>
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

<?php elseif (in_array($action, ['new', 'edit'])): ?>

<form method="POST" action="/admin/usuarios.php?action=<?= $action ?>&id=<?= $id ?>">
  <?= CSRF::field() ?>
  <div class="card" style="max-width:600px">
    <div class="card-header"><h3><?= e($pageTitle) ?></h3></div>
    <div class="card-body form-grid cols-2">
      <div class="form-group">
        <label class="form-label">Nome Completo</label>
        <input class="form-input" type="text" name="full_name" value="<?= e($user['full_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Perfil</label>
        <select class="form-select" name="role">
          <?php foreach (['admin' => 'Administrador', 'editor' => 'Editor', 'author' => 'Autor'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= ($user['role'] ?? 'editor') === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Usuário <span class="req">*</span></label>
        <input class="form-input" type="text" name="username" value="<?= e($user['username'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">E-mail <span class="req">*</span></label>
        <input class="form-input" type="email" name="email" value="<?= e($user['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Senha <?= $id ? '(deixe em branco para manter)' : '<span class="req">*</span>' ?></label>
        <input class="form-input" type="password" name="password" minlength="8" autocomplete="new-password">
      </div>
      <div class="form-group">
        <label class="form-label">Confirmar Senha</label>
        <input class="form-input" type="password" name="password_confirm" autocomplete="new-password">
      </div>
    </div>
    <div class="card-footer">
      <a href="/admin/usuarios.php" class="topbar-btn outline">Cancelar</a>
      <button type="submit" class="topbar-btn success">
        <span class="i i-check-circle"></span> <?= $id ? 'Atualizar' : 'Criar' ?> Usuário
      </button>
    </div>
  </div>
</form>

<?php endif;
include __DIR__ . '/includes/footer.php'; ?>
