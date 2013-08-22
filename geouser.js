jQuery(document).ready(function($){

var _map_id = 'geouser-map';
var _map = $('#' + _map_id);
var _search = $('#geouser-search');
var _lat = $('#geouser-lat');
var _lng = $('#geouser-lng');

if (!_map.length)
    return false;

var initial_center;
if (_lat.val() && _lng.val()) {
    initial_center = new google.maps.LatLng(_lat.val(), _lng.val());
    initial_zoom = 16;
} else {
    initial_center = new google.maps.LatLng(geouser.initial_lat, geouser.initial_lng);
    initial_zoom = 4;
}

var options = {
    center: initial_center,
    zoom: initial_zoom,
    maxZoom: 17,
    mapTypeId: google.maps.MapTypeId.ROADMAP,
    mapTypeControl: false,
    panControl: false,
    streetViewControl: false,
    zoomControlOptions: { style: google.maps.ZoomControlStyle.SMALL }
};

var map = new google.maps.Map(document.getElementById(_map_id), options);
var geocoder = new google.maps.Geocoder();
var marker;

google.maps.event.addDomListener(window, 'load', function(e) {
    if (!_lat.val() || !_lng.val())
        return false;
    marker = new google.maps.Marker({
        map: map,
        draggable: true,
        position: initial_center
    });
});

google.maps.event.addListener(map, 'click', function(e) {
    if (marker) {
        marker.setPosition(e.latLng);
    } else {
        marker = new google.maps.Marker({
            map: map,
            draggable: true,
            position: new google.maps.LatLng(e.latLng)
        });
        marker.setPosition(e.latLng);
    }
    _lat.val(e.latLng.lat());
    _lng.val(e.latLng.lng());
});

_search.keyup(function() {
    geocoder.geocode({'address': $(this).val()}, function(results){
        if (!results || !results.length)
            return false;
        var location = results[0].geometry.location;
        map.setCenter(location);
        map.setZoom(16);
    });
});

});
