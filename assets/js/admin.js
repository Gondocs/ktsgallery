
jQuery(function($){
  var frame;

  function refreshInput() {
    var ids = [];
    $('#kts-images-list .kts-image-item').each(function(){
      ids.push($(this).data('id'));
    });
    $('#kts-images-input').val(ids.join(','));
  }

  $('#kts-select-images').on('click', function(e){
    e.preventDefault();
    if (frame) { frame.open(); return; }
    frame = wp.media({
      title: 'Select or Upload Images',
      button: { text: 'Use these images' },
      multiple: true,
      library: { type: 'image' }
    });
    frame.on('select', function(){
      var selection = frame.state().get('selection');
      selection.each(function(attachment){
        attachment = attachment.toJSON();
        var id = attachment.id;
        var thumb = attachment.sizes && (attachment.sizes.thumbnail || attachment.sizes.medium || attachment.sizes.full);
        var url = thumb ? thumb.url : attachment.url;
        var title = attachment.title || '';
        var $li = $('<li class="kts-image-item" data-id="'+id+'" data-title="'+title+'">' +
                      '<img src="'+url+'" alt="" />' +
                      '<span class="kts-edit" title="Edit">✎</span>' +
                      '<span class="kts-remove" title="Remove">✕</span>' +
                    '</li>');
        $('#kts-images-list').append($li);
      });
      refreshInput();
    });
    frame.open();
  });

  $('#kts-images-list').on('click', '.kts-remove', function(){
    if (confirm('Are you sure you want to remove this image from the gallery?')) {
      $(this).closest('.kts-image-item').remove();
      refreshInput();
    }
  });

  // Edit image modal
  $('#kts-images-list').on('click', '.kts-edit', function(e){
    e.preventDefault();
    var $item = $(this).closest('.kts-image-item');
    var attachmentId = $item.data('id');
    var currentTitle = $item.data('title') || '';
    
    // Create modal
    var modal = $('<div class="kts-modal-overlay"><div class="kts-modal">' +
      '<div class="kts-modal-header">' +
        '<h2>Edit Image</h2>' +
        '<button class="kts-modal-close">✕</button>' +
      '</div>' +
      '<div class="kts-modal-body">' +
        '<div class="kts-modal-preview"><img src="' + $item.find('img').attr('src') + '" /></div>' +
        '<div class="kts-modal-field">' +
          '<label for="kts-edit-title">Image Title</label>' +
          '<input type="text" id="kts-edit-title" value="' + currentTitle + '" placeholder="Enter image title" />' +
          '<p class="kts-help">This title will be displayed when "Show Title" options are enabled.</p>' +
        '</div>' +
      '</div>' +
      '<div class="kts-modal-footer">' +
        '<button class="button button-secondary kts-modal-cancel">Cancel</button>' +
        '<button class="button button-primary kts-modal-save">Save Changes</button>' +
      '</div>' +
    '</div></div>');
    
    $('body').append(modal);
    modal.fadeIn(200);
    
    // Close modal
    modal.on('click', '.kts-modal-close, .kts-modal-cancel, .kts-modal-overlay', function(e){
      if(e.target === this) {
        modal.fadeOut(200, function(){ modal.remove(); });
      }
    });
    
    // Save changes
    modal.on('click', '.kts-modal-save', function(){
      var newTitle = $('#kts-edit-title').val();
      $item.attr('data-title', newTitle);
      
      // Update on server via AJAX
      $.post(ktsAdmin.ajaxurl, {
        action: 'kts_update_attachment_title',
        attachment_id: attachmentId,
        title: newTitle,
        nonce: ktsAdmin.nonce
      });
      
      modal.fadeOut(200, function(){ modal.remove(); });
    });
  });

  $('#kts-images-list').sortable({
    placeholder: 'kts-sortable-placeholder',
    stop: refreshInput
  });

  // Tabs in settings
  function initTabs(){
    var $wrap = $('.kts-tabs');
    if(!$wrap.length) return;
    // Delegated binding to be extra safe
    $(document).off('click.ktsTabs').on('click.ktsTabs', '.kts-tab-btn', function(){
      var $tabs = $(this).closest('.kts-tabs');
      var target = $(this).data('for');
      $tabs.find('.kts-tab-btn').removeClass('is-active');
      $(this).addClass('is-active');
      $tabs.find('.kts-tab-panel').removeClass('is-active');
      $tabs.find('.kts-tab-panel[data-tab="'+target+'"]').addClass('is-active');
    });
    // Ensure initial active state
    $wrap.each(function(){
      var $tabs = $(this);
      if(!$tabs.find('.kts-tab-btn.is-active').length){
        $tabs.find('.kts-tab-btn').first().addClass('is-active');
      }
      if(!$tabs.find('.kts-tab-panel.is-active').length){
        $tabs.find('.kts-tab-panel').first().addClass('is-active');
      }
    });
  }

  // Show/hide fields by layout selection
  function refreshVisibility(){
    var layout = $('#kts_layout').val();
    var isAuto = layout === 'automatic';
    var isMason = layout === 'mason';

    var $columnsRow = $('#kts_columns').closest('.kts-field');
    var $autoColsRow = $('input[name="kts_auto_columns"]').closest('.kts-field');
    var $minWidthRow = $('#kts_min_width').closest('.kts-field');
    var $rowHeight = $('#kts_row_height').closest('.kts-field');
    var $margins = $('#kts_margins').closest('.kts-field');
    var $height = $('#kts_height').closest('.kts-field');

    // columns only in non-automatic layouts
    $columnsRow.toggleClass('kts-hide', isAuto);
    // automatic columns/min-width only for grid/square/blogroll
    var allowAutoCols = !isAuto && !isMason;
    $autoColsRow.toggleClass('kts-hide', !allowAutoCols);
    $minWidthRow.toggleClass('kts-hide', !allowAutoCols);
    // row height & margins only for automatic layout
    $rowHeight.toggleClass('kts-hide', !isAuto);
    $margins.toggleClass('kts-hide', !isAuto);
    // image height not used in mason or automatic (natural heights or row-height based)
    $height.toggleClass('kts-hide', isAuto || isMason);
  }

  initTabs();
  refreshVisibility();
  // hide all inactive panels on load (in case CSS failed to apply earlier)
  $('.kts-tab-panel').not('.is-active').hide();
  $('.kts-tab-panel.is-active').show();
  $(document).on('click', '.kts-tab-btn', function(){
    var target = $(this).data('for');
    var $tabs = $(this).closest('.kts-tabs');
    $tabs.find('.kts-tab-panel').removeClass('is-active').hide();
    $tabs.find('.kts-tab-panel[data-tab="'+target+'"]').addClass('is-active').show();
  });
  $(document).on('change', '#kts_layout', refreshVisibility);
  
  // Show/hide custom dimensions based on image size selection
  function toggleCustomDimensions() {
    var selectedSize = $('#kts_image_size').val();
    if (selectedSize === 'custom') {
      $('#kts-custom-dimensions').removeClass('kts-hide').show();
    } else {
      $('#kts-custom-dimensions').addClass('kts-hide').hide();
    }
  }
  toggleCustomDimensions();
  $(document).on('change', '#kts_image_size', toggleCustomDimensions);
});
