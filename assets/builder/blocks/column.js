/**
 * blocks/column.js — Coluna dentro de uma section.
 * Container de blocos verticais.
 *
 * Migrado para shape canônico content/style/layout (Tarefa 3).
 *
 * Namespaces:
 *   content: {}                                          (sem conteúdo textual)
 *   style:   { background_color }                        (opcional)
 *   layout:  { width, vertical_align, gap }              (estrutura)
 *
 * Render usa ctx.renderChildren() — retorna Array<HTMLElement> com todos
 * os filhos já marcados com data-node-id/data-depth/etc.
 */

import { field, fieldset } from '../ui/inspector-fields.js';
import { getSetting, mergeSettings } from '../core/tree.js';

const WIDTH_OPTIONS = [
  ['25', '25%'], ['33', '33%'], ['50', '50%'],
  ['66', '66%'], ['75', '75%'], ['100', '100%'],
];

const VALID_WIDTHS   = [25, 33, 50, 66, 75, 100];
const VALID_VALIGN   = ['top', 'middle', 'bottom'];
const VALID_GAPS     = ['none', 'sm', 'md', 'lg', 'xl'];

export const ColumnBlock = {
  type:  'column',
  label: 'Coluna',
  icon:  '▯',
  category: 'layout',
  isContainer: true,

  // ── Defaults em namespaces canônicos ──────────────────────────────────
  defaultSettings() {
    return {
      content: {},
      style:   { background_color: '' },
      layout:  { width: { desktop: 100 }, vertical_align: 'top', gap: 'md' },
    };
  },

  // ── Render — getSetting suporta flat legacy E namespaces ──────────────
  render(node, ctx) {
    const s   = node.settings;
    const col = document.createElement('div');
    col.className = 'block-col';

    // width pode ser objeto { desktop: N } ou legado flat (número/string)
    const widthRaw    = getSetting(s, 'layout', 'width', { desktop: 100 });
    const widthInt    = typeof widthRaw === 'object'
      ? parseInt(widthRaw.desktop ?? 100)
      : parseInt(widthRaw ?? 100);
    const w = VALID_WIDTHS.includes(widthInt) ? widthInt : 100;
    col.classList.add(`col-d-${w}`);

    const valign = getSetting(s, 'layout', 'vertical_align', 'top');
    if (VALID_VALIGN.includes(valign)) col.classList.add(`valign-${valign}`);

    const gap = getSetting(s, 'layout', 'gap', 'md');
    if (VALID_GAPS.includes(gap)) col.classList.add(`gap-${gap}`);

    const bgColor = getSetting(s, 'style', 'background_color', '');
    if (bgColor) col.style.backgroundColor = bgColor;

    // ctx.renderChildren() retorna Array<HTMLElement> — já com todos os markers
    const childEls = ctx.renderChildren();
    if (childEls.length) {
      childEls.forEach(el => col.appendChild(el));
    } else {
      const empty = document.createElement('div');
      empty.className = 'builder-col-empty';
      empty.dataset.placeholder = 'true';
      empty.textContent = '+';
      empty.title = 'Coluna vazia — clique em um bloco no painel esquerdo';
      col.appendChild(empty);
    }

    return col;
  },

  // ── Inspector — usa mergeSettings para update imutável por namespace ───
  inspect(node, onChange) {
    const s = node.settings;

    const setNs = (ns, partial) =>
      onChange({ settings: mergeSettings(s, ns, partial) });

    // Lê width como objeto { desktop: N }, compatível com flat legado
    const widthRaw   = getSetting(s, 'layout', 'width', { desktop: 100 });
    const widthVal   = typeof widthRaw === 'object'
      ? String(widthRaw.desktop ?? 100)
      : String(widthRaw ?? 100);
    const currentWidth = widthRaw && typeof widthRaw === 'object'
      ? { ...widthRaw }
      : { desktop: parseInt(widthRaw ?? 100) };

    return [
      fieldset('Dimensões', [
        field('select', 'Largura (desktop)', widthVal,
          v => setNs('layout', { width: { ...currentWidth, desktop: parseInt(v) } }),
          { options: WIDTH_OPTIONS }
        ),
      ]),
      fieldset('Alinhamento', [
        field('select', 'Alinhamento vertical',
          getSetting(s, 'layout', 'vertical_align', 'top'),
          v => setNs('layout', { vertical_align: v }),
          { options: [['top','Topo'],['middle','Meio'],['bottom','Base']] }
        ),
        field('select', 'Espaço entre blocos',
          getSetting(s, 'layout', 'gap', 'md'),
          v => setNs('layout', { gap: v }),
          { options: [['none','Nenhum'],['sm','SM'],['md','MD'],['lg','LG'],['xl','XL']] }
        ),
      ]),
      fieldset('Cores', [
        field('color', 'Cor de fundo',
          getSetting(s, 'style', 'background_color', ''),
          v => setNs('style', { background_color: v })
        ),
      ]),
    ];
  },
};
