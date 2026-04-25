/* blocks.js — Editor de blocos estilo Elementor */
(function () {
  'use strict';

  let blocks    = [];
  let dragIdx   = null;
  const canvas  = document.getElementById('blocks-canvas');
  const jsonOut = document.getElementById('blocks-json');

  // Carregar blocos existentes
  if (jsonOut && jsonOut.value) {
    try { blocks = JSON.parse(jsonOut.value) || []; } catch { blocks = []; }
  }

  // ── Renderizar canvas ────────────────────────────────
  function render() {
    if (!canvas) return;
    if (!blocks.length) {
      canvas.innerHTML = `
        <div class="blocks-empty">
          <div class="icon">⊕</div>
          <p>Nenhum bloco adicionado.<br>Clique em um botão acima para começar.</p>
        </div>`;
    } else {
      canvas.innerHTML = blocks.map((b, i) => blockHTML(b, i)).join('');
      attachEvents();
    }
    sync();
  }

  function blockHTML(block, idx) {
    const label = { heading:'Título', text:'Texto', image:'Imagem', button:'Botão', divider:'Divisor' }[block.type] || block.type;
    return `
      <div class="block-item" data-idx="${idx}" draggable="true">
        <div class="block-item-header">
          <span class="block-drag-handle" title="Arrastar">⠿</span>
          <span>${label}</span>
          <div class="block-item-actions">
            <button class="block-action" onclick="blockMove(${idx},-1)" title="Mover para cima">↑</button>
            <button class="block-action" onclick="blockMove(${idx},1)"  title="Mover para baixo">↓</button>
            <button class="block-action" onclick="blockDupe(${idx})"   title="Duplicar">⎘</button>
            <button class="block-action delete" onclick="blockDel(${idx})" title="Remover">✕</button>
          </div>
        </div>
        <div class="block-item-body">
          ${fieldHTML(block, idx)}
        </div>
      </div>`;
  }

  function fieldHTML(block, idx) {
    const d = block.data || {};
    switch (block.type) {
      case 'heading': return `
        <select onchange="blockSet(${idx},'level',this.value)">
          ${[1,2,3,4,5,6].map(n=>`<option value="${n}" ${d.level==n?'selected':''}>${'H'+n}</option>`).join('')}
        </select>
        <input type="text" placeholder="Texto do título" value="${esc(d.text||'')}"
          oninput="blockSet(${idx},'text',this.value)">
        <input type="text" placeholder="Estilo CSS inline (opcional)" value="${esc(d.style||'')}"
          oninput="blockSet(${idx},'style',this.value)">`;

      case 'text': return `
        <textarea placeholder="HTML do conteúdo (parágrafos, listas, etc.)"
          oninput="blockSet(${idx},'html',this.value)">${esc(d.html||'')}</textarea>
        <input type="text" placeholder="Estilo CSS inline (opcional)" value="${esc(d.style||'')}"
          oninput="blockSet(${idx},'style',this.value)">`;

      case 'image': return `
        <input type="text" placeholder="URL da imagem" value="${esc(d.url||'')}"
          oninput="blockSet(${idx},'url',this.value)">
        <input type="text" placeholder="Texto alternativo (alt)" value="${esc(d.alt||'')}"
          oninput="blockSet(${idx},'alt',this.value)">
        <input type="text" placeholder="Legenda (opcional)" value="${esc(d.caption||'')}"
          oninput="blockSet(${idx},'caption',this.value)">
        <select onchange="blockSet(${idx},'align',this.value)">
          <option value="left" ${d.align==='left'?'selected':''}>Alinhar à esquerda</option>
          <option value="center" ${d.align==='center'?'selected':''}>Centralizar</option>
          <option value="right" ${d.align==='right'?'selected':''}>Alinhar à direita</option>
        </select>
        <button type="button" onclick="pickMedia(${idx})" style="margin-top:6px;padding:6px 12px;font-size:.78rem;cursor:pointer;border:1px solid #ccc;border-radius:4px;background:#fff">
          📷 Selecionar da Biblioteca
        </button>`;

      case 'button': return `
        <input type="text" placeholder="Texto do botão" value="${esc(d.text||'')}"
          oninput="blockSet(${idx},'text',this.value)">
        <input type="text" placeholder="URL / link" value="${esc(d.url||'#')}"
          oninput="blockSet(${idx},'url',this.value)">
        <select onchange="blockSet(${idx},'style',this.value)">
          <option value="primary" ${d.style==='primary'?'selected':''}>Primário (dourado)</option>
          <option value="secondary" ${d.style==='secondary'?'selected':''}>Secundário (azul)</option>
          <option value="outline" ${d.style==='outline'?'selected':''}>Contorno</option>
        </select>
        <select onchange="blockSet(${idx},'align',this.value)">
          <option value="left" ${d.align==='left'?'selected':''}>Esquerda</option>
          <option value="center" ${d.align==='center'?'selected':''}>Centro</option>
          <option value="right" ${d.align==='right'?'selected':''}>Direita</option>
        </select>`;

      case 'divider': return `<p style="font-size:.78rem;color:#999;margin:0">Linha separadora horizontal.</p>`;

      default: return `<p>Tipo de bloco desconhecido: ${esc(block.type)}</p>`;
    }
  }

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  // ── Sync para hidden input ───────────────────────────
  function sync() {
    if (jsonOut) jsonOut.value = JSON.stringify(blocks);
  }

  // ── Operações de bloco ───────────────────────────────
  window.blockSet = (idx, key, val) => {
    if (!blocks[idx]) return;
    blocks[idx].data = blocks[idx].data || {};
    blocks[idx].data[key] = val;
    sync();
  };

  window.blockDel = idx => {
    if (confirm('Remover este bloco?')) {
      blocks.splice(idx, 1);
      render();
    }
  };

  window.blockMove = (idx, dir) => {
    const to = idx + dir;
    if (to < 0 || to >= blocks.length) return;
    [blocks[idx], blocks[to]] = [blocks[to], blocks[idx]];
    render();
  };

  window.blockDupe = idx => {
    const clone = JSON.parse(JSON.stringify(blocks[idx]));
    blocks.splice(idx + 1, 0, clone);
    render();
  };

  // Adicionar bloco (chamado pelos botões da toolbar)
  window.addBlock = type => {
    const defaults = {
      heading: { level: 2, text: 'Novo Título', style: '' },
      text:    { html: '<p>Escreva seu conteúdo aqui...</p>', style: '' },
      image:   { url: '', alt: '', caption: '', align: 'center' },
      button:  { text: 'Saiba Mais', url: '#', style: 'primary', align: 'left' },
      divider: {},
    };
    blocks.push({ type, data: defaults[type] || {} });
    render();
  };

  // ── Drag & Drop ──────────────────────────────────────
  function attachEvents() {
    canvas.querySelectorAll('.block-item').forEach(el => {
      const idx = parseInt(el.dataset.idx);

      el.addEventListener('dragstart', e => {
        dragIdx = idx;
        e.dataTransfer.effectAllowed = 'move';
        el.style.opacity = '.4';
      });

      el.addEventListener('dragend', () => {
        el.style.opacity = '1';
        canvas.querySelectorAll('.block-item').forEach(b => b.classList.remove('drag-over'));
        dragIdx = null;
      });

      el.addEventListener('dragover', e => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        canvas.querySelectorAll('.block-item').forEach(b => b.classList.remove('drag-over'));
        el.classList.add('drag-over');
      });

      el.addEventListener('drop', e => {
        e.preventDefault();
        const toIdx = parseInt(el.dataset.idx);
        if (dragIdx === null || dragIdx === toIdx) return;
        const dragged = blocks.splice(dragIdx, 1)[0];
        blocks.splice(toIdx, 0, dragged);
        render();
      });
    });
  }

  // ── Picker de mídia ──────────────────────────────────
  window.pickMedia = async (blockIdx) => {
    try {
      const res  = await fetch('/api/media.php?action=list');
      const json = await res.json();
      if (!json.success) return;

      const modal = document.createElement('div');
      modal.className = 'modal-overlay open';
      modal.innerHTML = `
        <div class="modal" style="max-width:800px">
          <div class="modal-header">
            <h4>Biblioteca de Mídia</h4>
            <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">✕</button>
          </div>
          <div class="modal-body">
            <div class="media-grid">
              ${json.media.map(m => `
                <div class="media-thumb" data-url="${esc(m.file_path)}" data-alt="${esc(m.original_name)}">
                  <img src="${esc(m.file_path)}" alt="${esc(m.original_name)}" loading="lazy">
                  <div class="overlay">${esc(m.original_name)}</div>
                </div>`).join('')}
            </div>
          </div>
          <div class="modal-footer">
            <button class="topbar-btn outline" onclick="this.closest('.modal-overlay').remove()">Cancelar</button>
            <button class="topbar-btn primary" id="select-media-btn">Selecionar</button>
          </div>
        </div>`;

      document.body.appendChild(modal);

      let selected = null;
      modal.querySelectorAll('.media-thumb').forEach(th => {
        th.addEventListener('click', () => {
          modal.querySelectorAll('.media-thumb').forEach(t => t.classList.remove('selected'));
          th.classList.add('selected');
          selected = { url: th.dataset.url, alt: th.dataset.alt };
        });
      });

      modal.querySelector('#select-media-btn').addEventListener('click', () => {
        if (selected) {
          blockSet(blockIdx, 'url', selected.url);
          blockSet(blockIdx, 'alt', selected.alt);
          render();
        }
        modal.remove();
      });
    } catch (err) {
      console.error('Erro ao carregar mídia:', err);
    }
  };

  // ── Init ─────────────────────────────────────────────
  render();

})();
