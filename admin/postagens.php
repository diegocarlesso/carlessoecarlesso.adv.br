<?php
/**
 * admin/postagens.php — CRUD de Produções (rev v1.5)
 *
 * Pivô de foco rev v1.5: editor profissional + workflow Draft/Publish +
 * autoria + media picker + auditoria. Permissões granulares (não mais
 * requireRole hierárquico).
 *
 * Capabilities envolvidas:
 *   posts.edit     → ver lista, criar, editar, salvar como rascunho
 *   posts.publish  → publicar/despublicar
 *   posts.delete   → remover
 *   media.upload   → enviar imagem de capa
 */
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

use Carlesso\Services\Audit\AuditLog;

Auth::requireCan('posts.edit');

$action  = $_GET['action'] ?? 'list';
$id      = (int) ($_GET['id'] ?? 0);
$me      = Auth::user();
$canPub  = Auth::can('posts.publish');
$canDel  = Auth::can('posts.delete');
$canMed  = Auth::can('media.upload');

// ═══════════════════════════════════════════════════════════════════════════
// POST handlers
// ═══════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'delete') {
    CSRF::check();

    $titulo   = trim($_POST['titulo'] ?? '');
    $slug     = trim($_POST['slug'] ?? '');
    $resumo   = trim($_POST['resumo'] ?? '');
    $conteudo = sanitizeHtml($_POST['conteudo'] ?? '');
    $imagem   = trim($_POST['imagem'] ?? '');
    $dataPub  = $_POST['data_publicacao'] ?? '';
    $intent   = $_POST['intent'] ?? 'save'; // 'save' | 'publish' | 'unpublish'

    // Status derivado da intent + permissão
    $existing = $id ? Database::fetchOne('SELECT * FROM postagens WHERE id = ?', [$id]) : null;
    $status = $existing['status'] ?? 'rascunho';
    if ($intent === 'publish' && $canPub) {
        $status = 'publicado';
    } elseif ($intent === 'unpublish' && $canPub) {
        $status = 'rascunho';
    }
    // 'save' não muda status — preserva o atual ou 'rascunho' para novo

    if ($titulo === '') {
        flash('error', 'Título é obrigatório.', 'error');
    } else {
        // Slug auto se vazio
        if ($slug === '') $slug = generateSlug($titulo);
        $slug = generateSlug($slug);

        // Garante slug único
        $slugCheck = Database::fetchOne(
            'SELECT id FROM postagens WHERE slug = ? AND id != ? LIMIT 1',
            [$slug, $id ?: 0]
        );
        if ($slugCheck) {
            $slug = $slug . '-' . substr((string) time(), -4);
        }

        // Upload de capa
        if (!empty($_FILES['capa']['name']) && $canMed) {
            $up = handleUpload($_FILES['capa']);
            if ($up['success']) {
                $imagem = $up['url'];
            } else {
                flash('error', 'Falha no upload: ' . ($up['message'] ?? '?'), 'error');
            }
        }

        $dataPubFmt = $dataPub ? str_replace('T', ' ', $dataPub) . ':00' : date('Y-m-d H:i:s');
        $publicadoEm = $existing['publicado_em'] ?? null;
        if ($status === 'publicado' && !$publicadoEm) {
            $publicadoEm = date('Y-m-d H:i:s');
        }

        $data = [
            'titulo'          => $titulo,
            'slug'            => $slug,
            'resumo'          => mb_substr($resumo, 0, 500),
            'conteudo'        => $conteudo,
            'imagem'          => $imagem,
            'status'          => $status,
            'data_publicacao' => $dataPubFmt,
            'editor_id'       => (int) $me['id'],
            'publicado_em'    => $publicadoEm,
        ];

        try {
            if ($id) {
                Database::update('postagens', $data, 'id = ?', [$id]);
                AuditLog::record(
                    $intent === 'publish' ? 'post.published' : ($intent === 'unpublish' ? 'post.unpublished' : 'post.updated'),
                    'post', $id, ['titulo' => $titulo]
                );
                flash('success', $intent === 'publish' ? 'Produção publicada.' : ($intent === 'unpublish' ? 'Despublicado (rascunho).' : 'Produção atualizada.'));
            } else {
                $data['author_id'] = (int) $me['id'];
                $id = Database::insert('postagens', $data);
                AuditLog::record('post.created', 'post', $id, ['titulo' => $titulo, 'status' => $status]);
                flash('success', $status === 'publicado' ? 'Produção criada e publicada.' : 'Rascunho salvo.');
            }
            header('Location: /admin/postagens.php?action=edit&id=' . $id);
            exit;
        } catch (\Throwable $e) {
            flash('error', 'Erro ao salvar: ' . $e->getMessage(), 'error');
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// DELETE
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'delete' && $id) {
    Auth::requireCan('posts.delete');
    CSRF::check();
    $row = Database::fetchOne('SELECT titulo FROM postagens WHERE id = ?', [$id]);
    Database::query('DELETE FROM postagens WHERE id = ?', [$id]);
    AuditLog::record('post.deleted', 'post', $id, ['titulo' => $row['titulo'] ?? '?']);
    flash('success', 'Produção removida.');
    header('Location: /admin/postagens.php');
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// Data fetching
// ═══════════════════════════════════════════════════════════════════════════
$post  = $id ? Database::fetchOne('SELECT * FROM postagens WHERE id = ?', [$id]) : null;
$posts = Database::fetchAll(
    'SELECT p.*, u.full_name AS author_name, u.username AS author_username
     FROM postagens p
     LEFT JOIN usuarios u ON u.id = p.author_id
     ORDER BY p.atualizado_em DESC, p.data_publicacao DESC'
);

// Filtro por status (lista)
$filterStatus = $_GET['status'] ?? '';
if ($filterStatus === 'publicado' || $filterStatus === 'rascunho') {
    $posts = array_filter($posts, fn($p) => $p['status'] === $filterStatus);
}

$pageTitle  = $action === 'new' ? 'Nova Produção' : ($action === 'edit' ? 'Editar Produção' : 'Produções');
$breadcrumb = ['Produções' => '/admin/postagens.php'];
if ($action !== 'list') $breadcrumb[$pageTitle] = '';

$tinymceLocal = file_exists(PUBLIC_PATH . '/assets/tinymce/tinymce.min.js');

// ═══════════════════════════════════════════════════════════════════════════
// Editor scripts: TinyMCE 7 (se instalado) com fallback automático para
// editor.js (lite Word-class). Ambos os textareas (.rich-editor) recebem
// editor — o lite cede o elemento se o TinyMCE já tomou conta.
// ═══════════════════════════════════════════════════════════════════════════
$liteEditorVer = @filemtime(PUBLIC_PATH . '/assets/js/editor.js') ?: '1';

if ($tinymceLocal) {
    $extraScripts = <<<HTML
<script src="/assets/tinymce/tinymce.min.js"></script>
<script>
(function() {
  const fonts = "Open Sans=Open Sans,Helvetica,Arial,sans-serif;"
              + "Hepta Slab=Hepta Slab,Georgia,serif;"
              + "Calibri=Calibri,Trebuchet MS,sans-serif;"
              + "Cambria=Cambria,Georgia,serif;"
              + "Georgia=Georgia,serif;"
              + "Times New Roman=Times New Roman,Times,serif;"
              + "Arial=Arial,Helvetica,sans-serif;"
              + "Verdana=Verdana,Geneva,sans-serif;"
              + "Tahoma=Tahoma,Geneva,sans-serif;"
              + "Courier New=Courier New,Courier,monospace;";

  // Config compartilhada
  const baseConfig = {
    language: "pt_BR",
    branding: false,
    promotion: false,
    license_key: "gpl",
    convert_urls: false,
    relative_urls: false,
    remove_script_host: false,
    font_family_formats: fonts,
    font_size_formats: "10px 11px 12px 14px 16px 18px 20px 24px 28px 32px 36px 48px 60px 72px",
    content_css: ["/assets/css/style.css", "/assets/css/style-extras.css"],
    content_style: "body{font-family:'Open Sans',Helvetica,sans-serif;font-size:16px;line-height:1.6;padding:20px;max-width:760px;margin:0 auto}h1,h2,h3,h4{font-family:'Hepta Slab',Georgia,serif;color:#1a3554}a{color:#c8832a}blockquote{border-left:4px solid #c8832a;padding-left:16px;color:#4b5563;font-style:italic;background:#faf7f2}",
    paste_data_images: true,
    smart_paste: true,
  };

  // Editor PRINCIPAL (Conteúdo) — toolbar Word completa
  tinymce.init(Object.assign({}, baseConfig, {
    selector: "#post-editor",
    height: 720,
    min_height: 480,
    menubar: "file edit view insert format tools table help",
    plugins: ["advlist","anchor","autolink","autoresize","autosave","charmap","code","codesample","directionality","emoticons","fullscreen","help","image","importcss","insertdatetime","link","lists","media","nonbreaking","pagebreak","preview","quickbars","save","searchreplace","table","visualblocks","visualchars","wordcount","accordion"],
    toolbar: [
      "undo redo | restoredraft | styles fontfamily fontsize | searchreplace",
      "bold italic underline strikethrough | forecolor backcolor removeformat | superscript subscript",
      "alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | blockquote codesample",
      "link unlink anchor | image media table | hr pagebreak charmap emoticons",
      "ltr rtl | visualblocks visualchars | code preview fullscreen | help"
    ].join(" | "),
    quickbars_selection_toolbar: "bold italic underline | quicklink h2 h3 blockquote",
    quickbars_insert_toolbar: "image media table",
    table_toolbar: "tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol",
    table_default_styles: { width: "100%" },
    image_advtab: true,
    image_caption: true,
    image_dimensions: true,
    images_upload_url: "/api/upload.php",
    automatic_uploads: true,
    autosave_ask_before_unload: true,
    autosave_interval: "30s",
    autosave_prefix: "carlesso-post-{path}{query}-",
    autosave_retention: "30m",
    setup: function(ed) {
      ed.on("change keyup", () => ed.save());
      ed.addShortcut("meta+shift+s", "Salvar", () => document.querySelector("[data-intent=save]")?.click());
      ed.addShortcut("meta+shift+p", "Publicar", () => document.querySelector("[data-intent=publish]")?.click());
    },
  }));

  // Editor RESUMO — toolbar reduzida, altura menor
  tinymce.init(Object.assign({}, baseConfig, {
    selector: "#post-resumo",
    height: 180,
    min_height: 140,
    menubar: false,
    statusbar: false,
    plugins: ["link","lists","autolink","wordcount","searchreplace","charmap"],
    toolbar: "bold italic underline strikethrough | forecolor backcolor | bullist numlist | link unlink | removeformat",
    setup: function(ed) {
      ed.on("change keyup", () => ed.save());
    },
  }));
})();
</script>
<script src="/assets/js/editor.js?v={$liteEditorVer}" defer></script>
HTML;
} else {
    $extraScripts = <<<HTML
<script src="/assets/js/editor.js?v={$liteEditorVer}" defer></script>
HTML;
}

include __DIR__ . '/includes/header.php';

// ═══════════════════════════════════════════════════════════════════════════
// LIST view
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'list'): ?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:10px">
    <h3>Produções <span class="badge badge-blue"><?= count($posts) ?></span></h3>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <div class="filter-pills">
        <a href="/admin/postagens.php" class="pill <?= $filterStatus === '' ? 'active' : '' ?>">Todas</a>
        <a href="/admin/postagens.php?status=publicado" class="pill <?= $filterStatus === 'publicado' ? 'active' : '' ?>">Publicadas</a>
        <a href="/admin/postagens.php?status=rascunho" class="pill <?= $filterStatus === 'rascunho' ? 'active' : '' ?>">Rascunhos</a>
      </div>
      <a href="/admin/postagens.php?action=new" class="topbar-btn primary">
        <span class="i i-plus-circle"></span> Nova Produção
      </a>
    </div>
  </div>
  <?php showFlash('success'); showFlash('error'); ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:60px"></th>
          <th>Título</th>
          <th>Autor</th>
          <th>Status</th>
          <th>Atualizado</th>
          <th>Publicação</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($posts)): ?>
        <tr><td colspan="7" style="text-align:center;color:#9ca3af;padding:40px">
          Nenhuma produção <?= $filterStatus ? ' com este filtro' : '' ?>.
          <a href="/admin/postagens.php?action=new" style="color:var(--primary);text-decoration:underline">Criar a primeira</a>.
        </td></tr>
      <?php else: foreach ($posts as $p): ?>
        <tr>
          <td>
            <?php if ($p['imagem']): ?>
              <img src="<?= e($p['imagem']) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:6px;display:block">
            <?php else: ?>
              <div style="width:48px;height:48px;background:linear-gradient(135deg,#e5e9ef,#f5f7fa);border-radius:6px;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:1.4rem">📝</div>
            <?php endif; ?>
          </td>
          <td>
            <a href="/admin/postagens.php?action=edit&id=<?= $p['id'] ?>" style="font-weight:600;color:var(--text);text-decoration:none">
              <?= e(truncate($p['titulo'], 60)) ?>
            </a>
            <?php if ($p['slug']): ?>
              <div style="font-size:.72rem;color:#9ca3af;margin-top:2px"><code>/producoes/<?= e($p['slug']) ?></code></div>
            <?php endif; ?>
          </td>
          <td style="font-size:.85rem;color:var(--text-muted)">
            <?= e($p['author_name'] ?? $p['author_username'] ?? '—') ?>
          </td>
          <td>
            <span class="badge <?= $p['status'] === 'publicado' ? 'badge-green' : 'badge-yellow' ?>">
              <?= $p['status'] === 'publicado' ? '✓ publicado' : '✎ rascunho' ?>
            </span>
          </td>
          <td style="color:var(--text-muted);font-size:.78rem">
            <?= !empty($p['atualizado_em']) ? dateFormat($p['atualizado_em'], 'd/m/Y H:i') : dateFormat($p['data_publicacao'], 'd/m/Y') ?>
          </td>
          <td style="color:var(--text-muted);font-size:.78rem">
            <?= dateFormat($p['data_publicacao'], 'd/m/Y') ?>
          </td>
          <td>
            <div style="display:flex;gap:6px">
              <?php if ($p['status'] === 'publicado'): ?>
                <a href="/producoes/<?= e(!empty($p['slug']) ? $p['slug'] : $p['id']) ?>" target="_blank" class="topbar-btn outline" style="padding:4px 8px;font-size:.72rem" title="Visualizar no site">
                  <span class="i i-eye"></span>
                </a>
              <?php endif; ?>
              <a href="/admin/postagens.php?action=edit&id=<?= $p['id'] ?>" class="topbar-btn outline" style="padding:4px 8px;font-size:.72rem" title="Editar">
                <span class="i i-pencil"></span>
              </a>
              <?php if ($canDel): ?>
              <form method="POST" action="/admin/postagens.php?action=delete&id=<?= $p['id'] ?>" style="display:inline">
                <?= CSRF::field() ?>
                <button type="submit" class="topbar-btn danger" style="padding:4px 8px;font-size:.72rem"
                        data-confirm="Remover &lsquo;<?= e($p['titulo']) ?>&rsquo; permanentemente?" title="Remover">
                  <span class="i i-trash"></span>
                </button>
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

<style>
.filter-pills { display:flex; gap:4px; }
.filter-pills .pill { padding:6px 12px; border-radius:14px; font-size:.78rem; color:var(--text-muted); text-decoration:none; background:#f3f4f6; }
.filter-pills .pill.active { background:var(--primary); color:#fff; }
.filter-pills .pill:hover { background:#e5e7eb; }
.filter-pills .pill.active:hover { background:var(--navy); }
</style>

<?php // ═════════════════════════════════════════════════════════════════════
elseif (in_array($action, ['new', 'edit'])): ?>

<form method="POST" action="/admin/postagens.php?action=<?= $action ?>&id=<?= $id ?>" enctype="multipart/form-data" id="post-form">
  <?= CSRF::field() ?>
  <?php showFlash('success'); showFlash('error'); ?>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">

    <!-- Coluna principal -->
    <div style="display:flex;flex-direction:column;gap:20px">

      <!-- Título + Slug -->
      <div class="card">
        <div class="card-body">
          <div class="form-group" style="margin-bottom:16px">
            <label class="form-label">Título <span class="req">*</span></label>
            <input class="form-input" type="text" name="titulo" id="titulo"
                   value="<?= e($post['titulo'] ?? '') ?>" required autofocus
                   style="font-size:1.4rem;font-weight:600;padding:16px"
                   placeholder="Título da produção">
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Slug (URL)</label>
            <div style="display:flex;align-items:center;gap:8px">
              <span style="color:var(--text-muted);font-size:.85rem;font-family:monospace">/producoes/</span>
              <input class="form-input" type="text" name="slug" id="slug"
                     value="<?= e($post['slug'] ?? '') ?>" placeholder="auto-gerado a partir do título"
                     style="font-family:monospace;font-size:.9rem">
            </div>
          </div>
        </div>
      </div>

      <!-- Resumo -->
      <div class="card">
        <div class="card-header"><h3>Resumo / Chamada</h3></div>
        <div class="card-body">
          <textarea id="post-resumo" class="rich-editor" name="resumo" rows="3" maxlength="500"
                    data-compact="1"
                    placeholder="Resumo curto que aparece nas listagens e em compartilhamentos sociais (até 500 caracteres)."><?= e($post['resumo'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- Editor principal -->
      <div class="card">
        <div class="card-header">
          <h3>Conteúdo</h3>
          <span style="font-size:.78rem;color:var(--text-muted)">
            Atalhos: <kbd>Ctrl+S</kbd> salvar · <kbd>Ctrl+Shift+P</kbd> publicar · <kbd>F11</kbd> tela cheia
          </span>
        </div>
        <div class="card-body" style="padding:0">
          <textarea id="post-editor" class="rich-editor" name="conteudo"
                    data-upload-url="/api/upload.php"
                    style="min-height:480px;width:100%;border:none;padding:16px"><?= e($post['conteudo'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div style="display:flex;flex-direction:column;gap:20px">

      <!-- Publicação / ações -->
      <div class="card">
        <div class="card-header"><h3>Publicação</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:14px">

          <div>
            <span class="form-label">Status atual:</span>
            <span class="badge <?= ($post['status'] ?? 'rascunho') === 'publicado' ? 'badge-green' : 'badge-yellow' ?>" style="margin-left:6px">
              <?= ($post['status'] ?? 'rascunho') === 'publicado' ? '✓ Publicado' : '✎ Rascunho' ?>
            </span>
          </div>

          <?php if (!empty($post['publicado_em'])): ?>
          <div style="font-size:.78rem;color:var(--text-muted)">
            Publicado em <?= dateFormat($post['publicado_em'], 'd/m/Y H:i') ?>
          </div>
          <?php endif; ?>

          <?php if (!empty($post['atualizado_em'])): ?>
          <div style="font-size:.78rem;color:var(--text-muted)">
            Última edição <?= dateFormat($post['atualizado_em'], 'd/m/Y H:i') ?>
          </div>
          <?php endif; ?>

          <div class="form-group" style="margin:0">
            <label class="form-label">Data de publicação</label>
            <input class="form-input" type="datetime-local" name="data_publicacao"
                   value="<?= e($post['data_publicacao'] ? str_replace(' ', 'T', substr($post['data_publicacao'], 0, 16)) : date('Y-m-d\TH:i')) ?>">
            <div class="form-hint">Pode ser futura — será exibido no site a partir desta data.</div>
          </div>

          <div style="display:flex;flex-direction:column;gap:8px;margin-top:6px">
            <button type="submit" name="intent" value="save" data-intent="save" class="topbar-btn outline" style="justify-content:center">
              <span class="i i-check-circle"></span> Salvar rascunho
            </button>
            <?php if ($canPub): ?>
              <?php if (($post['status'] ?? '') === 'publicado'): ?>
                <button type="submit" name="intent" value="save" data-intent="save" class="topbar-btn success" style="justify-content:center">
                  <span class="i i-check-circle"></span> Atualizar publicação
                </button>
                <button type="submit" name="intent" value="unpublish" class="topbar-btn outline" style="justify-content:center;color:#a16207"
                        data-confirm="Despublicar esta produção?">
                  <span class="i i-arrow-counterclockwise"></span> Despublicar
                </button>
              <?php else: ?>
                <button type="submit" name="intent" value="publish" data-intent="publish" class="topbar-btn success" style="justify-content:center">
                  <span class="i i-rocket"></span> Publicar agora
                </button>
              <?php endif; ?>
            <?php else: ?>
              <div class="form-hint" style="text-align:center;padding:8px;background:#f9fafb;border-radius:6px;margin:0">
                ℹ️ Sem permissão para publicar. Salvar rascunho e pedir aprovação.
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Imagem de capa -->
      <div class="card">
        <div class="card-header"><h3>Imagem de Capa</h3></div>
        <div class="card-body">
          <div id="capa-preview-wrap" style="margin-bottom:12px; <?= empty($post['imagem']) ? 'display:none' : '' ?>">
            <img id="capa-preview" src="<?= e($post['imagem'] ?? '') ?>" alt="Capa selecionada"
                 style="width:100%;border-radius:6px;display:block">
            <button type="button" id="capa-remove" class="topbar-btn outline" style="margin-top:8px;width:100%;justify-content:center;color:#dc2626;font-size:.78rem">
              ✕ Remover capa
            </button>
          </div>
          <input type="hidden" name="imagem" value="<?= e($post['imagem'] ?? '') ?>" id="imagem-current">

          <?php if ($canMed): ?>
            <label class="form-label">Enviar nova imagem</label>
            <input class="form-input" type="file" name="capa" accept="image/jpeg,image/png,image/webp">
            <div class="form-hint">JPG, PNG ou WEBP. Máx. 5MB. Proporção sugerida 16:9.</div>
            <button type="button" class="topbar-btn outline" style="margin-top:10px;width:100%;justify-content:center"
                    onclick="openMediaPicker()">
              📚 Escolher da biblioteca
            </button>
          <?php else: ?>
            <div class="form-hint">Sem permissão para upload de mídia.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Autoria -->
      <div class="card">
        <div class="card-header"><h3>Autoria</h3></div>
        <div class="card-body" style="font-size:.85rem;color:var(--text-muted)">
          <?php if ($id && !empty($post['author_id'])):
              $author = Database::fetchOne('SELECT username, full_name FROM usuarios WHERE id = ?', [$post['author_id']]);
          ?>
            <div><strong>Autor:</strong> <?= e($author['full_name'] ?? $author['username'] ?? '—') ?></div>
          <?php else: ?>
            <div><strong>Autor:</strong> <?= e($me['full_name'] ?: $me['username']) ?> (você)</div>
          <?php endif; ?>
          <?php if ($id && !empty($post['editor_id']) && $post['editor_id'] != ($post['author_id'] ?? 0)):
              $ed = Database::fetchOne('SELECT username, full_name FROM usuarios WHERE id = ?', [$post['editor_id']]);
          ?>
            <div style="margin-top:6px"><strong>Última edição:</strong> <?= e($ed['full_name'] ?? $ed['username'] ?? '—') ?></div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</form>

<!-- Media Picker Modal (placeholder simples; Phase 4 vai aprimorar) -->
<div id="media-picker-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;max-width:800px;width:90%;max-height:80vh;overflow:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <h3 style="margin:0">Biblioteca de Mídia</h3>
      <button type="button" onclick="closeMediaPicker()" style="background:none;border:none;font-size:1.4rem;cursor:pointer">×</button>
    </div>
    <div id="media-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px">
      <div style="grid-column:1/-1;text-align:center;padding:30px;color:#9ca3af">Carregando…</div>
    </div>
  </div>
</div>

<script>
// ─── Slug auto-fill ──────────────────────────────────────────────
(function() {
  const titulo = document.getElementById('titulo');
  const slug   = document.getElementById('slug');
  if (!titulo || !slug) return;

  let manuallyEdited = slug.value.length > 0;
  slug.addEventListener('input', () => { manuallyEdited = true; });

  titulo.addEventListener('input', () => {
    if (manuallyEdited) return;
    slug.value = titulo.value.toLowerCase()
      .normalize('NFD').replace(/[̀-ͯ]/g, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  });
})();

// ─── (Resumo counter removido — o editor rico já mostra o contador embutido) ───

// ─── Confirm dialogs nos botões com data-confirm ─────────────────
document.querySelectorAll('[data-confirm]').forEach(btn => {
  btn.addEventListener('click', e => {
    if (!confirm(btn.dataset.confirm)) e.preventDefault();
  });
});

// ─── Media picker ────────────────────────────────────────────────
// IMPORTANTE: NÃO recarrega a página — só atualiza o preview e o hidden input.
// Isso preserva conteúdo não salvo do editor.
let mediaLoaded = false;

function setCoverImage(url) {
  document.getElementById('imagem-current').value = url || '';
  const wrap = document.getElementById('capa-preview-wrap');
  const img  = document.getElementById('capa-preview');
  if (url) {
    img.src = url;
    wrap.style.display = 'block';
  } else {
    img.src = '';
    wrap.style.display = 'none';
  }
}

document.getElementById('capa-remove')?.addEventListener('click', () => {
  if (confirm('Remover a imagem de capa?')) setCoverImage('');
});

function openMediaPicker() {
  const modal = document.getElementById('media-picker-modal');
  modal.style.display = 'flex';
  if (mediaLoaded) return;
  fetch('/api/media.php?action=list', { credentials: 'same-origin' })
    .then(r => r.json())
    .then(data => {
      const grid = document.getElementById('media-grid');
      if (!data.success || !data.media || !data.media.length) {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:30px;color:#9ca3af">Nenhuma imagem na biblioteca. Faça upload em Mídia primeiro.</div>';
        return;
      }
      grid.innerHTML = data.media.map(m =>
        `<button type="button" class="media-item" data-url="${m.file_path}"
            title="${(m.original_name || m.filename || '').replace(/"/g, '&quot;')}"
            style="border:2px solid #e5e9ef;border-radius:6px;padding:0;cursor:pointer;background:#f9fafb;aspect-ratio:1;overflow:hidden">
          <img src="${m.file_path}" alt="" style="width:100%;height:100%;object-fit:cover;display:block">
        </button>`
      ).join('');
      grid.querySelectorAll('.media-item').forEach(b => {
        b.addEventListener('click', () => {
          setCoverImage(b.dataset.url);
          closeMediaPicker();
        });
      });
      mediaLoaded = true;
    })
    .catch(err => {
      document.getElementById('media-grid').innerHTML =
        '<div style="grid-column:1/-1;color:#dc2626">Erro ao carregar: ' + err.message + '</div>';
    });
}
function closeMediaPicker() {
  document.getElementById('media-picker-modal').style.display = 'none';
}
document.getElementById('media-picker-modal')?.addEventListener('click', e => {
  if (e.target.id === 'media-picker-modal') closeMediaPicker();
});

// ─── Ctrl+S salva (sem mostrar dialog do browser) ────────────────
document.addEventListener('keydown', e => {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault();
    document.querySelector('[data-intent=save]')?.click();
  }
});
</script>

<?php endif;
include __DIR__ . '/includes/footer.php'; ?>
