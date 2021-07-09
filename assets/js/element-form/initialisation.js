initializeToHtml()

function initializeToHtml() {
  $('.to-html:not(.initialized)').each(function() { 
    if ($(this).text().includes('&lt;')) $(this).html($(this).text()); 
    $(this).addClass('initialized');
  });
}

jQuery(document).ready(function()
{
  function handleSelectInitialized($select) {
    $select.closest('.input-field').addClass("initialized")
    $select.closest('.input-field').find('.material-icons').addClass('active');
  }
  $('select:not(.select2)').each(function() {
    if ($(this).val()) handleSelectInitialized($(this))
  })
  $('select:not(.select2)').on('change', function() {
    handleSelectInitialized($(this))
  }).material_select()

  $('select.select2').change(function() {
    // add relevant classes to prefix icon, to it's colored the same way than other fields
    $(this).closest('.input-field').find('.material-icons').toggleClass('valid', !!$(this).val());
    // Because it was not possible to use a name like data[myfield][] with select, we store
    // the select2 value serialized in a hidden input. This hidden input will be deserilized in ElementFormService
    var result = {};
    var data = $(this).select2('data');
    for(var i in data) {
      var option = data[i];
      result[option.id] = option.text;
    }
    $(this).siblings('.select-encoded-result').val(JSON.stringify(result));
  })
  $('select.select2').trigger('change');
  // add relevant classes to prefix icon, to it's colored the same way than other fields
  $('input.select-dropdown, .select2-search__field').focus(function() { $(this).closest('.input-field').find('.material-icons').addClass('active') })
  $('.select2-search__field').blur(function() { $(this).closest('.input-field').find('.material-icons').removeClass('active') })
  $('input.select-dropdown').blur(function() { if (!$(this).val()) $(this).closest('.input-field').find('.material-icons').removeClass('active') })

  initializeToHtml()

  // Checkboxes, use custom checked value
  $('input[type=checkbox][data-checked-value]').change(function() {
    if ($(this).val() == 'on' && $(this).data('checked-value') != 'on') 
      $(this).val($(this).data('checked-value'))
  }).trigger('change')

  // TIMEPICKERS
  $('.timepicker').each(function(e) {
    var start_time;
    switch ($(this).data('slot-number'))
    {
      case 1: start_time = ["09", "00"];break;
      case 2: start_time = ["12", "00"];break;
      case 3: start_time = ["14", "00"];break;
      case 4: start_time = ["18", "00"];break;
    }
    if ($(this).val()) { start_time = $(this).val().split(':'); }
    $(this).timepicki({start_time: start_time, increase_direction:"up", show_meridian:false, step_size_minutes:15,min_hour_value:0, max_hour_value:23, overflow_minutes:true});
  });

	$('.tooltipped').tooltip();

  // fix problem on textarea initialisation
  $('.materialize-textarea').each(function() {
    $(this).val($(this).attr('value'));
    $(this).trigger('autoresize');
  });

	// ---------------
	// LISTENERS
	// ---------------

	// OPEN HOURS
	// 2nd time slot
	$('.add-time-slot-button').click(function() { addTimeSlot($(this).attr('id').split("_")[0]); });
  $('.clear-time-slot-button').click(function() { clearTimeSlot($(this).attr('id').split("_")[0]); });
	// recopy info
	$('.redo-time-slot-button').click(function() { redoTimeSlot($(this).attr('id').split("_")[0]); });

  $('.btn-add-file').click(function (e) {
    var type = $(this).data('type');
    var maxsize = $(this).data('maxsize');
    // grab the prototype template
    var counter = parseInt($(this).data('count'));
    var newWidget = $('.new-file-template.' + type).html().replace(/__count__/g, counter);
    console.log("add file", type, counter);
    $(this).data('count', counter + 1);

    // create a new list element and add it to the list
    var newElem = $('<li></li>').html(newWidget);
    newElem.appendTo($('.new-file-fields-list.' + type));
    var uploadField = newElem.find('input[type=file]');
    uploadField.trigger('click');

    uploadField.change(function() {
      newElem.find('.file-too-big').hide();
      if(this.files[0].size > maxsize){
         newElem.find('.file-too-big').show();
         this.value = "";
      };
    });
  });

  $('.file-fields-list .btn-close').click(function() {
    $(this).closest('li').remove();
  })
});



