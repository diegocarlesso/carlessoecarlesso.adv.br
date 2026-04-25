<?php
// templates/fundamentos.php
if (!defined('CARLESSO_CMS')) exit;
$missao = getContent('fundamentos', 'missao');
$visao  = getContent('fundamentos', 'visao');
$princ  = getContent('fundamentos', 'principios');
?>

<div class="gold-divider"></div>

<div class="fundamentos-layout">

  <!-- Princípios -->
  <article class="fundamento-card fundamento-card--full">
    <div class="fundamento-header">
      <div class="fundamento-icon"><?= svgIcon('scale') ?></div>
      <h3><?= e($princ['titulo'] ?? 'Princípios') ?></h3>
    </div>
    <p>Esta Sociedade (parceria) e as ações de seus associados, bem como a solução de eventuais dilemas, será norteada/regida pelos seguintes princípios:</p>
    <ul class="principios-list">
      <li><span>I</span> Sinceridade</li>
      <li><span>II</span> Honestidade</li>
      <li><span>III</span> Transparência</li>
      <li><span>IV</span> Profissionalismo</li>
      <li><span>V</span> Ética</li>
      <li><span>VI</span> Sigilo</li>
      <li><span>VII</span> Equidade</li>
      <li><span>VIII</span> Espírito de equipe</li>
      <li><span>IX</span> Entusiasmo</li>
      <li><span>X</span> Responsabilidade social</li>
    </ul>
  </article>

  <!-- Visão -->
  <article class="fundamento-card">
    <div class="fundamento-header">
      <div class="fundamento-icon"><?= svgIcon('eye') ?></div>
      <h3><?= e($visao['titulo'] ?? 'Visão') ?></h3>
    </div>
    <p>
      <?= e($visao['conteudo'] ?? 'Buscar ser reconhecido como uma sociedade de excelência no mercado de prestação de serviços, zelando pela alta qualidade em todas as atividades jurídicas; como uma sociedade justa, fraterna e igualitária; que acredita no direito como a melhor forma de solucionar dissídios e promover a justiça.') ?>
    </p>
  </article>

  <!-- Missão -->
  <article class="fundamento-card fundamento-card--missao">
    <div class="fundamento-header">
      <div class="fundamento-icon"><?= svgIcon('target') ?></div>
      <h3><?= e($missao['titulo'] ?? 'Missão') ?></h3>
    </div>
    <div class="missao-list">
      <p><strong>I –</strong> Atender e superar as expectativas de nossos clientes e parceiros, fornecendo serviços seguros e com qualidade diferenciada, através de modernos procedimentos, atuando com responsabilidade social e gerando valores para nossos clientes, parceiros, colaboradores e a sociedade.</p>
      <p><strong>II –</strong> Continuamente expandir no mercado jurídico, com o compromisso de aperfeiçoamento de seus serviços prestados.</p>
      <p><strong>III –</strong> Fomentar talentos; formar os melhores profissionais do mercado e investir continuamente em suas carreiras.</p>
      <p><strong>IV –</strong> Preservar um bom ambiente de trabalho.</p>
    </div>
  </article>

</div>
