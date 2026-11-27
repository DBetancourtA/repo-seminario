(() => {
  'use strict';

  // Modo claro / oscuro
  const root = document.documentElement;
  const syncThemeButtons = () => {
    const dark = root.getAttribute('data-theme') === 'dark';
    document.querySelectorAll('.theme-toggle').forEach((b) => {
      b.setAttribute('aria-pressed', String(dark));
      b.setAttribute('aria-label', dark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
    });
  };
  document.querySelectorAll('.theme-toggle').forEach((b) => {
    b.addEventListener('click', () => {
      const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('theme', next); } catch (_) { /* almacenamiento no disponible */ }
      syncThemeButtons();
    });
  });
  syncThemeButtons();
  // Si el usuario nunca eligió, sigue al sistema operativo
  if (window.matchMedia) {
    const mq = window.matchMedia('(prefers-color-scheme: dark)');
    const follow = (e) => {
      let saved = null;
      try { saved = localStorage.getItem('theme'); } catch (_) {}
      if (!saved) { root.setAttribute('data-theme', e.matches ? 'dark' : 'light'); syncThemeButtons(); }
    };
    if (mq.addEventListener) mq.addEventListener('change', follow);
  }

  // Menú móvil
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.getElementById('nav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
    });
  }

  // Contador de caracteres: <textarea data-count data-min="80" data-max="1000">
  document.querySelectorAll('textarea[data-count]').forEach((ta) => {
    const out = document.getElementById(ta.id + '-count');
    if (!out) return;
    const min = parseInt(ta.dataset.min || '0', 10);
    const max = parseInt(ta.dataset.max || '0', 10);
    const update = () => {
      const n = ta.value.trim().length;
      out.textContent = max ? `${n} / ${max}` : (min ? `${n} (mín. ${min})` : String(n));
      out.classList.toggle('is-short', min > 0 && n < min);
    };
    ta.addEventListener('input', update);
    update();
  });

  // Zona de arrastre con vista previa
  document.querySelectorAll('.drop').forEach((drop) => {
    const input = drop.querySelector('input[type="file"]');
    const list = document.getElementById(drop.dataset.previews);
    if (!input) return;
    ['dragenter', 'dragover'].forEach((ev) => drop.addEventListener(ev, () => drop.classList.add('is-over')));
    ['dragleave', 'drop'].forEach((ev) => drop.addEventListener(ev, () => drop.classList.remove('is-over')));
    input.addEventListener('change', () => {
      if (!list) return;
      list.innerHTML = '';
      Array.from(input.files).forEach((file) => {
        if (!file.type.startsWith('image/')) return;
        const fig = document.createElement('figure');
        const img = document.createElement('img');
        img.alt = '';
        img.src = URL.createObjectURL(file);
        img.onload = () => URL.revokeObjectURL(img.src);
        const cap = document.createElement('figcaption');
        cap.textContent = file.name;
        fig.append(img, cap);
        list.append(fig);
      });
      const max = parseInt(drop.dataset.max || '0', 10);
      const msg = document.getElementById(drop.dataset.previews + '-msg');
      if (msg) {
        msg.textContent = max && input.files.length > max ? `Seleccionaste ${input.files.length}; el máximo es ${max}.` : '';
      }
    });
  });

  // Objetivos específicos dinámicos
  const objList = document.getElementById('objetivos');
  const objAdd = document.getElementById('obj-add');
  if (objList && objAdd) {
    const max = 6;
    const renumber = () => {
      const items = objList.querySelectorAll('.objective-item');
      items.forEach((it, i) => {
        it.querySelector('.n').textContent = `${i + 1}.`;
        it.querySelector('textarea').setAttribute('aria-label', `Objetivo específico ${i + 1}`);
      });
      objAdd.hidden = items.length >= max;
    };
    const add = (value = '') => {
      const tpl = document.getElementById('obj-tpl');
      const node = tpl.content.firstElementChild.cloneNode(true);
      node.querySelector('textarea').value = value;
      objList.append(node);
      renumber();
      node.querySelector('textarea').focus();
    };
    objAdd.addEventListener('click', () => add());
    objList.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-remove]');
      if (!btn) return;
      btn.closest('.objective-item').remove();
      renumber();
      objAdd.focus();
    });
    renumber();
  }

  // Buscador de tecnologías
  const techSearch = document.getElementById('tech-search');
  if (techSearch) {
    const labels = document.querySelectorAll('.tech-options label');
    const norm = (s) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    techSearch.addEventListener('input', () => {
      const q = norm(techSearch.value.trim());
      labels.forEach((l) => {
        const checked = l.querySelector('input').checked;
        l.classList.toggle('is-hidden', q !== '' && !checked && !norm(l.textContent).includes(q));
      });
    });
  }

  // Copiar al portapapeles
  document.querySelectorAll('[data-copy]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(btn.dataset.copy);
        const old = btn.textContent;
        btn.textContent = 'Copiado';
        setTimeout(() => { btn.textContent = old; }, 1600);
      } catch (_) { /* sin permiso de portapapeles */ }
    });
  });

  // Ajustes de ficha: pide el motivo solo si el campo cambió
  document.querySelectorAll('.motivo[data-for]').forEach((box) => {
    const field = document.getElementById(box.dataset.for);
    if (!field) return;
    const norm = (v) => v.replace(/\r\n/g, '\n').trim();
    const sync = () => { box.hidden = norm(field.value) === norm(field.dataset.original || ''); };
    field.addEventListener('input', sync);
    sync();
  });

  // Repositorio de GitHub: ¿público o privado?
  const repoCache = {};
  const checkRepo = (url) => {
    const m = /^https?:\/\/(?:www\.)?github\.com\/([^\/\s]+)\/([^\/\s#?]+)/i.exec(url.trim());
    if (!m) return Promise.resolve(null);
    const key = (m[1] + '/' + m[2].replace(/\.git$/i, '')).toLowerCase();
    if (!repoCache[key]) {
      repoCache[key] = fetch('https://api.github.com/repos/' + key, { headers: { Accept: 'application/vnd.github+json' } })
        .then((r) => (r.status === 200 ? 'publico' : r.status === 404 ? 'privado' : null))
        .catch(() => null);
    }
    return repoCache[key];
  };
  const setupRepoField = (status, field) => {
    const correo = status.dataset.correo || 'tu docente';
    let timer = null;
    const run = () => {
      const url = field.value;
      checkRepo(url).then((res) => {
        if (field.value !== url) return;
        status.classList.remove('is-ok', 'is-warn');
        if (res === 'publico') {
          status.innerHTML = '<span aria-hidden="true">✓</span> Repositorio público: tu docente puede verlo.';
          status.classList.add('is-ok');
          status.hidden = false;
        } else if (res === 'privado') {
          status.textContent = '';
          const b = document.createElement('strong');
          b.textContent = 'No se puede ver públicamente. ';
          status.append(b, document.createTextNode('Es privado o la dirección está mal escrita. Si es privado, agrega a '));
          const c = document.createElement('span');
          c.className = 'mono';
          c.textContent = correo;
          status.append(c, document.createTextNode(' como colaborador (Settings → Collaborators → Add people).'));
          status.classList.add('is-warn');
          status.hidden = false;
        } else {
          status.hidden = true;
        }
      });
    };
    field.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(run, 700); });
    if (field.value) run();
  };
  document.querySelectorAll('.repo-hint[data-repo-for]').forEach((box) => {
    const field = document.getElementById(box.dataset.repoFor);
    const status = box.querySelector('.repo-status');
    if (field && status) { status.dataset.correo = box.dataset.correo; setupRepoField(status, field); }
  });
  document.querySelectorAll('p.repo-status[data-repo-for]').forEach((status) => {
    const field = document.getElementById(status.dataset.repoFor);
    if (field) setupRepoField(status, field);
  });

  // Confirmación para acciones destructivas
  document.querySelectorAll('form[data-confirm]').forEach((f) => {
    f.addEventListener('submit', (e) => {
      if (!window.confirm(f.dataset.confirm)) e.preventDefault();
    });
  });

  // Evita doble envío y avisa que se está subiendo
  document.querySelectorAll('form[data-busy]').forEach((f) => {
    f.addEventListener('submit', () => {
      const btn = f.querySelector('button[type="submit"]:not([name])') || f.querySelector('button[type="submit"]');
      if (btn) {
        btn.setAttribute('aria-disabled', 'true');
        btn.dataset.label = btn.textContent;
        btn.textContent = f.dataset.busy;
      }
    });
  });
})();
