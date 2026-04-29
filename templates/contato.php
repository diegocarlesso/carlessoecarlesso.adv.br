<?php
// templates/contato.php — v1.2 (mapa sem API key)
if (!defined('CARLESSO_CMS')) exit;
require_once __DIR__ . '/../includes/csrf.php';

$telefone = getConfig('telefone',      '(49) 3621-2254');
$email    = getConfig('email_contato', 'contato@carlessoecarlesso.adv.br');
$endereco = getConfig('endereco',      'R. Duque de Caxias, 1413 – Sala 301, Centro, São Miguel do Oeste – SC');
$horario  = getConfig('horario',       'Segunda a Sexta, das 8h às 18h');
$introducao = getContent('contato', 'introducao');

// Mapa: 3 modos (em ordem de prioridade)
// 1. mapa_embed_html: HTML completo do iframe colado pelo admin (Google Maps "Compartilhar > Incorporar mapa")
// 2. mapa_lat / mapa_lng: gera embed via OpenStreetMap (sem chave de API)
// 3. fallback: link direto para Google Maps
$mapaEmbed = getConfig('mapa_embed_html', '');
$mapaLat   = getConfig('mapa_lat', '-26.7252');
$mapaLng   = getConfig('mapa_lng', '-53.5189');
?>

<p class="lead-text">
  <?= e($introducao['conteudo'] ?? 'Estamos à disposição para esclarecer dúvidas, agendar atendimentos e oferecer orientação jurídica especializada.') ?>
</p>

<div class="section-bar"><h2>CONTATO</h2></div>
<div class="section-bar-line"></div>

<div class="contact-layout">
  <div class="contact-info">

    <div class="item">
      <div class="ico"><?= svgIcon('pin') ?></div>
      <div>
        <div class="label">Endereço</div>
        <div class="val"><?= nl2br(e($endereco)) ?></div>
      </div>
    </div>

    <div class="item">
      <div class="ico"><?= svgIcon('phone') ?></div>
      <div>
        <div class="label">Telefone</div>
        <div class="val">
          <a href="tel:<?= e(preg_replace('/\D/', '', $telefone)) ?>"><?= e($telefone) ?></a>
        </div>
      </div>
    </div>

    <div class="item">
      <div class="ico"><?= svgIcon('envelope') ?></div>
      <div>
        <div class="label">E-mail</div>
        <div class="val"><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></div>
      </div>
    </div>

    <div class="item">
      <div class="ico"><?= svgIcon('clock') ?></div>
      <div>
        <div class="label">Horário de Atendimento</div>
        <div class="val"><?= e($horario) ?></div>
      </div>
    </div>

    <?php $ig = getConfig('instagram', ''); if ($ig && $ig !== '#'): ?>
    <div class="item">
      <div class="ico"><?= svgIcon('instagram') ?></div>
      <div>
        <div class="label">Instagram</div>
        <div class="val"><a href="<?= e($ig) ?>" target="_blank" rel="noopener">@carlessoecarlessoadv</a></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Mapa -->
    <div class="map-embed">
      <?php if (!empty($mapaEmbed)): ?>
        <!-- HTML personalizado colado pelo admin -->
        <div class="map-embed-html"><?= $mapaEmbed /* trusted: admin only */ ?></div>

      <?php elseif ($mapaLat && $mapaLng): ?>
        <!-- OpenStreetMap (sem API key) -->
        <iframe
          src="https://www.openstreetmap.org/export/embed.html?bbox=<?= floatval($mapaLng) - 0.005 ?>%2C<?= floatval($mapaLat) - 0.003 ?>%2C<?= floatval($mapaLng) + 0.005 ?>%2C<?= floatval($mapaLat) + 0.003 ?>&amp;layer=mapnik&amp;marker=<?= floatval($mapaLat) ?>%2C<?= floatval($mapaLng) ?>"
          loading="lazy"
          title="Mapa de localização">
        </iframe>
        <div style="text-align:center;padding:8px;font-size:.78rem;background:#f9fafb;border-top:1px solid #e5e7eb">
          <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($endereco) ?>"
             target="_blank" rel="noopener"
             style="color:#527095;font-weight:600;text-decoration:none">
            Abrir no Google Maps →
          </a>
        </div>

      <?php else: ?>
        <!-- Fallback: link direto -->
        <div class="map-fallback">
          <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($endereco) ?>"
             target="_blank" rel="noopener">
            <?= svgIcon('pin') ?> Ver no Google Maps
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="contact-form-wrap">
    <h2>Envie uma Mensagem</h2>
    <form class="contact-form" id="contact-form" novalidate>
      <!-- Token CSRF: usa HMAC stateless se disponível (Csrf.php v1.5+),
           senão cai pro session-based (compat com Csrf.php antigo). -->
      <?php
        $csrfToken = method_exists('\CSRF', 'generateStateless')
            ? \CSRF::generateStateless()
            : \CSRF::generate();
      ?>
      <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

      <div class="form-group">
        <label for="cf-nome">Nome completo *</label>
        <input type="text" id="cf-nome" name="nome" required placeholder="Seu nome" autocomplete="name">
      </div>

      <div class="form-group">
        <label for="cf-email">E-mail *</label>
        <input type="email" id="cf-email" name="email" required placeholder="seu@email.com" autocomplete="email">
      </div>

      <div class="form-group">
        <label for="cf-telefone">Telefone *</label>
        <input type="tel" id="cf-telefone" name="telefone" required placeholder="(49) 99999-9999" autocomplete="tel" pattern="[0-9()+\-\s]{8,20}" title="Informe um telefone válido (mínimo 8 dígitos)">
      </div>

      <div class="form-group">
        <label for="cf-assunto">Assunto</label>
        <select id="cf-assunto" name="assunto">
          <option value="">Selecione uma área...</option>
          <option>Direito Penal</option>
          <option>Direito Previdenciário</option>
          <option>Direito Civil</option>
          <option>Direito do Trabalho</option>
          <option>Outro</option>
        </select>
      </div>

      <div class="form-group">
        <label for="cf-msg">Mensagem *</label>
        <textarea id="cf-msg" name="mensagem" required placeholder="Descreva brevemente sua situação..."></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-full">
        <?= svgIcon('send') ?> Enviar Mensagem
      </button>

      <p class="form-disclaimer">
        Suas informações são tratadas com total sigilo e confidencialidade.
      </p>
    </form>
  </div>
</div>
