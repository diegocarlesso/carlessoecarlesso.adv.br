/**
 * ui/inspector-fields.js — Helpers DOM para construir campos de form
 * usados pelo Inspector. Devolvem HTMLElements puros, sem framework.
 *
 * Cada bloco usa `field(type, label, value, onChange, opts)` para declarar
 * controles do inspector, e `fieldset(title, [...])` pra agrupar.
 */

/**
 * field(type, label, value, onChange, opts?)
 * type: 'text' | 'textarea' | 'select' | 'color' | 'number'
 * opts: {
 *   options: [[value, label], ...] (para select),
 *   rows: number (para textarea),
 *   placeholder, hint, monospace
 * }
 */
export function field(type, label, value, onChange, opts = {}) {
  const wrap = document.createElement('label');
  wrap.className = 'fld';

  const lbl = document.createElement('span');
  lbl.className = 'fld-label';
  lbl.textContent = label;
  wrap.appendChild(lbl);

  let input;
  switch (type) {
    case 'textarea': {
      input = document.createElement('textarea');
      input.rows = opts.rows || 4;
      input.value = value ?? '';
      if (opts.monospace) input.classList.add('mono');
      input.addEventListener('input', () => onChange(input.value));
      break;
    }
    case 'select': {
      input = document.createElement('select');
      (opts.options || []).forEach(([v, label2]) => {
        const o = document.createElement('option');
        o.value = v; o.textContent = label2;
        if (String(value) === String(v)) o.selected = true;
        input.appendChild(o);
      });
      input.addEventListener('change', () => onChange(input.value));
      break;
    }
    case 'color': {
      // dois inputs: color picker + texto livre (vazio = sem cor)
      const row = document.createElement('span');
      row.className = 'fld-color-row';

      const picker = document.createElement('input');
      picker.type = 'color';
      picker.value = isHex(value) ? value : '#000000';
      const text = document.createElement('input');
      text.type = 'text';
      text.placeholder = '#hex ou vazio';
      text.value = value || '';
      text.maxLength = 7;

      const sync = (v) => {
        if (v === '' || isHex(v)) {
          if (v) picker.value = v;
          onChange(v);
        }
      };
      picker.addEventListener('input', () => { text.value = picker.value; sync(picker.value); });
      text.addEventListener('input', () => sync(text.value.trim()));

      row.appendChild(picker);
      row.appendChild(text);
      wrap.appendChild(row);
      if (opts.hint) wrap.appendChild(hint(opts.hint));
      return wrap;
    }
    case 'number': {
      input = document.createElement('input');
      input.type = 'number';
      input.value = value ?? '';
      if (opts.min != null) input.min = opts.min;
      if (opts.max != null) input.max = opts.max;
      if (opts.step != null) input.step = opts.step;
      input.addEventListener('input', () => onChange(input.value));
      break;
    }
    default: {
      input = document.createElement('input');
      input.type = 'text';
      input.value = value ?? '';
      if (opts.placeholder) input.placeholder = opts.placeholder;
      input.addEventListener('input', () => onChange(input.value));
    }
  }

  if (input) {
    input.className = 'fld-input';
    if (opts.monospace) input.classList.add('mono');
    wrap.appendChild(input);
  }
  if (opts.hint) wrap.appendChild(hint(opts.hint));
  return wrap;
}

export function fieldset(title, fields) {
  const fs = document.createElement('div');
  fs.className = 'fldset';
  const h = document.createElement('div');
  h.className = 'fldset-title';
  h.textContent = title;
  fs.appendChild(h);
  fields.forEach(f => fs.appendChild(f));
  return fs;
}

export function group(...nodes) {
  const g = document.createElement('div');
  g.className = 'fld-group';
  nodes.forEach(n => g.appendChild(n));
  return g;
}

function hint(text) {
  const h = document.createElement('span');
  h.className = 'fld-hint';
  h.textContent = text;
  return h;
}
function isHex(s) {
  return typeof s === 'string' && /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(s);
}
