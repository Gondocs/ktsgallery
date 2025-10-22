
(function(){
  function qsa(sel, root){ return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  var overlay, inner, imageWrapper, img, titleEl, btnPrev, btnNext, btnClose, spinner, currentIndex = 0, items = [], showTitle = false;

  function createOverlay() {
    overlay = document.createElement('div');
    overlay.className = 'kts-lightbox-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');

    inner = document.createElement('div');
    inner.className = 'kts-lightbox-inner';

  imageWrapper = document.createElement('div');
  imageWrapper.className = 'kts-lightbox-image-wrapper';

  img = document.createElement('img');
  imageWrapper.appendChild(img);

  spinner = document.createElement('div');
  spinner.className = 'kts-spinner';
  imageWrapper.appendChild(spinner);

  inner.appendChild(imageWrapper);

  titleEl = document.createElement('div');
  titleEl.className = 'kts-lightbox-title';
  inner.appendChild(titleEl);

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

  // place nav on the overlay for consistent centering
  overlay.appendChild(btnPrev);
  overlay.appendChild(btnNext);
  overlay.appendChild(btnClose);
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

  function open(index, groupItems, displayTitle) {
    if (!overlay) createOverlay();
    items = groupItems;
    currentIndex = index;
    showTitle = displayTitle;
    overlay.classList.add('is-active');
    setImage(items[currentIndex].href, items[currentIndex].title);
  }

  function close() {
    overlay.classList.remove('is-active');
  }

  function prev() { currentIndex = (currentIndex - 1 + items.length) % items.length; setImage(items[currentIndex].href, items[currentIndex].title); }
  function next() { currentIndex = (currentIndex + 1) % items.length; setImage(items[currentIndex].href, items[currentIndex].title); }

  function setImage(src, title) {
    if (!img) return;
    img.classList.remove('is-visible');
    if (img._onload) {
      img.removeEventListener('load', img._onload);
    }
    spinner.style.display = 'grid';
    img._onload = function(){ 
      img.classList.add('is-visible'); 
      spinner.style.display = 'none'; 
    };
    img.addEventListener('load', img._onload, { once: true });
    img.src = src;
    
    // Update title - Debug logging
    console.log('setImage - showTitle:', showTitle, 'title:', title);
    if (showTitle && title) {
      titleEl.textContent = title;
      titleEl.classList.add('is-visible');
      console.log('Title should be visible now:', titleEl);
    } else {
      titleEl.textContent = '';
      titleEl.classList.remove('is-visible');
      console.log('Title hidden');
    }
  }

  function init() {
    qsa('.kts-gallery').forEach(function(gallery){
      if (gallery.getAttribute('data-no-rclick') === '1') {
        gallery.addEventListener('contextmenu', function(e){ e.preventDefault(); });
      }
      var showLightboxTitle = gallery.getAttribute('data-show-lightbox-title') === '1';
      console.log('Gallery lightbox title setting:', showLightboxTitle);
      var group = qsa('a.kts-item', gallery);
      group.forEach(function(a, i){
        a.addEventListener('click', function(e){
          var lb = a.getAttribute('data-kts-lightbox');
          var enabled = true; // by default enabled if script is enqueued
          if (enabled) {
            e.preventDefault();
            // Build fresh group in current DOM order with titles
            var fresh = qsa('a.kts-item', gallery).map(function(item){
              return {
                href: item.href,
                title: item.getAttribute('data-title') || ''
              };
            });
            console.log('Opening lightbox with items:', fresh);
            var startIndex = Array.prototype.indexOf.call(qsa('a.kts-item', gallery), a);
            open(startIndex, fresh, showLightboxTitle);
          }
        });
      });
    });
  }

  if (document.readyState !== 'loading') init();
  else document.addEventListener('DOMContentLoaded', init);
})();
