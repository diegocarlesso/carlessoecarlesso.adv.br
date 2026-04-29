<?php
// templates/servicos.php
// Página de Serviços Prestados
if (!defined('CARLESSO_CMS')) exit;

$penal           = getContent('servicos', 'penal');
$previdenciario  = getContent('servicos', 'previdenciario');
$civil           = getContent('servicos', 'civil');
$trabalho        = getContent('servicos', 'trabalho');
?>

<p class="lead-text">
  O escritório <strong>Carlesso & Carlesso Advogados Associados</strong> atua de forma
  técnica e personalizada nas principais áreas do Direito, oferecendo soluções jurídicas
  com sigilo, comprometimento e excelência.
</p>

<!-- Faixa: ÁREAS DE ATUAÇÃO (mesmo padrão de Equipe/Fundamentos) -->
<div class="section-bar">
  <h2>ÁREAS DE ATUAÇÃO</h2>
</div>
<div class="section-bar-line"></div>

<div class="fundamentos-layout">

  <!-- Direito Previdenciário -->
  <article class="fundamento-card" id="previdenciario">
    <div class="fundamento-header">
      <div class="fundamento-icon"><?= svgIcon('people') ?></div>
      <h3><?= e($previdenciario['titulo'] ?? 'Direito Previdenciário') ?></h3>
    </div>
    <p>
      <?= e($previdenciario['conteudo'] ?? 'Aposentadorias por idade, tempo de contribuição, especial e por invalidez. Auxílios, pensões, revisões de benefícios e planejamento previdenciário – tudo com análise técnica e atualização constante junto ao INSS.') ?>
    </p>
    <ul class="service-topics">
      <li>Aposentadorias (idade, tempo de contribuição, especial, invalidez)</li>
      <li>Auxílios e pensões por morte</li>
      <li>Revisão e recálculo de benefícios</li>
      <li>Planejamento previdenciário</li>
    </ul>
  </article>

  <!-- Direito do Trabalho -->
  <article class="fundamento-card" id="trabalho">
    <div class="fundamento-header">
      <div class="fundamento-icon"><?= svgIcon('briefcase') ?></div>
      <h3><?= e($trabalho['titulo'] ?? 'Direito do Trabalho') ?></h3>
    </div>
    <p>
      <?= e($trabalho['conteudo'] ?? 'Atuação consultiva e contenciosa para empregados e empregadores. Reconhecimento de vínculo, verbas rescisórias, horas extras, equiparação salarial, acidentes de trabalho e demais demandas trabalhistas.') ?>
    </p>
    <ul class="service-topics">
      <li>Reconhecimento de vínculo empregatício</li>
      <li>Verbas rescisórias e horas extras</li>
      <li>Acidentes de trabalho</li>
      <li>Consultoria preventiva para empresas</li>
    </ul>
  </article>

  <!-- Direito Civil -->
  <article class="fundamento-card" id="civil">
    <div class="fundamento-header">
      <div class="fundamento-icon"><?= svgIcon('scale') ?></div>
      <h3><?= e($civil['titulo'] ?? 'Direito Civil') ?></h3>
    </div>
    <p>
      <?= e($civil['conteudo'] ?? 'Contratos, responsabilidade civil, direito de família, sucessões, indenizações, direito do consumidor e demais demandas patrimoniais. Solução jurídica completa para questões civis e empresariais.') ?>
    </p>
    <ul class="service-topics">
      <li>Contratos e responsabilidade civil</li>
      <li>Direito de família e sucessões</li>
      <li>Direito do consumidor</li>
      <li>Indenizações e cobranças</li>
    </ul>
  </article>

  <!-- Direito Penal -->
  <article class="fundamento-card" id="penal">
    <div class="fundamento-header">
      <div class="fundamento-icon"><?= svgIcon('shield') ?></div>
      <h3><?= e($penal['titulo'] ?? 'Direito Penal') ?></h3>
    </div>
    <p>
      <?= e($penal['conteudo'] ?? 'Atuação em todas as fases do processo penal, desde inquérito policial até instâncias superiores. Defesa técnica em ações criminais, com atendimento personalizado e estratégico para cada caso.') ?>
    </p>
    <ul class="service-topics">
      <li>Defesa em inquérito policial</li>
      <li>Defesa em ações criminais (1ª e 2ª instâncias)</li>
      <li>Habeas corpus e medidas urgentes</li>
      <li>Recursos em Tribunais Superiores</li>
    </ul>
  </article>

</div>

<!-- CTA inline -->
<div class="services-cta">
  <h3>Tem uma demanda jurídica específica?</h3>
  <p>Agende uma consulta. Avaliamos seu caso com sigilo e atenção que ele merece.</p>
  <a href="/contato" class="btn btn-primary">Solicitar Atendimento</a>
</div>
