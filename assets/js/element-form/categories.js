var index = 1;
jQuery(document).ready(function()
{	
	// display parent if children is checked
	$('.option-field.selected').each(function() {
		$(this).parents('.option-field').addClass('selected')
	})
	
	$(".category-select").change(function()
	{ 
		if (!$(this).val()) return;

		$(this).parents('.category-field').removeClass('error');

		// if only single option, removing all others options laready selected
		if ($(this).data('single-option'))
		{
			$(this).closest('.category-field').find('> .option-field.selected').each(function() { removeOptionField($(this)); });
		}

		var optionField = $('#option-field-' + $(this).val());
		var order = index
		if (optionField.find('> .option-field-value').hasClass('with-description')
		    && optionField.closest('.category-field').hasClass('inline'))
			order = order + 1000; // when there is a mix betwwen category with description and with not, it put the description at the end so other one can be inlined
		optionField.addClass('selected')
		optionField.stop(true,false).slideDown({ duration: 350, easing: "easeOutQuart", queue: false, complete: function() {$(this).css('height', '');}});
		optionField.attr('data-index', order);
		optionField.css('-webkit-box-ordinal-group', order);
		optionField.css('-moz-box-ordinal-group', order);
		optionField.css('-ms-flex-order', order);
		optionField.css('-webkit-order', order);
		optionField.css('order', order);
		

		checkForSelectLabel(optionField, 1);
		index++;

		// open automatically mandatory sub select
		var firstMandatorySubSelect = optionField.find('.category-field.mandatory .select-dropdown')[0];
		if (firstMandatorySubSelect) setTimeout(function() { firstMandatorySubSelect.click(); }, 200);
	});

	$('.option-field-delete').click(function()
	{
		removeOptionField($('#option-field-' + $(this).attr('data-id')));
	});

	function removeOptionField(optionFieldToRemove)
	{
		if (optionFieldToRemove.hasClass('inline')) 
			optionFieldToRemove.hide();
		else
			optionFieldToRemove.stop(true,false).slideUp({ duration: 350, easing: "easeOutQuart", queue: false, complete: function() {$(this).css('height', '');}});
		optionFieldToRemove.removeClass('selected')
		checkForSelectLabel(optionFieldToRemove, 0);
	}

	function checkForSelectLabel(optionField, increment)
	{		
		var categorySelect = optionField.siblings('.category-field-select');
		var select = categorySelect.find('input.select-dropdown');

		if (optionField.siblings('.option-field.selected').length + increment === 0)
			select.val("Choisissez " + categorySelect.attr('data-picking-text'));
		else
			select.val("Ajoutez " + categorySelect.attr('data-picking-text'));
	}
});

function encodeOptionValuesIntoHiddenInput()
{
	var optionValues = [];

	$('.option-field.selected').each(function() 
	{
		var option = {};
		option.id = $(this).attr('data-id');
		option.index = $(this).attr('data-index');
		option.description = $(this).find('.option-field-description-input[data-id=' + option.id + ']').val() || "";
		optionValues.push(option);
	});

	$('input#options-values').val(JSON.stringify(optionValues));
}