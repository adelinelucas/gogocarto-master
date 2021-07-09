function addTimeSlot( day )
{
	$('#' + day + '-open-hours-second-line').addClass('active');	
	$('#' + day + '_ajout').css('visibility','hidden');
}

function clearTimeSlot( day )
{
	$('#' + day + '_ajout').css('visibility','visible');
	$('#' + day + '-open-hours-second-line').removeClass('active');
	$('#' + day + '-input-3').val(null);
	$('#' + day + '-input-4').val(null);
}

function redoTimeSlot( day )
{
	var day_to_copy = day - 1;

	$('#' + day + '-input-1').val($('#' + day_to_copy + '-input-1').val());
	$('#' + day + '-input-2').val($('#' + day_to_copy + '-input-2').val());
	$('#' + day + '-input-3').val($('#' + day_to_copy + '-input-3').val());
	$('#' + day + '-input-4').val($('#' + day_to_copy + '-input-4').val());

	// si on recopie une plage horaire bonus, on doit la montrer visible
	if ( $('#' + day + '-input-3').val() || $('#' + day + '-input-4').val())
	{
		addTimeSlot(day);
	}		
}

$(document).ready(function()
{	
   // Gets history state from browser
   window.onpopstate = function(event)
   {
	   if (location.hash.includes('#popup') ) {
	   		$(location.hash).openModal();
    		history.replaceState(null, '', location.origin + location.pathname);
    }
    else if (location.hash == '' ) {
    	$('.modal').closeModal();
    }
	 };
});