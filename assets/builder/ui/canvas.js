/**
 * ui/canvas.js — Área principal do builder + sistema de SELEÇÃO.
 *
 * Sistema de seleção:
 *   1. CLIQUE seleciona o nó MAIS PROFUNDO no path do alvo que tenha
 *      data-node-id (closest()). Isso resolve clicks dentro de elementos
 *      aninhados — clicar num <h2> dentro de uma <column> dentro de uma
 *      <section> seleciona o heading, não a section.
 *
 *   2. Placeholders (data-placeholder="true") não são selecionáveis.
 *
 *   3. CLIQUE NO FUNDO do canvas (sem nó alvo) limpa seleção.
 *
 *   4. ESC limpa seleção (desde que foco não esteja num input/textarea
 *      fora do canvas — não roubamos teclas do inspector).
 *
 *   5. Cliques em <a>, <button>, <form> e [type=submit] dentro do canvas
 *      têm preventDefault — não queremos navegação durante edição.
 *
 *   6. UI NÃO chama _setState. Sempre via actions do Store
 *      (store.selectBlock).
 *
 * Re-render é determinístico:
 *   - subscribeSlice sobre tree     → re-render quando árvore muda
 *   - subscribeSlice sobre selectedId → re-render quando seleção muda
 *
 * Construção do DOM SEM innerHTML em ponto algum.
 */

export class Canvas {
  /**
   * @param {HTMLElement} root  — div onde a árvore é renderizada
   * @param {object}      store — instância do Store
   * @param {function}    render — função do core/renderer.js já com registry
   */
  constructor(root, store, render) {
    this.root   = root;
    this.store  = store;
    this.render = render;

    this._renderBound  = this._render.bind(this);
    this._onClickBound = this._onClick.bind(this);
    this._onKeyBound   = this._onKey.bind(this);

    // Re-render reativo
    this._unsubTree = store.subscribeSlice(s => s.tree,       this._renderBound);
    this._unsubSel  = store.subscribeSlice(s => s.selectedId, this._renderBound);

    // Eventos
    this.root.addEventListener('click', this._onClickBound);
    document.addEventListener('keydown', this._onKeyBound);

    // Render inicial
    this._render();
  }

  destroy() {
    this._unsubTree?.();
    this._unsubSel?.();
    this.root.removeEventListener('click', this._onClickBound);
    document.removeEventListener('keydown', this._onKeyBound);
  }

  // ═══════════════════════════════════════════════════════════════════
  //  Render — sem innerHTML
  // ═══════════════════════════════════════════════════════════════════
  _render() {
    const { tree, selectedId } = this.store.getState();
    const blocks = Array.isArray(tree?.blocks) ? tree.blocks : [];

    // Limpa filhos sem usar innerHTML
    while (this.root.firstChild) this.root.removeChild(this.root.firstChild);

    if (!blocks.length) {
      this.root.appendChild(this._emptyState());
      return;
    }

    const ctx = { selectedId, depth: 0 };
    for (const node of blocks) {
      const el = this.render(node, ctx);
      if (el instanceof HTMLElement) this.root.appendChild(el);
    }
  }

  _emptyState() {
    const el = document.createElement('div');
    el.className = 'builder-canvas-empty';

    const icon = document.createElement('div');
    icon.style.fontSize     = '2.2rem';
    icon.style.marginBottom = '8px';
    icon.textContent        = '📄';
    el.appendChild(icon);

    const title = document.createElement('div');
    title.textContent = 'Página vazia.';
    el.appendChild(title);

    const hint = document.createElement('div');
    hint.style.fontSize  = '.85rem';
    hint.style.color     = '#9ca3af';
    hint.style.marginTop = '6px';
    hint.textContent     = 'Clique em um bloco no painel à esquerda para começar.';
    el.appendChild(hint);

    return el;
  }

  // ═══════════════════════════════════════════════════════════════════
  //  Seleção — handlers
  // ═══════════════════════════════════════════════════════════════════

  _onClick(e) {
    // Defesa: clique fora do canvas não é nossa responsabilidade
    if (!this.root.contains(e.target)) return;

    // Bloqueia navegação/submit durante edição (links, botões nativos,
    // forms internos a algum bloco). Sem este preventDefault, clicar em
    // <a href="..."> sairia da página do builder.
    const interactive = e.target.closest(
      'a[href], button:not([type="button"]), form, input[type="submit"]'
    );
    if (interactive && this.root.contains(interactive)) {
      e.preventDefault();
    }

    // Encontra o nó MAIS PROFUNDO no path do clique. closest() retorna
    // o ancestral mais próximo — em DOM aninhado, isso é o filho, não o pai.
    const nodeEl = e.target.closest('[data-node-id]');

    // Clique no fundo do canvas → desseleciona
    if (!nodeEl || !this.root.contains(nodeEl)) {
      this.store.selectBlock(null);
      return;
    }

    // Placeholders não são selecionáveis (são só feedback visual de erro)
    if (nodeEl.dataset.placeholder === 'true') {
      e.stopPropagation();
      return;
    }

    const id = nodeEl.dataset.nodeId;
    if (!id) return;

    // Para cliques acontecerem em elementos aninhados sem reselecionar
    // o root da canvas (que limparia tudo), paramos a propagação.
    e.stopPropagation();
    this.store.selectBlock(id);
  }

  _onKey(e) {
    if (e.key !== 'Escape') return;
    if (!this.store.getState().selectedId) return;

    // Não interfere se o usuário está digitando num campo do inspector ou
    // qualquer input fora do canvas — Escape ainda significa "fechar/cancelar"
    // pra eles. Só captamos quando NENHUM input está focado.
    const active = document.activeElement;
    if (active) {
      const tag = (active.tagName || '').toLowerCase();
      if (['input', 'textarea', 'select'].includes(tag) || active.isContentEditable) {
        return;
      }
    }

    e.preventDefault();
    this.store.selectBlock(null);
  }
}
