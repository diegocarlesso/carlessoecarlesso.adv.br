/**
 * ui/sidebar.js — Painel esquerdo: lista de blocos disponíveis.
 *
 * UI NUNCA muta estado direto. Usa as actions do Store:
 *   - store.addBlockFromDef(def, parentId, index)
 *
 * O Store (action) cuida de:
 *   - criar id único
 *   - expandir defaultChildren em nós reais
 *   - inserir no parent informado (root quando null)
 *   - SELECIONAR o novo bloco (fecha o ciclo com Canvas/Inspector)
 *   - marcar dirty=true
 *
 * Sem drag-and-drop nesta iteração: clique = adicionar no fim do root.
 */

import { blocksByCategory } from '../blocks/index.js';

const CATEGORY_LABELS = {
  layout:    'Layout',
  basico:    'Básicos',
  compostos: 'Compostos',
  outros:    'Outros',
};

export class Sidebar {
  constructor(root, store, blockRegistry) {
    this.root     = root;
    this.store    = store;
    this.registry = blockRegistry;
    this._render();
  }

  _render() {
    // Sem innerHTML — limpa via removeChild
    while (this.root.firstChild) this.root.removeChild(this.root.firstChild);

    const groups = blocksByCategory();
    Object.entries(groups).forEach(([cat, blocks]) => {
      const wrap = document.createElement('div');
      wrap.className = 'sb-group';

      const title = document.createElement('div');
      title.className = 'sb-group-title';
      title.textContent = CATEGORY_LABELS[cat] || cat;
      wrap.appendChild(title);

      const grid = document.createElement('div');
      grid.className = 'sb-grid';
      blocks.forEach(b => grid.appendChild(this._blockCard(b)));
      wrap.appendChild(grid);
      this.root.appendChild(wrap);
    });
  }

  _blockCard(block) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'sb-block';
    btn.title = `Inserir ${block.label}`;
    btn.dataset.blockType = block.type; // marker pra DnD futuro

    const icon = document.createElement('span');
    icon.className = 'sb-block-icon';
    icon.textContent = block.icon || '◻';
    btn.appendChild(icon);

    const lbl = document.createElement('span');
    lbl.className = 'sb-block-label';
    lbl.textContent = block.label;
    btn.appendChild(lbl);

    btn.addEventListener('click', () => {
      // Action única — Store faz tudo (criar id, expandir children,
      // selecionar o novo bloco, marcar dirty).
      this.store.addBlockFromDef(block);
    });
    return btn;
  }
}
