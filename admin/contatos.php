<?php
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireRole('editor');

// Marcar como lido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    CSRF::check();
    Database::query('UPDATE contatos SET lido=1 WHERE id=?', [(int)$_POST['mark_read']]);
    header('Location: /admin/contatos.php');
    exit;
}

$contacts = Database::fetchAll('SELECT * FROM contatos ORDER BY created_at DESC');
$unread   = array_filter($contacts, fn($c) => !$c['lido']);

$pageTitle  = 'Mensagens de Contato';
$breadcrumb = ['Mensagens' => ''];

include __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h3>Mensagens Recebidas
      <?php if ($unread): ?>
      <span class="badge badge-red" style="margin-left:6px"><?= count($unread) ?> não lidas</span>
      <?php endif; ?>
    </h3>
  </div>

  <?php if (empty($contacts)): ?>
  <div class="card-body" style="text-align:center;padding:48px;color:#9ca3af">
    <div style="font-size:2.5rem;margin-bottom:12px">📭</div>
    <p>Nenhuma mensagem recebida ainda.</p>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th></th><th>Nome</th><th>E-mail</th><th>Assunto</th><th>Mensagem</th><th>Data</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($contacts as $c): ?>
      <tr style="<?= !$c['lido'] ? 'background:#fffbeb' : '' ?>">
        <td><?= !$c['lido'] ? '<span class="badge badge-yellow">Nova</span>' : '<span style="color:#9ca3af;font-size:.75rem">Lida</span>' ?></td>
        <td><strong><?= e($c['nome']) ?></strong><?= $c['telefone'] ? '<br><span style="font-size:.75rem;color:#9ca3af">'.$c['telefone'].'</span>' : '' ?></td>
        <td><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></td>
        <td style="font-size:.83rem"><?= e($c['assunto'] ?: '—') ?></td>
        <td style="max-width:280px;font-size:.83rem;color:#6b7280"><?= e(truncate($c['mensagem'], 80)) ?></td>
        <td style="color:#9ca3af;font-size:.78rem;white-space:nowrap"><?= dateFormat($c['created_at'], 'd/m/Y H:i') ?></td>
        <td>
          <?php if (!$c['lido']): ?>
          <form method="POST" style="display:inline">
            <?= CSRF::field() ?>
            <input type="hidden" name="mark_read" value="<?= $c['id'] ?>">
            <button type="submit" class="topbar-btn outline" style="padding:4px 8px;font-size:.72rem">✓ Lida</button>
          </form>
          <?php endif; ?>
          <button onclick="showMsg(this)"
                  data-msg="<?= e($c['mensagem']) ?>"
                  data-nome="<?= e($c['nome']) ?>"
                  class="topbar-btn outline" style="padding:4px 8px;font-size:.72rem">👁</button>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Modal de mensagem completa -->
<div class="modal-overlay" id="msg-modal">
  <div class="modal" style="max-width:520px">
    <div class="modal-header">
      <h4 id="msg-modal-title">Mensagem</h4>
      <button class="modal-close" onclick="document.getElementById('msg-modal').classList.remove('open')">✕</button>
    </div>
    <div class="modal-body">
      <p id="msg-modal-body" style="white-space:pre-wrap;font-size:.9rem;line-height:1.8;color:#374151"></p>
    </div>
  </div>
</div>

<script>
function showMsg(btn) {
  document.getElementById('msg-modal-title').textContent = 'Mensagem de ' + btn.dataset.nome;
  document.getElementById('msg-modal-body').textContent  = btn.dataset.msg;
  document.getElementById('msg-modal').classList.add('open');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
