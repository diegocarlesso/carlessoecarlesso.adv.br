<?php
// templates/footer.php
if (!defined('CARLESSO_CMS')) exit;

$siteTitle  = getConfig('site_titulo', 'Carlesso & Carlesso Advogados Associados');
$footerText = getCustomization('footer_text', 'Todos os direitos reservados.');
$telefone   = getConfig('telefone',      '(49) 3621-2254');
$whatsapp   = getConfig('whatsapp',      preg_replace('/\D/', '', $telefone));
$email      = getConfig('email_contato', 'contato@carlessoecarlesso.adv.br');
$endereco   = getConfig('endereco',      'R. Duque de Caxias, 1413 – Sala 301, Centro, São Miguel do Oeste – SC');
$horario    = getConfig('horario',       'Segunda a Sexta, das 8h às 18h');
$instagram  = getConfig('instagram', '#');
$facebook   = getConfig('facebook',  '#');
$linkedin   = getConfig('linkedin',  '#');
$year       = date('Y');
?>

<footer id="site-footer" role="contentinfo">
  <div class="container">
    <div class="footer-grid">

      <!-- Coluna 1: Brand -->
      <div class="footer-brand">
        <a href="/" class="footer-logo" aria-label="<?= e($siteTitle) ?>">
          <img src="/assets/images/logo_sem_texto.png" alt="" width="46" height="46" aria-hidden="true">
          <div class="footer-logo-text">
            <span class="name">CARLESSO &amp; CARLESSO</span>
            <span class="subtitle">ADVOGADOS ASSOCIADOS</span>
          </div>
        </a>
        <p class="footer-tagline">
          Excelência e compromisso na defesa dos seus direitos em São Miguel do Oeste/SC e região.
        </p>
        <div class="footer-social">
          <?php if ($instagram && $instagram !== '#'): ?>
          <a href="<?= e($instagram) ?>" target="_blank" rel="noopener" aria-label="Instagram">
            <span class="i i-instagram"></span>
          </a>
          <?php endif; ?>
          <?php if ($facebook && $facebook !== '#'): ?>
          <a href="<?= e($facebook) ?>" target="_blank" rel="noopener" aria-label="Facebook">
            <span class="i i-facebook"></span>
          </a>
          <?php endif; ?>
          <?php if ($linkedin && $linkedin !== '#'): ?>
          <a href="<?= e($linkedin) ?>" target="_blank" rel="noopener" aria-label="LinkedIn">
            <span class="i i-linkedin"></span>
          </a>
          <?php endif; ?>
          <?php if ($whatsapp): ?>
          <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $whatsapp)) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
            <span class="i i-whatsapp"></span>
          </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Coluna 2: Navegação -->
      <nav class="footer-nav" aria-label="Navegação rodapé">
        <h4>Navegação</h4>
        <ul>
          <li><a href="/">Início</a></li>
          <li><a href="/escritorio">Escritório</a></li>
          <li><a href="/equipe">Equipe</a></li>
          <li><a href="/fundamentos">Nossos Fundamentos</a></li>
          <li><a href="/servicos">Serviços</a></li>
          <li><a href="/producoes">Produções</a></li>
          <li><a href="/contato">Contato</a></li>
        </ul>
      </nav>

      <!-- Coluna 3: Contato -->
      <div class="footer-contact">
        <h4>Contato</h4>
        <p>
          <span class="i i-pin" aria-hidden="true"></span>
          <?= nl2br(e($endereco)) ?>
        </p>
        <p>
          <span class="i i-envelope" aria-hidden="true"></span>
          <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
        </p>
        <p>
          <span class="i i-phone" aria-hidden="true"></span>
          <a href="tel:<?= e(preg_replace('/\D/', '', $telefone)) ?>"><?= e($telefone) ?></a>
        </p>
        <p>
          <span class="i i-clock" aria-hidden="true"></span>
          <?= e($horario) ?>
        </p>
      </div>

    </div><!-- .footer-grid -->

    <div class="footer-bottom">
      <span>&copy; <?= $year ?> <?= e($siteTitle) ?>. <?= e($footerText) ?></span>
      <span>OAB/SC</span>
    </div>
  </div>
</footer>

<!-- Scripts -->
<script src="/assets/js/main.js" defer></script>
</body>
</html>
