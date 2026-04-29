/**
 * blocks/text.js — Bloco de texto rico (rich_text).
 *
 * Settings em 3 namespaces:
 *   content: { html }       → o QUE (HTML)
 *   style:   { color }      → COMO (cor de texto)
 *   layout:  { align }      → ONDE (alinhamento)
 *
 * Render parseia o HTML usando DOMParser e clona os nós para o canvas
 * (sem innerHTML direto — alinha com a regra "Renderer não usa innerHTML").
 * O parsing via DOMParser não executa scripts; tags <script> são ignoradas
 * porque o parser HTML usado em "text/html" cria-as como dados, e ainda
 * fazemos um filtro defensivo aqui.
 */

import { field, fieldset } from '../ui/inspector-fields.js';
import { getSetting, mergeSettings } from '../core/tree.js';

const PARSER = new DOMParser();

export const TextBlock = {
  type:  'rich_text',
  label: 'Texto',
  icon:  '¶',
  category: 'basico',

  // ── Defaults ─────────────────────────────────────────────────────────
  defaultSettings() {
    return {
      content: { html: '<p>Texto novo. Edite no painel à direita.</p>' },
      style:   { color: '' },
      layout:  { align: 'left' },
    };
  },

  // ── Render (sem innerHTML) ───────────────────────────────────────────
  render(node) {
    const s    = node.settings;
    const html = String(getSetting(s, 'content', 'html', '') ?? '');
    const color = String(getSetting(s, 'style', 'color', '') ?? '');
    const align = String(getSetting(s, 'layout', 'align', 'left') ?? 'left');

    const el = document.createElement('div');
    el.className = 'block-rich-text';
    if (color) el.style.color = color;
    if (align) el.style.textAlign = align;

    appendParsedHtml(el, html);
    return el;
  },

  // ── Inspector ────────────────────────────────────────────────────────
  inspect(node, onChange) {
    const s = node.settings;
    const setNs = (ns, partial) => {
      onChange({ settings: mergeSettings(s, ns, partial) });
    };

    return [
      fieldset('Conteúdo', [
        field('textarea',
          'HTML',
          getSetting(s, 'content', 'html', ''),
          v => setNs('content', { html: v }),
          {
            rows: 8,
            monospace: true,
            hint: 'Tags básicas: <p>, <strong>, <em>, <a>, <ul>, <li>, <h2>...',
          }
        ),
      ]),
      fieldset('Estilo', [
        field('color',
          'Cor do texto',
          getSetting(s, 'style', 'color', ''),
          v => setNs('style', { color: v })
        ),
      ]),
      fieldset('Layout', [
        field('select',
          'Alinhamento',
          getSetting(s, 'layout', 'align', 'left'),
          v => setNs('layout', { align: v }),
          { options: [
            ['left','Esquerda'], ['center','Centro'],
            ['right','Direita'], ['justify','Justificado']
          ]}
        ),
      ]),
    ];
  },
};

/**
 * Parseia HTML via DOMParser e anexa os nós resultantes ao container.
 * Filtra defensivamente <script>, <style>, <iframe>, on*= e javascript:.
 * Não usa innerHTML em nenhum momento (regra do builder).
 */
function appendParsedHtml(container, html) {
  if (!html) return;
  let doc;
  try {
    doc = PARSER.parseFromString(`<!DOCTYPE html><body>${html}`, 'text/html');
  } catch (e) {
    container.appendChild(document.createTextNode(html));
    return;
  }
  for (const child of Array.from(doc.body.childNodes)) {
    const cleaned = sanitizeNode(child);
    if (cleaned) container.appendChild(cleaned);
  }
}

const FORBIDDEN_TAGS = new Set([
  'script', 'style', 'iframe', 'object', 'embed', 'link', 'meta', 'base',
]);

/** Clona node para um documento "limpo", filtrando tags e atributos perigosos. */
function sanitizeNode(node) {
  if (node.nodeType === Node.TEXT_NODE) {
    return document.createTextNode(node.nodeValue || '');
  }
  if (node.nodeType !== Node.ELEMENT_NODE) return null;
  const tag = node.tagName.toLowerCase();
  if (FORBIDDEN_TAGS.has(tag)) return null;

  const el = document.createElement(tag);
  // Atributos: ignora on*= e javascript:
  for (const attr of Array.from(node.attributes)) {
    const name = attr.name.toLowerCase();
    const val  = String(attr.value);
    if (name.startsWith('on')) continue;
    if (/^javascript:/i.test(val)) continue;
    el.setAttribute(name, val);
  }
  // Recursivo
  for (const child of Array.from(node.childNodes)) {
    const c = sanitizeNode(child);
    if (c) el.appendChild(c);
  }
  return el;
}
