/**
 * cookie-consent.js — Consentimento de Cookies LGPD (Lei 13.709/2018)
 * Carlesso & Carlesso Advogados Associados
 *
 * Categorias:
 *   necessary  — sempre ativo (sessão, CSRF, segurança)
 *   analytics  — Google Analytics (GA4) — requer consentimento
 *   marketing  — reservado para uso futuro
 *
 * Armazenamento: cookie HTTP `carlesso_lgpd` (12 meses)
 * Consent Mode: Google Consent Mode v2 via gtag()
 */

(function () {
  'use strict';

  // ── Configuração ───────────────────────────────────────────────────────────
  const COOKIE_NAME    = 'carlesso_lgpd';
  const COOKIE_DAYS    = 365;
  const COOKIE_VERSION = 1;

  // ── Utilitários de cookie ──────────────────────────────────────────────────

  function setCookie(name, value, days) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    const secure  = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = name + '=' + encodeURIComponent(value)
      + '; expires=' + expires
      + '; path=/'
      + '; SameSite=Lax'
      + secure;
  }

  function getCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
  }

  function getConsent() {
    try {
      const raw = getCookie(COOKIE_NAME);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  function saveConsent(prefs) {
    prefs.timestamp = new Date().toISOString();
    prefs.version   = COOKIE_VERSION;
    setCookie(COOKIE_NAME, JSON.stringify(prefs), COOKIE_DAYS);
  }

  // ── Google Consent Mode v2 ─────────────────────────────────────────────────

  function applyGoogleConsent(prefs) {
    if (typeof gtag !== 'function') return;
    gtag('consent', 'update', {
      analytics_storage:  prefs.analytics  ? 'granted' : 'denied',
      ad_storage:         prefs.marketing  ? 'granted' : 'denied',
      ad_user_data:       prefs.marketing  ? 'granted' : 'denied',
      ad_personalization: prefs.marketing  ? 'granted' : 'denied',
    });
  }

  // ── UI: Banner e Painel de Preferências ───────────────────────────────────

  function showBanner() {
    const banner = document.getElementById('lgpd-banner');
    if (banner) banner.removeAttribute('hidden');
  }

  function hideBanner() {
    const banner = document.getElementById('lgpd-banner');
    if (banner) {
      banner.setAttribute('hidden', '');
      banner.setAttribute('aria-hidden', 'true');
    }
  }

  function showPanel() {
    const panel = document.getElementById('lgpd-panel');
    if (!panel) return;
    panel.removeAttribute('hidden');
    panel.setAttribute('aria-modal', 'true');
    document.body.style.overflow = 'hidden';
    // Foco no primeiro elemento interativo
    const first = panel.querySelector('button, [tabindex]');
    if (first) setTimeout(() => first.focus(), 50);
  }

  function hidePanel() {
    const panel = document.getElementById('lgpd-panel');
    if (!panel) return;
    panel.setAttribute('hidden', '');
    panel.removeAttribute('aria-modal');
    document.body.style.overflow = '';
  }

  function syncToggles(prefs) {
    const tAnalytics = document.getElementById('toggle-analytics');
    const tMarketing = document.getElementById('toggle-marketing');
    if (tAnalytics) tAnalytics.checked = !!prefs.analytics;
    if (tMarketing) tMarketing.checked = !!prefs.marketing;
  }

  // ── Ações ─────────────────────────────────────────────────────────────────

  function acceptAll() {
    const prefs = { necessary: true, analytics: true, marketing: true };
    saveConsent(prefs);
    applyGoogleConsent(prefs);
    hideBanner();
    hidePanel();
    updateToggleButton(true);
  }

  function acceptNecessary() {
    const prefs = { necessary: true, analytics: false, marketing: false };
    saveConsent(prefs);
    applyGoogleConsent(prefs);
    hideBanner();
    hidePanel();
    updateToggleButton(true);
  }

  function savePreferences() {
    const tAnalytics = document.getElementById('toggle-analytics');
    const tMarketing = document.getElementById('toggle-marketing');
    const prefs = {
      necessary: true,
      analytics: tAnalytics ? tAnalytics.checked : false,
      marketing: tMarketing ? tMarketing.checked : false,
    };
    saveConsent(prefs);
    applyGoogleConsent(prefs);
    hideBanner();
    hidePanel();
    updateToggleButton(true);
  }

  // Botão flutuante para reabrir preferências
  function updateToggleButton(show) {
    const btn = document.getElementById('lgpd-reopen-btn');
    if (!btn) return;
    btn.style.display = show ? 'flex' : 'none';
  }

  // ── Inicialização ─────────────────────────────────────────────────────────

  function init() {
    const existing = getConsent();

    if (existing) {
      // Consentimento já registrado — aplica e não mostra banner
      applyGoogleConsent(existing);
      updateToggleButton(true);
    } else {
      // Primeira visita — exibe banner
      showBanner();
      updateToggleButton(false);
    }

    // ── Event listeners ──────────────────────────────────────────────────

    // Aceitar todos
    document.querySelectorAll('[data-lgpd="accept-all"]').forEach(el => {
      el.addEventListener('click', acceptAll);
    });

    // Apenas necessários
    document.querySelectorAll('[data-lgpd="accept-necessary"]').forEach(el => {
      el.addEventListener('click', acceptNecessary);
    });

    // Abrir painel de preferências
    document.querySelectorAll('[data-lgpd="open-panel"]').forEach(el => {
      el.addEventListener('click', () => {
        const prefs = getConsent() || { analytics: false, marketing: false };
        syncToggles(prefs);
        hidePanel(); // garante estado limpo
        showPanel();
        hideBanner();
      });
    });

    // Fechar painel sem salvar
    document.querySelectorAll('[data-lgpd="close-panel"]').forEach(el => {
      el.addEventListener('click', () => {
        hidePanel();
        if (!getConsent()) showBanner();
      });
    });

    // Salvar preferências personalizadas
    document.querySelectorAll('[data-lgpd="save-prefs"]').forEach(el => {
      el.addEventListener('click', savePreferences);
    });

    // Botão flutuante: abre painel
    const reopenBtn = document.getElementById('lgpd-reopen-btn');
    if (reopenBtn) {
      reopenBtn.addEventListener('click', () => {
        const prefs = getConsent() || { analytics: false, marketing: false };
        syncToggles(prefs);
        showPanel();
      });
    }

    // Fechar painel ao clicar no backdrop
    const panel = document.getElementById('lgpd-panel');
    if (panel) {
      panel.addEventListener('click', e => {
        if (e.target === panel) {
          hidePanel();
          if (!getConsent()) showBanner();
        }
      });
      // ESC fecha o painel
      document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !panel.hasAttribute('hidden')) {
          hidePanel();
          if (!getConsent()) showBanner();
        }
      });
    }
  }

  // Aguarda o DOM estar pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
