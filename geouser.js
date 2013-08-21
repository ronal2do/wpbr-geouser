jQuery(document).ready(function($){

if (!$('#geouser-map').length)
    return false;

var options = {
    center: new google.maps.LatLng(-14.235004,-51.92527999999999),
    zoom: 7,
    maxZoom: 17,
    mapTypeId: google.maps.MapTypeId.ROADMAP,
    mapTypeControl: false,
    panControl: false,
    streetViewControl: false,
    zoomControlOptions: {style: google.maps.ZoomControlStyle.SMALL }
};

map = new google.maps.Map(document.getElementById("geouser-map"), options);

    /*var myPosition = lugares.params['coords'].split(',');
    myPosition = new google.maps.LatLng(myPosition[0], myPosition[1]);
    lugares.markers['myLocation'] = new google.maps.Marker({
        map: lugares.map,
        title: 'Minha Localização',
        position: myPosition,
        icon: catracaIconMe,
        shadow: catracaIconShadow
    });
    bounds.extend(myPosition);*/

});
