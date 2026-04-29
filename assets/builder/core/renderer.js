/**
 * core/renderer.js — Converte um nó da árvore em DOM element no canvas.
 *
 * CONTRATO:
 *   1. NUNCA usa innerHTML / outerHTML / insertAdjacentHTML / document.write.
 *      Constrói DOM somente via document.createElement + appendChild +
 *      setAttribute + dataset + classList + textContent.
 *
 *   2. TODO elemento devolvido tem:
 *        data-node-id     = id do nó
 *        data-block-type  = type do nó
 *        data-depth       = nível na árvore (0 = root)
 *        data-parent-id   = id do nó pai (omitido no root)
 *        class "builder-node"
 *        class "builder-node--selected" quando ctx.selectedId === node.id
 *
 *   3. Recursão de filhos é responsabilidade do bloco — mas o renderer
 *      injeta no `ctx` os helpers seguros:
 *        ctx.render(childNode)         → HTMLElement do filho
 *        ctx.renderChildren()          → HTMLElement[] de node.children
 *
 *      Blocos container (section, column) devem usar ESTES helpers — não
 *      chamar render diretamente nem montar nodes manualmente.
 *
 *   4. Re-render é determinístico: dado o mesmo (node, ctx), o output
 *      é a mesma árvore DOM. O bloco NÃO deve manter estado interno.
 *
 *   5. Falhas são contidas: se um bloco lançar, devolve um placeholder
 *      visível com a mensagem; outros blocos da árvore continuam OK.
 *
 *   6. Profundidade limitada (MAX_DEPTH) — proteção contra árvores cíclicas
 *      ou patológicas que escaparem da validação no save.
 */

const MAX_DEPTH = 12;

/**
 * Cria a função `render(node, ctx)` ligada a um blockRegistry.
 *
 * @param {Object<string, BlockDefinition>} blockRegistry
 * @returns {(node: Node, ctx?: Object) => HTMLElement}
 */
export function makeRenderer(blockRegistry) {

  function render(node, ctx = {}) {
    // ── Guards ─────────────────────────────────────────────────────────
    const depth = Number.isFinite(ctx.depth) ? ctx.depth : 0;
    if (depth > MAX_DEPTH) return placeholder('profundidade máxima atingida');
    if (!node || typeof node !== 'object') return placeholder('nó inválido');
    if (typeof node.type !== 'string' || node.type === '') {
      return placeholder('nó sem type');
    }
    if (typeof node.id !== 'string' || node.id === '') {
      // Sem id, não conseguimos marcar pra seleção — devolve placeholder
      // ao invés de renderizar "às escuras". O Store sempre cria com id;
      // isso só dispara em dados manualmente corrompidos.
      return placeholder(`nó "${node.type}" sem id`);
    }

    // ── Resolver bloco ─────────────────────────────────────────────────
    const block = blockRegistry?.[node.type];
    if (!block || typeof block.render !== 'function') {
      return placeholder(`bloco desconhecido: ${node.type}`);
    }

    // ── ctx para o bloco ───────────────────────────────────────────────
    // Cada chamada filha herda selectedId mas reseta parentId/depth.
    const childCtxBase = {
      selectedId: ctx.selectedId,
      device:     ctx.device || 'desktop',
      depth:      depth + 1,
      parentId:   node.id,
    };
    const blockCtx = {
      ...childCtxBase,
      // Renderiza UM filho — uso típico dentro de section/column.
      render: (child) => render(child, childCtxBase),
      // Atalho: devolve array com todos os filhos já renderizados.
      renderChildren: () => {
        const list = Array.isArray(node.children) ? node.children : [];
        const out  = [];
        for (const child of list) {
          const el = render(child, childCtxBase);
          if (el instanceof HTMLElement) out.push(el);
        }
        return out;
      },
    };

    // ── Render do bloco (com error boundary) ──────────────────────────
    let el;
    try {
      el = block.render(node, blockCtx);
    } catch (e) {
      console.error('[renderer] block.render threw for', node.type, '#' + node.id, e);
      return placeholder(`erro em ${node.type}: ${e?.message || e}`);
    }

    if (!(el instanceof HTMLElement)) {
      console.warn('[renderer] block.render() de', node.type, 'não devolveu HTMLElement, devolveu:', el);
      return placeholder(`${node.type}: render() inválido`);
    }

    // ── Markers obrigatórios ───────────────────────────────────────────
    // dataset.* só aceita strings — sem risco de injeção.
    el.dataset.nodeId    = node.id;
    el.dataset.blockType = node.type;
    el.dataset.depth     = String(depth);
    if (ctx.parentId) el.dataset.parentId = ctx.parentId;

    // classList aditivo — não quebra classes que o bloco já adicionou.
    el.classList.add('builder-node');
    if (ctx.selectedId && ctx.selectedId === node.id) {
      el.classList.add('builder-node--selected');
    }

    return el;
  }

  return render;
}

// ═══════════════════════════════════════════════════════════════════════
//  Placeholder — usado quando um nó não pode ser renderizado.
//  Construído puramente via createElement + textContent (sem innerHTML).
// ═══════════════════════════════════════════════════════════════════════
function placeholder(msg) {
  const el = document.createElement('div');
  el.className = 'builder-node builder-node-placeholder';
  // Marca como "node fantasma" — não tem id real, mas evita que cliques
  // selecionem este placeholder como bloco.
  el.dataset.placeholder = 'true';

  const icon = document.createElement('span');
  icon.className = 'builder-node-placeholder-icon';
  icon.textContent = '⚠';
  el.appendChild(icon);

  const text = document.createElement('span');
  text.className = 'builder-node-placeholder-text';
  text.textContent = String(msg);
  el.appendChild(text);

  return el;
}
