/**
 * blocks/team.js — Card de membro da equipe (foto + nome + cargo + bio).
 *
 * Tipo do backend: team_card (Phase 2 já tem TeamCardBlock.php — mesmo formato).
 *
 * Migrado para shape canônico content/style/layout (Tarefa 3).
 *
 * Namespaces:
 *   content: { name, role, bio, photo_url, oab, email }  (dados/conteúdo)
 *   style:   {}
 *   layout:  {}
 *
 * getSetting() garante compatibilidade com dados planos salvos antes da migração.
 */

import { field, fieldset } from '../ui/inspector-fields.js';
import { getSetting, mergeSettings } from '../core/tree.js';

export const TeamMemberBlock = {
  type:  'team_card',
  label: 'Membro da equipe',
  icon:  '👤',
  category: 'compostos',

  // ── Defaults em namespaces canônicos ──────────────────────────────────
  defaultSettings() {
    return {
      content: {
        name:      'Nome do Advogado',
        role:      'Advogado · OAB/SC',
        bio:       'Bacharel em Direito pela UNOESC. Atuação em...',
        photo_url: '',
        oab:       '',
        email:     '',
      },
      style:  {},
      layout: {},
    };
  },

  // ── Render — getSetting suporta flat legacy E namespaces ──────────────
  render(node) {
    const s   = node.settings;
    const art = document.createElement('article');
    art.className = 'block-team-card';

    const name      = getSetting(s, 'content', 'name',      '');
    const role      = getSetting(s, 'content', 'role',      '');
    const bio       = getSetting(s, 'content', 'bio',       '');
    const photoUrl  = getSetting(s, 'content', 'photo_url', '');
    const oab       = getSetting(s, 'content', 'oab',       '');
    const email     = getSetting(s, 'content', 'email',     '');

    // foto
    const photoWrap = document.createElement('div');
    if (photoUrl) {
      photoWrap.className = 'team-photo';
      const img = document.createElement('img');
      img.src     = photoUrl;
      img.alt     = name || 'Foto';
      img.loading = 'lazy';
      photoWrap.appendChild(img);
    } else {
      photoWrap.className = 'team-photo team-photo-empty';
    }
    art.appendChild(photoWrap);

    // body
    const body = document.createElement('div');
    body.className = 'team-body';
    if (name)  body.appendChild(h('h3', 'team-name', name));
    if (role)  body.appendChild(h('p',  'team-role', role));
    if (oab)   body.appendChild(h('p',  'team-oab',  'OAB ' + oab));
    if (bio)   body.appendChild(h('p',  'team-bio',  bio));
    if (email) {
      const a = document.createElement('a');
      a.className   = 'team-email';
      a.href        = 'mailto:' + email;
      a.textContent = email;
      body.appendChild(a);
    }
    art.appendChild(body);
    return art;
  },

  // ── Inspector — mergeSettings para update imutável por namespace ───────
  inspect(node, onChange) {
    const s = node.settings;

    const setNs = (ns, partial) =>
      onChange({ settings: mergeSettings(s, ns, partial) });

    return [
      fieldset('Identificação', [
        field('text', 'Nome',
          getSetting(s, 'content', 'name', ''),
          v => setNs('content', { name: v })
        ),
        field('text', 'Cargo',
          getSetting(s, 'content', 'role', ''),
          v => setNs('content', { role: v })
        ),
        field('text', 'OAB',
          getSetting(s, 'content', 'oab', ''),
          v => setNs('content', { oab: v }),
          { placeholder: 'SC/12345' }
        ),
        field('text', 'E-mail',
          getSetting(s, 'content', 'email', ''),
          v => setNs('content', { email: v }),
          { placeholder: 'nome@dominio.adv.br' }
        ),
      ]),
      fieldset('Foto', [
        field('text', 'URL da foto',
          getSetting(s, 'content', 'photo_url', ''),
          v => setNs('content', { photo_url: v }),
          { hint: 'Ex: /assets/images/guilherme.jpg' }
        ),
      ]),
      fieldset('Biografia', [
        field('textarea', 'Bio',
          getSetting(s, 'content', 'bio', ''),
          v => setNs('content', { bio: v }),
          { rows: 5 }
        ),
      ]),
    ];
  },
};

function h(tag, cls, txt) {
  const el = document.createElement(tag);
  el.className = cls;
  el.textContent = txt;
  return el;
}
