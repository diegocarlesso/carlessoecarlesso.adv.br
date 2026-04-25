/* ═══════════════════════════════════════════════════════════
   editor.js — Editor rich-text minimalista (sem dependências)
   Aplica-se a qualquer <textarea class="rich-editor">
   Sem TinyMCE necessário. Funciona offline, ~5KB.
═══════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  function makeBtn(label, title, opts = {}) {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'le-btn' + (opts.cls ? ' ' + opts.cls : '');
    b.title = title;
    b.innerHTML = label;
    return b;
  }

  function initEditor(textarea) {
    if (textarea.dataset.leInit) return;
    textarea.dataset.leInit = '1';

    // Wrapper
    const wrap = document.createElement('div');
    wrap.className = 'lite-editor';

    // Toolbar
    const toolbar = document.createElement('div');
    toolbar.className = 'le-toolbar';

    // Editor area
    const area = document.createElement('div');
    area.className = 'le-area';
    area.contentEditable = 'true';
    area.spellcheck = true;
    area.innerHTML = textarea.value || '<p><br></p>';

    // Source view (HTML) area
    const source = document.createElement('textarea');
    source.className = 'le-source';
    source.style.display = 'none';
    source.value = textarea.value;

    // ── Sync helper ─────────────────────────────────────────
    function syncToTextarea() {
      if (source.style.display !== 'none') {
        textarea.value = source.value;
        area.innerHTML = source.value || '<p><br></p>';
      } else {
        textarea.value = area.innerHTML;
        source.value = area.innerHTML;
      }
    }

    function exec(cmd, arg) {
      area.focus();
      document.execCommand(cmd, false, arg ?? null);
      syncToTextarea();
    }

    function getSelectionParent() {
      const sel = window.getSelection();
      if (!sel.rangeCount) return null;
      let node = sel.getRangeAt(0).commonAncestorContainer;
      if (node.nodeType === 3) node = node.parentNode;
      return node;
    }

    // ── Botões ──────────────────────────────────────────────
    const btnBold      = makeBtn('<b>B</b>',     'Negrito (Ctrl+B)');
    const btnItalic    = makeBtn('<i>I</i>',     'Itálico (Ctrl+I)');
    const btnUnderline = makeBtn('<u>U</u>',     'Sublinhado (Ctrl+U)');
    const btnStrike    = makeBtn('<s>S</s>',     'Tachado');

    const btnH2        = makeBtn('H2',           'Título 2');
    const btnH3        = makeBtn('H3',           'Título 3');
    const btnP         = makeBtn('P',            'Parágrafo');

    const btnUl        = makeBtn('• Lista',      'Lista');
    const btnOl        = makeBtn('1. Lista',     'Lista numerada');

    const btnAlignL    = makeBtn('⇤',           'Alinhar à esquerda');
    const btnAlignC    = makeBtn('☰',           'Centralizar');
    const btnAlignR    = makeBtn('⇥',           'Alinhar à direita');

    const btnLink      = makeBtn('🔗 Link',     'Inserir link');
    const btnUnlink    = makeBtn('Sem link',     'Remover link');
    const btnImage     = makeBtn('🖼 Img',       'Inserir imagem por URL');
    const btnHr        = makeBtn('—',            'Linha divisória');
    const btnQuote     = makeBtn('❝',            'Citação');

    const btnUndo      = makeBtn('↶',            'Desfazer (Ctrl+Z)');
    const btnRedo      = makeBtn('↷',            'Refazer (Ctrl+Y)');
    const btnClear     = makeBtn('Limpar',       'Limpar formatação');
    const btnSource    = makeBtn('&lt;/&gt;',    'Ver código HTML');

    btnBold.onclick      = () => exec('bold');
    btnItalic.onclick    = () => exec('italic');
    btnUnderline.onclick = () => exec('underline');
    btnStrike.onclick    = () => exec('strikeThrough');

    btnH2.onclick        = () => exec('formatBlock', 'H2');
    btnH3.onclick        = () => exec('formatBlock', 'H3');
    btnP.onclick         = () => exec('formatBlock', 'P');

    btnUl.onclick        = () => exec('insertUnorderedList');
    btnOl.onclick        = () => exec('insertOrderedList');

    btnAlignL.onclick    = () => exec('justifyLeft');
    btnAlignC.onclick    = () => exec('justifyCenter');
    btnAlignR.onclick    = () => exec('justifyRight');

    btnLink.onclick = () => {
      const sel = window.getSelection();
      const txt = sel.toString();
      const url = prompt('URL do link:', 'https://');
      if (!url) return;
      if (!txt) {
        const display = prompt('Texto exibido:', url) || url;
        exec('insertHTML', `<a href="${url}" target="_blank" rel="noopener">${display}</a>`);
      } else {
        exec('createLink', url);
      }
    };
    btnUnlink.onclick = () => exec('unlink');

    btnImage.onclick = () => {
      const url = prompt('URL da imagem:', 'https://');
      if (url) exec('insertHTML', `<img src="${url}" alt="" style="max-width:100%;height:auto">`);
    };

    btnHr.onclick    = () => exec('insertHorizontalRule');
    btnQuote.onclick = () => exec('formatBlock', 'BLOCKQUOTE');
    btnUndo.onclick  = () => exec('undo');
    btnRedo.onclick  = () => exec('redo');
    btnClear.onclick = () => exec('removeFormat');

    btnSource.onclick = () => {
      const showingSource = source.style.display !== 'none';
      if (showingSource) {
        // Volta para visual
        area.innerHTML = source.value || '<p><br></p>';
        source.style.display = 'none';
        area.style.display = 'block';
        btnSource.classList.remove('active');
      } else {
        // Mostra HTML
        source.value = area.innerHTML;
        source.style.display = 'block';
        area.style.display = 'none';
        btnSource.classList.add('active');
      }
      syncToTextarea();
    };

    // Separadores
    const sep = () => {
      const s = document.createElement('span');
      s.className = 'le-sep';
      return s;
    };

    [
      btnBold, btnItalic, btnUnderline, btnStrike, sep(),
      btnH2, btnH3, btnP, sep(),
      btnUl, btnOl, sep(),
      btnAlignL, btnAlignC, btnAlignR, sep(),
      btnLink, btnUnlink, btnImage, btnQuote, btnHr, sep(),
      btnUndo, btnRedo, btnClear, sep(),
      btnSource,
    ].forEach(el => toolbar.appendChild(el));

    // ── Eventos ─────────────────────────────────────────────
    area.addEventListener('input',  syncToTextarea);
    area.addEventListener('blur',   syncToTextarea);
    source.addEventListener('input', syncToTextarea);

    // Atalhos
    area.addEventListener('keydown', e => {
      if (e.ctrlKey || e.metaKey) {
        if (e.key === 'b') { e.preventDefault(); exec('bold'); }
        else if (e.key === 'i') { e.preventDefault(); exec('italic'); }
        else if (e.key === 'u') { e.preventDefault(); exec('underline'); }
        else if (e.key === 'k') { e.preventDefault(); btnLink.click(); }
      }
    });

    // Cola texto puro (evita lixo de HTML do Word/sites)
    area.addEventListener('paste', e => {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text/plain');
      document.execCommand('insertText', false, text);
    });

    // Sync no submit do form
    const form = textarea.closest('form');
    if (form) {
      form.addEventListener('submit', syncToTextarea);
    }

    // Esconde textarea original e injeta editor
    textarea.style.display = 'none';
    wrap.appendChild(toolbar);
    wrap.appendChild(area);
    wrap.appendChild(source);
    textarea.parentNode.insertBefore(wrap, textarea.nextSibling);
  }

  function init() {
    document.querySelectorAll('textarea.rich-editor, textarea[data-rich-editor]').forEach(initEditor);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.LiteEditor = { init, initEditor };
})();
