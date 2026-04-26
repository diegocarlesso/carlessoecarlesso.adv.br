<?php
/**
 * admin/perfil.php — Self-edit do usuário logado.
 *
 * Qualquer usuário logado pode editar:
 *  - nome completo, e-mail
 *  - bio (aparece junto às produções publicadas)
 *  - própria senha (com verificação da senha atual)
 *
 * NÃO pode mudar: username, role, status enabled. Isso é privilégio de
 * users.manage (admin).
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

Auth::requireLogin();

$me      = Auth::user();
$myId    = (int) $me['id'];
$current = Database::fetchOne('SELECT * FROM usuarios WHERE id = ?', [$myId]);

if (!$current) {
    Auth::logout();
    header('Location: /admin/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();

    $email     = trim($_POST['email'] ?? '');
    $fullName  = trim($_POST['full_name'] ?? '');
    $bio       = trim($_POST['bio'] ?? '');
    $currPass  = $_POST['current_password'] ?? '';
    $newPass   = $_POST['new_password'] ?? '';
    $newPass2  = $_POST['new_password_confirm'] ?? '';

    $errors = [];
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'E-mail inválido.';
    }
    // E-mail único
    $dup = Database::fetchOne('SELECT id FROM usuarios WHERE email = ? AND id != ? LIMIT 1', [$email, $myId]);
    if ($dup) $errors[] = 'Este e-mail já está em uso por outro usuário.';

    $changingPassword = $newPass !== '';
    if ($changingPassword) {
        if (!password_verify($currPass, $current['password'])) {
            $errors[] = 'Senha atual incorreta.';
        }
        if (strlen($newPass) < 8) {
            $errors[] = 'Nova senha deve ter ao menos 8 caracteres.';
        }
        if ($newPass !== $newPass2) {
            $errors[] = 'Nova senha e confirmação não conferem.';
        }
    }

    if ($errors) {
        flash('error', implode(' • ', $errors), 'error');
    } else {
        $data = [
            'email'     => $email,
            'full_name' => $fullName,
            'bio'       => $bio,
        ];
        if ($changingPassword) {
            $data['password'] = password_hash($newPass, PASSWORD_BCRYPT);
            $data['must_change_password'] = 0;
        }
        try {
            Database::update('usuarios', $data, 'id = ?', [$myId]);
            $_SESSION['full_name'] = $fullName; // refresh visible name
            if ($changingPassword) {
                $_SESSION['must_change_password'] = 0; // libera o redirect forçado
            }
            AuditLog::record('user.self_updated', 'user', $myId, ['changed_password' => $changingPassword]);
            flash('success', 'Perfil atualizado com sucesso.' . ($changingPassword ? ' Senha alterada.' : ''));
            header('Location: /admin/perfil.php');
            exit;
        } catch (\Throwable $e) {
            flash('error', 'Erro: ' . $e->getMessage(), 'error');
        }
    }
}

$pageTitle  = 'Meu Perfil';
$breadcrumb = ['Perfil' => ''];

include __DIR__ . '/includes/header.php';

$roleLabels = ['admin' => 'Administrador', 'editor' => 'Editor', 'author' => 'Autor'];
?>

<form method="POST" action="/admin/perfil.php" style="max-width:800px;margin:0 auto">
  <?= CSRF::field() ?>
  <?php showFlash('success'); showFlash('error'); ?>

  <div style="display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start">

    <div style="display:flex;flex-direction:column;gap:20px">

      <div class="card">
        <div class="card-header"><h3>Informações pessoais</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
          <div class="form-group">
            <label class="form-label">Username (login)</label>
            <input class="form-input" type="text" value="<?= e($current['username']) ?>" disabled style="background:#f9fafb">
            <div class="form-hint">Username não pode ser alterado por aqui. Peça ao admin se necessário.</div>
          </div>
          <div class="form-group">
            <label class="form-label">Nome completo</label>
            <input class="form-input" type="text" name="full_name" value="<?= e($current['full_name'] ?? '') ?>" autofocus>
          </div>
          <div class="form-group">
            <label class="form-label">E-mail</label>
            <input class="form-input" type="email" name="email" value="<?= e($current['email']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Bio</label>
            <textarea class="form-textarea" name="bio" rows="4" placeholder="Aparece junto às produções publicadas."><?= e($current['bio'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Trocar senha</h3>
          <?php if (!empty($current['must_change_password'])): ?>
            <span class="badge badge-yellow">⚠ obrigatório</span>
          <?php endif; ?>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
          <?php if (!empty($current['must_change_password'])): ?>
            <div class="alert alert-yellow">
              O administrador exigiu que você troque a senha. Defina uma nova abaixo.
            </div>
          <?php endif; ?>
          <div class="form-group">
            <label class="form-label">Senha atual <?= empty($current['must_change_password']) ? '(somente se for trocar)' : '<span class="req">*</span>' ?></label>
            <input class="form-input" type="password" name="current_password" autocomplete="current-password">
          </div>
          <div class="form-group">
            <label class="form-label">Nova senha</label>
            <input class="form-input" type="password" name="new_password" minlength="8" autocomplete="new-password">
            <div class="form-hint">Mínimo 8 caracteres.</div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirmar nova senha</label>
            <input class="form-input" type="password" name="new_password_confirm" autocomplete="new-password">
          </div>
        </div>
      </div>

    </div>

    <!-- Sidebar -->
    <div style="display:flex;flex-direction:column;gap:20px">
      <div class="card">
        <div class="card-header"><h3>Resumo</h3></div>
        <div class="card-body" style="font-size:.85rem">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
            <div style="width:48px;height:48px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700">
              <?= strtoupper(substr($current['full_name'] ?: $current['username'], 0, 1)) ?>
            </div>
            <div>
              <div style="font-weight:600"><?= e($current['full_name'] ?: $current['username']) ?></div>
              <div style="font-size:.78rem;color:var(--text-muted)">@<?= e($current['username']) ?></div>
            </div>
          </div>

          <div style="padding:8px 0;border-top:1px solid #e5e9ef">
            <strong>Perfil:</strong>
            <span class="badge <?= $current['role'] === 'admin' ? 'badge-red' : ($current['role'] === 'editor' ? 'badge-blue' : 'badge-gray') ?>">
              <?= e($roleLabels[$current['role']] ?? $current['role']) ?>
            </span>
          </div>
          <div style="padding:8px 0;border-top:1px solid #e5e9ef">
            <strong>Permissões:</strong> <?= count(Permissions::forRole($current['role'])) ?>
          </div>
          <?php if (!empty($current['last_login'])): ?>
          <div style="padding:8px 0;border-top:1px solid #e5e9ef">
            <strong>Último acesso:</strong><br>
            <span style="color:var(--text-muted)"><?= dateFormat($current['last_login'], 'd/m/Y H:i') ?></span>
          </div>
          <?php endif; ?>
          <div style="padding:8px 0;border-top:1px solid #e5e9ef">
            <strong>Conta criada:</strong><br>
            <span style="color:var(--text-muted)"><?= dateFormat($current['created_at'], 'd/m/Y') ?></span>
          </div>
        </div>
        <div class="card-footer">
          <a href="/admin/index.php" class="topbar-btn outline">Voltar</a>
          <button type="submit" class="topbar-btn success">
            <span class="i i-check-circle"></span> Salvar
          </button>
        </div>
      </div>
    </div>
  </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
