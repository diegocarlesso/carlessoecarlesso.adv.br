<?php
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireRole('editor');

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ── Save ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();
    $titulo   = trim($_POST['titulo'] ?? '');
    $conteudo = sanitizeHtml($_POST['conteudo'] ?? '');
    $status   = in_array($_POST['status'] ?? '', ['publicado','rascunho']) ? $_POST['status'] : 'rascunho';
    $imagem   = trim($_POST['imagem'] ?? '');
    $data_pub = !empty($_POST['data_publicacao']) ? $_POST['data_publicacao'] : date('Y-m-d H:i:s');

    if (!$titulo) {
        flash('error', 'Título é obrigatório.', 'error');
    } else {
        // Upload de capa
        if (!empty($_FILES['capa']['name'])) {
            $up = handleUpload($_FILES['capa']);
            if ($up['success']) $imagem = $up['url'];
        }

        $data = ['titulo' => $titulo, 'conteudo' => $conteudo, 'status' => $status,
                 'imagem' => $imagem, 'data_publicacao' => $data_pub];
        if ($id) {
            Database::update('postagens', $data, 'id = ?', [$id]);
            flash('success', 'Produção atualizada.');
        } else {
            Database::insert('postagens', $data);
            flash('success', 'Produção criada.');
        }
        header('Location: /admin/postagens.php');
        exit;
    }
}

// ── Delete ─────────────────────────────────────────────────
if ($action === 'delete' && $id && Auth::isAdmin()) {
    CSRF::check();
    Database::query('DELETE FROM postagens WHERE id = ?', [$id]);
    flash('success', 'Produção removida.');
    header('Location: /admin/postagens.php');
    exit;
}

$post  = $id ? Database::fetchOne('SELECT * FROM postagens WHERE id = ?', [$id]) : null;
$posts = Database::fetchAll('SELECT * FROM postagens ORDER BY data_publicacao DESC');

$pageTitle  = $action === 'new' ? 'Nova Produção' : ($action === 'edit' ? 'Editar Produção' : 'Produções');
$breadcrumb = ['Produções' => '/admin/postagens.php'];
if ($action !== 'list') $breadcrumb[$pageTitle] = '';

$tinymceLocal = file_exists(PUBLIC_PATH . '/assets/tinymce/tinymce.min.js');

if ($tinymceLocal) {
    $extraScripts = '
<script src="/assets/tinymce/tinymce.min.js"></script>
<script>
tinymce.init({
  selector: "#post-editor",
  language: "pt_BR",
  height: 480,
  menubar: true,
  branding: false,
  promotion: false,
  license_key: "gpl",
  plugins: ["advlist","autolink","lists","link","image","charmap","searchreplace",
            "visualblocks","code","fullscreen","media","table","wordcount"],
  toolbar: "undo redo | blocks fontfamily | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link image | code fullscreen",
  content_css: "/assets/css/style.css",
  images_upload_url: "/api/upload.php",
  automatic_uploads: true,
  setup: ed => ed.on("change", () => ed.save()),
});
</script>';
} else {
    $extraScripts = '<script src="/assets/js/editor.js" defer></script>';
}

include __DIR__ . '/includes/header.php';

if ($action === 'list'): ?>

<div class="card">
  <div class="card-header">
    <h3>Produções <span class="badge badge-blue"><?= count($posts) ?></span></h3>
    <a href="/admin/postagens.php?action=new" class="topbar-btn primary">
      <span class="i i-plus-circle"></span> Nova Produção
    </a>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Título</th><th>Status</th><th>Publicação</th><th>Ações</th></tr></thead>
      <tbody>
      <?php if (empty($posts)): ?>
        <tr><td colspan="4" style="text-align:center;color:#9ca3af;padding:32px">Nenhuma produção criada.</td></tr>
      <?php else: foreach ($posts as $p): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <?php if ($p['imagem']): ?>
                <img src="<?= e($p['imagem']) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:4px;flex-shrink:0">
              <?php endif; ?>
              <a href="/admin/postagens.php?action=edit&id=<?= $p['id'] ?>" style="font-weight:500;color:var(--text)">
                <?= e(truncate($p['titulo'], 50)) ?>
              </a>
            </div>
          </td>
          <td><span class="badge <?= $p['status'] === 'publicado' ? 'badge-green' : 'badge-yellow' ?>"><?= e($p['status']) ?></span></td>
          <td style="color:var(--text-muted);font-size:.8rem"><?= dateFormat($p['data_publicacao']) ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <?php if ($p['status'] === 'publicado'): ?>
              <a href="/?slug=producoes&post=<?= $p['id'] ?>" target="_blank" class="topbar-btn outline" style="padding:4px 8px;font-size:.72rem"><span class="i i-eye"></span></a>
              <?php endif; ?>
              <a href="/admin/postagens.php?action=edit&id=<?= $p['id'] ?>" class="topbar-btn outline" style="padding:4px 8px;font-size:.72rem"><span class="i i-pencil"></span></a>
              <?php if (Auth::isAdmin()): ?>
              <form method="POST" action="/admin/postagens.php?action=delete&id=<?= $p['id'] ?>" style="display:inline">
                <?= CSRF::field() ?>
                <button type="submit" class="topbar-btn danger" style="padding:4px 8px;font-size:.72rem"
                        data-confirm="Remover '<?= e($p['titulo']) ?>'?"><span class="i i-trash"></span></button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif (in_array($action, ['new', 'edit'])): ?>

<form method="POST" action="/admin/postagens.php?action=<?= $action ?>&id=<?= $id ?>" enctype="multipart/form-data">
  <?= CSRF::field() ?>

  <div style="display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start">
    <div style="display:flex;flex-direction:column;gap:20px">
      <div class="card">
        <div class="card-body">
          <div class="form-group" style="margin-bottom:16px">
            <label class="form-label">Título <span class="req">*</span></label>
            <input class="form-input" type="text" name="titulo"
                   value="<?= e($post['titulo'] ?? '') ?>" required autofocus>
          </div>
          <div class="form-group">
            <label class="form-label">Conteúdo</label>
            <textarea id="post-editor" class="rich-editor" name="conteudo" style="min-height:320px;width:100%;border:1px solid #d1d5db;border-radius:6px;padding:14px"><?= e($post['conteudo'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:20px">
      <div class="card">
        <div class="card-header"><h3>Publicação</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option value="publicado" <?= ($post['status'] ?? '') === 'publicado' ? 'selected' : '' ?>>✅ Publicado</option>
              <option value="rascunho"  <?= ($post['status'] ?? 'rascunho') === 'rascunho' ? 'selected' : '' ?>>📝 Rascunho</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Data de Publicação</label>
            <input class="form-input" type="datetime-local" name="data_publicacao"
                   value="<?= e(str_replace(' ','T', $post['data_publicacao'] ?? date('Y-m-d\TH:i'))) ?>">
          </div>
        </div>
        <div class="card-footer">
          <a href="/admin/postagens.php" class="topbar-btn outline">Cancelar</a>
          <button type="submit" class="topbar-btn success">
            <span class="i i-check-circle"></span> Salvar
          </button>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h3>Imagem de Capa</h3></div>
        <div class="card-body">
          <?php if (!empty($post['imagem'])): ?>
            <img src="<?= e($post['imagem']) ?>" alt="Capa atual" style="width:100%;border-radius:6px;margin-bottom:12px">
            <input type="hidden" name="imagem" value="<?= e($post['imagem']) ?>">
          <?php endif; ?>
          <label class="form-label">Upload nova imagem</label>
          <input class="form-input" type="file" name="capa" accept="image/*">
          <div class="form-hint">JPG, PNG, WEBP. Máx. 5MB.</div>
        </div>
      </div>
    </div>
  </div>
</form>

<?php endif;
include __DIR__ . '/includes/footer.php'; ?>
