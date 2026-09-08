/**
 * Quantum Pedia Child UI scripts.
 */
(function () {
  'use strict';

  document.querySelectorAll('.qp-desktop-nav__toggle').forEach(function (button) {
    button.addEventListener('click', function (event) {
      event.preventDefault();
      var item = button.closest('.qp-desktop-nav__item');
      var expanded = button.getAttribute('aria-expanded') === 'true';

      document.querySelectorAll('.qp-desktop-nav__item.is-open').forEach(function (openItem) {
        if (openItem !== item) {
          openItem.classList.remove('is-open');
          var openBtn = openItem.querySelector('.qp-desktop-nav__toggle');
          if (openBtn) {
            openBtn.setAttribute('aria-expanded', 'false');
          }
        }
      });

      item.classList.toggle('is-open', !expanded);
      button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    });
  });

  document.addEventListener('click', function (event) {
    if (!event.target.closest('.qp-desktop-nav__item')) {
      document.querySelectorAll('.qp-desktop-nav__item.is-open').forEach(function (item) {
        item.classList.remove('is-open');
        var btn = item.querySelector('.qp-desktop-nav__toggle');
        if (btn) {
          btn.setAttribute('aria-expanded', 'false');
        }
      });
    }
  });
})();
