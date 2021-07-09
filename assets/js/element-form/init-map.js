var map, marker, markerPosition, mapZoom, firstGeocodeDone;

// Google map initialisation
function initMap()
{
	var mapCenter;
	if ($('#input-latitude').val() && $('#input-latitude').val() != 0 &&
	    $('#input-longitude').val() && $('#input-longitude').val() != 0)
	{
		markerPosition = new L.LatLng($('#input-latitude').val(), $('#input-longitude').val());
		mapCenter = markerPosition;
		mapZoom = 18;
		firstGeocodeDone = true;
	}
	else
	{
		markerPosition = null;
		mapCenter = new L.LatLng(46.897045, 2.425235);
		mapZoom = 5;
	}

	map = L.map('address-preview-map', {
	    center: mapCenter,
	    zoom: mapZoom,
	    zoomControl: true,
	    scrollWheelZoom : false
	});

	// defaultBounds is set in address.html.twig
	if (!firstGeocodeDone && defaultBounds) map.fitBounds(defaultBounds);

	L.tileLayer(defaultTileLayer).addTo(map);

	if (markerPosition) createMarker(markerPosition);
}

function createMarker(position)
{
	if (marker) marker.remove();

	marker = new L.Marker(position, { draggable: true } ).addTo(map);
	marker.on('dragend', function()
	{
	  	$('#input-latitude').attr('value',marker.getLatLng().lat);
		$('#input-longitude').attr('value',marker.getLatLng().lng);
  	});

  	marker.bindPopup(`<center>${t('js.element_form.geocoded_marker_text')}</center>`).openPopup();
}

function fitBounds(rawbounds)
{
	//console.log("fitbounds", rawbounds);

	var corner1 = L.latLng(rawbounds[0], rawbounds[1]);
	var corner2 = L.latLng(rawbounds[2], rawbounds[3]);
	var bounds = L.latLngBounds(corner1, corner2);

	map.fitBounds(bounds);
}


