/**
 * blocks/section.js — Container raiz. Agrupa colunas horizontalmente.
 *
 * Migrado para shape canônico content/style/layout (Tarefa 3).
 *
 * Namespaces:
 *   content: {}                                       (sem conteúdo textual)
 *   style:   { background_color, text_color }         (aparência visual)
 *   layout:  { container, padding }                   (estrutura/espaçamento)
 *
 * getSetting() cuida do fallback para keys flat em _legacy — garante
 * compatibilidade com dados salvos antes da migração.
 */

import { field, fieldset } from '../ui/inspector-fields.js';
import { getSetting, mergeSettings } from '../core/tree.js';

export const SectionBlock = {
  type:  'section',
  label: 'Seção',
  icon:  '▭',
  category: 'layout',
  isContainer: true,

  // ── Defaults em namespaces canônicos ──────────────────────────────────
  defaultSettings() {
    return {
      content: {},
      style:   { background_color: '', text_color: '' },
      layout:  { container: 'boxed', padding: 'lg' },
    };
  },

  defaultChildren() {
    // toda section nasce com 1 column 100%
    // (Store.addBlockFromDef usa createNode para gerar id real)
    return [
      { type: 'column', settings: { layout: { width: { desktop: 100 } } }, children: [] },
    ];
  },

  // ── Render — usa getSetting para suportar flat legacy E namespaces ─────
  render(node, ctx) {
    const s   = node.settings;
    const sec = document.createElement('section');
    sec.className = 'block-section';

    // getSetting faz: namespace → _legacy → flat direto (3 níveis de fallback)
    const container = getSetting(s, 'layout', 'container', 'boxed');
    const padding   = getSetting(s, 'layout', 'padding',   'lg');
    const bgColor   = getSetting(s, 'style',  'background_color', '');
    const txtColor  = getSetting(s, 'style',  'text_color',       '');

    if (container && container !== 'inherit') {
      sec.classList.add(`container-${container}`);
    }
    if (padding) {
      sec.classList.add(`p-d-${padding}`);
    }
    if (bgColor)  sec.style.backgroundColor = bgColor;
    if (txtColor) sec.style.color           = txtColor;

    const row = document.createElement('div');
    row.className = 'section-row';
    sec.appendChild(row);

    // ctx.renderChildren() retorna Array de HTMLElement já com data-node-id
    const childEls = ctx.renderChildren();
    if (childEls.length) {
      childEls.forEach(el => row.appendChild(el));
    } else {
      const empty = document.createElement('div');
      empty.className = 'builder-section-empty';
      empty.dataset.placeholder = 'true';
      empty.textContent = '+ Adicione colunas ou blocos pelo painel esquerdo';
      row.appendChild(empty);
    }

    return sec;
  },

  // ── Inspector — usa mergeSettings para update imutável por namespace ───
  inspect(node, onChange) {
    const s = node.settings;

    // setNs: aplica partial em um namespace e envia o settings completo
    const setNs = (ns, partial) =>
      onChange({ settings: mergeSettings(s, ns, partial) });

    return [
      fieldset('Layout', [
        field('select', 'Largura do container',
          getSetting(s, 'layout', 'container', 'boxed'),
          v => setNs('layout', { container: v }),
          { options: [
            ['boxed', 'Limitada (1200px)'],
            ['wide',  'Larga (1480px)'],
            ['full',  'Tela cheia'],
          ] }
        ),
        field('select', 'Espaçamento interno',
          getSetting(s, 'layout', 'padding', 'lg'),
          v => setNs('layout', { padding: v }),
          { options: [
            ['none','Nenhum'],['xs','XS'],['sm','SM'],['md','MD'],
            ['lg','LG'],['xl','XL'],['xxl','XXL'],
          ] }
        ),
      ]),
      fieldset('Cores', [
        field('color', 'Cor de fundo',
          getSetting(s, 'style', 'background_color', ''),
          v => setNs('style', { background_color: v })
        ),
        field('color', 'Cor do texto',
          getSetting(s, 'style', 'text_color', ''),
          v => setNs('style', { text_color: v })
        ),
      ]),
    ];
  },
};
