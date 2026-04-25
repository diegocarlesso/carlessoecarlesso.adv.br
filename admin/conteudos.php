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
    $id       = (int)($_POST['id'] ?? 0);
    $titulo   = trim($_POST['titulo'] ?? '');
    $conteudo = sanitizeHtml($_POST['conteudo'] ?? '');
    $imagem   = trim($_POST['imagem'] ?? '');

    if ($id) {
        $data = ['titulo' => $titulo, 'conteudo' => $conteudo];
        if ($imagem) $data['imagem'] = $imagem;
        Database::update('conteudos', $data, 'id = ?', [$id]);
        flash('success', 'Seção atualizada com sucesso.');
    }
    header('Location: /admin/conteudos.php');
    exit;
}

$conteudos = Database::fetchAll('SELECT * FROM conteudos ORDER BY pagina ASC, secao ASC');

// Agrupar por página
$grouped = [];
foreach ($conteudos as $c) {
    $grouped[$c['pagina']][] = $c;
}

$pageTitle  = 'Seções de Conteúdo';
$breadcrumb = ['Seções' => ''];

include __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom:16px">
  <p style="color:var(--text-muted);font-size:.88rem">
    Edite o conteúdo de cada seção das páginas do site. Para conteúdo mais avançado, use o editor de <a href="/admin/paginas.php">Páginas</a>.
  </p>
</div>

<?php foreach ($grouped as $pagina => $secoes): ?>
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <h3>📄 <?= ucfirst(e($pagina)) ?></h3>
    <a href="/?slug=<?= e($pagina) ?>" target="_blank" class="topbar-btn outline" style="padding:4px 10px;font-size:.75rem">
      <span class="i i-eye"></span> Ver
    </a>
  </div>
  <div class="card-body">
    <?php foreach ($secoes as $c): ?>
    <details style="border:1px solid var(--card-border);border-radius:6px;margin-bottom:10px;overflow:hidden">
      <summary style="padding:12px 16px;cursor:pointer;font-weight:600;font-size:.85rem;background:#f8fafc;display:flex;align-items:center;gap:8px">
        <span>📑</span> <?= e($c['secao']) ?> — <span style="color:var(--text-muted);font-weight:400"><?= e(truncate($c['titulo'] ?? '', 50)) ?></span>
      </summary>
      <form method="POST" style="padding:16px">
        <?= CSRF::field() ?>
        <input type="hidden" name="id" value="<?= $c['id'] ?>">
        <div class="form-group" style="margin-bottom:12px">
          <label class="form-label">Título</label>
          <input class="form-input" type="text" name="titulo" value="<?= e($c['titulo'] ?? '') ?>">
        </div>
        <div class="form-group" style="margin-bottom:12px">
          <label class="form-label">Conteúdo</label>
          <textarea class="form-textarea" name="conteudo" rows="4"><?= e($c['conteudo'] ?? '') ?></textarea>
        </div>
        <?php if ($c['imagem'] !== null): ?>
        <div class="form-group" style="margin-bottom:12px">
          <label class="form-label">URL da Imagem</label>
          <input class="form-input" type="text" name="imagem" value="<?= e($c['imagem'] ?? '') ?>" placeholder="/assets/images/foto.jpg">
        </div>
        <?php endif; ?>
        <button type="submit" class="topbar-btn primary" style="font-size:.8rem;padding:7px 14px">
          <span class="i i-check-circle"></span> Salvar Seção
        </button>
      </form>
    </details>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach;

include __DIR__ . '/includes/footer.php'; ?>
