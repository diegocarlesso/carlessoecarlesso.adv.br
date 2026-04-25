<?php
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireRole('editor');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();
    $paginas = $_POST['seo'] ?? [];
    foreach ($paginas as $slug => $seoData) {
        $slug  = generateSlug($slug);
        $title = trim($seoData['meta_title'] ?? '');
        $desc  = trim($seoData['meta_description'] ?? '');
        $kw    = trim($seoData['keywords'] ?? '');

        // Upsert
        Database::query(
            'INSERT INTO seo (pagina, meta_title, meta_description, keywords) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE meta_title=?, meta_description=?, keywords=?',
            [$slug, $title, $desc, $kw, $title, $desc, $kw]
        );

        // Atualiza paginas também
        Database::query(
            'UPDATE paginas SET meta_title=?, meta_description=? WHERE slug=?',
            [$title, $desc, $slug]
        );
    }
    flash('success', 'Configurações de SEO salvas.');
    header('Location: /admin/seo.php');
    exit;
}

$pages   = Database::fetchAll('SELECT * FROM paginas ORDER BY menu_order ASC');
$seoData = [];
foreach (Database::fetchAll('SELECT * FROM seo') as $s) {
    $seoData[$s['pagina']] = $s;
}

$pageTitle  = 'SEO';
$breadcrumb = ['SEO' => ''];

include __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom:16px">
  <p style="color:var(--text-muted);font-size:.88rem">
    Configure os metadados de SEO para cada página. O título ideal tem até 60 caracteres e a descrição até 155.
  </p>
</div>

<form method="POST">
  <?= CSRF::field() ?>

  <?php foreach ($pages as $pg):
    $s = $seoData[$pg['slug']] ?? [];
    $metaTitle = $s['meta_title'] ?? $pg['meta_title'] ?? '';
    $metaDesc  = $s['meta_description'] ?? $pg['meta_description'] ?? '';
    $keywords  = $s['keywords'] ?? '';
  ?>
  <div class="card" style="margin-bottom:16px">
    <div class="card-header">
      <h3>
        <?= e($pg['titulo']) ?>
        <code style="font-size:.72rem;color:#6b7280;font-weight:400;margin-left:8px">/<?= e($pg['slug']) ?></code>
      </h3>
      <a href="/?slug=<?= e($pg['slug']) ?>" target="_blank" class="topbar-btn outline" style="padding:4px 10px;font-size:.75rem">
        <span class="i i-eye"></span> Visualizar
      </a>
    </div>
    <div class="card-body form-grid cols-2">
      <div class="form-group full">
        <label class="form-label">Meta Title</label>
        <input class="form-input" type="text" name="seo[<?= e($pg['slug']) ?>][meta_title]"
               value="<?= e($metaTitle) ?>" maxlength="80"
               placeholder="Título para mecanismos de busca (recomendado: 50-60 chars)"
               oninput="updateCounter(this)">
        <div class="form-hint" id="cnt_title_<?= e($pg['id']) ?>">
          <?= strlen($metaTitle) ?> caracteres
        </div>
      </div>
      <div class="form-group full">
        <label class="form-label">Meta Description</label>
        <textarea class="form-textarea" name="seo[<?= e($pg['slug']) ?>][meta_description]"
                  rows="2" maxlength="200"
                  placeholder="Descrição que aparece nos resultados do Google (recomendado: 120-155 chars)"
                  oninput="updateCounter(this)"><?= e($metaDesc) ?></textarea>
        <div class="form-hint"><?= strlen($metaDesc) ?> caracteres</div>
      </div>
      <div class="form-group full">
        <label class="form-label">Keywords <span style="font-weight:400;color:var(--text-muted)">(opcional)</span></label>
        <input class="form-input" type="text" name="seo[<?= e($pg['slug']) ?>][keywords]"
               value="<?= e($keywords) ?>" placeholder="advocacia, direito penal, são miguel do oeste">
      </div>

      <!-- Prévia Google -->
      <div class="form-group full">
        <label class="form-label">Prévia (Google)</label>
        <div style="border:1px solid #e5e9ef;border-radius:6px;padding:16px;background:#fff">
          <div style="font-size:.72rem;color:#202124;font-family:Arial,sans-serif">
            <div style="color:#1a0dab;font-size:1rem;font-weight:400;margin-bottom:2px" id="prev_title_<?= $pg['id'] ?>">
              <?= e($metaTitle ?: $pg['titulo']) ?>
            </div>
            <div style="color:#006621;font-size:.78rem;margin-bottom:2px">
              carlessoecarlesso.adv.br/<?= e($pg['slug']) ?>
            </div>
            <div style="color:#545454;font-size:.82rem" id="prev_desc_<?= $pg['id'] ?>">
              <?= e($metaDesc ?: 'Adicione uma descrição para esta página.') ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <div style="display:flex;justify-content:flex-end;margin-top:8px">
    <button type="submit" class="topbar-btn success" style="padding:12px 28px;font-size:.9rem">
      <span class="i i-check-circle"></span> Salvar Todas as Configurações de SEO
    </button>
  </div>
</form>

<script>
function updateCounter(el) {
  const hint = el.nextElementSibling;
  if (hint) hint.textContent = el.value.length + " caracteres";
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
