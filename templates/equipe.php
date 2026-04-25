<?php
// templates/equipe.php — v1.2 (layout alternado conforme PDF)
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
        <img src="<?= e($guilherme['imagem']) ?>" alt="<?= e($guilherme['titulo'] ?? 'Guilherme Carlesso') ?>">
      <?php else: ?>
        <div class="team-photo-placeholder">FOTO</div>
      <?php endif; ?>
    </div>
    <div class="team-info">
      <h3><?= e($guilherme['titulo'] ?? 'Guilherme Carlesso') ?></h3>
      <div class="team-role">Advogado · OAB/SC</div>
      <p class="team-bio">
        <?= e($guilherme['conteudo'] ?? 'Guilherme Carlesso é Advogado, de São Miguel do Oeste/SC. Bacharel em Direito pela Universidade do Oeste de Santa Catarina – UNOESC e especialista em Advocacia Trabalhista pela Universidade Leonardo da Vinci.') ?>
      </p>
    </div>
  </div>

  <!-- Linha 2: texto esquerda + FOTO direita (Jean) -->
  <div class="team-row reverse">
    <div class="team-photo">
      <?php if (!empty($jean['imagem'])): ?>
        <img src="<?= e($jean['imagem']) ?>" alt="<?= e($jean['titulo'] ?? 'Jean Carlos Carlesso') ?>">
      <?php else: ?>
        <div class="team-photo-placeholder">FOTO</div>
      <?php endif; ?>
    </div>
    <div class="team-info">
      <h3><?= e($jean['titulo'] ?? 'Jean Carlos Carlesso') ?></h3>
      <div class="team-role">Advogado · OAB/SC</div>
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
