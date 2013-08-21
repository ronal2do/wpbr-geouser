<?php
/*
Plugin Name: GeoUser
Plugin URI: http://github.com/WP-Brasil/geouser
Description: Add georeference information fields to user profile. Made to be used with the <a href="#">map theme</a>.
Author: Ricardo Moraleida, Vinicius Massuchetto
Version: 1.0
Author URI: http://github.com/WP-Brasil/geouser
*/

add_action( 'admin_enqueue_scripts', 'geouser_scripts' );

function geouser_scripts() {

    if ( empty( $_SERVER['SCRIPT_NAME'] ) ||
        !in_array( $_SERVER['SCRIPT_NAME'],
        array( '/wp-admin/profile.php', '/wp-admin/user-edit.php' ) ) )
        return false;

    wp_enqueue_script( 'google-maps-v3', 'http://maps.google.com/maps/api/js?sensor=false' );
    wp_enqueue_script( 'geouser', plugins_url( '/geouser.js', __FILE__ ) );

}

add_action( 'show_user_profile', 'geouser_fields' );
add_action( 'edit_user_profile', 'geouser_fields' );

function geouser_fields( $user ) {
    ?>
    <h3><?php _e( 'Geolocalization', 'geouser' ); ?></h3>
    <table class="form-table">
    <tr>
    <th><label for="address"><?php _e( 'Pin your location in the map', 'geouser' ); ?></label></th>
    <td>
    <div id="geouser-map" style="display:block; width:500px; height: 300px"></div>
    <input type="hidden" name="lat" value="<?php echo get_user_meta( $user, 'lat' ); ?>" />
    <input type="hidden" name="lng" value="<?php echo get_user_meta( $user, 'lng' ); ?>" />
    </td>
    </tr>
    </table>
<?php }

add_action( 'personal_options_update', 'geouser_save' );
add_action( 'edit_user_profile_update', 'geouser_save' );

function geouser_save( $user_id ) {

    if ( !current_user_can( 'edit_user', $user_id ) )
        return false;

    foreach( array( 'lat', 'lng' ) as $i ) {
        if ( !empty( $_POST[ $i ] ) && floatval( $_POST[ $i ] ) )
            update_user_meta( $user_id, $i, $_POST[ $i ] );
    }

}
