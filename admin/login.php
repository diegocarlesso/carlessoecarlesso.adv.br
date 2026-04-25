<?php
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::start();

// Já logado?
if (Auth::check()) {
    header('Location: /admin/index.php');
    exit;
}

$error    = '';
$redirect = $_GET['redirect'] ?? '/admin/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Preencha usuário e senha.';
    } else {
        $result = Auth::login($username, $password);
        if ($result['success']) {
            header('Location: ' . $redirect);
            exit;
        }
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Entrar — Carlesso CMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/icons/icons.css?v=1">
  <link rel="stylesheet" href="/assets/css/admin.css">
  <?= CSRF::meta() ?>
</head>
<body>
<div class="login-page">
  <div class="login-card">

    <div class="login-logo">
      <div style="width:56px;height:56px;background:linear-gradient(135deg,#527095,#1a3554);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto">⚖️</div>
      <div class="brand" style="margin-top:12px">Carlesso & Carlesso</div>
      <div class="sub">Painel Administrativo</div>
    </div>

    <h1>Bem-vindo</h1>
    <p class="tagline">Acesse o painel para gerenciar o conteúdo do site.</p>

    <?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:20px">
      <span class="i i-warning"></span> <?= e($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
      <?= CSRF::field() ?>
      <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

      <div class="form-group" style="margin-bottom:16px">
        <label class="form-label" for="username">Usuário ou E-mail</label>
        <input class="form-input" type="text" id="username" name="username"
               value="<?= e($_POST['username'] ?? '') ?>"
               required autofocus autocomplete="username"
               placeholder="admin">
      </div>

      <div class="form-group" style="margin-bottom:24px">
        <label class="form-label" for="password">Senha</label>
        <div style="position:relative">
          <input class="form-input" type="password" id="password" name="password"
                 required autocomplete="current-password"
                 placeholder="••••••••" style="padding-right:42px">
          <button type="button" id="toggle-pass"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;font-size:.95rem"
                  onclick="const i=document.getElementById('password');i.type=i.type==='password'?'text':'password'">
            👁
          </button>
        </div>
      </div>

      <button type="submit" class="topbar-btn primary w-full" style="padding:12px;font-size:.9rem;justify-content:center">
        Entrar
      </button>
    </form>

    <div class="login-footer">
      <a href="/">← Voltar ao site</a>
    </div>
  </div>
</div>
</body>
</html>
