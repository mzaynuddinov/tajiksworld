(function () {
  'use strict';
  const cfg = window.TajiksWorldBuilder;
  const root = document.getElementById('tw-builder-app');
  if (!cfg || !root || !cfg.registry) {
    return;
  }

  const t = (key) => (cfg.i18n && cfg.i18n[key]) || key;
  let layout = { version: 1, sections: [] };
  let history = [];
  let future = [];
  let selected = null;
  let armed = 'heading';
  let device = 'desktop';
  let previewTimer = 0;
  let statusText = '';

  const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&',
    '<': '<',
    '>': '>',
    '"': '"',
    "'": '&#39;',
  }[char]));

  const clone = (value) => JSON.parse(JSON.stringify(value));
  const uid = (prefix) => prefix + '_' + Math.random().toString(36).slice(2, 10);

  function blankBlock(type) {
    const def = cfg.registry[type] || cfg.registry.spacer;
    const settings = {};
    (def.fields || []).forEach((field) => {
      settings[field.key] = field.default;
    });
    const block = { id: uid('blk'), type: def === cfg.registry[type] ? type : 'spacer', settings, style: {} };
    if (def.nesting === 'blocks') {
      block.blocks = [];
    }
    if (def.nesting === 'columns') {
      const count = Math.max(2, Math.min(4, Number(settings.count || 2)));
      block.columns = Array.from({ length: count }, () => ({
        id: uid('col'),
        width: Math.floor(100 / count),
        blocks: [],
      }));
    }
    return block;
  }

  function blankSection() {
    return {
      id: uid('sec'),
      settings: { container: 'boxed', gap: 28, padding_top: '24px', padding_bottom: '24px' },
      columns: [{ id: uid('col'), width: 100, blocks: [] }],
    };
  }

  function snapshot() {
    history.push(clone(layout));
    if (history.length > 50) {
      history.shift();
    }
    future = [];
  }

  function mapBlocks(list, id, fn) {
    return (list || []).map((block) => {
      if (block.id === id) {
        return fn(block);
      }
      const copy = clone(block);
      if (copy.blocks) {
        copy.blocks = mapBlocks(copy.blocks, id, fn);
      }
      if (copy.columns) {
        copy.columns = copy.columns.map((column) => ({ ...column, blocks: mapBlocks(column.blocks || [], id, fn) }));
      }
      return copy;
    });
  }

  function filterBlocks(list, id) {
    return (list || []).filter((block) => block.id !== id).map((block) => {
      const copy = clone(block);
      if (copy.blocks) {
        copy.blocks = filterBlocks(copy.blocks, id);
      }
      if (copy.columns) {
        copy.columns = copy.columns.map((column) => ({ ...column, blocks: filterBlocks(column.blocks || [], id) }));
      }
      return copy;
    });
  }

  function findBlock(id, list) {
    for (const block of list || []) {
      if (block.id === id) {
        return block;
      }
      const nested = findBlock(id, block.blocks) || findInColumns(id, block.columns);
      if (nested) {
        return nested;
      }
    }
    return null;
  }

  function findInColumns(id, columns) {
    for (const column of columns || []) {
      const found = findBlock(id, column.blocks || []);
      if (found) {
        return found;
      }
    }
    return null;
  }

  function findSection(id) {
    return layout.sections.find((section) => section.id === id) || null;
  }

  function locate(id) {
    const section = findSection(id);
    if (section) {
      return { kind: 'section', section };
    }
    for (const sectionItem of layout.sections) {
      for (const column of sectionItem.columns || []) {
        const block = findBlock(id, column.blocks || []);
        if (block) {
          return { kind: 'block', block, section: sectionItem };
        }
      }
    }
    return null;
  }

  async function api(path, options) {
    const response = await fetch(cfg.rest + path, {
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': cfg.nonce,
      },
      ...options,
    });
    const payload = await response.json();
    if (!response.ok) {
      const message = payload.message || 'Request failed';
      throw new Error(message);
    }
    return payload;
  }

  async function load() {
    const query = new URLSearchParams({
      context: cfg.context,
      post_id: String(cfg.postId || 0),
      which: 'draft',
    });
    const payload = await api('layout?' + query.toString(), { method: 'GET' });
    layout = payload.layout && payload.layout.sections ? payload.layout : { version: 1, sections: [] };
    render();
    schedulePreview();
  }

  function schedulePreview() {
    window.clearTimeout(previewTimer);
    previewTimer = window.setTimeout(pushPreview, 450);
  }

  async function pushPreview() {
    try {
      await api('preview', { method: 'POST', body: JSON.stringify({ layout, context: cfg.context, post_id: cfg.postId || 0 }) });
      const frame = root.querySelector('[data-preview]');
      if (frame) {
        const glue = cfg.preview.indexOf('?') === -1 ? '?' : '&';
        frame.src = cfg.preview + glue + 't=' + Date.now();
      }
    } catch (error) {
      statusText = error.message;
      paintStatus();
    }
  }

  async function save(status) {
    statusText = '…';
    paintStatus();
    try {
      await api('layout', {
        method: 'POST',
        body: JSON.stringify({ layout, status, context: cfg.context, post_id: cfg.postId || 0 }),
      });
      statusText = status === 'draft' ? t('draft') : t('published');
    } catch (error) {
      statusText = error.message;
    }
    paintStatus();
  }

  function paintStatus() {
    const node = root.querySelector('[data-status]');
    if (node) {
      node.textContent = statusText;
    }
  }

  function fieldControl(field, value, id) {
    const current = value == null ? '' : value;
    if (field.type === 'textarea' || field.type === 'html') {
      return '<textarea data-setting="' + esc(field.key) + '" data-id="' + esc(id) + '" rows="5">' + esc(current) + '</textarea>';
    }
    if (field.type === 'select') {
      const options = Object.entries(field.options || {}).map(([key, label]) => {
        return '<option value="' + esc(key) + '"' + (String(current) === String(key) ? ' selected' : '') + '>' + esc(label) + '</option>';
      }).join('');
      return '<select data-setting="' + esc(field.key) + '" data-id="' + esc(id) + '">' + options + '</select>';
    }
    if (field.type === 'category') {
      const options = ['<option value="">—</option>'].concat((cfg.categories || []).map((cat) => {
        return '<option value="' + esc(cat.slug) + '"' + (current === cat.slug ? ' selected' : '') + '>' + esc(cat.name) + '</option>';
      }));
      return '<select data-setting="' + esc(field.key) + '" data-id="' + esc(id) + '">' + options.join('') + '</select>';
    }
    if (field.type === 'checkbox') {
      return '<label class="twb-check"><input type="checkbox" data-setting="' + esc(field.key) + '" data-id="' + esc(id) + '"' + (current === true || current === '1' || current === 1 ? ' checked' : '') + ' /> ' + esc(field.label) + '</label>';
    }
    const type = field.type === 'number' ? 'number' : field.type === 'url' ? 'url' : 'text';
    return '<input type="' + type + '" data-setting="' + esc(field.key) + '" data-id="' + esc(id) + '" value="' + esc(current) + '" />';
  }

  function settingsPanel() {
    if (!selected) {
      return '<p class="twb-muted">' + esc(t('empty')) + '</p>';
    }
    const located = locate(selected);
    if (!located) {
      return '<p class="twb-muted">' + esc(t('empty')) + '</p>';
    }
    if (located.kind === 'section') {
      const settings = located.section.settings || {};
      return [
        '<h3>Section</h3>',
        labeled('Container', '<select data-section="' + esc(located.section.id) + '" data-skey="container"><option value="boxed">Boxed</option><option value="wide"' + (settings.container === 'wide' ? ' selected' : '') + '>Wide</option><option value="full"' + (settings.container === 'full' ? ' selected' : '') + '>Full</option></select>'),
        labeled('Gap', '<input type="number" min="0" max="80" data-section="' + esc(located.section.id) + '" data-skey="gap" value="' + esc(settings.gap ?? 28) + '" />'),
        labeled('Padding top', '<input data-section="' + esc(located.section.id) + '" data-skey="padding_top" value="' + esc(settings.padding_top || '') + '" />'),
        labeled('Padding bottom', '<input data-section="' + esc(located.section.id) + '" data-skey="padding_bottom" value="' + esc(settings.padding_bottom || '') + '" />'),
        labeled('Background', '<input data-section="' + esc(located.section.id) + '" data-skey="background" value="' + esc(settings.background || '') + '" placeholder="#f4f1ea" />'),
        labeled('Text', '<input data-section="' + esc(located.section.id) + '" data-skey="color" value="' + esc(settings.color || '') + '" placeholder="#161513" />'),
        labeled('Border width', '<input type="number" min="0" max="12" data-section="' + esc(located.section.id) + '" data-skey="border_width" value="' + esc(settings.border_width || 0) + '" />'),
        labeled('Border color', '<input data-section="' + esc(located.section.id) + '" data-skey="border_color" value="' + esc(settings.border_color || '') + '" />'),
        labeled('Radius', '<input type="number" min="0" max="40" data-section="' + esc(located.section.id) + '" data-skey="radius" value="' + esc(settings.radius || 0) + '" />'),
        labeled('CSS class', '<input data-section="' + esc(located.section.id) + '" data-skey="css_class" value="' + esc(settings.css_class || '') + '" />'),
        labeled('CSS id', '<input data-section="' + esc(located.section.id) + '" data-skey="css_id" value="' + esc(settings.css_id || '') + '" />'),
        '<label class="twb-check"><input type="checkbox" data-section="' + esc(located.section.id) + '" data-skey="hide_desktop"' + (settings.hide_desktop ? ' checked' : '') + ' /> Hide desktop</label>',
        '<label class="twb-check"><input type="checkbox" data-section="' + esc(located.section.id) + '" data-skey="hide_tablet"' + (settings.hide_tablet ? ' checked' : '') + ' /> Hide tablet</label>',
        '<label class="twb-check"><input type="checkbox" data-section="' + esc(located.section.id) + '" data-skey="hide_mobile"' + (settings.hide_mobile ? ' checked' : '') + ' /> Hide mobile</label>',
        '<p><button type="button" data-lib-save="' + esc(located.section.id) + '">Save to library</button></p>',
        '<h3>Columns</h3>',
        (located.section.columns || []).map((column, index) => '<p>Column ' + (index + 1) + ' width <input type="number" min="10" max="100" data-col="' + esc(column.id) + '" data-section="' + esc(located.section.id) + '" value="' + esc(column.width) + '" /></p>').join(''),
        '<p><button type="button" data-add-col="' + esc(located.section.id) + '">Add column</button></p>',
      ].join('');
    }
    const def = cfg.registry[located.block.type];
    if (!def) {
      return '';
    }
    const fields = (def.fields || []).map((field) => {
      if (field.type === 'checkbox') {
        return fieldControl(field, located.block.settings[field.key], located.block.id);
      }
      return labeled(field.label, fieldControl(field, located.block.settings[field.key], located.block.id));
    }).join('');
    const style = located.block.style || {};
    return '<h3>' + esc(def.label) + '</h3>' + fields +
      labeled('Margin top', '<input data-style="margin_top" data-id="' + esc(located.block.id) + '" value="' + esc(style.margin_top || '') + '" />') +
      labeled('Margin bottom', '<input data-style="margin_bottom" data-id="' + esc(located.block.id) + '" value="' + esc(style.margin_bottom || '') + '" />') +
      '<label class="twb-check"><input type="checkbox" data-style="hide_desktop" data-id="' + esc(located.block.id) + '"' + (style.hide_desktop ? ' checked' : '') + ' /> Hide desktop</label>' +
      '<label class="twb-check"><input type="checkbox" data-style="hide_tablet" data-id="' + esc(located.block.id) + '"' + (style.hide_tablet ? ' checked' : '') + ' /> Hide tablet</label>' +
      '<label class="twb-check"><input type="checkbox" data-style="hide_mobile" data-id="' + esc(located.block.id) + '"' + (style.hide_mobile ? ' checked' : '') + ' /> Hide mobile</label>';
  }

  function labeled(label, control) {
    return '<label class="twb-field"><span>' + esc(label) + '</span>' + control + '</label>';
  }

  function blockTree(block) {
    const def = cfg.registry[block.type];
    const label = def ? def.label : block.type;
    const preview = block.settings && (block.settings.text || block.settings.title || block.settings.label || '');
    let nested = '';
    if (block.columns) {
      nested = '<div class="twb-nested">' + block.columns.map((column) => {
        return '<div class="twb-col" data-drop="' + esc(column.id) + '"><header><span>' + esc(column.width) + '%</span><button type="button" data-insert="' + esc(column.id) + '">+ Add</button></header>' +
          (column.blocks || []).map(blockTree).join('') + '</div>';
      }).join('') + '</div>';
    }
    if (block.blocks) {
      nested += '<div class="twb-nested" data-drop-blocks="' + esc(block.id) + '">' + block.blocks.map(blockTree).join('') +
        '<button type="button" data-insert-blocks="' + esc(block.id) + '">+ Add inside</button></div>';
    }
    return '<article class="twb-block' + (selected === block.id ? ' is-selected' : '') + '" draggable="true" data-block="' + esc(block.id) + '"><header><button type="button" data-select="' + esc(block.id) + '">' + esc(label) + '</button><em>' + esc(preview) + '</em><span><button type="button" data-up="' + esc(block.id) + '">↑</button><button type="button" data-down="' + esc(block.id) + '">↓</button><button type="button" data-dup="' + esc(block.id) + '">⧉</button><button type="button" data-del="' + esc(block.id) + '">✕</button></span></header>' + nested + '</article>';
  }

  function canvas() {
    if (!layout.sections.length) {
      return '<p class="twb-muted">Add a section to start this layout.</p>';
    }
    return layout.sections.map((section, index) => {
      const columns = (section.columns || []).map((column) => {
        return '<div class="twb-col" data-drop="' + esc(column.id) + '" data-section="' + esc(section.id) + '"><header><span>' + esc(column.width) + '%</span><button type="button" data-insert="' + esc(column.id) + '" data-section="' + esc(section.id) + '">+ Add</button></header>' +
          (column.blocks || []).map(blockTree).join('') + '</div>';
      }).join('');
      return '<section class="twb-section' + (selected === section.id ? ' is-selected' : '') + '" draggable="true" data-section-card="' + esc(section.id) + '"><header><button type="button" data-select="' + esc(section.id) + '">Section ' + (index + 1) + '</button><span><button type="button" data-sec-up="' + esc(section.id) + '">↑</button><button type="button" data-sec-down="' + esc(section.id) + '">↓</button><button type="button" data-sec-dup="' + esc(section.id) + '">⧉</button><button type="button" data-sec-del="' + esc(section.id) + '">✕</button></span></header><div class="twb-cols">' + columns + '</div></section>';
    }).join('');
  }

  function palette() {
    const groups = {};
    Object.entries(cfg.registry).forEach(([type, def]) => {
      const category = def.category || 'basic';
      groups[category] = groups[category] || [];
      groups[category].push([type, def.label]);
    });
    return Object.entries(groups).map(([category, items]) => {
      return '<div class="twb-group"><h3>' + esc(category) + '</h3>' + items.map(([type, label]) => {
        return '<button type="button" draggable="true" class="twb-chip' + (armed === type ? ' is-armed' : '') + '" data-arm="' + esc(type) + '">' + esc(label) + '</button>';
      }).join('') + '</div>';
    }).join('');
  }

  function render() {
    const width = device === 'tablet' ? '800px' : device === 'mobile' ? '390px' : '100%';
    root.innerHTML = [
      '<div class="twb-toolbar">',
      '<button type="button" data-undo>Undo</button><button type="button" data-redo>Redo</button>',
      '<button type="button" data-device="desktop"' + (device === 'desktop' ? ' class="is-on"' : '') + '>Desktop</button>',
      '<button type="button" data-device="tablet"' + (device === 'tablet' ? ' class="is-on"' : '') + '>Tablet</button>',
      '<button type="button" data-device="mobile"' + (device === 'mobile' ? ' class="is-on"' : '') + '>Mobile</button>',
      '<button type="button" data-add-section>Add section</button>',
      '<button type="button" data-export>Export</button><label class="twb-import">Import<input type="file" accept="application/json" data-import /></label>',
      '<button type="button" data-library>Library</button>',
      '<button type="button" data-save="draft">Save draft</button><button type="button" class="twb-primary" data-save="publish">Publish</button>',
      '<strong data-status></strong></div>',
      '<div class="twb-body"><aside class="twb-palette">' + palette() + '</aside>',
      '<div class="twb-canvas">' + canvas() + '<div class="twb-frame" style="width:' + width + '"><iframe title="Preview" data-preview src="about:blank"></iframe></div></div>',
      '<aside class="twb-settings">' + settingsPanel() + '</aside></div>',
      '<div class="twb-library" data-library-panel hidden></div>',
    ].join('');
    paintStatus();
  }

  function move(list, id, direction) {
    const index = list.findIndex((item) => item.id === id);
    const next = index + direction;
    if (index < 0 || next < 0 || next >= list.length) {
      return list;
    }
    const copy = list.slice();
    const [item] = copy.splice(index, 1);
    copy.splice(next, 0, item);
    return copy;
  }

  function moveBlock(id, direction) {
    const shift = (list) => {
      if ((list || []).some((block) => block.id === id)) {
        return move(list, id, direction);
      }
      return (list || []).map((block) => {
        const copy = clone(block);
        if (copy.blocks) {
          copy.blocks = shift(copy.blocks);
        }
        if (copy.columns) {
          copy.columns = copy.columns.map((column) => ({ ...column, blocks: shift(column.blocks || []) }));
        }
        return copy;
      });
    };
    layout.sections = layout.sections.map((section) => ({
      ...section,
      columns: (section.columns || []).map((column) => ({ ...column, blocks: shift(column.blocks || []) })),
    }));
  }

  function insertIntoColumn(sectionId, columnId, block) {
    const place = (columns) => (columns || []).map((column) => {
      if (column.id === columnId) {
        return { ...column, blocks: (column.blocks || []).concat([block]) };
      }
      return {
        ...column,
        blocks: (column.blocks || []).map((child) => {
          const copy = clone(child);
          if (copy.columns) {
            copy.columns = place(copy.columns);
          }
          if (copy.blocks) {
            copy.blocks = copy.blocks.map((nested) => {
              const inner = clone(nested);
              if (inner.columns) {
                inner.columns = place(inner.columns);
              }
              return inner;
            });
          }
          return copy;
        }),
      };
    });
    layout.sections = layout.sections.map((section) => {
      if (sectionId && section.id !== sectionId) {
        return section;
      }
      return { ...section, columns: place(section.columns || []) };
    });
  }

  function insertIntoNested(parentId, block) {
    const add = (list) => (list || []).map((item) => {
      if (item.id === parentId) {
        const copy = clone(item);
        copy.blocks = (copy.blocks || []).concat([block]);
        return copy;
      }
      const copy = clone(item);
      if (copy.blocks) {
        copy.blocks = add(copy.blocks);
      }
      if (copy.columns) {
        copy.columns = copy.columns.map((column) => {
          if (column.id === parentId) {
            return { ...column, blocks: (column.blocks || []).concat([block]) };
          }
          return { ...column, blocks: add(column.blocks || []) };
        });
      }
      return copy;
    });
    layout.sections = layout.sections.map((section) => ({
      ...section,
      columns: (section.columns || []).map((column) => ({ ...column, blocks: add(column.blocks || []) })),
    }));
  }

  function reidentifyBlock(block) {
    const next = clone(block);
    next.id = uid('blk');
    if (next.blocks) {
      next.blocks = next.blocks.map(reidentifyBlock);
    }
    if (next.columns) {
      next.columns = next.columns.map((column) => ({
        ...column,
        id: uid('col'),
        blocks: (column.blocks || []).map(reidentifyBlock),
      }));
    }
    return next;
  }

  function reidentifySection(section) {
    const copy = clone(section);
    copy.id = uid('sec');
    copy.columns = (copy.columns || []).map((column) => ({
      ...column,
      id: uid('col'),
      blocks: (column.blocks || []).map(reidentifyBlock),
    }));
    return copy;
  }

  function duplicateBlock(id) {
    const source = locate(id);
    if (!source || source.kind !== 'block') {
      return;
    }
    const copy = reidentifyBlock(source.block);
    const sprinkle = (list) => (list || []).flatMap((block) => {
      if (block.id === id) {
        return [block, copy];
      }
      const next = clone(block);
      if (next.blocks) {
        next.blocks = sprinkle(next.blocks);
      }
      if (next.columns) {
        next.columns = next.columns.map((column) => ({ ...column, blocks: sprinkle(column.blocks || []) }));
      }
      return [next];
    });
    layout.sections = layout.sections.map((section) => ({
      ...section,
      columns: section.columns.map((column) => ({ ...column, blocks: sprinkle(column.blocks || []) })),
    }));
  }

  root.addEventListener('click', (event) => {
    const target = event.target.closest('button, label');
    if (!target) {
      return;
    }
    if (target.dataset.arm) {
      armed = target.dataset.arm;
      render();
      return;
    }
    if (target.dataset.device) {
      device = target.dataset.device;
      render();
      schedulePreview();
      return;
    }
    if (target.dataset.addSection !== undefined) {
      snapshot();
      layout.sections.push(blankSection());
      render();
      schedulePreview();
      return;
    }
    if (target.dataset.undo !== undefined) {
      if (history.length) {
        future.push(clone(layout));
        layout = history.pop();
        render();
        schedulePreview();
      }
      return;
    }
    if (target.dataset.redo !== undefined) {
      if (future.length) {
        history.push(clone(layout));
        layout = future.pop();
        render();
        schedulePreview();
      }
      return;
    }
    if (target.dataset.save) {
      save(target.dataset.save);
      return;
    }
    if (target.dataset.export !== undefined) {
      const blob = new Blob([JSON.stringify({ theme: 'tajiksworld', layout }, null, 2)], { type: 'application/json' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'tajiksworld-layout.json';
      link.click();
      return;
    }
    if (target.dataset.select) {
      selected = target.dataset.select;
      render();
      return;
    }
    if (target.dataset.insert) {
      snapshot();
      insertIntoColumn(target.dataset.section || '', target.dataset.insert, blankBlock(armed));
      render();
      schedulePreview();
      return;
    }
    if (target.dataset.insertBlocks) {
      snapshot();
      insertIntoNested(target.dataset.insertBlocks, blankBlock(armed));
      render();
      schedulePreview();
      return;
    }
    if (target.dataset.secDel) {
      if (!window.confirm(t('confirmDelete'))) {
        return;
      }
      snapshot();
      layout.sections = layout.sections.filter((section) => section.id !== target.dataset.secDel);
      selected = null;
      render();
      schedulePreview();
      return;
    }
    if (target.dataset.del) {
      snapshot();
      layout.sections = layout.sections.map((section) => ({
        ...section,
        columns: section.columns.map((column) => ({ ...column, blocks: filterBlocks(column.blocks || [], target.dataset.del) })),
      }));
      selected = null;
      render();
      schedulePreview();
      return;
    }
    if (target.dataset.secUp || target.dataset.secDown) {
      snapshot();
      layout.sections = move(layout.sections, target.dataset.secUp || target.dataset.secDown, target.dataset.secUp ? -1 : 1);
      render();
      schedulePreview();
      return;
    }
    if (target.dataset.up || target.dataset.down) {
      snapshot();
      moveBlock(target.dataset.up || target.dataset.down, target.dataset.up ? -1 : 1);
      render();
      schedulePreview();
      return;
    }
    if (target.dataset.secDup) {
      snapshot();
      const section = findSection(target.dataset.secDup);
      if (section) {
        const copy = reidentifySection(section);
        const index = layout.sections.findIndex((item) => item.id === section.id);
        layout.sections.splice(index + 1, 0, copy);
      }
      render();
      schedulePreview();
      return;
    }
    if (target.dataset.dup) {
      snapshot();
      duplicateBlock(target.dataset.dup);
      render();
      schedulePreview();
      return;
    }
    if (target.dataset.addCol) {
      snapshot();
      layout.sections = layout.sections.map((section) => {
        if (section.id !== target.dataset.addCol) {
          return section;
        }
        return { ...section, columns: section.columns.concat([{ id: uid('col'), width: 50, blocks: [] }]) };
      });
      render();
      return;
    }
    if (target.dataset.libSave) {
      const section = findSection(target.dataset.libSave);
      const name = window.prompt('Library name', 'Reusable section');
      if (!section || !name) {
        return;
      }
      api('library', { method: 'POST', body: JSON.stringify({ name, section }) }).then(() => {
        statusText = 'Saved to library.';
        paintStatus();
      }).catch((error) => {
        statusText = error.message;
        paintStatus();
      });
      return;
    }
    if (target.dataset.library !== undefined) {
      api('library', { method: 'GET' }).then((items) => {
        const panel = root.querySelector('[data-library-panel]');
        panel.hidden = false;
        panel.innerHTML = '<header><strong>Library</strong><button type="button" data-library-close>Close</button></header>' +
          (items || []).map((item) => '<p><button type="button" data-lib-insert="' + esc(item.id) + '">' + esc(item.name) + '</button> <button type="button" data-lib-del="' + esc(item.id) + '">Delete</button></p>').join('');
        panel._items = items;
      }).catch((error) => {
        statusText = error.message;
        paintStatus();
      });
      return;
    }
    if (target.dataset.libraryClose !== undefined) {
      root.querySelector('[data-library-panel]').hidden = true;
      return;
    }
    if (target.dataset.libInsert) {
      const panel = root.querySelector('[data-library-panel]');
      const item = (panel._items || []).find((entry) => entry.id === target.dataset.libInsert);
      if (item) {
        snapshot();
        const section = reidentifySection(item.section);
        layout.sections.push(section);
        render();
        schedulePreview();
      }
      return;
    }
    if (target.dataset.libDel) {
      api('library?id=' + encodeURIComponent(target.dataset.libDel), { method: 'DELETE' }).then(() => {
        target.closest('p').remove();
      });
    }
  });

  root.addEventListener('input', (event) => {
    const setting = event.target.dataset.setting;
    const id = event.target.dataset.id;
    if (setting && id) {
      const value = event.target.type === 'checkbox' ? event.target.checked : event.target.value;
      layout.sections = layout.sections.map((section) => ({
        ...section,
        columns: section.columns.map((column) => ({
          ...column,
          blocks: mapBlocks(column.blocks || [], id, (block) => {
            const copy = clone(block);
            copy.settings = { ...copy.settings, [setting]: value };
            if (copy.type === 'columns' && setting === 'count') {
              const count = Math.max(2, Math.min(4, Number(value) || 2));
              copy.columns = copy.columns || [];
              while (copy.columns.length < count) {
                copy.columns.push({ id: uid('col'), width: Math.floor(100 / count), blocks: [] });
              }
              copy.columns = copy.columns.slice(0, count);
            }
            return copy;
          }),
        })),
      }));
      schedulePreview();
      return;
    }
    if (event.target.dataset.style && id) {
      const key = event.target.dataset.style;
      const value = event.target.type === 'checkbox' ? event.target.checked : event.target.value;
      layout.sections = layout.sections.map((section) => ({
        ...section,
        columns: section.columns.map((column) => ({
          ...column,
          blocks: mapBlocks(column.blocks || [], id, (block) => {
            const copy = clone(block);
            copy.style = { ...(copy.style || {}), [key]: value };
            return copy;
          }),
        })),
      }));
      schedulePreview();
      return;
    }
    if (event.target.dataset.skey) {
      const sectionId = event.target.dataset.section;
      const key = event.target.dataset.skey;
      const value = event.target.type === 'checkbox' ? event.target.checked : event.target.value;
      layout.sections = layout.sections.map((section) => section.id === sectionId ? { ...section, settings: { ...section.settings, [key]: value } } : section);
      schedulePreview();
      return;
    }
    if (event.target.dataset.col) {
      const sectionId = event.target.dataset.section;
      const columnId = event.target.dataset.col;
      const width = Number(event.target.value) || 50;
      layout.sections = layout.sections.map((section) => section.id !== sectionId ? section : {
        ...section,
        columns: section.columns.map((column) => column.id === columnId ? { ...column, width } : column),
      });
      schedulePreview();
    }
  });

  root.addEventListener('change', (event) => {
    if (event.target.dataset.import !== undefined && event.target.files && event.target.files[0]) {
      const reader = new FileReader();
      reader.onload = () => {
        try {
          const data = JSON.parse(String(reader.result));
          const next = data.layout && data.layout.sections ? data.layout : data;
          if (!next.sections) {
            throw new Error('Missing sections');
          }
          snapshot();
          layout = next;
          statusText = t('imported');
          render();
          schedulePreview();
        } catch (error) {
          statusText = error.message;
          paintStatus();
        }
      };
      reader.readAsText(event.target.files[0]);
    }
  });

  root.addEventListener('dragstart', (event) => {
    const chip = event.target.closest('[data-arm]');
    const block = event.target.closest('[data-block]');
    const section = event.target.closest('[data-section-card]');
    if (chip) {
      event.dataTransfer.setData('text/tw-type', chip.dataset.arm);
    } else if (block) {
      event.dataTransfer.setData('text/tw-block', block.dataset.block);
    } else if (section) {
      event.dataTransfer.setData('text/tw-section', section.dataset.sectionCard);
    }
  });

  root.addEventListener('dragover', (event) => {
    if (event.target.closest('[data-drop], [data-section-card]')) {
      event.preventDefault();
    }
  });

  root.addEventListener('drop', (event) => {
    const column = event.target.closest('[data-drop]');
    const type = event.dataTransfer.getData('text/tw-type');
    const blockId = event.dataTransfer.getData('text/tw-block');
    const sectionId = event.dataTransfer.getData('text/tw-section');
    if (column && (type || blockId)) {
      event.preventDefault();
      snapshot();
      if (type) {
        insertIntoColumn(column.dataset.section || '', column.dataset.drop, blankBlock(type));
      } else if (blockId) {
        const source = findMoved(history[history.length - 1], blockId);
        layout.sections = layout.sections.map((section) => ({
          ...section,
          columns: section.columns.map((col) => ({
            ...col,
            blocks: filterBlocks(col.blocks || [], blockId),
          })),
        }));
        if (source) {
          insertIntoColumn(column.dataset.section || '', column.dataset.drop, source);
        }
      }
      render();
      schedulePreview();
      return;
    }
    const card = event.target.closest('[data-section-card]');
    if (card && sectionId && card.dataset.sectionCard !== sectionId) {
      event.preventDefault();
      snapshot();
      const from = layout.sections.findIndex((section) => section.id === sectionId);
      const to = layout.sections.findIndex((section) => section.id === card.dataset.sectionCard);
      if (from > -1 && to > -1) {
        const [item] = layout.sections.splice(from, 1);
        layout.sections.splice(to, 0, item);
        render();
        schedulePreview();
      }
    }
  });

  function findMoved(sourceLayout, id) {
    if (!sourceLayout) {
      return null;
    }
    for (const section of sourceLayout.sections || []) {
      for (const column of section.columns || []) {
        const found = findBlock(id, column.blocks || []);
        if (found) {
          return clone(found);
        }
      }
    }
    return null;
  }

  document.addEventListener('keydown', (event) => {
    if (!(event.metaKey || event.ctrlKey) || event.key.toLowerCase() !== 'z') {
      return;
    }
    event.preventDefault();
    if (event.shiftKey) {
      root.querySelector('[data-redo]').click();
    } else {
      root.querySelector('[data-undo]').click();
    }
  });

  load().catch((error) => {
    statusText = error.message;
    render();
    paintStatus();
  });
}());
