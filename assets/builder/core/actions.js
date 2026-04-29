/**
 * core/actions.js — Camada de ações sobre o Store.
 *
 * Regra: a UI NÃO chama store.setState() diretamente. Sempre via actions.
 * Isso centraliza a lógica de mutação, facilita logging, undo/redo e testes.
 *
 * Cada action retorna o id do nó afetado quando relevante (para uso por
 * exemplo em "selecionar logo após adicionar").
 */

import {
  addNode, updateNode, deleteNode, moveNode, createNode, findNode,
} from './tree.js';

/**
 * Cria o objeto de actions associado a um store.
 * @param {Store} store
 */
export function makeActions(store) {
  return {
    // ── Seleção ────────────────────────────────────────────────────────
    selectBlock(id) {
      const cur = store.getState().selectedId;
      if (cur === id) return;
      store.setState({ selectedId: id ?? null });
    },

    clearSelection() {
      if (store.getState().selectedId !== null) {
        store.setState({ selectedId: null });
      }
    },

    // ── Inserção ───────────────────────────────────────────────────────
    /**
     * Adiciona um nó já formado.
     * @param {object}  node       — nó completo (com id, type, settings, children)
     * @param {string?} parentId   — id do pai (null = root)
     * @param {number?} index      — posição (null = append)
     */
    addBlock(node, parentId = null, index = null) {
      store.setState(s => ({
        ...s,
        tree: { ...s.tree, blocks: addNode(s.tree.blocks, parentId, node, index) },
        selectedId: node.id,
        dirty: true,
      }));
      return node.id;
    },

    /**
     * Adiciona um bloco a partir de uma definition (block registry).
     * Cuida de criar o id e expandir defaultChildren.
     */
    addBlockFromDef(blockDef, parentId = null, index = null) {
      const settings = typeof blockDef.defaultSettings === 'function'
        ? blockDef.defaultSettings()
        : {};
      const childTemplates = typeof blockDef.defaultChildren === 'function'
        ? blockDef.defaultChildren()
        : [];
      const children = childTemplates.map(t =>
        createNode(t.type, t.settings || {}, [])
      );
      const node = createNode(blockDef.type, settings, children);
      return this.addBlock(node, parentId, index);
    },

    // ── Atualização ────────────────────────────────────────────────────
    /**
     * Atualiza updates parcial de um nó. Aceita { settings, type, children }.
     * Para settings, faz merge SHALLOW na raiz (ver updateBlockSettings para
     * mexer em namespaces específicos).
     */
    updateBlock(id, updates) {
      store.setState(s => ({
        ...s,
        tree: { ...s.tree, blocks: updateNode(s.tree.blocks, id, updates) },
        dirty: true,
      }));
    },

    /**
     * Atualiza UM namespace de settings (content/style/layout) com merge raso.
     *
     * @param {string} id
     * @param {'content'|'style'|'layout'} namespace
     * @param {object} partial — campos a sobrescrever no namespace
     */
    updateBlockSettings(id, namespace, partial) {
      if (!['content', 'style', 'layout'].includes(namespace)) {
        console.warn('[actions] namespace inválido:', namespace);
        return;
      }
      store.setState(s => {
        const node = findNode(s.tree.blocks, id);
        if (!node) return s;
        const cur = (node.settings && node.settings[namespace]) || {};
        const merged = { ...cur, ...partial };
        const newSettings = { ...(node.settings || {}), [namespace]: merged };
        return {
          ...s,
          tree:  { ...s.tree, blocks: updateNode(s.tree.blocks, id, { settings: newSettings }) },
          dirty: true,
        };
      });
    },

    // ── Remoção ────────────────────────────────────────────────────────
    deleteBlock(id) {
      store.setState(s => ({
        ...s,
        tree:       { ...s.tree, blocks: deleteNode(s.tree.blocks, id) },
        selectedId: s.selectedId === id ? null : s.selectedId,
        dirty:      true,
      }));
    },

    // ── Movimentação ───────────────────────────────────────────────────
    moveBlock(id, newParentId = null, index = null) {
      store.setState(s => ({
        ...s,
        tree:  { ...s.tree, blocks: moveNode(s.tree.blocks, id, newParentId, index) },
        dirty: true,
      }));
    },

    // ── Diversos ───────────────────────────────────────────────────────
    setDevice(device) {
      if (!['desktop', 'tablet', 'mobile'].includes(device)) return;
      store.setState({ device });
    },

    setSaved(savedAt) {
      store.setState({ dirty: false, savedAt });
    },

    setMode(mode) {
      if (!['edit', 'preview'].includes(mode)) return;
      store.setState({ mode });
    },
  };
}
