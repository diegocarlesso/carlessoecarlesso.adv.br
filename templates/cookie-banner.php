<?php
/**
 * templates/cookie-banner.php — Banner de Cookies LGPD (Lei 13.709/2018)
 * Carlesso & Carlesso Advogados Associados
 *
 * Inclua este template no footer.php, antes de </body>.
 * Requer cookie-consent.js carregado na mesma página.
 */
if (!defined('CARLESSO_CMS')) exit;
$siteTitle = getConfig('site_titulo', 'Carlesso & Carlesso Advogados Associados');
?>

<!-- ═══ LGPD: BANNER DE COOKIES ═══════════════════════════════════════ -->
<div id="lgpd-banner"
     role="dialog"
     aria-live="polite"
     aria-label="Aviso de Cookies"
     aria-describedby="lgpd-banner-desc"
     hidden>
  <div class="lgpd-banner-inner">
    <div class="lgpd-banner-text">
      <p id="lgpd-banner-desc">
        <strong>Utilizamos cookies</strong> para garantir o funcionamento do site e, com seu consentimento, para analisar o tráfego via Google Analytics. Nenhum dado é compartilhado com terceiros para fins publicitários.
        <a href="/privacidade" class="lgpd-link">Política de Privacidade</a>
      </p>
    </div>
    <div class="lgpd-banner-actions">
      <button class="lgpd-btn lgpd-btn--secondary" data-lgpd="accept-necessary">
        Apenas Necessários
      </button>
      <button class="lgpd-btn lgpd-btn--ghost" data-lgpd="open-panel">
        Personalizar
      </button>
      <button class="lgpd-btn lgpd-btn--primary" data-lgpd="accept-all">
        Aceitar Todos
      </button>
    </div>
  </div>
</div>

<!-- ═══ LGPD: PAINEL DE PREFERÊNCIAS ══════════════════════════════════ -->
<div id="lgpd-panel"
     role="dialog"
     aria-modal="true"
     aria-labelledby="lgpd-panel-title"
     hidden>
  <div class="lgpd-panel-box">

    <!-- Cabeçalho do painel -->
    <div class="lgpd-panel-header">
      <h2 id="lgpd-panel-title">Preferências de Privacidade</h2>
      <button class="lgpd-panel-close" data-lgpd="close-panel" aria-label="Fechar painel">
        <span aria-hidden="true">&#10005;</span>
      </button>
    </div>

    <div class="lgpd-panel-body">
      <p class="lgpd-panel-intro">
        Respeitamos sua privacidade conforme a <strong>Lei Geral de Proteção de Dados (Lei 13.709/2018)</strong>.
        Escolha quais categorias de cookies você autoriza. O consentimento pode ser alterado a qualquer momento.
      </p>

      <!-- Categoria: Necessários -->
      <div class="lgpd-category">
        <div class="lgpd-category-header">
          <div class="lgpd-category-info">
            <strong>Cookies Necessários</strong>
            <span class="lgpd-badge lgpd-badge--always">Sempre ativo</span>
          </div>
          <label class="lgpd-toggle lgpd-toggle--disabled" aria-label="Sempre ativo">
            <input type="checkbox" checked disabled>
            <span class="lgpd-toggle-track"></span>
          </label>
        </div>
        <p class="lgpd-category-desc">
          Essenciais para o funcionamento do site: autenticação de sessão, proteção CSRF e preferências de formulário. Não coletam dados pessoais identificáveis e não podem ser desativados.
          <br><strong>Base legal:</strong> Art. 7º, II — execução de contrato / legítimo interesse operacional.
        </p>
      </div>

      <!-- Categoria: Análise -->
      <div class="lgpd-category">
        <div class="lgpd-category-header">
          <div class="lgpd-category-info">
            <strong>Cookies de Análise</strong>
            <span class="lgpd-badge">Opcional</span>
          </div>
          <label class="lgpd-toggle" aria-label="Ativar cookies de análise">
            <input type="checkbox" id="toggle-analytics">
            <span class="lgpd-toggle-track"></span>
          </label>
        </div>
        <p class="lgpd-category-desc">
          Google Analytics (GA4) — coletam dados anônimos sobre páginas visitadas, tempo de permanência e origem do acesso, para melhorarmos o conteúdo do site. O IP é anonimizado.
          Nenhuma informação é cruzada com dados pessoais ou compartilhada com terceiros para fins comerciais.
          <br><strong>Base legal:</strong> Art. 7º, I — consentimento do titular.
        </p>
      </div>

      <!-- Categoria: Marketing (desativado / reservado) -->
      <div class="lgpd-category">
        <div class="lgpd-category-header">
          <div class="lgpd-category-info">
            <strong>Cookies de Marketing</strong>
            <span class="lgpd-badge">Opcional</span>
          </div>
          <label class="lgpd-toggle" aria-label="Ativar cookies de marketing">
            <input type="checkbox" id="toggle-marketing">
            <span class="lgpd-toggle-track"></span>
          </label>
        </div>
        <p class="lgpd-category-desc">
          Destinados a campanhas publicitárias personalizadas em plataformas externas (ex: Meta, LinkedIn). Atualmente não utilizamos cookies de marketing — esta categoria está prevista para uso futuro e estará sempre sujeita ao seu consentimento prévio.
          <br><strong>Base legal:</strong> Art. 7º, I — consentimento do titular.
        </p>
      </div>

      <!-- Direitos do Titular -->
      <div class="lgpd-rights">
        <h3>Seus Direitos (LGPD, Art. 18)</h3>
        <p>
          Você tem direito a: <strong>confirmar</strong> o tratamento, <strong>acessar</strong> seus dados, <strong>corrigir</strong> informações incompletas, <strong>solicitar exclusão</strong> de dados tratados com base em consentimento, <strong>revogar</strong> o consentimento a qualquer momento e <strong>solicitar portabilidade</strong>. Para exercer esses direitos, entre em contato pelo
          <a href="mailto:<?= e(getConfig('email_contato', 'contato@carlessoecarlesso.adv.br')) ?>" class="lgpd-link">
            <?= e(getConfig('email_contato', 'contato@carlessoecarlesso.adv.br')) ?>
          </a>.
        </p>
      </div>
    </div><!-- .lgpd-panel-body -->

    <!-- Ações do painel -->
    <div class="lgpd-panel-footer">
      <button class="lgpd-btn lgpd-btn--secondary" data-lgpd="accept-necessary">
        Apenas Necessários
      </button>
      <div class="lgpd-panel-footer-right">
        <button class="lgpd-btn lgpd-btn--primary" data-lgpd="save-prefs">
          Salvar Preferências
        </button>
        <button class="lgpd-btn lgpd-btn--primary" data-lgpd="accept-all">
          Aceitar Todos
        </button>
      </div>
    </div>

  </div><!-- .lgpd-panel-box -->
</div><!-- #lgpd-panel -->

<!-- ═══ Botão flutuante: reabrir preferências ════════════════════════ -->
<button id="lgpd-reopen-btn"
        data-lgpd="open-panel"
        aria-label="Gerenciar preferências de cookies"
        title="Gerenciar cookies"
        style="display:none">
  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
    <path d="M21.598 11.064a1 1 0 0 0-.854-.172A2.938 2.938 0 0 1 20 11c-1.654 0-3-1.346-3.003-2.937.005-.034.016-.136.017-.17a1 1 0 0 0-1.297-1.034 2.962 2.962 0 0 1-3.587-2.744C12.13 3.547 11.8 3 11.064 3A9.015 9.015 0 0 0 3 11c0 4.963 4.037 9 9 9s9-4.037 9-9c0-.242-.016-.482-.042-.72a1 1 0 0 0-.36-.216zM12 18c-3.859 0-7-3.14-7-7a7.01 7.01 0 0 1 5.567-6.86A4.956 4.956 0 0 0 15 8c.08 0 .158-.005.237-.009A4.978 4.978 0 0 0 19.95 12.3 7.007 7.007 0 0 1 12 18z"/>
    <circle cx="9" cy="13" r="1.25"/>
    <circle cx="11.5" cy="16.5" r="1.25"/>
    <circle cx="14.5" cy="13.5" r="1.5"/>
  </svg>
</button>
