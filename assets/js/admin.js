
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
        var $li = $('<li class="kts-image-item" data-id="'+id+'">' +
                      '<img src="'+url+'" alt="" />' +
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
});
