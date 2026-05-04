/**
 * ui/inspector.js — Painel direito (propriedades do bloco selecionado).
 *
 * UI NUNCA muta estado direto. Usa as actions do Store:
 *   - store.updateBlock(id, updates)  — quando block.inspect chama onChange
 *   - store.deleteBlock(id)           — botão remover (auto-limpa seleção)
 *
 * Re-render reativo:
 *   - subscribeSlice(s => s.selectedId, ...) → re-render quando seleção muda
 *   - subscribeSlice(s => s.tree,       ...) → re-render quando árvore muda
 *     (usuário editou um campo → tree muta → inspector re-renderiza com
 *     os valores atuais; mantém referência ao node sempre fresh).
 */

import { findNode } from '../core/tree.js';

export class Inspector {
  /**
   * @param {HTMLElement} root
   * @param {object} store
   * @param {object} blockRegistry — blocks/index.js BLOCK_REGISTRY
   */
  constructor(root, store, blockRegistry) {
    this.root     = root;
    this.store    = store;
    this.registry = blockRegistry;

    this._renderBound = this._render.bind(this);

    // Re-renderiza APENAS quando o bloco selecionado muda.
    // NÃO assina mudanças de tree — isso evita que o inspector
    // refaça o DOM (e perca o foco do input) a cada keystroke do usuário.
    // Os campos de input mantêm seus valores via estado DOM nativo;
    // o canvas (que assina tree) cuida de refletir as mudanças visualmente.
    this._unsubSel = store.subscribeSlice(s => s.selectedId, this._renderBound);

    this._render();
  }

  destroy() {
    this._unsubSel?.();
  }

  _render() {
    const { tree, selectedId } = this.store.getState();

    // Sem innerHTML — limpa via removeChild
    while (this.root.firstChild) this.root.removeChild(this.root.firstChild);

    if (!selectedId) {
      this.root.appendChild(this._empty('Selecione um bloco no canvas para editar.'));
      return;
    }

    const node = findNode(tree.blocks, selectedId);
    if (!node) {
      this.root.appendChild(this._empty('Bloco não encontrado.'));
      return;
    }

    const block = this.registry[node.type];
    if (!block) {
      this.root.appendChild(this._empty(`Bloco "${node.type}" sem definição no registry.`));
      return;
    }

    // Header — nome + botão remover
    this.root.appendChild(this._header(block, node));

    // Body — campos do bloco
    // onChange é passado para block.inspect(). Os blocos fecham sobre
    // `s = node.settings` da hora do render, o que gera um "stale closure"
    // se o usuário mudar dois campos no mesmo namespace sem re-selecionar.
    //
    // Para mitigar: lemos o nó FRESCO do store antes de cada update e
    // fazemos merge namespace-a-namespace. Assim campos não alterados nessa
    // chamada específica não sobrescrevem mudanças anteriores.
    const nodeId  = node.id;
    const onChange = (updates) => {
      if (!updates) return;

      if (updates.settings && typeof updates.settings === 'object') {
        // Lê nó atual (pode já ter sido atualizado por chamadas anteriores)
        const freshNode = this.store.getSelected();
        if (freshNode && freshNode.id === nodeId) {
          const cur      = freshNode.settings || {};
          const incoming = updates.settings;

          // Merge namespace-a-namespace: only overwrite keys present in incoming ns
          const merged = {
            content:  { ...(cur.content  || {}) },
            style:    { ...(cur.style    || {}) },
            layout:   { ...(cur.layout   || {}) },
            _legacy:  { ...(cur._legacy  || {}) },
          };
          for (const ns of ['content', 'style', 'layout']) {
            if (incoming[ns] && typeof incoming[ns] === 'object') {
              merged[ns] = { ...merged[ns], ...incoming[ns] };
            }
          }
          // Flat keys (blocos pré-migração de namespaces) vão para _legacy
          for (const [k, v] of Object.entries(incoming)) {
            if (!['content', 'style', 'layout', '_legacy'].includes(k)) {
              merged._legacy[k] = v;
            }
          }
          // _legacy explícito no incoming também é mergeado (não sobrescrito)
          if (incoming._legacy && typeof incoming._legacy === 'object') {
            merged._legacy = { ...merged._legacy, ...incoming._legacy };
          }

          this.store.updateBlock(nodeId, { ...updates, settings: merged });
          return;
        }
      }

      // Fallback: sem settings no update (ex: children, type) — repassa direto
      this.store.updateBlock(nodeId, updates);
    };

    let fields;
    try {
      fields = block.inspect(node, onChange);
    } catch (e) {
      console.error('[inspector] block.inspect erro:', e);
      this.root.appendChild(this._empty('Erro ao montar inspetor: ' + e.message));
      return;
    }

    const body = document.createElement('div');
    body.className = 'insp-body';
    if (Array.isArray(fields)) {
      fields.forEach(f => f instanceof HTMLElement && body.appendChild(f));
    } else if (fields instanceof HTMLElement) {
      body.appendChild(fields);
    }
    this.root.appendChild(body);
  }

  // ── Helpers de DOM (sem innerHTML) ─────────────────────────────────
  _empty(msg) {
    const el = document.createElement('div');
    el.className = 'insp-empty';
    el.textContent = msg;
    return el;
  }

  _header(block, node) {
    const header = document.createElement('div');
    header.className = 'insp-header';

    const title = document.createElement('div');
    title.className = 'insp-title';
    if (block.icon) {
      const icon = document.createElement('span');
      icon.className = 'insp-icon';
      icon.textContent = block.icon;
      title.appendChild(icon);
    }
    const lbl = document.createElement('span');
    lbl.textContent = block.label;
    title.appendChild(lbl);
    header.appendChild(title);

    const del = document.createElement('button');
    del.type = 'button';
    del.className = 'insp-delete';
    del.title = 'Remover bloco';
    del.textContent = '✕ Remover';
    del.addEventListener('click', () => {
      if (!confirm('Remover este bloco?')) return;
      // Action — Store também limpa seleção automaticamente
      this.store.deleteBlock(node.id);
    });
    header.appendChild(del);

    return header;
  }
}
