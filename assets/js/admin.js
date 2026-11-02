(function () {
  'use strict';
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-tw-media]');
    if (!button || !window.wp || !window.wp.media) {
      return;
    }
    event.preventDefault();
    const name = button.getAttribute('data-tw-media');
    const input = document.querySelector('[name="tw[' + name + ']"]') || document.querySelector('[name="' + name + '"]');
    const frame = window.wp.media({ title: 'TajiksWorld', library: { type: 'image' }, multiple: false });
    frame.on('select', () => {
      const attachment = frame.state().get('selection').first().toJSON();
      if (input && attachment && attachment.id) {
        input.value = attachment.id;
      }
    });
    frame.open();
  });
}());
