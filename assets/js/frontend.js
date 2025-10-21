
(function(){
  function qsa(sel, root){ return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  var overlay, inner, img, btnPrev, btnNext, btnClose, currentIndex = 0, items = [];

  function createOverlay() {
    overlay = document.createElement('div');
    overlay.className = 'kts-lightbox-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');

    inner = document.createElement('div');
    inner.className = 'kts-lightbox-inner';

    img = document.createElement('img');
    inner.appendChild(img);

    btnPrev = document.createElement('button');
    btnPrev.type = 'button';
    btnPrev.className = 'kts-lightbox-prev';
    btnPrev.textContent = '‹';

    btnNext = document.createElement('button');
    btnNext.type = 'button';
    btnNext.className = 'kts-lightbox-next';
    btnNext.textContent = '›';

    btnClose = document.createElement('button');
    btnClose.type = 'button';
    btnClose.className = 'kts-lightbox-close';
    btnClose.textContent = '✕';

    inner.appendChild(btnPrev);
    inner.appendChild(btnNext);
    inner.appendChild(btnClose);
    overlay.appendChild(inner);
    document.body.appendChild(overlay);

    overlay.addEventListener('click', function(e){
      if (e.target === overlay) close();
    });
    btnClose.addEventListener('click', close);
    btnPrev.addEventListener('click', prev);
    btnNext.addEventListener('click', next);
    document.addEventListener('keydown', function(e){
      if (!overlay.classList.contains('is-active')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowLeft') prev();
      if (e.key === 'ArrowRight') next();
    });
  }

  function open(index, groupItems) {
    if (!overlay) createOverlay();
    items = groupItems;
    currentIndex = index;
    overlay.classList.add('is-active');
    setImage(items[currentIndex].href);
  }

  function close() {
    overlay.classList.remove('is-active');
  }

  function prev() { currentIndex = (currentIndex - 1 + items.length) % items.length; setImage(items[currentIndex].href); }
  function next() { currentIndex = (currentIndex + 1) % items.length; setImage(items[currentIndex].href); }

  function setImage(src) {
    if (!img) return;
    img.classList.remove('is-visible');
    if (img._onload) {
      img.removeEventListener('load', img._onload);
    }
    img._onload = function(){ img.classList.add('is-visible'); };
    img.addEventListener('load', img._onload, { once: true });
    img.src = src;
  }

  function init() {
    qsa('.kts-gallery').forEach(function(gallery){
      if (gallery.getAttribute('data-no-rclick') === '1') {
        gallery.addEventListener('contextmenu', function(e){ e.preventDefault(); });
      }
      var group = qsa('a.kts-item', gallery);
      group.forEach(function(a, i){
        a.addEventListener('click', function(e){
          var lb = a.getAttribute('data-kts-lightbox');
          var enabled = true; // by default enabled if script is enqueued
          if (enabled) {
            e.preventDefault();
            // Build fresh group in current DOM order
            var fresh = qsa('a.kts-item', gallery);
            var startIndex = fresh.indexOf(a);
            open(startIndex, fresh);
          }
        });
      });
    });
  }

  if (document.readyState !== 'loading') init();
  else document.addEventListener('DOMContentLoaded', init);
})();
