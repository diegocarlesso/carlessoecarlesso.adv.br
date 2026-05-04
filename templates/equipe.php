<?php
// templates/equipe.php — v1.3 (srcset/WebP responsivo)
if (!defined('CARLESSO_CMS')) exit;
$guilherme = getContent('equipe', 'guilherme');
$jean      = getContent('equipe', 'jean');
$apoio     = getContent('equipe', 'apoio');
?>

<!-- Faixa: ADVOGADOS ASSOCIADOS -->
<div class="section-bar">
  <h2>ADVOGADOS ASSOCIADOS</h2>
</div>
<div class="section-bar-line"></div>

<div class="team-layout">

  <!-- Linha 1: FOTO esquerda + texto direita (Guilherme) -->
  <div class="team-row">
    <div class="team-photo">
      <?php if (!empty($guilherme['imagem'])): ?>
        <?php $srcset_g = generateSrcset($guilherme['imagem']); ?>
        <?php if ($srcset_g): ?>
        <picture>
          <source type="image/webp"
                  srcset="<?= $srcset_g ?>"
                  sizes="(max-width:768px) 90vw, 240px">
          <img src="<?= e($guilherme['imagem']) ?>"
               alt="<?= e($guilherme['titulo'] ?? 'Guilherme Carlesso') ?>"
               srcset="<?= $srcset_g ?>"
               sizes="(max-width:768px) 90vw, 240px"
               loading="lazy" decoding="async">
        </picture>
        <?php else: ?>
        <img src="<?= e($guilherme['imagem']) ?>"
             alt="<?= e($guilherme['titulo'] ?? 'Guilherme Carlesso') ?>"
             loading="lazy" decoding="async">
        <?php endif; ?>
      <?php else: ?>
        <div class="team-photo-placeholder">FOTO</div>
      <?php endif; ?>
    </div>
    <div class="team-info">
      <h3><?= e($guilherme['titulo'] ?? 'Guilherme Carlesso') ?></h3>
      <div class="team-role">Advogado · OAB 43906/SC</div>
      <p class="team-bio">
        <?= e($guilherme['conteudo'] ?? 'Guilherme Carlesso é Advogado, de São Miguel do Oeste/SC. Bacharel em Direito pela Universidade do Oeste de Santa Catarina – UNOESC e especialista em Advocacia Trabalhista pela Universidade Leonardo da Vinci.') ?>
      </p>
    </div>
  </div>

  <!-- Linha 2: texto esquerda + FOTO direita (Jean) -->
  <div class="team-row reverse">
    <div class="team-photo">
      <?php if (!empty($jean['imagem'])): ?>
        <?php $srcset_j = generateSrcset($jean['imagem']); ?>
        <?php if ($srcset_j): ?>
        <picture>
          <source type="image/webp"
                  srcset="<?= $srcset_j ?>"
                  sizes="(max-width:768px) 90vw, 240px">
          <img src="<?= e($jean['imagem']) ?>"
               alt="<?= e($jean['titulo'] ?? 'Jean Carlos Carlesso') ?>"
               srcset="<?= $srcset_j ?>"
               sizes="(max-width:768px) 90vw, 240px"
               loading="lazy" decoding="async">
        </picture>
        <?php else: ?>
        <img src="<?= e($jean['imagem']) ?>"
             alt="<?= e($jean['titulo'] ?? 'Jean Carlos Carlesso') ?>"
             loading="lazy" decoding="async">
        <?php endif; ?>
      <?php else: ?>
        <div class="team-photo-placeholder">FOTO</div>
      <?php endif; ?>
    </div>
    <div class="team-info">
      <h3><?= e($jean['titulo'] ?? 'Jean Carlos Carlesso') ?></h3>
      <div class="team-role">Advogado · OAB 33732/SC</div>
      <p class="team-bio">
        <?= e($jean['conteudo'] ?? 'Jean Carlos Carlesso é Advogado, de São Miguel do Oeste/SC, formado em Direito pela Universidade do Oeste de Santa Catarina – UNOESC; Pós-Graduado em Direito Penal e Processual Penal pela Faculdade Damásio de Jesus e licenciado em Filosofia pelo Centro Universitário Internacional Uninter.') ?>
      </p>
    </div>
  </div>

</div>

<!-- Faixa: EQUIPE DE APOIO -->
<div class="section-bar">
  <h2>EQUIPE DE APOIO</h2>
</div>
<div class="section-bar-line"></div>

<div class="support-block">
  <?php if (!empty($apoio['conteudo'])): ?>
    <div class="rich-content"><?= sanitizeHtml($apoio['conteudo']) ?></div>
  <?php else: ?>
    <p class="support-row"><strong>Advogados:</strong> Higor Mateus Scain e Andréia Colle.</p>
    <p class="support-row"><strong>Secretário:</strong> Jean Pedro Hemsing.</p>
  <?php endif; ?>
</div>
