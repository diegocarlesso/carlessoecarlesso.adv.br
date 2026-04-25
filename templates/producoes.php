<?php
// templates/producoes.php
// Página de Produções (artigos / conteúdo institucional)
if (!defined('CARLESSO_CMS')) exit;

$introducao = getContent('producoes', 'introducao');
$posts      = getPublishedPosts(50);
$singlePost = null;

// Visualização de post individual
if (!empty($_GET['post'])) {
    $singlePost = Database::fetchOne(
        'SELECT * FROM postagens WHERE id = ? AND status = "publicado"',
        [(int)$_GET['post']]
    );
}
?>

<?php if ($singlePost): ?>
  <!-- ═══ Visualização de produção individual ═══ -->
  <article class="single-post">
    <a href="/producoes" class="back-link">← Voltar para Produções</a>

    <header class="single-post-header">
      <div class="post-date"><?= dateFormat($singlePost['data_publicacao'], 'd \d\e F \d\e Y') ?></div>
      <h1 style="margin-top:8px"><?= e($singlePost['titulo']) ?></h1>
      <div class="gold-divider" style="margin:24px 0"></div>
    </header>

    <?php if (!empty($singlePost['imagem'])): ?>
      <img class="single-post-image" src="<?= e($singlePost['imagem']) ?>" alt="<?= e($singlePost['titulo']) ?>">
    <?php endif; ?>

    <div class="rich-content">
      <?= sanitizeHtml($singlePost['conteudo'] ?? '') ?>
    </div>
  </article>

<?php else: ?>

  <!-- ═══ Listagem geral ═══ -->
  <p class="lead-text">
    <?= e($introducao['conteudo'] ?? 'Espaço dedicado às produções e trabalhos elaborados pelos integrantes do escritório. Conteúdo jurídico voltado à informação, atualização e contribuição com a comunidade.') ?>
  </p>

  <div class="gold-divider"></div>

  <?php if (empty($posts)): ?>

    <div class="empty-state">
      <div class="empty-icon"><?= svgIcon('document') ?></div>
      <h3>Em breve</h3>
      <p>Nossas produções serão publicadas neste espaço. Acompanhe nossas redes sociais para novidades.</p>
    </div>

  <?php else: ?>

    <div class="posts-grid posts-grid--full">
      <?php foreach ($posts as $post): ?>
        <article class="post-card" id="post-<?= (int)$post['id'] ?>">
          <?php if (!empty($post['imagem'])): ?>
            <img class="post-thumb" src="<?= e($post['imagem']) ?>" alt="<?= e($post['titulo']) ?>" loading="lazy">
          <?php else: ?>
            <div class="post-thumb post-thumb--placeholder" aria-hidden="true">
              <?= svgIcon('document') ?>
            </div>
          <?php endif; ?>
          <div class="post-body">
            <div class="post-date"><?= dateFormat($post['data_publicacao'], 'd \d\e F \d\e Y') ?></div>
            <h3 class="post-title">
              <a href="/producoes?post=<?= (int)$post['id'] ?>"><?= e($post['titulo']) ?></a>
            </h3>
            <p class="post-excerpt">
              <?= e(truncate(strip_tags($post['conteudo'] ?? ''), 160)) ?>
            </p>
            <a href="/producoes?post=<?= (int)$post['id'] ?>" class="post-link">Ler completo →</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>

<?php endif; ?>
