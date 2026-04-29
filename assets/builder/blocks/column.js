/**
 * blocks/column.js — Coluna dentro de uma section.
 * Container de blocos verticais.
 */

import { field, fieldset } from '../ui/inspector-fields.js';

const WIDTH_OPTIONS = [
  ['25', '25%'], ['33', '33%'], ['50', '50%'],
  ['66', '66%'], ['75', '75%'], ['100', '100%'],
];

export const ColumnBlock = {
  type:  'column',
  label: 'Coluna',
  icon:  '▯',
  category: 'layout',
  isContainer: true,

  defaultSettings() {
    return { width: { desktop: 100 }, vertical_align: 'top', gap: 'md' };
  },

  render(node, ctx) {
    const s   = node.settings;
    const col = document.createElement('div');
    col.className = 'block-col';

    const w = parseInt(s.width?.desktop ?? 100);
    if ([25, 33, 50, 66, 75, 100].includes(w)) col.classList.add(`col-d-${w}`);
    else col.classList.add('col-d-100');

    if (s.vertical_align && ['top','middle','bottom'].includes(s.vertical_align)) {
      col.classList.add(`valign-${s.vertical_align}`);
    }
    if (s.gap && ['none','sm','md','lg','xl'].includes(s.gap)) {
      col.classList.add(`gap-${s.gap}`);
    }

    if (Array.isArray(node.children) && node.children.length) {
      node.children.forEach(child => col.appendChild(ctx.render(child)));
    } else {
      const empty = document.createElement('div');
      empty.className = 'builder-col-empty';
      empty.textContent = '+';
      empty.title = 'Coluna vazia';
      col.appendChild(empty);
    }
    return col;
  },

  inspect(node, onChange) {
    const s = node.settings;
    return [
      fieldset('Dimensões', [
        field('select', 'Largura (desktop)', String(s.width?.desktop ?? 100),
          v => onChange({ settings: { width: { ...(s.width || {}), desktop: parseInt(v) } } }),
          { options: WIDTH_OPTIONS }
        ),
      ]),
      fieldset('Alinhamento', [
        field('select', 'Alinhamento vertical', s.vertical_align || 'top',
          v => onChange({ settings: { vertical_align: v } }),
          { options: [['top','Topo'],['middle','Meio'],['bottom','Base']] }
        ),
        field('select', 'Espaço entre blocos', s.gap || 'md',
          v => onChange({ settings: { gap: v } }),
          { options: [['none','Nenhum'],['sm','SM'],['md','MD'],['lg','LG'],['xl','XL']] }
        ),
      ]),
    ];
  },
};
