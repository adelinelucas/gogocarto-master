var apiMerge, apiMarkAsNonDuplicate;
if ($('#page-content.duplicates').length > 0)
{
  $(document).ready(function() {
    $('.actions button, .actions a').click(function(e) {
      e.stopPropagation();
    });

    $('.btn-non-duplicates').on('click', function() {
      var button = $(this);
      jQuery.post(apiMarkAsNonDuplicate, {elementId: button.data('id')}, function(data, textStatus, xhr) {
        button.addClass('action-done');
        button.siblings('.btn').addClass('disabled');
      });      
    });

    $('.btn-merge').on('click', function() {
      var button = $(this);
      if (button.hasClass('disabled')) return false;
      jQuery.post(apiMerge, {elementId: button.data('id'), message: "Doublon conservé"}, function(data, textStatus, xhr) {
        button.text('Fusionnés !');
        button.addClass('action-done');
        button.siblings('.btn').addClass('disabled');
      });      
    });
  });  
}