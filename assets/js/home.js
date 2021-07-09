$(document).ready(function()
{
	$('select').material_select();

	$('#bottom-more-info').click( function()
	{
		$('html, body').animate({scrollTop: $('.bottom-section:first').offset().top}, 700);
	});

	// Create an autocomplete search-bar with gogocartoJs lib
	gogoJsConf['mode'] = { autocompleteOnly: true };
	carto = goGoCarto('#gogocarto', gogoJsConf);

	// on search submit, redirect to the route provided by gogocartoJs
  $('.search-bar').on('searchRoute', function(evt, route) {
		var mainOption;
		// in small screen a select is displayed
		if ($('.category-field-select').is(':visible'))
		{
			var select = document.getElementById('category-select');
			mainOption = select.value;
		}
		else
		// in large screen radio button are displayed
		{
			mainOption = $('.main-option-radio-btn:checked').attr('data-name');
		}

		if (mainOption) route += '?cat=' + mainOption;
		var path = window.location.pathname + '/annuaire' + route;
		window.location.href = window.location.origin + path.replace('//', '/');
  })

	// clear viewport and address cookies
	eraseCookie('viewport');
	eraseCookie('address');
});

function createCookie(name,value)
{
	var days = 100;

	var date = new Date();
	date.setTime(date.getTime()+(days*24*60*60*1000));
	var expires = "; expires="+date.toUTCString();

	document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function eraseCookie(name) {
	createCookie(name,"");
}