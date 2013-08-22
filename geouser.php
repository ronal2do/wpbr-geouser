<?php
/*
Plugin Name: GeoUser
Plugin URI: http://github.com/WP-Brasil/geouser
Description: Add georeference information fields to user profile. Made to be used with the <a href="#">map theme</a>.
Author: Ricardo Moraleida, Vinicius Massuchetto
Version: 1.0
Author URI: http://github.com/WP-Brasil/geouser
*/

 if ( !defined( 'GEOUSER_INITIAL_LAT' ) || !GEOUSER_INITIAL_LAT
    || !defined( 'GEOUSER_INITIAL_LNG' ) || !GEOUSER_INITIAL_LNG ) {
    // Brazil
    define( 'GEOUSER_INITIAL_LAT', -15 );
    define( 'GEOUSER_INITIAL_LNG', -55 );
}

add_action( 'admin_enqueue_scripts', 'geouser_scripts' );

function geouser_scripts() {

    global $pagenow;

    if ( !in_array( $pagenow, array( 'profile.php', 'user-edit.php' ) ) )
        return false;

    wp_enqueue_script( 'google-maps-v3', 'http://maps.google.com/maps/api/js?sensor=false' );
    wp_enqueue_script( 'geouser', plugins_url( '/geouser.js', __FILE__ ) );

    $params = array(
        'initial_lat' => GEOUSER_INITIAL_LAT,
        'initial_lng' => GEOUSER_INITIAL_LNG
    );

    wp_localize_script( 'geouser', 'geouser', $params );

}

add_action( 'show_user_profile', 'geouser_fields' );
add_action( 'edit_user_profile', 'geouser_fields' );

function geouser_fields( $user ) {
    $location = array( 'lat' => false, 'lng' => false );
    if ( $loc = get_user_meta( $user->ID, 'location', true ) ) {
        $location['lat'] = $loc[0];
        $location['lng'] = $loc[1];
    }
    ?>
    <h3><?php _e( 'Geolocalization', 'geouser' ); ?></h3>
    <table class="form-table">
    <tr>
    <th><label for="address"><?php _e( 'Pin your location in the map', 'geouser' ); ?></label></th>
    <td>
    <p><?php _e( 'Search address', 'geouser' ); ?>:&nbsp;<input type="text" id="geouser-search" class="regular-text" /></p>
    <div id="geouser-map" style="display:block; width:500px; height: 300px; border: 1px solid #DFDFDF;"></div>
    <input type="hidden" id="geouser-lat" name="lat" value="<?php echo $location['lat']; ?>" />
    <input type="hidden" id="geouser-lng" name="lng" value="<?php echo $location['lng']; ?>" />
    <p class="help"><?php printf( __( 'Map theme will use %s information for your baloon.<br/>Update your %s account to show a full profile.', 'geouser' ), '<a href="http://gravatar.com">Gravatar</a>', '<b>' . $user->user_email . '</b>' ); ?></p>
    </td>
    </tr>
    </table>
<?php }

add_action( 'personal_options_update', 'geouser_save' );
add_action( 'edit_user_profile_update', 'geouser_save' );

function geouser_save( $user_id ) {

    if ( !current_user_can( 'edit_user', $user_id ) )
        return false;

    if ( !empty( $_POST['lat'] ) && floatval( $_POST['lat'] )
        && !empty( $_POST['lng'] ) && floatval( $_POST['lng'] ) ) {
        echo update_user_meta( $user_id, 'location', array( $_POST['lat'], $_POST['lng'] ) );
    }

}
