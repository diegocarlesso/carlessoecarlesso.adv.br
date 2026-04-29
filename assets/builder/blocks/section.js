/**
 * blocks/section.js — Container raiz. Agrupa colunas horizontalmente.
 *
 * É um bloco-container: render() chama ctx.render(child) recursivamente
 * para cada filho.
 */

import { field, fieldset } from '../ui/inspector-fields.js';

export const SectionBlock = {
  type:  'section',
  label: 'Seção',
  icon:  '▭',
  category: 'layout',
  isContainer: true,

  defaultSettings() {
    return {
      container:        'boxed',
      background_color: '',
      text_color:       '',
      padding:          'lg',
    };
  },

  defaultChildren() {
    // toda section nasce com 1 column 100%
    // (o app.js usa createNode pra dar id real)
    return [
      { type: 'column', settings: { width: { desktop: 100 } }, children: [] },
    ];
  },

  render(node, ctx) {
    const s   = node.settings;
    const sec = document.createElement('section');
    sec.className = 'block-section';

    if (s.container && s.container !== 'inherit') {
      sec.classList.add(`container-${s.container}`);
    }
    if (s.padding) {
      sec.classList.add(`p-d-${s.padding}`);
    }
    if (s.background_color) sec.style.backgroundColor = s.background_color;
    if (s.text_color)       sec.style.color           = s.text_color;

    const row = document.createElement('div');
    row.className = 'section-row';
    sec.appendChild(row);

    if (Array.isArray(node.children) && node.children.length) {
      node.children.forEach(child => row.appendChild(ctx.render(child)));
    } else {
      const empty = document.createElement('div');
      empty.className = 'builder-section-empty';
      empty.textContent = '+ Adicione blocos pelo painel esquerdo';
      row.appendChild(empty);
    }
    return sec;
  },

  inspect(node, onChange) {
    const s = node.settings;
    return [
      fieldset('Layout', [
        field('select', 'Largura', s.container || 'boxed',
          v => onChange({ settings: { container: v } }),
          { options: [
            ['boxed', 'Limitada (1200px)'],
            ['wide',  'Larga (1480px)'],
            ['full',  'Tela cheia'],
          ] }
        ),
        field('select', 'Espaçamento interno', s.padding || 'lg',
          v => onChange({ settings: { padding: v } }),
          { options: [
            ['none','Nenhum'],['xs','XS'],['sm','SM'],['md','MD'],
            ['lg','LG'],['xl','XL'],['xxl','XXL']
          ] }
        ),
      ]),
      fieldset('Cores', [
        field('color', 'Cor de fundo', s.background_color || '',
          v => onChange({ settings: { background_color: v } })),
        field('color', 'Cor do texto', s.text_color || '',
          v => onChange({ settings: { text_color: v } })),
      ]),
    ];
  },
};
