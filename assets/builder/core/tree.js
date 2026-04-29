/**
 * core/tree.js — Operações imutáveis sobre a árvore de blocos.
 *
 * Toda função retorna uma NOVA árvore (não muta a entrada). IDs únicos via
 * uuid v4. Suporte a children aninhados em qualquer profundidade.
 *
 * Schema canônico de um nó:
 *   {
 *     id: string,
 *     type: string,
 *     settings: {
 *       content: object,    ← O QUE: texto, urls, dados
 *       style:   object,    ← COMO: cores, tipografia, sombras
 *       layout:  object,    ← ONDE: alinhamento, padding, largura, breakpoints
 *     },
 *     children: Node[]
 *   }
 *
 * Funções:
 *   createNode(type, settings, children)       — settings opcional e em qualquer formato
 *   normalizeSettings(settings)                — garante shape { content, style, layout }
 *   getSetting(settings, namespace, key, def)  — lê com fallback p/ flat legacy
 *   mergeSettings(prevSettings, ns, partial)   — produz settings novos com ns mesclado
 *
 *   findNode / findParent / addNode / updateNode / deleteNode / moveNode / walk
 */

// ═══════════════════════════════════════════════════════════════════════
//  IDs
// ═══════════════════════════════════════════════════════════════════════
export function uuid() {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) return crypto.randomUUID();
  const a = new Uint8Array(16);
  if (typeof crypto !== 'undefined' && crypto.getRandomValues) crypto.getRandomValues(a);
  else for (let i = 0; i < 16; i++) a[i] = Math.floor(Math.random() * 256);
  a[6] = (a[6] & 0x0f) | 0x40;
  a[8] = (a[8] & 0x3f) | 0x80;
  const h = Array.from(a).map(b => b.toString(16).padStart(2, '0'));
  return `${h.slice(0,4).join('')}-${h.slice(4,6).join('')}-${h.slice(6,8).join('')}-${h.slice(8,10).join('')}-${h.slice(10,16).join('')}`;
}

// ═══════════════════════════════════════════════════════════════════════
//  Settings — shape canônico { content, style, layout }
// ═══════════════════════════════════════════════════════════════════════

const SETTING_NAMESPACES = ['content', 'style', 'layout'];

/**
 * Garante que settings tenha as 3 namespaces. Aceita:
 *  - já normalizado: { content, style, layout }
 *  - parcial: { content } → preenche outras com {}
 *  - flat legacy: { text, color, align, ... } → preserva sob "_legacy"
 *    para que getSetting() consiga ler com fallback (não joga fora dados).
 */
export function normalizeSettings(settings) {
  const src = (settings && typeof settings === 'object') ? settings : {};
  const out = {
    content: isPlainObj(src.content) ? { ...src.content } : {},
    style:   isPlainObj(src.style)   ? { ...src.style }   : {},
    layout:  isPlainObj(src.layout)  ? { ...src.layout }  : {},
  };
  // Detecta keys flat (qualquer key fora dos 3 namespaces). Mantém-as
  // num bucket "_legacy" para fallback. Não migramos automaticamente
  // — o bloco saberá onde cada chave deve ir via getSetting().
  for (const k of Object.keys(src)) {
    if (SETTING_NAMESPACES.includes(k)) continue;
    if (!out._legacy) out._legacy = {};
    out._legacy[k] = src[k];
  }
  return out;
}

/**
 * Lê uma setting por (namespace, key) com fallback para flat legacy.
 * Ex: getSetting(node.settings, 'content', 'text', 'Padrão')
 *     1º: settings.content.text         (canônico)
 *     2º: settings._legacy.text         (dados antigos pré-migração)
 *     3º: settings.text                 (caso normalize não tenha rodado)
 *     fim: defaultValue
 */
export function getSetting(settings, namespace, key, defaultValue = undefined) {
  if (!settings) return defaultValue;
  const ns = settings[namespace];
  if (isPlainObj(ns) && key in ns) return ns[key];
  if (isPlainObj(settings._legacy) && key in settings._legacy) return settings._legacy[key];
  if (key in settings && !SETTING_NAMESPACES.includes(key)) return settings[key];
  return defaultValue;
}

/**
 * Constrói o novo objeto settings com `partial` mesclado em `namespace`.
 * Os outros namespaces ficam intactos. NÃO muta a entrada.
 *
 * Útil para inspectores: o bloco usa isso para gerar updates a serem
 * passados ao Store via `updateBlock(id, { settings: ... })`.
 */
export function mergeSettings(prevSettings, namespace, partial) {
  if (!SETTING_NAMESPACES.includes(namespace)) {
    console.warn('[tree.mergeSettings] namespace inválido:', namespace);
    return prevSettings;
  }
  const base = normalizeSettings(prevSettings);
  return {
    ...base,
    [namespace]: { ...(base[namespace] || {}), ...(partial || {}) },
  };
}

function isPlainObj(v) {
  return v !== null && typeof v === 'object' && !Array.isArray(v);
}

// ═══════════════════════════════════════════════════════════════════════
//  Factory
// ═══════════════════════════════════════════════════════════════════════
export function createNode(type, settings = {}, children = []) {
  return {
    id:       uuid(),
    type,
    settings: normalizeSettings(settings),
    children: Array.isArray(children) ? children.map(c => ({ ...c })) : [],
  };
}

// ═══════════════════════════════════════════════════════════════════════
//  Lookups (read-only)
// ═══════════════════════════════════════════════════════════════════════
export function findNode(blocks, id) {
  if (!Array.isArray(blocks) || !id) return null;
  for (const node of blocks) {
    if (node.id === id) return node;
    if (Array.isArray(node.children) && node.children.length) {
      const found = findNode(node.children, id);
      if (found) return found;
    }
  }
  return null;
}

/** Retorna { parent, index, list } ou null. */
export function findParent(blocks, id) {
  if (!Array.isArray(blocks)) return null;
  const idx = blocks.findIndex(n => n.id === id);
  if (idx !== -1) return { parent: null, index: idx, list: blocks };
  for (const node of blocks) {
    if (Array.isArray(node.children) && node.children.length) {
      const inChild = node.children.findIndex(c => c.id === id);
      if (inChild !== -1) return { parent: node, index: inChild, list: node.children };
      const deeper = findParent(node.children, id);
      if (deeper) return deeper;
    }
  }
  return null;
}

// ═══════════════════════════════════════════════════════════════════════
//  Mutações imutáveis
// ═══════════════════════════════════════════════════════════════════════

/**
 * Adiciona node em parentId (root se null). Se index omitido, append.
 * Garante que o node tenha id e settings normalizado.
 */
export function addNode(blocks, parentId, node, index = null) {
  const safe = ensureShape(node);
  if (!parentId) {
    const next = [...blocks];
    if (index === null || index < 0 || index > next.length) next.push(safe);
    else next.splice(index, 0, safe);
    return next;
  }
  return blocks.map(n => mapAddIntoChild(n, parentId, safe, index));
}

function mapAddIntoChild(node, parentId, newNode, index) {
  if (node.id === parentId) {
    const children = Array.isArray(node.children) ? [...node.children] : [];
    if (index === null || index < 0 || index > children.length) children.push(newNode);
    else children.splice(index, 0, newNode);
    return { ...node, children };
  }
  if (Array.isArray(node.children) && node.children.length) {
    return { ...node, children: node.children.map(c => mapAddIntoChild(c, parentId, newNode, index)) };
  }
  return node;
}

/**
 * Atualiza um nó. updates pode conter { settings, children, type }.
 *
 * settings: SUBSTITUI o objeto inteiro (após normalizar). Para mesclar
 * apenas um namespace, use mergeSettings() antes e passe o resultado aqui.
 * (Mantém updateNode genérico — quem sabe o domínio é mergeSettings.)
 */
export function updateNode(blocks, id, updates) {
  return blocks.map(n => mapUpdate(n, id, updates));
}

function mapUpdate(node, id, updates) {
  if (node.id === id) {
    const next = { ...node };
    if (updates.type)     next.type     = updates.type;
    if (updates.settings) next.settings = normalizeSettings(updates.settings);
    if (updates.children) next.children = updates.children;
    return next;
  }
  if (Array.isArray(node.children) && node.children.length) {
    return { ...node, children: node.children.map(c => mapUpdate(c, id, updates)) };
  }
  return node;
}

/** Remove o nó (em qualquer profundidade). */
export function deleteNode(blocks, id) {
  return blocks
    .filter(n => n.id !== id)
    .map(n => Array.isArray(n.children) && n.children.length
      ? { ...n, children: deleteNode(n.children, id) }
      : n);
}

/** Move (remove + add). Bloqueia ciclos (move dentro de descendente próprio). */
export function moveNode(blocks, id, newParentId, index = null) {
  const node = findNode(blocks, id);
  if (!node) return blocks;
  if (newParentId && (newParentId === id || isDescendant(node, newParentId))) {
    return blocks;
  }
  const without = deleteNode(blocks, id);
  return addNode(without, newParentId, node, index);
}

function isDescendant(node, targetId) {
  if (!Array.isArray(node.children)) return false;
  for (const c of node.children) {
    if (c.id === targetId) return true;
    if (isDescendant(c, targetId)) return true;
  }
  return false;
}

// ═══════════════════════════════════════════════════════════════════════
//  Walking
// ═══════════════════════════════════════════════════════════════════════
export function walk(blocks, visitor, parent = null) {
  for (const n of blocks) {
    const cont = visitor(n, parent);
    if (cont === false) return false;
    if (Array.isArray(n.children) && n.children.length) {
      const sub = walk(n.children, visitor, n);
      if (sub === false) return false;
    }
  }
  return true;
}

// ═══════════════════════════════════════════════════════════════════════
//  Helpers internos
// ═══════════════════════════════════════════════════════════════════════
function ensureShape(node) {
  return {
    id:       node.id || uuid(),
    type:     node.type,
    settings: normalizeSettings(node.settings),
    children: Array.isArray(node.children) ? node.children : [],
  };
}
