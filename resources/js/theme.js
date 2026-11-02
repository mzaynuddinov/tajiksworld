(function () {
  'use strict';
  const cfg = window.TajiksWorldFront || {};

  const menuButton = document.querySelector('[data-tw-menu]');
  const panel = document.getElementById('tw-mobile-panel');
  if (menuButton && panel) {
    menuButton.addEventListener('click', () => {
      const open = panel.hasAttribute('hidden');
      if (open) {
        panel.removeAttribute('hidden');
      } else {
        panel.setAttribute('hidden', '');
      }
      menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  document.addEventListener('click', (event) => {
    const open = event.target.closest('[data-tw-search-open]');
    const overlay = document.querySelector('[data-tw-search]');
    if (open && overlay) {
      overlay.removeAttribute('hidden');
      const input = overlay.querySelector('input[type="search"]');
      if (input) {
        input.focus();
      }
    }
    if (event.target.closest('[data-tw-search-close]') && overlay) {
      overlay.setAttribute('hidden', '');
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      document.querySelectorAll('[data-tw-search], [data-tw-lightbox]').forEach((node) => node.setAttribute('hidden', ''));
      if (panel) {
        panel.setAttribute('hidden', '');
      }
    }
  });

  document.querySelectorAll('[data-tw-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('.tw-carousel-track');
    carousel.querySelectorAll('[data-dir]').forEach((button) => {
      button.addEventListener('click', () => {
        const width = track.clientWidth || 320;
        track.scrollBy({ left: width * Number(button.dataset.dir) * 0.8, behavior: 'smooth' });
      });
    });
  });

  document.querySelectorAll('[data-tw-gallery]').forEach((gallery) => {
    gallery.addEventListener('click', (event) => {
      const link = event.target.closest('a');
      if (!link) {
        return;
      }
      event.preventDefault();
      const box = document.querySelector('[data-tw-lightbox]');
      const image = box ? box.querySelector('img') : null;
      if (!box || !image) {
        return;
      }
      image.src = link.href;
      image.alt = '';
      box.removeAttribute('hidden');
    });
  });
  const lightbox = document.querySelector('[data-tw-lightbox]');
  if (lightbox) {
    lightbox.addEventListener('click', (event) => {
      if (event.target === lightbox || event.target.closest('.tw-lightbox-close')) {
        lightbox.setAttribute('hidden', '');
      }
    });
  }

  document.querySelectorAll('[data-tw-font]').forEach((button) => {
    button.addEventListener('click', () => {
      const body = document.querySelector('.tw-article-body');
      if (!body) {
        return;
      }
      const size = button.dataset.twFont === 'sm' ? '1rem' : button.dataset.twFont === 'lg' ? '1.35rem' : '1.15rem';
      body.style.fontSize = size;
      document.querySelectorAll('[data-tw-font]').forEach((item) => item.setAttribute('aria-pressed', item === button ? 'true' : 'false'));
      try {
        localStorage.setItem('tw-font', button.dataset.twFont);
      } catch (error) {
        /* ignore */
      }
    });
  });
  try {
    const saved = localStorage.getItem('tw-font');
    if (saved) {
      const button = document.querySelector('[data-tw-font="' + saved + '"]');
      if (button) {
        button.click();
      }
    }
  } catch (error) {
    /* ignore */
  }

  document.querySelectorAll('[data-tw-print]').forEach((button) => {
    button.addEventListener('click', () => window.print());
  });

  document.querySelectorAll('[data-tw-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(button.dataset.twCopy);
        button.textContent = 'Copied';
      } catch (error) {
        window.prompt('Copy link', button.dataset.twCopy);
      }
    });
  });

  document.querySelectorAll('[data-tw-theme]').forEach((button) => {
    button.addEventListener('click', () => {
      const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      if (next === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
      } else {
        document.documentElement.removeAttribute('data-theme');
      }
      button.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');
      try {
        localStorage.setItem('tw-theme', next);
      } catch (error) {
        /* ignore */
      }
    });
  });

  document.querySelectorAll('[data-tw-newsletter]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const note = form.querySelector('.tw-form-note');
      const response = await fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin' });
      let payload = {};
      try {
        payload = await response.json();
      } catch (error) {
        payload = {};
      }
      const message = (payload.data && payload.data.message) || payload.message || 'Unable to subscribe.';
      if (note) {
        note.textContent = message;
      }
    });
  });

  if (cfg.postId && cfg.ajaxUrl && cfg.viewNonce) {
    const body = new FormData();
    body.append('action', 'tw_view');
    body.append('nonce', cfg.viewNonce);
    body.append('post_id', String(cfg.postId));
    fetch(cfg.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' })
      .then((response) => response.json())
      .then((payload) => {
        const node = document.querySelector('[data-tw-view-count]');
        if (node && payload.success && payload.data && payload.data.views != null) {
          node.textContent = String(payload.data.views);
        }
      })
      .catch(() => {});
  }
}());
