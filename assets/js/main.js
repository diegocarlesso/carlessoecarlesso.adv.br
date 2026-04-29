/* main.js — Carlesso & Carlesso Frontend */
(function () {
  'use strict';

  // ═══════════════════════════════════════════════════════════════════
  //  Proteção anti-cópia (obscuridade, não segurança real)
  //  Bloqueia: clique direito, F12, Ctrl+U, Ctrl+Shift+I/J/C, Ctrl+S, Ctrl+P
  //  Detecta DevTools aberto via diferença de tamanho window outer/inner
  //  Para desativar: adicione data-no-protect="1" ao <body>
  //  IMPORTANTE: usuário com conhecimento técnico contorna fácil. Nunca
  //  coloque informação sensível no HTML/JS confiando só nisso.
  // ═══════════════════════════════════════════════════════════════════
  if (!document.body.dataset.noProtect) {
    // Botão direito do mouse
    document.addEventListener('contextmenu', e => {
      e.preventDefault();
      return false;
    });

    // Atalhos de teclado para devtools / view source / save / print
    document.addEventListener('keydown', e => {
      const k = e.key.toLowerCase();
      const ctrl = e.ctrlKey || e.metaKey;

      // F12 (DevTools)
      if (e.key === 'F12') { e.preventDefault(); return false; }

      // Ctrl+U (view source)
      if (ctrl && k === 'u') { e.preventDefault(); return false; }

      // Ctrl+S (salvar página)
      if (ctrl && k === 's') { e.preventDefault(); return false; }

      // Ctrl+P (imprimir)
      if (ctrl && k === 'p') { e.preventDefault(); return false; }

      // Ctrl+Shift+I (DevTools), Ctrl+Shift+J (Console), Ctrl+Shift+C (Inspector)
      if (ctrl && e.shiftKey && (k === 'i' || k === 'j' || k === 'c')) {
        e.preventDefault();
        return false;
      }
    });

    // Bloqueia drag de imagens / texto
    document.addEventListener('dragstart', e => e.preventDefault());

    // Disable text selection (opcional — comentado pra não atrapalhar leitura)
    // document.body.style.userSelect = 'none';
    // document.body.style.webkitUserSelect = 'none';
    // Mantemos seleção para o usuário poder copiar trechos legítimos.
  }

  // ═══════════════════════════════════════════════════════════════════

  // ── Header scroll ──────────────────────────────────────
  const header = document.getElementById('site-header');
  if (header) {
    const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 60);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // ── Mobile menu ────────────────────────────────────────
  const toggle = document.querySelector('.menu-toggle');
  const nav    = document.querySelector('.site-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      nav.classList.toggle('open');
      const open = nav.classList.contains('open');
      toggle.setAttribute('aria-expanded', open);
      document.body.style.overflow = open ? 'hidden' : '';
    });

    // Fechar ao clicar fora
    document.addEventListener('click', e => {
      if (!header.contains(e.target) && nav.classList.contains('open')) {
        nav.classList.remove('open');
        document.body.style.overflow = '';
      }
    });

    // Toggle dropdown mobile
    nav.querySelectorAll('.nav-item > a').forEach(a => {
      if (a.nextElementSibling?.classList.contains('nav-dropdown')) {
        a.addEventListener('click', e => {
          if (window.innerWidth <= 768) {
            e.preventDefault();
            a.closest('.nav-item').classList.toggle('open');
          }
        });
      }
    });
  }

  // ── Active nav link ────────────────────────────────────
  const currentSlug = new URLSearchParams(location.search).get('slug') || '';
  document.querySelectorAll('.site-nav a').forEach(a => {
    const href = a.getAttribute('href') || '';
    if ((currentSlug && href.includes(currentSlug)) || (!currentSlug && href === '/')) {
      a.classList.add('active');
    }
  });

  // ── Intersection Observer (fade-in) ───────────────────
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('fade-in-up');
        observer.unobserve(e.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll('.service-card, .team-card, .fundamento-card, .post-card').forEach(el => {
    observer.observe(el);
  });

  // ── Smooth scroll para anchors ─────────────────────────
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const target = document.querySelector(a.getAttribute('href'));
      if (target) {
        e.preventDefault();
        const offset = (header?.offsetHeight ?? 90) + 16;
        window.scrollTo({ top: target.offsetTop - offset, behavior: 'smooth' });
      }
    });
  });

  // ── Formulário de contato (AJAX) ───────────────────────
  const contactForm = document.getElementById('contact-form');
  if (contactForm) {
    contactForm.addEventListener('submit', async e => {
      e.preventDefault();
      const btn = contactForm.querySelector('[type="submit"]');
      const csrfInput = contactForm.querySelector('input[name="_csrf"]');
      const originalBtnHtml = btn.innerHTML;
      btn.disabled = true;
      btn.textContent = 'Enviando…';

      // Best-effort refresh do token (silencioso)
      const refreshToken = async () => {
        try {
          const r = await fetch('/api/csrf-token.php', { credentials: 'same-origin', cache: 'no-store' });
          if (!r.ok) return false;
          const j = await r.json();
          if (j && j.token && csrfInput) { csrfInput.value = j.token; return true; }
        } catch (err) {
          console.warn('[contact] csrf-token refresh falhou:', err);
        }
        return false;
      };

      // Wrapper de envio que SEMPRE tenta parsear JSON; se receber HTML/erro,
      // captura o texto bruto pra log e devolve um objeto de erro estruturado.
      const send = async () => {
        const data = new FormData(contactForm);
        let res;
        try {
          res = await fetch('/api/contact.php', { method: 'POST', body: data, credentials: 'same-origin' });
        } catch (netErr) {
          console.error('[contact] erro de rede:', netErr);
          return { success: false, message: 'Erro de conexão. Verifique sua internet e tente de novo.' };
        }
        const text = await res.text();
        try {
          const parsed = JSON.parse(text);
          parsed._status = res.status;
          return parsed;
        } catch (parseErr) {
          console.error('[contact] resposta não-JSON do servidor (status', res.status, '):\n', text);
          return {
            success: false,
            message: 'Resposta inesperada do servidor (' + res.status + '). Tente WhatsApp ou ligue diretamente.',
            _status: res.status,
            _rawSnippet: text.slice(0, 500),
          };
        }
      };

      try {
        await refreshToken();
        let json = await send();

        // Retry uma vez em caso de "Sessão expirada"
        if (!json.success && /sess/i.test(json.message || '')) {
          console.info('[contact] CSRF falhou, retry com token novo');
          await refreshToken();
          json = await send();
        }

        showNotif(json.message || 'Mensagem enviada!', json.success ? 'success' : 'error');
        if (json.success) contactForm.reset();
        if (!json.success) {
          console.warn('[contact] falha:', json);
        }
      } finally {
        btn.disabled = false;
        btn.innerHTML = originalBtnHtml;
      }
    });
  }

  // ── Notificação toast ──────────────────────────────────
  function showNotif(msg, type = 'success') {
    const el = document.createElement('div');
    el.className = `notif notif-${type}`;
    el.textContent = msg;
    Object.assign(el.style, {
      position: 'fixed', bottom: '24px', right: '24px', zIndex: '9999',
      padding: '14px 20px', borderRadius: '6px', fontSize: '.9rem',
      fontFamily: 'Open Sans, sans-serif', fontWeight: '600',
      background: type === 'success' ? '#10b981' : '#ef4444',
      color: '#fff', boxShadow: '0 4px 20px rgba(0,0,0,.2)',
      transform: 'translateX(120%)', transition: 'transform .3s ease',
    });
    document.body.appendChild(el);
    requestAnimationFrame(() => { el.style.transform = 'translateX(0)'; });
    setTimeout(() => {
      el.style.transform = 'translateX(120%)';
      setTimeout(() => el.remove(), 400);
    }, 4000);
  }

})();
