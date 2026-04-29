/**
 * blocks/team.js — Card de membro da equipe (foto + nome + cargo + bio).
 *
 * Tipo do backend: team_card (Phase 2 já tem TeamCardBlock.php — mesmo formato).
 */

import { field, fieldset } from '../ui/inspector-fields.js';

export const TeamMemberBlock = {
  type:  'team_card',
  label: 'Membro da equipe',
  icon:  '👤',
  category: 'compostos',

  defaultSettings() {
    return {
      name:      'Nome do Advogado',
      role:      'Advogado · OAB/SC',
      bio:       'Bacharel em Direito pela UNOESC. Atuação em...',
      photo_url: '',
      oab:       '',
      email:     '',
    };
  },

  render(node) {
    const s   = node.settings;
    const art = document.createElement('article');
    art.className = 'block-team-card';

    // foto
    const photoWrap = document.createElement('div');
    if (s.photo_url) {
      photoWrap.className = 'team-photo';
      const img = document.createElement('img');
      img.src = s.photo_url;
      img.alt = s.name || 'Foto';
      img.loading = 'lazy';
      photoWrap.appendChild(img);
    } else {
      photoWrap.className = 'team-photo team-photo-empty';
    }
    art.appendChild(photoWrap);

    // body
    const body = document.createElement('div');
    body.className = 'team-body';
    if (s.name) body.appendChild(h('h3', 'team-name', s.name));
    if (s.role) body.appendChild(h('p',  'team-role', s.role));
    if (s.oab)  body.appendChild(h('p',  'team-oab',  'OAB ' + s.oab));
    if (s.bio)  body.appendChild(h('p',  'team-bio',  s.bio));
    if (s.email) {
      const a = document.createElement('a');
      a.className = 'team-email';
      a.href = 'mailto:' + s.email;
      a.textContent = s.email;
      body.appendChild(a);
    }
    art.appendChild(body);
    return art;
  },

  inspect(node, onChange) {
    const s = node.settings;
    return [
      fieldset('Identificação', [
        field('text', 'Nome',      s.name || '',  v => onChange({ settings: { name: v } })),
        field('text', 'Cargo',     s.role || '',  v => onChange({ settings: { role: v } })),
        field('text', 'OAB',       s.oab  || '',  v => onChange({ settings: { oab: v } }),
          { placeholder: 'SC/12345' }),
        field('text', 'E-mail',    s.email || '', v => onChange({ settings: { email: v } }),
          { placeholder: 'nome@dominio.adv.br' }),
      ]),
      fieldset('Foto', [
        field('text', 'URL da foto', s.photo_url || '',
          v => onChange({ settings: { photo_url: v } }),
          { hint: 'Ex: /assets/images/guilherme.jpg' }),
      ]),
      fieldset('Biografia', [
        field('textarea', 'Bio', s.bio || '',
          v => onChange({ settings: { bio: v } }),
          { rows: 5 }),
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
