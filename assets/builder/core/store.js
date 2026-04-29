/**
 * core/store.js — Estado global reativo + camada de actions.
 *
 * Toda mutação passa pelos métodos públicos (addBlock, updateBlock, etc.).
 * A UI NUNCA chama _setState diretamente — o método existe interno apenas
 * para os actions reduzirem o estado de forma controlada.
 *
 * Internamente usa tree.js para todas as operações de árvore (imutáveis).
 *
 * Schema do state:
 *   {
 *     page:       { id, titulo, slug, status },
 *     tree:       { version: 2, blocks: [...] },
 *     selectedId: string | null,
 *     device:     'desktop' | 'tablet' | 'mobile',
 *     mode:       'edit' | 'preview',
 *     dirty:      boolean,
 *     savedAt:    string | null,
 *   }
 *
 * Eventos:
 *   - subscribe(fn)             → fn(newState, prevState) em qualquer mudança
 *   - subscribeSlice(sel, fn)   → fn(newSlice, prevSlice, state) só quando o slice muda
 */

import {
  addNode, updateNode, deleteNode, moveNode, createNode, findNode,
} from './tree.js';

export class Store {
  constructor(initial = {}) {
    this._state = {
      page:       null,
      tree:       { version: 2, blocks: [] },
      selectedId: null,
      device:     'desktop',
      mode:       'edit',
      dirty:      false,
      savedAt:    null,
      ...initial,
    };
    this._subs = new Set();
  }

  // ── Leitura ────────────────────────────────────────────────────────────
  getState() {
    return this._state;
  }

  /** Acesso conveniente ao nó selecionado (ou null) */
  getSelected() {
    const { tree, selectedId } = this._state;
    return selectedId ? findNode(tree.blocks, selectedId) : null;
  }

  // ── Subscriptions ──────────────────────────────────────────────────────
  subscribe(fn) {
    this._subs.add(fn);
    return () => this._subs.delete(fn);
  }

  subscribeSlice(selector, fn) {
    let last = selector(this._state);
    return this.subscribe((state) => {
      const cur = selector(state);
      if (cur !== last) {
        const prev = last;
        last = cur;
        fn(cur, prev, state);
      }
    });
  }

  // ═══════════════════════════════════════════════════════════════════════
  //  ACTIONS — única forma autorizada de mutar o estado pela UI
  // ═══════════════════════════════════════════════════════════════════════

  /**
   * Seleciona um bloco por id. Passe null para limpar a seleção.
   */
  selectBlock(id) {
    if (this._state.selectedId === (id ?? null)) return;
    this._setState({ selectedId: id ?? null });
  }

  /**
   * Adiciona um nó já formado em parentId (null = root) na posição index
   * (null = append). Marca o novo nó como selecionado.
   *
   * @param {object}  node       — { id, type, settings, children }
   * @param {string?} parentId
   * @param {number?} index
   * @returns {string} id do nó adicionado
   */
  addBlock(node, parentId = null, index = null) {
    if (!node || !node.type) {
      console.warn('[store.addBlock] nó inválido', node);
      return null;
    }
    // Garante id (createNode já cuida, mas defensive)
    const safe = node.id ? node : createNode(node.type, node.settings || {}, node.children || []);
    this._setState(s => ({
      tree:       { ...s.tree, blocks: addNode(s.tree.blocks, parentId, safe, index) },
      selectedId: safe.id,
      dirty:      true,
    }));
    return safe.id;
  }

  /**
   * Açúcar para criar e adicionar a partir de uma block definition (sidebar).
   * Expande defaultChildren em nós reais (com id) antes de adicionar.
   */
  addBlockFromDef(def, parentId = null, index = null) {
    const settings = typeof def.defaultSettings === 'function' ? def.defaultSettings() : {};
    const childTpl = typeof def.defaultChildren === 'function' ? def.defaultChildren() : [];
    const children = childTpl.map(t => createNode(t.type, t.settings || {}, t.children || []));
    return this.addBlock(createNode(def.type, settings, children), parentId, index);
  }

  /**
   * Atualiza um nó. updates pode conter { settings, type, children }.
   * settings é merged SHALLOW na raiz — para mexer em namespaces aninhados
   * (content/style/layout), passe a estrutura completa do namespace.
   */
  updateBlock(id, updates) {
    if (!id || !updates) return;
    this._setState(s => ({
      tree:  { ...s.tree, blocks: updateNode(s.tree.blocks, id, updates) },
      dirty: true,
    }));
  }

  /**
   * Atualiza UM namespace de settings (content/style/layout) com merge raso.
   * Útil para o inspector — campos sabem em que namespace mexem.
   */
  updateBlockSettings(id, namespace, partial) {
    if (!['content', 'style', 'layout'].includes(namespace)) {
      console.warn('[store.updateBlockSettings] namespace inválido:', namespace);
      return;
    }
    const node = findNode(this._state.tree.blocks, id);
    if (!node) return;
    const cur = (node.settings && node.settings[namespace]) || {};
    const merged = { ...cur, ...partial };
    const newSettings = { ...(node.settings || {}), [namespace]: merged };
    this._setState(s => ({
      tree:  { ...s.tree, blocks: updateNode(s.tree.blocks, id, { settings: newSettings }) },
      dirty: true,
    }));
  }

  /**
   * Remove o nó (em qualquer profundidade). Limpa a seleção se for o nó atual.
   */
  deleteBlock(id) {
    if (!id) return;
    this._setState(s => ({
      tree:       { ...s.tree, blocks: deleteNode(s.tree.blocks, id) },
      selectedId: s.selectedId === id ? null : s.selectedId,
      dirty:      true,
    }));
  }

  /**
   * Move o nó para newParentId (null = root) na posição index (null = append).
   * Bloqueado se gerar ciclo (move sob descendente próprio).
   */
  moveBlock(id, newParentId = null, index = null) {
    if (!id) return;
    this._setState(s => ({
      tree:  { ...s.tree, blocks: moveNode(s.tree.blocks, id, newParentId, index) },
      dirty: true,
    }));
  }

  // ── Estado auxiliar (não-tree) ─────────────────────────────────────────
  setDevice(device) {
    if (!['desktop', 'tablet', 'mobile'].includes(device)) return;
    if (this._state.device === device) return;
    this._setState({ device });
  }

  setMode(mode) {
    if (!['edit', 'preview'].includes(mode)) return;
    if (this._state.mode === mode) return;
    this._setState({ mode });
  }

  /**
   * Marca como salvo (chamado pelo app.js após save bem-sucedido).
   * Não muta a árvore — só dirty/savedAt.
   */
  markSaved(savedAt) {
    this._setState({ dirty: false, savedAt: savedAt ?? new Date().toISOString() });
  }

  /**
   * Substitui a árvore inteira (usado no boot, após load).
   * Reseta dirty.
   */
  hydrateTree(tree, opts = {}) {
    this._setState({
      tree:       (tree && Array.isArray(tree.blocks)) ? tree : { version: 2, blocks: [] },
      selectedId: null,
      dirty:      false,
      savedAt:    opts.savedAt ?? null,
    });
  }

  // ═══════════════════════════════════════════════════════════════════════
  //  Privado — não chamar fora do Store
  // ═══════════════════════════════════════════════════════════════════════
  _setState(next) {
    const prev    = this._state;
    const partial = typeof next === 'function' ? next(prev) : next;
    const computed = { ...prev, ...partial };
    if (computed === prev) return;
    this._state = computed;
    for (const fn of this._subs) {
      try { fn(this._state, prev); }
      catch (e) { console.error('[store] subscriber error:', e); }
    }
  }
}
