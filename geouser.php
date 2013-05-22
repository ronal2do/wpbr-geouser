<?php
/*
Plugin Name: Geo User Registration
Plugin URI: 
Description: Get geolocalization infos from your users at signup
Author: moraleida.me
Version: 1.0
Author URI: http://moraleida.me
*/

    add_action('register_form','geouser_register_form');
    // add_filter('registration_errors', 'geouser_registration_errors', 10, 3);
    add_action('user_register', 'geouser_user_register');
    add_action('login_enqueue_scripts', 'geouser_scripts');

    function geouser_scripts() {
        wp_enqueue_script( 'jquery' );
    }

    

    function geouser_register_form (){
        // $first_name = ( isset( $_POST['first_name'] ) ) ? $_POST['first_name']: '';
        $latlon = array(-14.264383,-56.601563);
        ?>
        <p><label><?php _e('Mark yourself on the map','geouser') ?></p>
        <div id="map-canvas" style="min-height: 200px;"></div>
        <p><label for="lat"><?php _e('Latitude','geouser') ?><br />
            <input type="text" name="geouser_lat" id="id_la" /></p>
        <p><label for="long"><?php _e('Longitude','geouser') ?><br />
            <input type="text" name="geouser_lon" id="id_lo" /></p>
        <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false&amp;key=AIzaSyC4x-ee_PZUEJElO-TPqlcFqRnPRDOLRyA"></script>
        <script type="text/javascript">
    jQuery(document).ready(function() {
        
        var latlon = <?php echo json_encode($latlon); ?>;
        
        var lat1 = latlon[0];

        function getCityState(latlon) {
            
            geocoder.geocode({'location': latlon.latLng}, function(results, status){
                if(status == google.maps.GeocoderStatus.OK){
                    
                  // Clean variables
                  city = '';
                  county = '';
                  state = '';
                  // this code from: http://stackoverflow.com/a/13679937/1001109
                  for (i = 0 ; i < results.length ; ++i) {
                    var super_var1 = results[i].address_components;
                    for (j = 0 ; j < super_var1.length ; ++j) {
                      var super_var2 = super_var1[j].types;
                      for (k = 0 ; k < super_var2.length ; ++k) {
                        //find city
                        if (super_var2[k] == "locality") {
                          city = super_var1[j].long_name;
                        }
                        //find county 
                        if (super_var2[k] == "administrative_area_level_2") {
                          //put the county name in the form
                          county = super_var1[j].long_name;
                        }
                        //find state
                        if (super_var2[k] == "administrative_area_level_1") {
                          //put the state abbreviation in the form
                          
                          state = super_var1[j].short_name;
                        }
                      }
                    }
                  }

                    // update form values and reset map
                    marker.setOptions({position: latlon.latLng, map: map});
                    jQuery('#city').val(city);
                    jQuery('#state').val(state);
                    map.setCenter(latlon.latLng);
                }

            });
        }
        
        // Sem uso no momento, mas está aqui pra quando implementar a busca digitando o nome da cidade e estado
        function showAddress(address, zoom) {
            geocoder.geocode({'address': address}, function(results, status){
                if(status == google.maps.GeocoderStatus.OK){
                    // console.log(results);
                    latlng = results[0].geometry.location;
                    marker.setOptions({position: latlng, map: map});
                    jQuery('#id_la').val(latlng.lat());
                    jQuery('#id_lo').val(latlng.lng());
                    map.setCenter(latlng);
                    map.setZoom(zoom);
                }
            });


        }
        
        var mapDiv = document.getElementById('map-canvas');
        var map = new google.maps.Map(mapDiv, {
            center: new google.maps.LatLng(0, 0),
            zoom: 1,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        });
        var infowindow = new google.maps.InfoWindow({
                maxWidth: 300
            });
            
        var markers = [];
            
        for(var i = 0; i < latlon.length; i++) {
            
            var marker = new google.maps.Marker({
                map: map,
                position: new google.maps.LatLng(latlon[i]['lat'],latlon[i]['lon']),
                draggable: true,
                clickable: true,
                title: latlon[i]['author'],
                content: latlon[i]['content'],
                content: '<h3>'+latlon[i]['author']+'</h3><h5>'+latlon[i]['title']+'</h5><p class="cat">'+latlon[i]['categoria']+'</p><p class="com">'+latlon[i]['content']+'</p>'
                // icon: ''
            });
            
            //infowindow.open(map, marker);
            google.maps.event.addListener(marker, 'click', function() {
                infowindow.setContent('<div class="infowindow"><p>'+this.content+'</p></div>');
                infowindow.setPosition(this.position);
                infowindow.open(map);
            });
            
            markers.push(marker);
        }
        
        var marker = new google.maps.Marker({
            map: map,
            position: new google.maps.LatLng(0, 0),
            draggable: true
            // icon: ''
        });
        
        markers.push(marker);
        
        
        var geocoder = new google.maps.Geocoder();
        
        google.maps.event.addListener(marker, 'dragend', function(event) {
            jQuery('#id_la').val(event.latLng.lat());
            jQuery('#id_lo').val(event.latLng.lng());
            getCityState(event);
        });
        
    });
</script>
        <p>
            <label for="city"><?php _e('City','geouser') ?><br />
                <input type="text" name="geouser_city" id="city" class="input" value="<?php echo esc_attr(stripslashes($city)); ?>" size="25" /></label>
        </p>
        <p>
            <label for="city"><?php _e('State','geouser') ?><br />
                <input type="text" name="geouser_state" id="state" class="input" value="<?php echo esc_attr(stripslashes($city)); ?>" size="25" /></label>
        </p>
        <?php
    }


    function geouser_registration_errors ($errors, $sanitized_user_login, $user_email) {

        // if ( empty( $_POST['geouser_lat'] ) )
           // $errors->add( 'geouser_lat_error', __('<strong>ERROR</strong>: You must include a value for latitude.','geouser') );

        // return $errors;
    }


    function geouser_user_register ($user_id) {
        if ( isset( $_POST['geouser_lat'] ) )
            update_user_meta($user_id, 'geouser_lat', $_POST['geouser_lat']);

        if ( isset( $_POST['geouser_lon'] ) )
            update_user_meta($user_id, 'geouser_lon', $_POST['geouser_lon']);

        if ( isset( $_POST['geouser_city'] ) )
            update_user_meta($user_id, 'geouser_city', $_POST['geouser_city']);

        if ( isset( $_POST['geouser_state'] ) )
            update_user_meta($user_id, 'geouser_state', $_POST['geouser_state']);
    }