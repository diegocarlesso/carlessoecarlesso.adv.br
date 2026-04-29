<?php
// admin/paginas.php — v1.2 (edição de conteúdos inline + editor lite)
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireCan('pages.edit');

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ── Processar formulário ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();

    $titulo      = trim($_POST['titulo'] ?? '');
    $slug        = trim($_POST['slug'] ?? '') ?: generateSlug($titulo);
    $slug        = generateSlug($slug);
    $conteudo    = sanitizeHtml($_POST['conteudo'] ?? '');
    $blocos      = $_POST['blocos'] ?? '';
    $status      = in_array($_POST['status'] ?? '', ['publicado','rascunho']) ? $_POST['status'] : 'rascunho';
    $meta_title  = trim($_POST['meta_title'] ?? '');
    $meta_desc   = trim($_POST['meta_description'] ?? '');
    $show_menu   = isset($_POST['show_in_menu']) ? 1 : 0;
    $menu_order  = (int)($_POST['menu_order'] ?? 0);

    if (!$titulo) {
        flash('error', 'Título é obrigatório.', 'error');
    } else {
        $data = [
            'titulo'           => $titulo,
            'slug'             => $slug,
            'conteudo'         => $conteudo,
            'blocos'           => $blocos,
            'status'           => $status,
            'meta_title'       => $meta_title,
            'meta_description' => $meta_desc,
            'show_in_menu'     => $show_menu,
            'menu_order'       => $menu_order,
        ];
        if ($id) {
            Database::update('paginas', $data, 'id = ?', [$id]);
        } else {
            $id = Database::insert('paginas', $data);
        }

        // ── Salva seções de conteúdo (conteudos table) ──
        if (!empty($_POST['secoes']) && is_array($_POST['secoes'])) {
            foreach ($_POST['secoes'] as $secaoId => $sec) {
                $secaoId = (int)$secaoId;
                if (!$secaoId) continue;
                Database::update('conteudos', [
                    'titulo'   => trim($sec['titulo'] ?? ''),
                    'conteudo' => sanitizeHtml($sec['conteudo'] ?? ''),
                ], 'id = ?', [$secaoId]);
            }
        }

        flash('success', 'Página e conteúdos atualizados com sucesso.');
        header('Location: /admin/paginas.php?action=edit&id=' . $id);
        exit;
    }
}

// ── DELETE ────────────────────────────────────────────────
if ($action === 'delete' && $id && Auth::can('pages.delete')) {
    CSRF::check();
    Database::query('DELETE FROM paginas WHERE id = ?', [$id]);
    flash('success', 'Página removida.');
    header('Location: /admin/paginas.php');
    exit;
}

// ── Dados ─────────────────────────────────────────────────
$page  = $id ? Database::fetchOne('SELECT * FROM paginas WHERE id = ?', [$id]) : null;
$pages = Database::fetchAll('SELECT * FROM paginas ORDER BY menu_order ASC, titulo ASC');

// Carrega seções de conteúdo desta página (tabela conteudos)
$secoes = [];
if ($page) {
    $secoes = Database::fetchAll(
        'SELECT * FROM conteudos WHERE pagina_slug = ? ORDER BY ordem ASC, secao_chave ASC',
        [$page['slug']]
    );
}

$pageTitle  = $action === 'new' ? 'Nova Página' : ($action === 'edit' ? 'Editar Página' : 'Páginas');
$breadcrumb = ['Páginas' => '/admin/paginas.php'];
if ($action !== 'list') $breadcrumb[$pageTitle] = '';

$tinymceLocal = file_exists(PUBLIC_PATH . '/assets/tinymce/tinymce.min.js');

// Editor: TinyMCE local se disponível, senão LiteEditor (sempre funciona)
if ($tinymceLocal) {
    $extraScripts = '
<script src="/assets/tinymce/tinymce.min.js"></script>
<script>
tinymce.init({
  selector: "#tinymce-editor",
  language: "pt_BR",
  height: 420,
  menubar: true,
  branding: false,
  promotion: false,
  license_key: "gpl",
  plugins: ["advlist","autolink","lists","link","image","charmap","anchor","searchreplace",
            "visualblocks","code","fullscreen","media","table","wordcount"],
  toolbar: "undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | code fullscreen",
  content_css: "/assets/css/style.css",
  setup: function(ed) { ed.on("change", function() { ed.save(); }); },
  images_upload_url: "/api/upload.php",
  automatic_uploads: true,
});
</script>
<script src="/assets/js/blocks.js" defer></script>
';
} else {
    $extraScripts = '
<script src="/assets/js/editor.js" defer></script>
<script src="/assets/js/blocks.js" defer></script>
';
}

include __DIR__ . '/includes/header.php';

if ($action === 'list'): ?>

<div class="card">
  <div class="card-header">
    <h3>Todas as Páginas <span class="badge badge-blue" style="margin-left:6px"><?= count($pages) ?></span></h3>
    <a href="/admin/paginas.php?action=new" class="topbar-btn primary">
      <span class="i i-plus-circle"></span> Nova Página
    </a>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Título</th><th>Slug</th><th>Seções</th><th>Status</th><th>Menu</th><th>Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($pages as $pg):
          $secCount = Database::fetchOne('SELECT COUNT(*) AS c FROM conteudos WHERE pagina_slug = ?', [$pg['slug']]);
      ?>
        <tr>
          <td><strong><?= e($pg['titulo']) ?></strong></td>
          <td><code style="font-size:.75rem">/<?= e($pg['slug']) ?></code></td>
          <td><span class="badge badge-blue"><?= (int)($secCount['c'] ?? 0) ?> seções</span></td>
          <td>
            <span class="badge <?= $pg['status'] === 'publicado' ? 'badge-green' : 'badge-yellow' ?>">
              <?= e($pg['status']) ?>
            </span>
          </td>
          <td><?= $pg['show_in_menu'] ? '✓' : '–' ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="/?slug=<?= e($pg['slug']) ?>" target="_blank" class="topbar-btn outline" style="padding:4px 8px;font-size:.72rem" title="Visualizar">
                <span class="i i-eye"></span>
              </a>
              <a href="/admin/builder.php?page_id=<?= $pg['id'] ?>" class="topbar-btn primary" style="padding:4px 10px;font-size:.72rem" title="Editor Visual">
                ⚡ Builder
              </a>
              <a href="/admin/paginas.php?action=edit&id=<?= $pg['id'] ?>" class="topbar-btn outline" style="padding:4px 8px;font-size:.72rem" title="Editar (formulário)">
                <span class="i i-pencil"></span>
              </a>
              <?php if (Auth::can('pages.delete')): ?>
              <form method="POST" action="/admin/paginas.php?action=delete&id=<?= $pg['id'] ?>" style="display:inline">
                <?= CSRF::field() ?>
                <button type="submit" class="topbar-btn danger" style="padding:4px 8px;font-size:.72rem"
                        data-confirm="Remover a página '<?= e($pg['titulo']) ?>'?">
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

<?php elseif (in_array($action, ['new', 'edit'])): ?>

<form method="POST" action="/admin/paginas.php?action=<?= $action ?>&id=<?= $id ?>">
  <?= CSRF::field() ?>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">

    <!-- Coluna principal -->
    <div style="display:flex;flex-direction:column;gap:20px">
      <!-- Título + Slug -->
      <div class="card">
        <div class="card-body">
          <div class="form-group" style="margin-bottom:16px">
            <label class="form-label">Título da Página <span class="req">*</span></label>
            <input class="form-input" type="text" name="titulo" id="titulo"
                   value="<?= e($page['titulo'] ?? '') ?>" required placeholder="Ex: Nossa História" autofocus>
          </div>
          <div class="form-group">
            <label class="form-label">Slug (URL)</label>
            <div style="display:flex;align-items:center;gap:8px">
              <span style="color:var(--text-muted);font-size:.85rem">/</span>
              <input class="form-input" type="text" name="slug" id="slug"
                     value="<?= e($page['slug'] ?? '') ?>" placeholder="minha-pagina">
            </div>
            <div class="form-hint">Deixe vazio para gerar automaticamente a partir do título.</div>
          </div>
        </div>
      </div>

      <!-- ═══ SEÇÕES DE CONTEÚDO INLINE (PRINCIPAL EDIÇÃO) ═══ -->
      <?php if ($id && !empty($secoes)): ?>
      <div class="card">
        <div class="card-header">
          <h3>📝 Conteúdo desta página
            <span class="badge badge-blue" style="margin-left:6px"><?= count($secoes) ?></span>
          </h3>
          <span style="font-size:.78rem;color:var(--text-muted)">
            Cada seção corresponde a um bloco específico da página
          </span>
        </div>
        <div class="card-body">
          <div class="content-sections">
            <?php foreach ($secoes as $sec): ?>
              <div class="content-section">
                <div class="content-section-header">
                  <span class="content-section-key"><?= e($sec['secao_chave']) ?></span>
                  <span class="content-section-tipo"><?= e($sec['tipo'] ?? 'text') ?></span>
                </div>
                <input class="cs-titulo"
                       type="text"
                       name="secoes[<?= (int)$sec['id'] ?>][titulo]"
                       value="<?= e($sec['titulo'] ?? '') ?>"
                       placeholder="Título / cabeçalho desta seção">
                <textarea class="cs-conteudo"
                          name="secoes[<?= (int)$sec['id'] ?>][conteudo]"
                          rows="3"
                          placeholder="Conteúdo desta seção…"><?= e($sec['conteudo'] ?? '') ?></textarea>
              </div>
            <?php endforeach; ?>
          </div>

          <p style="margin-top:14px;font-size:.82rem;color:var(--text-muted)">
            ℹ️ As seções acima são as que aparecem visualmente na página.
            Para gerenciar a lista completa de seções, vá em
            <a href="/admin/conteudos.php">Conteúdos</a>.
          </p>
        </div>
      </div>
      <?php elseif ($id): ?>
        <div class="card">
          <div class="card-body" style="text-align:center;padding:32px;color:var(--text-muted)">
            <p>Esta página ainda não tem seções de conteúdo configuradas.</p>
            <a href="/admin/conteudos.php?action=new&pagina=<?= e($page['slug']) ?>" class="topbar-btn primary" style="margin-top:12px">
              <span class="i i-plus-circle"></span> Adicionar primeira seção
            </a>
          </div>
        </div>
      <?php endif; ?>

      <!-- Editor de conteúdo livre (legacy/avançado) -->
      <div class="card">
        <div class="card-header" style="gap:0;padding:0">
          <div style="display:flex;width:100%">
            <button type="button" id="tab-rich" onclick="switchTab('rich')"
                    style="padding:12px 20px;border:none;background:var(--primary);color:#fff;font-size:.82rem;font-weight:600;cursor:pointer;border-radius:10px 0 0 0">
              ✏️ Editor Rico (HTML livre)
            </button>
            <button type="button" id="tab-blocks" onclick="switchTab('blocks')"
                    style="padding:12px 20px;border:none;background:#f8fafc;color:var(--text-muted);font-size:.82rem;font-weight:600;cursor:pointer">
              ⊞ Editor de Blocos
            </button>
          </div>
        </div>

        <div id="panel-rich">
          <div class="card-body" style="padding:0">
            <textarea id="tinymce-editor" class="rich-editor" name="conteudo" style="min-height:320px;width:100%;border:none;padding:16px"><?= e($page['conteudo'] ?? '') ?></textarea>
          </div>
          <div class="card-body" style="padding:8px 16px;background:#f9fafb;border-top:1px solid #e5e9ef;font-size:.78rem;color:var(--text-muted)">
            <?php if (!$tinymceLocal): ?>
              💡 Editor lite ativo. Para mais recursos,
              <a href="/admin/install-tinymce.php">instale o TinyMCE</a>.
            <?php else: ?>
              ✓ TinyMCE 7 ativo.
            <?php endif; ?>
            Conteúdo HTML livre — usado quando a página não tem seções específicas.
          </div>
        </div>

        <div id="panel-blocks" style="display:none">
          <div class="blocks-toolbar">
            <button type="button" class="block-add-btn" onclick="addBlock('heading')">+ Título</button>
            <button type="button" class="block-add-btn" onclick="addBlock('text')">+ Texto</button>
            <button type="button" class="block-add-btn" onclick="addBlock('image')">+ Imagem</button>
            <button type="button" class="block-add-btn" onclick="addBlock('button')">+ Botão</button>
            <button type="button" class="block-add-btn" onclick="addBlock('divider')">+ Divisor</button>
          </div>
          <div class="blocks-canvas" id="blocks-canvas"></div>
          <input type="hidden" name="blocos" id="blocks-json" value="<?= e($page['blocos'] ?? '') ?>">
        </div>
      </div>

      <!-- SEO -->
      <div class="card">
        <div class="card-header"><h3>🔍 SEO</h3></div>
        <div class="card-body form-grid">
          <div class="form-group full">
            <label class="form-label">Meta Title</label>
            <input class="form-input" type="text" name="meta_title"
                   value="<?= e($page['meta_title'] ?? '') ?>" placeholder="Título para o Google (60 chars)">
          </div>
          <div class="form-group full">
            <label class="form-label">Meta Description</label>
            <textarea class="form-textarea" name="meta_description" rows="2"
                      placeholder="Descrição breve para o Google (155 chars)"><?= e($page['meta_description'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Coluna lateral -->
    <div style="display:flex;flex-direction:column;gap:20px">
      <div class="card">
        <div class="card-header"><h3>Publicação</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option value="publicado" <?= ($page['status'] ?? '') === 'publicado' ? 'selected' : '' ?>>✅ Publicado</option>
              <option value="rascunho"  <?= ($page['status'] ?? 'rascunho') === 'rascunho' ? 'selected' : '' ?>>📝 Rascunho</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Ordem no Menu</label>
            <input class="form-input" type="number" name="menu_order" value="<?= (int)($page['menu_order'] ?? 0) ?>" min="0">
          </div>
          <div class="toggle-wrap">
            <label class="toggle">
              <input type="checkbox" name="show_in_menu" <?= ($page['show_in_menu'] ?? 1) ? 'checked' : '' ?>>
              <span class="toggle-slider"></span>
            </label>
            <span style="font-size:.85rem">Exibir no menu</span>
          </div>
        </div>
        <div class="card-footer">
          <a href="/admin/paginas.php" class="topbar-btn outline">Cancelar</a>
          <button type="submit" class="topbar-btn success">
            <span class="i i-check-circle"></span> <?= $id ? 'Salvar Tudo' : 'Criar' ?>
          </button>
        </div>
      </div>

      <?php if ($id): ?>
      <div class="card">
        <div class="card-header"><h3>Prévia</h3></div>
        <div class="card-body">
          <a href="/?slug=<?= e($page['slug'] ?? '') ?>" target="_blank" class="topbar-btn outline w-full" style="justify-content:center">
            <span class="i i-external"></span> Ver Página
          </a>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</form>

<script>
document.getElementById('titulo')?.addEventListener('input', function() {
  const slugField = document.getElementById('slug');
  if (!slugField.dataset.manual) {
    slugField.value = this.value.toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
      .replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
  }
});
document.getElementById('slug')?.addEventListener('input', function() {
  this.dataset.manual = '1';
});

function switchTab(tab) {
  const rich   = document.getElementById('panel-rich');
  const blocks = document.getElementById('panel-blocks');
  const tRich  = document.getElementById('tab-rich');
  const tBlocks= document.getElementById('tab-blocks');
  if (tab === 'rich') {
    rich.style.display = 'block'; blocks.style.display = 'none';
    tRich.style.background = 'var(--primary)'; tRich.style.color = '#fff';
    tBlocks.style.background = '#f8fafc'; tBlocks.style.color = 'var(--text-muted)';
  } else {
    rich.style.display = 'none'; blocks.style.display = 'block';
    tBlocks.style.background = 'var(--primary)'; tBlocks.style.color = '#fff';
    tRich.style.background = '#f8fafc'; tRich.style.color = 'var(--text-muted)';
  }
}

const hasBlocos = <?= !empty($page['blocos']) ? 'true' : 'false' ?>;
if (hasBlocos) switchTab('blocks');
</script>

<?php endif;

include __DIR__ . '/includes/footer.php'; ?>
