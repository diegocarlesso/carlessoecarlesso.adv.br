/* main.js — Carlesso & Carlesso Frontend */
(function () {
  'use strict';

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
      const btn  = contactForm.querySelector('[type="submit"]');
      const data = new FormData(contactForm);
      btn.disabled = true;
      btn.textContent = 'Enviando…';

      try {
        const res  = await fetch('/api/contact.php', { method: 'POST', body: data });
        const json = await res.json();
        showNotif(json.message || 'Mensagem enviada!', json.success ? 'success' : 'error');
        if (json.success) contactForm.reset();
      } catch {
        showNotif('Erro ao enviar mensagem.', 'error');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Enviar Mensagem';
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
