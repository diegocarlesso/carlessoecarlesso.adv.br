/**
 * blocks/heading.js — Bloco de título (h1–h6).
 *
 * Settings agora estão organizados em 3 namespaces:
 *
 *   content: { text, level }     → o QUE
 *   style:   { color }           → COMO (visual)
 *   layout:  { align }           → ONDE (alinhamento)
 *
 * Ao ler, usamos `getSetting(settings, ns, key, default)` que aceita
 * o shape canônico E faz fallback para keys flat (compat com dados
 * antigos pré-namespacing — não jogamos away).
 *
 * Ao escrever (inspector), usamos `mergeSettings()` para gerar um novo
 * settings com APENAS o namespace alvo mesclado, mantendo os outros
 * intactos.
 */

import { field, fieldset } from '../ui/inspector-fields.js';
import { getSetting, mergeSettings } from '../core/tree.js';

export const HeadingBlock = {
  type:  'heading',
  label: 'Título',
  icon:  'H',
  category: 'basico',

  // ── Defaults: shape canônico ─────────────────────────────────────────
  defaultSettings() {
    return {
      content: { text: 'Novo título', level: 2 },
      style:   { color: '' },
      layout:  { align: 'left' },
    };
  },

  // ── Render ───────────────────────────────────────────────────────────
  render(node) {
    const s = node.settings;
    const level = clamp(parseInt(getSetting(s, 'content', 'level', 2)) || 2, 1, 6);
    const text  = String(getSetting(s, 'content', 'text', 'Novo título') ?? '');
    const color = String(getSetting(s, 'style', 'color', '') ?? '');
    const align = String(getSetting(s, 'layout', 'align', 'left') ?? 'left');

    const el = document.createElement(`h${level}`);
    el.className = `block-heading block-heading-h${level}`;
    el.textContent = text;
    if (color) el.style.color = color;
    if (align) el.style.textAlign = align;
    return el;
  },

  // ── Inspector ────────────────────────────────────────────────────────
  inspect(node, onChange) {
    const s = node.settings;

    // Helper local: gera updates só do namespace alvo, deixando os outros
    // intactos. Inspector já chama store.updateBlock(id, updates).
    const setNs = (ns, partial) => {
      onChange({ settings: mergeSettings(s, ns, partial) });
    };

    return [
      // ── CONTENT ──
      fieldset('Conteúdo', [
        field('text',
          'Texto',
          getSetting(s, 'content', 'text', ''),
          v => setNs('content', { text: v })
        ),
        field('select',
          'Nível',
          String(getSetting(s, 'content', 'level', 2)),
          v => setNs('content', { level: parseInt(v) || 2 }),
          { options: [['1','H1'],['2','H2'],['3','H3'],['4','H4'],['5','H5'],['6','H6']] }
        ),
      ]),

      // ── STYLE ──
      fieldset('Estilo', [
        field('color',
          'Cor do texto',
          getSetting(s, 'style', 'color', ''),
          v => setNs('style', { color: v })
        ),
      ]),

      // ── LAYOUT ──
      fieldset('Layout', [
        field('select',
          'Alinhamento',
          getSetting(s, 'layout', 'align', 'left'),
          v => setNs('layout', { align: v }),
          { options: [['left','Esquerda'],['center','Centro'],['right','Direita']] }
        ),
      ]),
    ];
  },
};

function clamp(n, min, max) { return Math.min(max, Math.max(min, n)); }
