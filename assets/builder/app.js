/**
 * app.js — Inicialização do Builder.
 *
 * Bootstrap:
 *   1. Lê window.__BUILDER_BOOT__ (page id, csrf, api base)
 *   2. Chama /api/builder/load.php para carregar a tree
 *   3. Cria Store, renderer, Canvas, Inspector, Sidebar
 *   4. Wire de salvar / preview no topbar
 *
 * UI nunca muta estado direto. Sempre via actions:
 *   - store.selectBlock / addBlockFromDef / updateBlock / deleteBlock / moveBlock
 *   - store.markSaved / setMode / setDevice / hydrateTree
 */

import { Store }          from './core/store.js';
import { makeRenderer }   from './core/renderer.js';
import { Canvas }         from './ui/canvas.js';
import { Inspector }      from './ui/inspector.js';
import { Sidebar }        from './ui/sidebar.js';
import { BLOCK_REGISTRY } from './blocks/index.js';

const BOOT = window.__BUILDER_BOOT__ || {};
const $    = (id) => document.getElementById(id);

const els = {
  canvas:     $('builder-canvas'),
  sidebar:    $('builder-sidebar-list'),
  inspector:  $('builder-inspector-body'),
  saveBtn:    $('builder-save'),
  previewBtn: $('builder-preview'),
  saveState:  $('builder-save-state'),
};

if (!els.canvas) {
  console.error('[builder] root canvas não encontrado');
  throw new Error('Builder root missing');
}

boot().catch(err => {
  console.error('[builder] boot falhou:', err);
  // Sem innerHTML — monta o erro via createElement
  while (els.canvas.firstChild) els.canvas.removeChild(els.canvas.firstChild);
  const errEl = document.createElement('div');
  errEl.className = 'builder-canvas-empty';
  errEl.style.color = '#dc2626';
  errEl.textContent = 'Erro ao carregar: ' + err.message;
  els.canvas.appendChild(errEl);
});

async function boot() {
  setSaveState('Carregando…');
  const data = await loadTree(BOOT.pageId);
  if (!data?.ok) throw new Error(data?.error || 'Falha ao carregar a página.');
  if (data.csrf) BOOT.csrf = data.csrf;

  const store = new Store({
    page:       data.page,
    tree:       data.tree && Array.isArray(data.tree.blocks)
                  ? data.tree
                  : { version: 2, blocks: [] },
    selectedId: null,
    device:     'desktop',
    mode:       'edit',
    dirty:      false,
    savedAt:    null,
  });

  const render = makeRenderer(BLOCK_REGISTRY);

  // eslint-disable-next-line no-unused-vars
  const canvas    = new Canvas(els.canvas, store, render);
  // eslint-disable-next-line no-unused-vars
  const inspector = new Inspector(els.inspector, store, BLOCK_REGISTRY);
  // eslint-disable-next-line no-unused-vars
  const sidebar   = new Sidebar(els.sidebar, store, BLOCK_REGISTRY);

  // Topbar: save state reativo (subscribeSlice é leitura, ok)
  store.subscribeSlice(s => s.dirty, (dirty) => {
    setSaveState(dirty ? 'Mudanças não salvas' : 'Salvo');
  });
  setSaveState('Pronto');

  els.saveBtn?.addEventListener('click', () => saveTree(store));

  // Ctrl+S
  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
      e.preventDefault();
      saveTree(store);
    }
  });

  // Preview: abre /<slug> em outra aba (preview real via iframe vem
  // numa iteração futura — escopo desta etapa é só seleção)
  els.previewBtn?.addEventListener('click', () => {
    const slug = store.getState().page?.slug;
    if (slug) window.open('/' + slug, '_blank', 'noopener');
  });

  // Aviso ao sair com mudanças
  window.addEventListener('beforeunload', (e) => {
    if (store.getState().dirty) {
      e.preventDefault();
      e.returnValue = '';
    }
  });

  // Debug
  window.__builder = { store, BLOCK_REGISTRY };
}

// ── API client ──────────────────────────────────────────────────────────
async function loadTree(pageId) {
  const r = await fetch(`${BOOT.apiBase}/load.php?page_id=${encodeURIComponent(pageId)}`, {
    credentials: 'same-origin',
    cache: 'no-store',
  });
  return r.json();
}

async function saveTree(store) {
  const s = store.getState();
  setSaveState('Salvando…');
  els.saveBtn?.setAttribute('disabled', '');
  try {
    const r = await fetch(`${BOOT.apiBase}/save.php`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        page_id: BOOT.pageId,
        csrf:    BOOT.csrf,
        tree:    s.tree,
      }),
    });
    const json = await r.json();
    if (!json.ok) throw new Error(json.error || 'Erro desconhecido');

    // Action — substitui o setState direto
    store.markSaved(json.saved_at);
    setSaveState('Salvo • ' + new Date().toLocaleTimeString());
  } catch (err) {
    console.error('[builder] save error:', err);
    setSaveState('Erro: ' + err.message, true);
  } finally {
    els.saveBtn?.removeAttribute('disabled');
  }
}

function setSaveState(text, isError = false) {
  if (!els.saveState) return;
  els.saveState.textContent = text;
  els.saveState.classList.toggle('is-error', isError);
}
