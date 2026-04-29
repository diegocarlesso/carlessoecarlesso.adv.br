/* ═══════════════════════════════════════════════════════════════════════
   editor.js — Editor rich-text estilo Word (sem dependências externas).

   Aplica-se a qualquer <textarea class="rich-editor">.

   Funcionalidades (toolbar inteira):
     · Família de fonte + tamanho (com botões grow/shrink)
     · Estilo de bloco (P, H1-H4, Citação, Pré-formatado)
     · Negrito / Itálico / Sublinhado / Tachado / Sub / Super
     · Cor de texto + cor de destaque (color picker com paleta)
     · Limpar formatação
     · Listas marcador / numerada
     · Aumentar / Diminuir indentação
     · Alinhamento esquerda / centro / direita / justificar
     · Espaçamento de linha
     · Inserir link (com dialog), remover link
     · Inserir imagem (URL ou upload)
     · Inserir tabela (com seletor de dimensão)
     · Citação, divisória, código
     · Desfazer / Refazer
     · Localizar / Substituir (Ctrl+F)
     · Modo HTML (source view)
     · Tela cheia
     · Contador palavras + caracteres + (limite opcional via maxlength)

   Atalhos: Ctrl+B/I/U, Ctrl+K (link), Ctrl+Shift+H (heading), F11 (fullscreen).

   Atributos especiais no <textarea>:
     class="rich-editor"               — ativa o editor
     data-compact="1"                  — toolbar reduzida (resumos curtos)
     maxlength="500"                   — limite hard de caracteres
     data-upload-url="/api/upload.php" — endpoint para upload de imagens
═══════════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  // ── Configuração ────────────────────────────────────────────────────────
  const PALETTE = [
    '#000000', '#1c1c1c', '#374151', '#6b7280', '#9ca3af', '#d1d5db', '#f3f4f6', '#ffffff',
    '#1a3554', '#527095', '#c8832a', '#e8d5c0', // paleta institucional
    '#7f1d1d', '#dc2626', '#ea580c', '#ca8a04',
    '#15803d', '#16a34a', '#0891b2', '#0369a1',
    '#1d4ed8', '#7c3aed', '#a21caf', '#be185d',
  ];

  const FONTS = [
    { label: 'Open Sans (corpo)',     value: "'Open Sans', Helvetica, Arial, sans-serif" },
    { label: 'Hepta Slab (títulos)',  value: "'Hepta Slab', Georgia, serif" },
    { label: 'Arial',                 value: "Arial, Helvetica, sans-serif" },
    { label: 'Calibri',               value: "Calibri, 'Trebuchet MS', sans-serif" },
    { label: 'Cambria',               value: "Cambria, Georgia, serif" },
    { label: 'Courier New',           value: "'Courier New', Courier, monospace" },
    { label: 'Garamond',              value: "Garamond, 'Times New Roman', serif" },
    { label: 'Georgia',               value: "Georgia, serif" },
    { label: 'Helvetica',             value: "Helvetica, Arial, sans-serif" },
    { label: 'Tahoma',                value: "Tahoma, Geneva, sans-serif" },
    { label: 'Times New Roman',       value: "'Times New Roman', Times, serif" },
    { label: 'Trebuchet MS',          value: "'Trebuchet MS', sans-serif" },
    { label: 'Verdana',               value: "Verdana, Geneva, sans-serif" },
  ];

  const SIZES = ['10', '11', '12', '14', '16', '18', '20', '24', '28', '32', '36', '48', '60', '72'];

  // ── DOM helpers ─────────────────────────────────────────────────────────
  function el(tag, attrs, ...children) {
    const node = document.createElement(tag);
    if (attrs) {
      for (const k in attrs) {
        const v = attrs[k];
        if (v == null) continue;
        if (k === 'class') node.className = v;
        else if (k === 'style' && typeof v === 'object') Object.assign(node.style, v);
        else if (k === 'html') node.innerHTML = v;
        else if (k.startsWith('on') && typeof v === 'function') node.addEventListener(k.slice(2).toLowerCase(), v);
        else if (typeof v === 'boolean') { if (v) node.setAttribute(k, ''); }
        else node.setAttribute(k, v);
      }
    }
    for (const c of children.flat()) {
      if (c == null) continue;
      node.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);
    }
    return node;
  }

  // ── Inject CSS once ─────────────────────────────────────────────────────
  function injectStyles() {
    if (document.getElementById('le-styles')) return;
    const s = document.createElement('style');
    s.id = 'le-styles';
    s.textContent = `
.lite-editor { border:1px solid #d1d5db; border-radius:8px; background:#fff; overflow:hidden; }
.lite-editor.fullscreen { position:fixed; inset:0; z-index:9999; border-radius:0; display:flex; flex-direction:column; }
.lite-editor.fullscreen .le-area { flex:1; max-height:none; overflow:auto; }
.le-toolbar { display:flex; flex-wrap:wrap; gap:2px; padding:6px; background:linear-gradient(180deg,#fafbfc,#f3f4f6); border-bottom:1px solid #e5e9ef; align-items:center; }
.le-toolbar .le-group { display:flex; gap:1px; padding:0 4px; border-right:1px solid #e5e9ef; align-items:center; }
.le-toolbar .le-group:last-child { border-right:none; }
.le-btn {
  display:inline-flex; align-items:center; justify-content:center;
  min-width:28px; height:28px; padding:0 6px;
  border:1px solid transparent; border-radius:4px;
  background:transparent; color:#1c1c1c; font-size:13px; font-family:inherit;
  cursor:pointer; transition:all .12s;
}
.le-btn:hover { background:#e5e9ef; border-color:#d1d5db; }
.le-btn:active, .le-btn.active { background:#dbeafe; border-color:#93c5fd; color:#1d4ed8; }
.le-btn.icon-stack { flex-direction:column; line-height:1; gap:1px; padding:2px 6px; }
.le-btn.icon-stack .swatch { width:18px; height:3px; border-radius:1px; background:currentColor; }
.le-btn[disabled] { opacity:.4; cursor:not-allowed; }
.le-select {
  height:28px; padding:0 6px; border:1px solid #d1d5db; border-radius:4px;
  background:#fff; color:#1c1c1c; font-size:12px; font-family:inherit; cursor:pointer;
  min-width:60px;
}
.le-select.font { min-width:140px; }
.le-select:hover { border-color:#9ca3af; }
.le-select:focus { outline:none; border-color:#527095; box-shadow:0 0 0 2px rgba(82,112,149,.2); }

.le-area {
  padding:18px 24px; min-height:160px; max-height:70vh; overflow:auto;
  font-family:'Open Sans', Helvetica, Arial, sans-serif; font-size:15px; line-height:1.6; color:#1c1c1c;
  outline:none;
}
.le-area:focus { background:#fcfcfd; }
.le-area p { margin:0 0 .8em; }
.le-area h1, .le-area h2, .le-area h3, .le-area h4 { font-family:'Hepta Slab', Georgia, serif; color:#1a3554; margin:1em 0 .4em; line-height:1.25; }
.le-area h1 { font-size:1.8em; }
.le-area h2 { font-size:1.5em; }
.le-area h3 { font-size:1.25em; }
.le-area h4 { font-size:1.1em; }
.le-area ul, .le-area ol { padding-left:1.6em; margin:0 0 .8em; }
.le-area blockquote { border-left:3px solid #c8832a; padding:.4em 1em; color:#4b5563; font-style:italic; margin:0 0 .8em; background:#faf7f2; }
.le-area pre { background:#f3f4f6; padding:.6em .8em; border-radius:4px; overflow:auto; font-family:'Courier New', monospace; font-size:.92em; }
.le-area code { background:#f3f4f6; padding:1px 4px; border-radius:3px; font-family:'Courier New', monospace; font-size:.92em; }
.le-area hr { border:none; border-top:1px solid #d1d5db; margin:1.4em 0; }
.le-area a { color:#c8832a; }
.le-area img { max-width:100%; height:auto; }
.le-area table { border-collapse:collapse; width:100%; margin:0 0 .8em; }
.le-area table td, .le-area table th { border:1px solid #d1d5db; padding:6px 10px; }
.le-area table th { background:#f3f4f6; font-weight:600; text-align:left; }
.le-area :first-child { margin-top:0; }
.le-area :last-child { margin-bottom:0; }

.le-source { width:100%; padding:14px 18px; min-height:160px; border:none; outline:none;
  font-family:'Courier New', monospace; font-size:13px; line-height:1.5; color:#1c1c1c; resize:vertical; }

.le-status { padding:5px 12px; background:#fafbfc; border-top:1px solid #e5e9ef;
  font-size:.7rem; color:#6b7280; display:flex; justify-content:space-between; gap:8px; }
.le-status.over { color:#dc2626; font-weight:600; }

/* Color picker popover */
.le-popover { position:absolute; z-index:1000; background:#fff; border:1px solid #d1d5db;
  border-radius:6px; padding:8px; box-shadow:0 6px 20px rgba(0,0,0,.12); }
.le-color-grid { display:grid; grid-template-columns:repeat(8, 22px); gap:3px; }
.le-color-cell { width:22px; height:22px; border-radius:3px; cursor:pointer; border:1px solid rgba(0,0,0,.08); transition:transform .1s; }
.le-color-cell:hover { transform:scale(1.15); border-color:#1c1c1c; }
.le-color-cell.no-color { background:linear-gradient(135deg, transparent 45%, #dc2626 45%, #dc2626 55%, transparent 55%); background-color:#fff; }
.le-color-custom { display:flex; gap:6px; align-items:center; margin-top:8px; padding-top:8px; border-top:1px solid #e5e9ef; }
.le-color-custom input[type=color] { width:30px; height:24px; border:1px solid #d1d5db; border-radius:3px; padding:0; cursor:pointer; }

/* Table picker */
.le-table-grid { display:grid; grid-template-columns:repeat(10, 18px); gap:1px; padding:6px; background:#f3f4f6; }
.le-table-cell { width:18px; height:18px; background:#fff; border:1px solid #d1d5db; cursor:pointer; }
.le-table-cell.hover { background:#dbeafe; border-color:#93c5fd; }
.le-table-label { padding:4px 8px 0; font-size:.75rem; color:#6b7280; text-align:center; }

/* Compact mode (toolbars curtas) */
.lite-editor-compact .le-toolbar { padding:4px; }
.lite-editor-compact .le-area { padding:10px 14px; min-height:80px; max-height:200px; font-size:14px; }

/* Modal de link */
.le-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9998;
  display:flex; align-items:center; justify-content:center; }
.le-modal { background:#fff; border-radius:8px; padding:20px; min-width:400px; max-width:90vw;
  box-shadow:0 12px 32px rgba(0,0,0,.18); }
.le-modal h4 { margin:0 0 14px; font-size:1.05rem; color:#1a3554; }
.le-modal label { display:block; margin-bottom:10px; font-size:.85rem; color:#374151; }
.le-modal input[type=text], .le-modal input[type=url] { width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:4px; font-size:.9rem; margin-top:4px; }
.le-modal-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:14px; }
.le-modal-actions button { padding:7px 14px; border-radius:4px; font-size:.85rem; cursor:pointer; border:1px solid transparent; }
.le-modal-actions button.primary { background:#527095; color:#fff; border-color:#527095; }
.le-modal-actions button.cancel { background:#fff; color:#374151; border-color:#d1d5db; }
.le-modal-actions button:hover { filter:brightness(.95); }
`;
    document.head.appendChild(s);
  }

  // ── Selection persistence (módulo escopo) ──────────────────────────────
  // Mapa: cada área editável guarda a última Range válida observada via
  // 'selectionchange'. Antes de qualquer ação (cor, link, imagem, tabela),
  // restauramos a Range salva. Garante que clique em botão/popover/modal
  // não desfaz a seleção do usuário.
  const editorSelectionMap = new WeakMap();

  function registerSelectionTracking(area) {
    document.addEventListener('selectionchange', () => {
      const sel = window.getSelection();
      if (!sel || sel.rangeCount === 0) return;
      const range = sel.getRangeAt(0);
      if (area.contains(range.startContainer) && area.contains(range.endContainer)) {
        editorSelectionMap.set(area, range.cloneRange());
      }
    });
  }

  function getSavedRange(area) {
    return editorSelectionMap.get(area) || null;
  }

  function restoreSavedRange(area) {
    const r = editorSelectionMap.get(area);
    if (!r) {
      area.focus();
      return false;
    }
    area.focus();
    const sel = window.getSelection();
    sel.removeAllRanges();
    try { sel.addRange(r); } catch (e) { return false; }
    return true;
  }

  // ── Color picker popover ────────────────────────────────────────────────
  function openColorPicker(anchor, onPick) {
    closeAllPopovers();
    // IMPORTANTE: salva a Range agora — antes do popover possivelmente
    // descartar a seleção. Mas o tracking via selectionchange já faz isso
    // automaticamente; aqui apenas pegamos o snapshot atual.
    const savedRange = saveCurrentRange();

    function applyAndClose(color) {
      restoreRange(savedRange);
      onPick(color);
      pop.remove();
    }

    const grid = el('div', { class: 'le-color-grid' },
      el('div', { class: 'le-color-cell no-color', title: 'Sem cor',
        onmousedown: e => e.preventDefault(),
        onclick: () => applyAndClose('') }),
      ...PALETTE.map(c => el('div', {
        class: 'le-color-cell',
        style: { background: c },
        title: c,
        onmousedown: e => e.preventDefault(),
        onclick: () => applyAndClose(c)
      }))
    );
    const customInput = el('input', { type: 'color', value: '#1a3554',
      onmousedown: e => e.preventDefault(),
      onchange: e => applyAndClose(e.target.value)
    });
    const custom = el('div', { class: 'le-color-custom' },
      el('span', null, 'Outra:'), customInput
    );
    const pop = el('div', { class: 'le-popover',
      onmousedown: e => e.preventDefault() // impede o popover de roubar o foco
    }, grid, custom);
    document.body.appendChild(pop);
    positionPopover(pop, anchor);
    setTimeout(() => document.addEventListener('mousedown', onOutside), 10);
    function onOutside(e) {
      if (!pop.contains(e.target)) { pop.remove(); document.removeEventListener('mousedown', onOutside); }
    }
  }

  function saveCurrentRange() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return null;
    return sel.getRangeAt(0).cloneRange();
  }

  function restoreRange(range) {
    if (!range) return;
    // Focus precisa preceder addRange em alguns browsers (Chrome notavelmente)
    let host = range.startContainer;
    while (host && host.nodeType === 3) host = host.parentNode;
    while (host && !host.isContentEditable) host = host.parentNode;
    if (host && typeof host.focus === 'function') host.focus();
    const sel = window.getSelection();
    sel.removeAllRanges();
    try { sel.addRange(range); } catch (e) {}
  }

  function closeAllPopovers() {
    document.querySelectorAll('.le-popover').forEach(p => p.remove());
  }

  function positionPopover(pop, anchor) {
    const r = anchor.getBoundingClientRect();
    pop.style.position = 'absolute';
    pop.style.top = (window.scrollY + r.bottom + 4) + 'px';
    pop.style.left = (window.scrollX + r.left) + 'px';
  }

  // ── Table picker popover ────────────────────────────────────────────────
  function openTablePicker(anchor, onPick) {
    closeAllPopovers();
    const savedRange = saveCurrentRange();
    const ROWS = 8, COLS = 10;
    const cells = [];
    const grid = el('div', { class: 'le-table-grid',
      onmousedown: e => e.preventDefault()
    });
    for (let r = 1; r <= ROWS; r++) {
      for (let c = 1; c <= COLS; c++) {
        const cell = el('div', {
          class: 'le-table-cell',
          'data-r': r, 'data-c': c,
          onmouseenter: () => paint(r, c),
          onmousedown: e => e.preventDefault(),
          onclick: () => { restoreRange(savedRange); onPick(r, c); pop.remove(); }
        });
        cells.push(cell);
        grid.appendChild(cell);
      }
    }
    const label = el('div', { class: 'le-table-label' }, '0 × 0');
    function paint(rr, cc) {
      cells.forEach(cell => {
        const r = +cell.dataset.r, c = +cell.dataset.c;
        cell.classList.toggle('hover', r <= rr && c <= cc);
      });
      label.textContent = `${rr} × ${cc}`;
    }
    const pop = el('div', { class: 'le-popover',
      onmousedown: e => e.preventDefault()
    }, label, grid);
    document.body.appendChild(pop);
    positionPopover(pop, anchor);
    setTimeout(() => document.addEventListener('mousedown', onOutside), 10);
    function onOutside(e) {
      if (!pop.contains(e.target)) { pop.remove(); document.removeEventListener('mousedown', onOutside); }
    }
  }

  // ── Modal genérico ──────────────────────────────────────────────────────
  function openModal(title, fields, onSubmit) {
    const overlay = el('div', { class: 'le-modal-overlay' });
    const inputs = {};
    const formChildren = fields.map(f => {
      const input = el('input', { type: f.type || 'text', value: f.value || '', placeholder: f.placeholder || '' });
      inputs[f.name] = input;
      return el('label', null, f.label, input);
    });
    const modal = el('div', { class: 'le-modal' },
      el('h4', null, title),
      ...formChildren,
      el('div', { class: 'le-modal-actions' },
        el('button', { class: 'cancel', type: 'button', onclick: () => overlay.remove() }, 'Cancelar'),
        el('button', { class: 'primary', type: 'button', onclick: () => {
          const values = {};
          for (const k in inputs) values[k] = inputs[k].value;
          onSubmit(values);
          overlay.remove();
        }}, 'OK')
      )
    );
    overlay.appendChild(modal);
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
    document.body.appendChild(overlay);
    setTimeout(() => Object.values(inputs)[0]?.focus(), 50);
  }

  // ═══════════════════════════════════════════════════════════════════════
  //  initEditor — instancia o editor para um <textarea>
  // ═══════════════════════════════════════════════════════════════════════
  function initEditor(textarea) {
    if (textarea.dataset.leInit) return;
    textarea.dataset.leInit = '1';

    const opts = {
      compact:    textarea.dataset.compact === '1',
      maxLength:  parseInt(textarea.getAttribute('maxlength') || textarea.dataset.maxlength || '0', 10) || 0,
      uploadUrl:  textarea.dataset.uploadUrl || '/api/upload.php',
    };

    const wrap = el('div', { class: 'lite-editor' + (opts.compact ? ' lite-editor-compact' : '') });
    const toolbar = el('div', { class: 'le-toolbar' });
    const area = el('div', {
      class: 'le-area',
      contenteditable: 'true',
      spellcheck: 'true',
    });
    area.innerHTML = textarea.value || '<p><br></p>';

    const source = el('textarea', { class: 'le-source' });
    source.style.display = 'none';
    source.value = textarea.value;

    const status = el('div', { class: 'le-status' });
    const wordCount = el('span', null, '0 palavras');
    const charCount = el('span', null, '0 caracteres');
    status.appendChild(wordCount);
    status.appendChild(charCount);

    let sourceMode = false;
    let fullscreen = false;

    function exec(cmd, arg = null) {
      area.focus();
      try { document.execCommand(cmd, false, arg); } catch (e) {}
      sync();
    }

    function sync() {
      if (sourceMode) {
        textarea.value = source.value;
      } else {
        textarea.value = area.innerHTML;
        source.value   = area.innerHTML;
      }
      updateStatus();
    }

    function updateStatus() {
      const text = (sourceMode ? source.value.replace(/<[^>]*>/g, ' ') : area.innerText) || '';
      const words = text.trim().split(/\s+/).filter(Boolean).length;
      const chars = text.length;
      wordCount.textContent = words + ' palavras';
      charCount.textContent = opts.maxLength
        ? `${chars} / ${opts.maxLength} caracteres`
        : `${chars} caracteres`;
      if (opts.maxLength && chars > opts.maxLength) status.classList.add('over');
      else status.classList.remove('over');
    }

    // ── Helpers de toolbar ──────────────────────────────────────────────
    function group(...items) {
      const g = el('div', { class: 'le-group' });
      items.forEach(i => g.appendChild(i));
      return g;
    }
    function btn(label, title, onclick, opts2 = {}) {
      const b = el('button', {
        type: 'button',
        class: 'le-btn ' + (opts2.cls || ''),
        title: title,
        // CRÍTICO: previne o button de roubar foco do contenteditable.
        // Sem isso, o navegador move o foco pra button no mousedown e
        // a Range salva no contenteditable é perdida.
        onmousedown: e => { e.preventDefault(); restoreSavedRange(area); },
        onclick: () => { restoreSavedRange(area); onclick(b); }
      });
      b.innerHTML = label;
      return b;
    }
    function select(items, title, onchange, opts2 = {}) {
      const s = el('select', {
        class: 'le-select ' + (opts2.cls || ''),
        title: title,
        // Restaura a seleção antes de aplicar — selects perdem foco do area
        onmousedown: () => restoreSavedRange(area),
        onchange: e => {
          restoreSavedRange(area);
          onchange(e.target.value);
          e.target.selectedIndex = 0;
        }
      });
      s.appendChild(el('option', { value: '' }, opts2.placeholder || '—'));
      items.forEach(it => {
        const opt = el('option', { value: it.value || it }, it.label || it);
        if (it.style) opt.setAttribute('style', it.style);
        s.appendChild(opt);
      });
      return s;
    }

    // ── Construção da toolbar ───────────────────────────────────────────
    if (!opts.compact) {
      // Fonte + tamanho
      const fontSel = select(
        FONTS.map(f => ({ label: f.label, value: f.value, style: 'font-family:' + f.value })),
        'Família da fonte',
        v => exec('fontName', v),
        { cls: 'font', placeholder: 'Fonte' }
      );
      const sizeSel = select(
        SIZES.map(s => ({ label: s + 'px', value: s })),
        'Tamanho',
        v => {
          // execCommand fontSize aceita 1-7; usamos uma técnica que aplica style direto
          exec('fontSize', '7');
          // Substitui os <font size="7"> recém-criados por <span style="font-size:Xpx">
          area.querySelectorAll('font[size="7"]').forEach(f => {
            const span = document.createElement('span');
            span.style.fontSize = v + 'px';
            span.innerHTML = f.innerHTML;
            f.parentNode.replaceChild(span, f);
          });
          sync();
        },
        { placeholder: 'Tam' }
      );
      toolbar.appendChild(group(fontSel, sizeSel,
        btn('A<sup>+</sup>', 'Aumentar fonte', () => growFont(2)),
        btn('A<sup>−</sup>', 'Diminuir fonte', () => growFont(-2))
      ));
    }

    // Estilo de bloco
    const blockSel = select(
      [
        { label: 'Parágrafo',     value: 'P' },
        { label: 'Título 1',      value: 'H1' },
        { label: 'Título 2',      value: 'H2' },
        { label: 'Título 3',      value: 'H3' },
        { label: 'Título 4',      value: 'H4' },
        { label: 'Citação',       value: 'BLOCKQUOTE' },
        { label: 'Pré-formatado', value: 'PRE' },
      ],
      'Estilo de bloco',
      v => exec('formatBlock', v),
      { placeholder: 'Estilo' }
    );
    toolbar.appendChild(group(
      blockSel,
      btn('<span style="text-decoration:line-through">A</span>', 'Limpar formatação', () => exec('removeFormat'))
    ));

    // Format básico
    toolbar.appendChild(group(
      btn('<b>N</b>',   'Negrito (Ctrl+B)',     () => exec('bold')),
      btn('<i>I</i>',   'Itálico (Ctrl+I)',     () => exec('italic')),
      btn('<u>S</u>',   'Sublinhado (Ctrl+U)',  () => exec('underline')),
      btn('<s>T</s>',   'Tachado',              () => exec('strikeThrough')),
      btn('X<sup>2</sup>', 'Sobrescrito',       () => exec('superscript')),
      btn('X<sub>2</sub>', 'Subscrito',         () => exec('subscript'))
    ));

    // Cores
    toolbar.appendChild(group(
      btn('<span>A</span><span class="swatch" style="color:#dc2626"></span>', 'Cor do texto',
        b => openColorPicker(b, c => applyColor('text', c)),
        { cls: 'icon-stack' }
      ),
      btn('<span>🖍</span><span class="swatch" style="color:#fde047"></span>', 'Cor de destaque',
        b => openColorPicker(b, c => applyColor('highlight', c)),
        { cls: 'icon-stack' }
      )
    ));

    // Aplica cor com tratamento de "sem cor" e fallback hiliteColor → backColor.
    // Wraps em <span style="color:..."> ou <span style="background:..."> para
    // contornar comportamento errático do execCommand em browsers modernos
    // (foreColor às vezes é ignorado se o fragment selecionado já tem cor).
    function applyColor(kind, color) {
      // Restaura a Range antes de aplicar — seleção pode ter sido perdida
      // por clique no popover.
      if (!restoreSavedRange(area)) return;

      const sel = window.getSelection();
      if (!sel.rangeCount) return;
      const range = sel.getRangeAt(0);

      if (range.collapsed) {
        // Sem texto selecionado — não faz nada (evita aplicar cor "futura"
        // que confunde o usuário). Avisa via console.
        console.info('[editor] selecione texto antes de aplicar cor');
        return;
      }

      // Wrap em <span> direto — funciona em 100% dos browsers modernos
      const span = document.createElement('span');
      if (kind === 'text') {
        if (color) span.style.color = color;
        else { /* sem cor: vai chamar removeFormat fallback abaixo */ }
      } else {
        if (color) span.style.backgroundColor = color;
        else span.style.backgroundColor = 'transparent';
      }

      try {
        if (color || kind === 'highlight') {
          // surroundContents pode falhar se a seleção atravessa nós
          range.surroundContents(span);
          sel.removeAllRanges();
          sel.addRange(range);
        } else {
          // "Sem cor" no texto — usa execCommand removeFormat só nessa range
          area.focus();
          document.execCommand('removeFormat');
        }
      } catch (e) {
        // Fallback: extractContents + insertNode
        try {
          const frag = range.extractContents();
          span.appendChild(frag);
          range.insertNode(span);
          sel.removeAllRanges();
          const newRange = document.createRange();
          newRange.selectNodeContents(span);
          sel.addRange(newRange);
        } catch (e2) {
          // Último fallback: execCommand
          area.focus();
          document.execCommand(kind === 'text' ? 'foreColor' : 'hiliteColor', false, color || 'inherit') ||
            document.execCommand('backColor', false, color || 'transparent');
        }
      }
      sync();
    }

    // Listas + indent
    toolbar.appendChild(group(
      btn('•≡', 'Lista com marcadores',  () => exec('insertUnorderedList')),
      btn('1≡', 'Lista numerada',        () => exec('insertOrderedList')),
      btn('⇤',  'Diminuir indentação',   () => exec('outdent')),
      btn('⇥',  'Aumentar indentação',   () => exec('indent'))
    ));

    // Alinhamento
    toolbar.appendChild(group(
      btn('⬱',  'Alinhar à esquerda',    () => exec('justifyLeft')),
      btn('☰',  'Centralizar',           () => exec('justifyCenter')),
      btn('⬰',  'Alinhar à direita',     () => exec('justifyRight')),
      btn('⊟',  'Justificar',            () => exec('justifyFull'))
    ));

    // Insert
    toolbar.appendChild(group(
      btn('🔗', 'Inserir link (Ctrl+K)', () => insertLink()),
      btn('🔗̶', 'Remover link',          () => exec('unlink')),
      btn('🖼', 'Inserir imagem',         () => insertImage()),
      btn('▦',  'Inserir tabela',         b => openTablePicker(b, (r, c) => insertTable(r, c))),
      btn('❝',  'Citação',                () => exec('formatBlock', 'BLOCKQUOTE')),
      btn('—',  'Linha divisória',        () => exec('insertHorizontalRule')),
      btn('{ }','Código inline',          () => insertCode())
    ));

    // Ações
    toolbar.appendChild(group(
      btn('↶',   'Desfazer (Ctrl+Z)',      () => exec('undo')),
      btn('↷',   'Refazer (Ctrl+Shift+Z)', () => exec('redo')),
      btn('🔍',  'Localizar (Ctrl+F)',     () => openFind()),
      btn('&lt;/&gt;', 'Modo HTML',         b => toggleSource(b)),
      btn('⛶',   'Tela cheia (F11)',       b => toggleFullscreen(b))
    ));

    // ── Funções de inserção ──────────────────────────────────────────────
    function growFont(delta) {
      const sel = window.getSelection();
      if (!sel.rangeCount) return;
      const range = sel.getRangeAt(0);
      if (range.collapsed) return;
      const span = document.createElement('span');
      const cur = parseFloat(window.getComputedStyle(area).fontSize);
      span.style.fontSize = Math.max(8, cur + delta) + 'px';
      try {
        range.surroundContents(span);
      } catch {
        // Selection cruza elementos — fallback usando execCommand
        exec('fontSize', delta > 0 ? '5' : '2');
      }
      sync();
    }

    // Helper: restaura range salva e executa insertHTML no ponto correto.
    function insertHtmlAtSavedRange(html) {
      restoreSavedRange(area);
      const sel = window.getSelection();
      if (sel.rangeCount === 0) {
        // Sem range — append no fim do area
        area.focus();
        document.execCommand('insertHTML', false, html);
      } else {
        const range = sel.getRangeAt(0);
        range.deleteContents();
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        const frag = document.createDocumentFragment();
        let lastNode;
        while (tmp.firstChild) {
          lastNode = frag.appendChild(tmp.firstChild);
        }
        range.insertNode(frag);
        if (lastNode) {
          range.setStartAfter(lastNode);
          range.setEndAfter(lastNode);
          sel.removeAllRanges();
          sel.addRange(range);
        }
      }
      sync();
    }

    function insertLink() {
      const savedR = saveCurrentRange() || getSavedRange(area);
      const selectedText = savedR ? savedR.toString() : '';
      openModal('Inserir link', [
        { name: 'url',    label: 'URL:',          type: 'url',  placeholder: 'https://' },
        { name: 'text',   label: 'Texto exibido:', type: 'text', value: selectedText },
        { name: 'target', label: 'Abrir em nova aba? (s/n)', type: 'text', value: 's' },
      ], v => {
        if (!v.url) return;
        const target = v.target.toLowerCase().startsWith('s') ? '_blank' : '_self';
        const rel = target === '_blank' ? ' rel="noopener noreferrer"' : '';
        const text = v.text || v.url;
        insertHtmlAtSavedRange(`<a href="${escapeAttr(v.url)}" target="${target}"${rel}>${escapeHtml(text)}</a>`);
      });
    }

    function insertImage() {
      openModal('Inserir imagem', [
        { name: 'url', label: 'URL da imagem:', type: 'url', placeholder: 'https://... ou /assets/images/...' },
        { name: 'alt', label: 'Texto alternativo (alt):', type: 'text' },
      ], v => {
        if (!v.url) return;
        insertHtmlAtSavedRange(`<img src="${escapeAttr(v.url)}" alt="${escapeAttr(v.alt || '')}" style="max-width:100%;height:auto">`);
      });
    }

    function insertTable(rows, cols) {
      let html = '<table><tbody>';
      for (let r = 0; r < rows; r++) {
        html += '<tr>';
        for (let c = 0; c < cols; c++) {
          html += r === 0 ? '<th>&nbsp;</th>' : '<td>&nbsp;</td>';
        }
        html += '</tr>';
      }
      html += '</tbody></table><p><br></p>';
      insertHtmlAtSavedRange(html);
    }

    function insertCode() {
      const savedR = getSavedRange(area);
      const text = savedR ? savedR.toString() : '';
      if (text) {
        insertHtmlAtSavedRange(`<code>${escapeHtml(text)}</code>`);
      } else {
        insertHtmlAtSavedRange('<code>&nbsp;</code>');
      }
    }

    function openFind() {
      const term = prompt('Localizar:');
      if (!term) return;
      const replace = prompt('Substituir por (deixe vazio para só localizar):');
      if (replace === null) return;
      if (replace === '') {
        // só destaca a primeira ocorrência
        const range = document.createRange();
        const walker = document.createTreeWalker(area, NodeFilter.SHOW_TEXT);
        let node;
        while ((node = walker.nextNode())) {
          const idx = node.textContent.indexOf(term);
          if (idx >= 0) {
            range.setStart(node, idx);
            range.setEnd(node, idx + term.length);
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
            return;
          }
        }
        alert('Não encontrado.');
      } else {
        area.innerHTML = area.innerHTML.split(term).join(replace);
        sync();
      }
    }

    function toggleSource(b) {
      sourceMode = !sourceMode;
      if (sourceMode) {
        source.value = area.innerHTML;
        source.style.display = 'block';
        area.style.display = 'none';
        b.classList.add('active');
      } else {
        area.innerHTML = source.value || '<p><br></p>';
        source.style.display = 'none';
        area.style.display = 'block';
        b.classList.remove('active');
      }
      sync();
    }

    function toggleFullscreen(b) {
      fullscreen = !fullscreen;
      wrap.classList.toggle('fullscreen', fullscreen);
      b.classList.toggle('active', fullscreen);
      document.body.style.overflow = fullscreen ? 'hidden' : '';
    }

    // ── Eventos ─────────────────────────────────────────────────────────
    area.addEventListener('input',  sync);
    area.addEventListener('blur',   sync);
    source.addEventListener('input', sync);

    // Limit de caracteres (para Resumo)
    area.addEventListener('keydown', e => {
      // Atalhos primeiro
      if (e.ctrlKey || e.metaKey) {
        switch (e.key.toLowerCase()) {
          case 'b': e.preventDefault(); exec('bold');      return;
          case 'i': e.preventDefault(); exec('italic');    return;
          case 'u': e.preventDefault(); exec('underline'); return;
          case 'k': e.preventDefault(); insertLink();      return;
          case 'f': e.preventDefault(); openFind();        return;
        }
      }
      if (e.key === 'F11') { e.preventDefault(); toggleFullscreen(toolbar.querySelector('[title^="Tela"]')); return; }
      if (e.key === 'Escape' && fullscreen) { toggleFullscreen(toolbar.querySelector('[title^="Tela"]')); return; }

      // Limit de chars
      if (opts.maxLength) {
        const cur = (area.innerText || '').length;
        if (cur >= opts.maxLength && e.key.length === 1 && !e.ctrlKey && !e.metaKey) {
          e.preventDefault();
        }
      }
    });

    // Paste limpo (preserva formatação básica, mata mso/Word lixo)
    area.addEventListener('paste', e => {
      e.preventDefault();
      const cd   = e.clipboardData || window.clipboardData;
      const html = cd.getData('text/html');
      const text = cd.getData('text/plain');
      if (html) {
        let clean = html
          .replace(/<!--[\s\S]*?-->/g, '')
          .replace(/<\/?(meta|link|style|script|html|body|head|o:p|w:[a-z]+|m:[a-z]+|v:[a-z]+)[^>]*>/gi, '')
          .replace(/\sclass\s*=\s*"[^"]*"/gi, '')
          .replace(/\sstyle\s*=\s*"[^"]*?(mso-|Word|font-family:[^;"]+;?)[^"]*"/gi, '')
          .replace(/\s(lang|dir|xml:lang)\s*=\s*"[^"]*"/gi, '')
          .replace(/<(p|div|span)\s*>\s*<\/\1>/gi, '');
        document.execCommand('insertHTML', false, clean);
      } else {
        document.execCommand('insertText', false, text);
      }
      sync();
    });

    // Sync no submit
    const form = textarea.closest('form');
    if (form) form.addEventListener('submit', sync);

    // Esconde textarea original
    textarea.style.display = 'none';
    wrap.appendChild(toolbar);
    wrap.appendChild(area);
    wrap.appendChild(source);
    wrap.appendChild(status);
    textarea.parentNode.insertBefore(wrap, textarea.nextSibling);

    // Tracking contínuo de seleção (essencial para cor/link/imagem/tabela
    // funcionarem mesmo após clique em outros elementos da toolbar).
    registerSelectionTracking(area);

    updateStatus();
  }

  // ── Helpers HTML escape ─────────────────────────────────────────────────
  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
  function escapeAttr(s) {
    return String(s).replace(/"/g, '&quot;').replace(/</g, '&lt;');
  }

  // ── Bootstrap ───────────────────────────────────────────────────────────
  function init() {
    injectStyles();
    document.querySelectorAll('textarea.rich-editor, textarea[data-rich-editor]').forEach(ta => {
      // Não tomar conta se o TinyMCE já gerencia
      if (window.tinymce && window.tinymce.get && window.tinymce.get(ta.id)) return;
      initEditor(ta);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.LiteEditor = { init, initEditor };
})();
