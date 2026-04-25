<?php
// templates/escritorio.php
if (!defined('CARLESSO_CMS')) exit;
$historia = getContent('escritorio', 'historia');
?>

<div id="historia" class="history-text">
  <h2><?= e($historia['titulo'] ?? 'Nossa História') ?></h2>
  <div class="gold-divider"></div>

  <?php if (!empty($historia['conteudo'])): ?>
    <div class="rich-content"><?= sanitizeHtml($historia['conteudo']) ?></div>
  <?php else: ?>
    <p>A história do escritório Carlesso e Carlesso Advogados Associados é datada em 1º de junho de 2012 e idealizada pelo sócio fundador, Jean Carlos Carlesso, em parceria com dois colegas de graduação. À época, a sociedade atuava sob a denominação – Carlesso e Minuscolli Advogados Associados.</p>
    <p>Com o passar dos anos e em decorrência de reestruturações internas, aconteceu a saída dos então sócios, sendo que, em novembro de 2016, passou a integrar a sociedade o advogado Guilherme Carlesso, em conjunto com a Advogada Nelita Muller e Jhyonnattann C. Ganzer, dando origem ao escritório – Carlesso e Ganzer Advogados Associados.</p>
    <p>Posteriormente, em meio às transformações impostas pelo cenário da pandemia, ocorreram novas mudanças societárias, culminando com a saída dos sócios Nelita Muller e Jhyonnattann C. Ganzer. Assim, em 24 de agosto de 2021, consolidou-se a atual estrutura sob a denominação – Carlesso e Carlesso Advogados Associados, marca que representa, até hoje, a identidade e os valores do escritório.</p>
    <p>Nosso trabalho valoriza o respeito com cada pessoa que chega até nós. Confiança, sigilo e zelo nos processos, são fundamentos que priorizamos.</p>
  <?php endif; ?>
</div>

<!-- Stats institucionais -->
<div class="institutional-stats">
  <div class="stat">
    <div class="stat-icon"><?= svgIcon('calendar') ?></div>
    <div class="stat-num">2012</div>
    <div class="stat-label">Ano de fundação</div>
  </div>
  <div class="stat">
    <div class="stat-icon"><?= svgIcon('briefcase') ?></div>
    <div class="stat-num">04</div>
    <div class="stat-label">Áreas de atuação</div>
  </div>
  <div class="stat">
    <div class="stat-icon"><?= svgIcon('pin') ?></div>
    <div class="stat-num">SMO</div>
    <div class="stat-label">São Miguel do Oeste / SC</div>
  </div>
  <div class="stat">
    <div class="stat-icon"><?= svgIcon('shield') ?></div>
    <div class="stat-num">Sigilo</div>
    <div class="stat-label">Em cada atendimento</div>
  </div>
</div>
