<?php
// templates/home.php — v1.2 (H1 limpo)
if (!defined('CARLESSO_CMS')) exit;

$banner_titulo    = getContent('inicio', 'banner_titulo');
$banner_subtitulo = getContent('inicio', 'banner_subtitulo');
$banner_descricao = getContent('inicio', 'banner_descricao');
$sobre_titulo     = getContent('inicio', 'sobre_titulo');
$sobre_descricao  = getContent('inicio', 'sobre_descricao');
$telefone         = getConfig('telefone', '(49) 3621-2254');

$servicos = [
    ['slug' => 'previdenciario', 'icon' => 'people',   'data' => getContent('servicos', 'previdenciario'), 'nome' => 'Direito Previdenciário'],
    ['slug' => 'trabalho',       'icon' => 'briefcase','data' => getContent('servicos', 'trabalho'),       'nome' => 'Direito do Trabalho'],
    ['slug' => 'civil',          'icon' => 'scale',    'data' => getContent('servicos', 'civil'),          'nome' => 'Direito Civil'],
    ['slug' => 'penal',          'icon' => 'shield',   'data' => getContent('servicos', 'penal'),          'nome' => 'Direito Penal'],
];
?>

<!-- ═══ HERO ═══ -->
<section class="hero hero--split">
  <div class="hero-bg" aria-hidden="true"></div>
  <div class="hero-pattern" aria-hidden="true"></div>

  <div class="container hero-grid">
    <div class="hero-content">

      <div class="hero-eyebrow">
        <?= e($banner_subtitulo['conteudo'] ?? 'Atuação consolidada desde 2012') ?>
      </div>

      <h1>
        <?= e($banner_titulo['titulo'] ?? 'Excelência jurídica em São Miguel do Oeste') ?>
      </h1>

      <p class="hero-desc">
        <?= e($banner_descricao['conteudo'] ?? 'Olá, que bom que você chegou até nós. Sabemos que a confiança em qualquer área de trabalho é construída a partir da história das pessoas que conduzem os processos. Nosso trabalho valoriza o respeito com cada pessoa que chega até nós – confiança, sigilo e zelo nos processos são fundamentos que priorizamos.') ?>
      </p>

      <div class="hero-actions">
        <a href="/contato" class="btn btn-primary">
          <span class="i i-envelope"></span> Fale Conosco
        </a>
        <a href="/servicos" class="btn btn-outline">Nossos Serviços</a>
      </div>
    </div>

    <!-- Logo institucional grande no lado direito -->
    <div class="hero-logo" aria-hidden="true">
      <img src="/assets/images/logo_com_texto.png" alt="<?= e(getConfig('site_titulo', 'Carlesso & Carlesso')) ?>">
    </div>
  </div>
</section>

<!-- ═══ SERVIÇOS PRESTADOS (faixa azul ao topo, conforme PDF) ═══ -->
<section class="section section--alt">
  <div class="container">

    <div class="section-bar"><h2>SERVIÇOS PRESTADOS</h2></div>
    <div class="section-bar-line"></div>

    <div class="services-grid">
      <?php foreach ($servicos as $s): ?>
        <a href="/servicos#<?= e($s['slug']) ?>" class="service-card">
          <div class="service-icon">
            <?= svgIcon($s['icon']) ?>
          </div>
          <h3><?= e($s['nome']) ?></h3>
          <p><?= e(truncate($s['data']['conteudo'] ?? '', 110)) ?></p>
          <span class="service-link">Saiba mais →</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══ SOBRE O ESCRITÓRIO ═══ -->
<section class="about-banner">
  <div class="container">
    <div class="about-grid">
      <div class="about-content">
        <div class="hero-eyebrow">Sobre o Escritório</div>
        <h2><?= e($sobre_titulo['titulo'] ?? 'Tradição, ética e compromisso') ?></h2>
        <p>
          <?= e($sobre_descricao['conteudo'] ?? 'Fundado em 1º de junho de 2012, o escritório Carlesso & Carlesso é uma sociedade de advogados que prima pela excelência, ética e compromisso com cada cliente.') ?>
        </p>
        <a href="/escritorio" class="btn btn-primary" style="margin-top:24px">
          Conheça nossa história
        </a>
      </div>
      <div class="about-stats">
        <div class="stat-block">
          <div class="stat-num">2012</div>
          <div class="stat-label">Ano de fundação</div>
        </div>
        <div class="stat-block">
          <div class="stat-num">04</div>
          <div class="stat-label">Áreas de atuação</div>
        </div>
        <div class="stat-block">
          <div class="stat-num">SMO</div>
          <div class="stat-label">São Miguel do Oeste – SC</div>
        </div>
        <div class="stat-block">
          <div class="stat-num">+</div>
          <div class="stat-label">Equipe associada</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ FUNDAMENTOS preview ═══ -->
<section class="section">
  <div class="container">
    <div class="section-bar"><h2>NOSSOS FUNDAMENTOS</h2></div>
    <div class="section-bar-line"></div>
    <p class="section-lead">Sigilo, ética e profissionalismo em cada processo.</p>

    <div class="principles-row">
      <div class="principle"><span class="dot"></span>Sinceridade</div>
      <div class="principle"><span class="dot"></span>Honestidade</div>
      <div class="principle"><span class="dot"></span>Transparência</div>
      <div class="principle"><span class="dot"></span>Profissionalismo</div>
      <div class="principle"><span class="dot"></span>Ética</div>
      <div class="principle"><span class="dot"></span>Sigilo</div>
      <div class="principle"><span class="dot"></span>Equidade</div>
      <div class="principle"><span class="dot"></span>Espírito de equipe</div>
      <div class="principle"><span class="dot"></span>Entusiasmo</div>
      <div class="principle"><span class="dot"></span>Responsabilidade social</div>
    </div>

    <div style="text-align:center;margin-top:40px">
      <a href="/fundamentos" class="btn btn-outline btn-dark">Ver Missão, Visão e Princípios</a>
    </div>
  </div>
</section>

<!-- ═══ PRODUÇÕES preview ═══ -->
<?php
$recentPosts = getPublishedPosts(3);
if (!empty($recentPosts)):
?>
<section class="section section--alt">
  <div class="container">
    <div class="section-bar"><h2>PRODUÇÕES</h2></div>
    <div class="section-bar-line"></div>

    <div class="posts-grid">
      <?php foreach ($recentPosts as $post): ?>
        <article class="post-card">
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
              <a href="/producoes/<?= e(!empty($post['slug']) ? $post['slug'] : $post['id']) ?>"><?= e($post['titulo']) ?></a>
            </h3>
            <p class="post-excerpt"><?= e(truncate(strip_tags($post['conteudo'] ?? ''), 130)) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center;margin-top:40px">
      <a href="/producoes" class="btn btn-outline btn-dark">Ver todas as produções</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══ CTA CONTATO ═══ -->
<section class="cta-section">
  <div class="container cta-inner">
    <div>
      <h2>Precisa de orientação jurídica?</h2>
      <p>Estamos prontos para atendê-lo com o sigilo e o zelo que sua causa merece.</p>
    </div>
    <div class="cta-actions">
      <a href="tel:<?= e(preg_replace('/\D/','',$telefone)) ?>" class="btn btn-primary">
        <span class="i i-phone"></span> <?= e($telefone) ?>
      </a>
      <a href="/contato" class="btn btn-outline">Enviar mensagem</a>
    </div>
  </div>
</section>
