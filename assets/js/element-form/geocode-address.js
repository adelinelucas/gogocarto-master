var geocoderJS;
var geocodingProcessing = false;
var firstGeocodeDone = false;
var geocodedFormatedAddress = '';
var geocodeResult = null;

function getInputAddress() { return $('#input-address').val(); }

jQuery(document).ready(function()
{
	geocoderJS = UniversalGeocoder.createGeocoder({provider: "nominatim", useSsl: true, userAgent: "GoGoCarto"});

	// Geocoding address
	$('#input-address').change(function ()
  {
    if (!firstGeocodeDone) handleInputAdressChange();
  });

	$('#input-address').keyup(function(e)
	{
		if(e.keyCode == 13) // touche entrée
		{
			handleInputAdressChange();
		}
	});

	$('.btn-geolocalize').click(function () { handleInputAdressChange(); });
});

function handleInputAdressChange()
{
	geocodeAddress(getInputAddress());
}

function geocodeAddress(address) {

	console.log("geocodeAddress", address);

	if (geocodingProcessing || !address) { console.log("Already processing or no address provided", address); return null; }

	$('#geocode-spinner-loader').show();

	geocodingProcessing = true;

	geocoderJS.geocode(address, function(results)
	{
		if (results !== null && results.length > 0)
		{
			firstGeocodeDone = true;
			geocodeResult = results[0];
			map.setView(results[0].getCoordinates(), 18);
			createMarker(results[0].getCoordinates());

			console.log("Geocode result :", results[0]);
			$(window).trigger('geocoded', results[0]);

			// Detect street address when geocoder fails to retrieve it (OSM case)
			var patt = new RegExp(/^\d+/g);
			var potentialStreetNumber = patt.exec(address);
			var streetNumber = results[0].streetNumber;
			if (potentialStreetNumber != results[0].postalCode && !results[0].streetNumber && results[0].streetName)
			{
				console.log("street number detected", potentialStreetNumber);
				streetNumber = potentialStreetNumber;
			}

			let streetAddress = '';
			if (streetNumber && results[0].streetName) streetAddress += streetNumber + ' ';
			if (results[0].streetName) streetAddress +=  results[0].streetName;

			let geocodedFormatedAddress = "";
			if (streetAddress) geocodedFormatedAddress += streetAddress + ', ';
			if (results[0].postalCode) geocodedFormatedAddress += results[0].postalCode + ' ';
			if (results[0].locality) geocodedFormatedAddress += results[0].locality;

			$('#input-latitude').val(marker.getLatLng().lat);
			$('#input-longitude').val(marker.getLatLng().lng);
			$('#input-postalCode').val(results[0].postalCode);
			$('#input-addressCountry').val(results[0].countryCode);
			$('#input-addressLocality').val(results[0].locality);
			$('#input-streetAddress').val(streetAddress);

			$('#input-address').val(geocodedFormatedAddress);

			$('#input-address').closest('.input-field').removeClass("error");
			$('#input-address').removeClass('invalid');
			$('#geocode-error').hide();
		}
		else if ($('#input-address').length > 0)
		{
			$('#input-address').addClass("invalid");
			$('#input-address').closest('.input-field').addClass("error");

			if (marker) marker.remove();

			$('.geolocalize-help-text').show();

			$('#input-latitude').val('');
			$('#input-longitude').val('');
			$('#input-postalCode').val('');
			$('#input-addressLocality').val('');
			$('#input-addressCountry').val('');
			$('#input-streetAddress').val('');

			console.log("geocoding error", status);
		} else {
			$('#geocode-error').text(t('js.element_form.geocode_error', {address: address})).show()
		}

		$('#geocode-spinner-loader').hide();
		geocodingProcessing = false;
	});
}

function checkCustomFormatedAddressBeforeSend()
{
	if (getInputAddress() != geocodedFormatedAddress)
	{
		$('#input-customFormatedAddress').val(getInputAddress());
	}
	else
	{
		$('#input-customFormatedAddress').val(null);
	}
}