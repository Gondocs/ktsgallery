
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
    
    // Update title
    if (showTitle && title) {
      titleEl.textContent = title;
      titleEl.classList.add('is-visible');
    } else {
      titleEl.textContent = '';
      titleEl.classList.remove('is-visible');
    }
  }

  function init() {
    // Initialize Masonry layout if present
    initMasonry();
    // Add loading overlays to all galleries and hide when first images are ready
    qsa('.kts-gallery').forEach(setupGalleryLoading);
    qsa('.kts-gallery').forEach(function(gallery){
      if (gallery.getAttribute('data-no-rclick') === '1') {
        gallery.addEventListener('contextmenu', function(e){ e.preventDefault(); });
      }
      var showLightboxTitle = gallery.getAttribute('data-show-lightbox-title') === '1';
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
            var startIndex = Array.prototype.indexOf.call(qsa('a.kts-item', gallery), a);
            open(startIndex, fresh, showLightboxTitle);
          }
        });
      });
    });
  }

  if (document.readyState !== 'loading') init();
  else document.addEventListener('DOMContentLoaded', init);

  // Masonry initialization (Desandro masonry-layout v4)
  function initMasonry(){
    var grids = qsa('.kts-gallery.kts-layout-mason');
    if (!grids.length) return;
    if (typeof Masonry === 'undefined') return; // Masonry not loaded, skip

    function toPixels(len){
      // Convert a CSS length (px, rem, etc.) to pixels
      if (!len) return 0;
      var t = document.createElement('div');
      t.style.position = 'absolute';
      t.style.visibility = 'hidden';
      t.style.width = len;
      document.body.appendChild(t);
      var px = t.getBoundingClientRect().width;
      document.body.removeChild(t);
      return px;
    }

    function debounce(fn, wait){
      var tid; return function(){ var ctx=this, args=arguments; clearTimeout(tid); tid=setTimeout(function(){ fn.apply(ctx,args); }, wait); };
    }

    grids.forEach(function(grid){
      // Ensure sizer exists as first child
      var first = grid.firstElementChild;
      if (!first || !first.classList.contains('kts-sizer')) {
        var s = document.createElement('div'); s.className = 'kts-sizer';
        grid.insertBefore(s, grid.firstChild);
      }

      var auto = grid.getAttribute('data-auto') === '1';
      var comp = getComputedStyle(grid);
      var minLen = (comp.getPropertyValue('--kts-min') || '').trim();

      function setColumns(){
        if (!auto) return;
        var minPx = toPixels(minLen || '220px') || 220;
        var gridW = grid.clientWidth;
        var cols = Math.max(1, Math.floor(gridW / minPx));
        grid.style.setProperty('--kts-columns', String(cols));
      }

      setColumns();

      // compute pixel gutter from CSS var --kts-gap (default 8px), preserving 0
      var gapLen = (comp.getPropertyValue('--kts-gap') || '8px').trim();
      var pxTmp = toPixels(gapLen);
      var gutterPx = Math.round(Number.isFinite(pxTmp) ? pxTmp : 8);

      // expose numeric gutter as CSS variable so CSS width calc can subtract it
      grid.style.setProperty('--kts-gap-px', gutterPx + 'px');

      var msnry = new Masonry(grid, {
        itemSelector: '.kts-item',
        columnWidth: '.kts-sizer',
        percentPosition: true,
        gutter: gutterPx
      });

      // Relayout after each image loads
      if (typeof imagesLoaded !== 'undefined') {
        imagesLoaded(grid)
          .on('progress', function(){ msnry.layout(); })
          .on('always', function(){ msnry.layout(); });
      }

      var onResize = debounce(function(){ setColumns(); msnry.layout(); }, 150);
      window.addEventListener('resize', onResize);
    });
  }

  // Add a loading overlay to a gallery and hide it depending on selected mode
  function setupGalleryLoading(gallery){
    if (gallery._ktsLoadingInit) return; // guard
    gallery._ktsLoadingInit = true;
    var mode = gallery.getAttribute('data-loading') || 'first';
    if (mode === 'none') return;
    var overlay = document.createElement('div');
    overlay.className = 'kts-gallery-loading';
    gallery.classList.add('is-loading');
    gallery.appendChild(overlay);

    var isMason = gallery.classList.contains('kts-layout-mason');
    if (typeof imagesLoaded !== 'undefined') {
      var il = imagesLoaded(gallery);
      if (mode === 'first') {
        var hid = false;
        il.on('progress', function(){ if (!hid) { hid = true; hide(); } });
        il.on('always', function(){ hide(); });
      } else if (mode === 'all') {
        il.on('always', function(){ hide(); });
      }
    } else if (!isMason) {
      // Fallback only for non-masonry when imagesLoaded is unavailable
      var imgs = qsa('img', gallery);
      if (!imgs.length) { hide(); return; }
      if (mode === 'first') {
        var needed = 1, count = 0;
        imgs.forEach(function(im){
          if (im.complete && im.naturalWidth > 0) { count++; }
          else {
            im.addEventListener('load', function(){ count++; if (count >= needed) hide(); }, { once: true });
            im.addEventListener('error', function(){ count++; if (count >= needed) hide(); }, { once: true });
          }
        });
        if (count >= needed) hide();
        setTimeout(hide, 1500);
      } else {
        var remain = imgs.length;
        imgs.forEach(function(im){
          if (im.complete && im.naturalWidth > 0) { remain--; }
          else {
            im.addEventListener('load', function(){ remain--; if (remain <= 0) hide(); }, { once: true });
            im.addEventListener('error', function(){ remain--; if (remain <= 0) hide(); }, { once: true });
          }
        });
        if (remain <= 0) hide();
        setTimeout(hide, 4000);
      }
    }

    function hide(){
      gallery.classList.remove('is-loading');
      overlay.remove();
    }
  }
})();
