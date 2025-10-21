
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
    $(this).closest('.kts-image-item').remove();
    refreshInput();
  });

  $('#kts-images-list').sortable({
    placeholder: 'kts-sortable-placeholder',
    stop: refreshInput
  });
});
