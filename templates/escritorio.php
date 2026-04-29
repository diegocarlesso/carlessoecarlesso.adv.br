<?php
// templates/escritorio.php
if (!defined('CARLESSO_CMS')) exit;
$historia = getContent('escritorio', 'historia');
?>

<!-- Faixa: NOSSA HISTÓRIA (mesmo padrão de Equipe/Fundamentos) -->
<div class="section-bar">
  <h2><?= e(strtoupper($historia['titulo'] ?? 'NOSSA HISTÓRIA')) ?></h2>
</div>
<div class="section-bar-line"></div>

<article class="fundamento-card fundamento-card--full" id="historia">
  <?php if (!empty($historia['conteudo'])): ?>
    <div class="rich-content"><?= sanitizeHtml($historia['conteudo']) ?></div>
  <?php else: ?>
    <p>A história do escritório Carlesso e Carlesso Advogados Associados é datada em 1º de junho de 2012 e idealizada pelo sócio fundador, Jean Carlos Carlesso, em parceria com dois colegas de graduação. À época, a sociedade atuava sob a denominação – Carlesso e Minuscolli Advogados Associados.</p>
    <p>Com o passar dos anos e em decorrência de reestruturações internas, aconteceu a saída dos então sócios, sendo que, em novembro de 2016, passou a integrar a sociedade o advogado Guilherme Carlesso, em conjunto com a Advogada Nelita Muller e Jhyonnattann C. Ganzer, dando origem ao escritório – Carlesso e Ganzer Advogados Associados.</p>
    <p>Posteriormente, em meio às transformações impostas pelo cenário da pandemia, ocorreram novas mudanças societárias, culminando com a saída dos sócios Nelita Muller e Jhyonnattann C. Ganzer. Assim, em 24 de agosto de 2021, consolidou-se a atual estrutura sob a denominação – Carlesso e Carlesso Advogados Associados, marca que representa, até hoje, a identidade e os valores do escritório.</p>
    <p>Nosso trabalho valoriza o respeito com cada pessoa que chega até nós. Confiança, sigilo e zelo nos processos, são fundamentos que priorizamos.</p>
  <?php endif; ?>
</article>

