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
    $location = array( 'lat' => false, 'lng' => false, 'type' => false );
    if ( $loc = get_user_meta( $user->ID, 'location', true ) ) {
        $location['lat'] = $loc[0];
        $location['lng'] = $loc[1];
        $location['type'] = $loc[2];
    }
    ?>
    <?php $map_table = ''; ?>
    <?php $map_table .= '<h3>'.__( "Geolocalization", "geouser" ).'</h3>'; ?>
    <?php $map_table .= '<table class="form-table">'; ?>
    <?php $map_table .= '<tr>';?>
    <?php $map_table .= '<th><label for="address">'.__( "Pin your location in the map", "geouser" ).'</label></th>';?>
    <?php $map_table .= '<td>';?>
    <?php $map_table .= '<p>'.__( "Search address", "geouser" ).'&nbsp;<input type="text" id="geouser-search" class="regular-text" /></p>';?>
    <?php $map_table .= '<div id="geouser-map" style="display:block; width:500px; height: 300px; border: 1px solid #DFDFDF;"></div>';?>
    <?php $map_table .= '<input type="hidden" id="geouser-lat" name="lat" value="'.$location["lat"].'" />';?>
    <?php $map_table .= '<input type="hidden" id="geouser-lng" name="lng" value="'.$location["lng"].'" />';?>
    <?php $gravatar_url = '<a href="http://gravatar.com">Gravatar</a>';?>
    <?php $map_table .= '<p class="help">'; ?>
    <?php $map_table .= sprintf( __( "Map theme will use %s information for your baloon.<br/>Update your %s account to show a full profile.", "geouser" ), $gravatar_url, '<b>' . $user->user_email . '</b>' );?>
    <?php $map_table .= '</p>';?>
    <?php $map_table .= '</td>';?>
    <?php $map_table .= '</tr>';?>
    <?php $map_table .= '</table>';?>
    <?php echo apply_filters('geouser_map_table',$map_table); ?>

    <?php $map_table = ''; ?>
    <?php $map_table .= '<h3>'.__( "Who are you? / Who do you work for?", "geouser" ).'</h3>'; ?>
    <?php $map_table .= '<table class="form-table">'; ?>
    <?php $map_table .= '<tr>';?>
    <?php $map_table .= '<div style="float:left;display:inline-block;padding:10px">';?>
    <?php $map_table .= '<label>';?>
    <?php $map_table .= '<img width="32" height="32" src="' . plugins_url( 'images/pins/big-blue.png', __FILE__ ) . '">';?>
    <?php $map_table .= '<div style="clear:both;width:100%;height:5px;padding-top:10px;"></div>'; ?> 
    <?php $map_table .= __( "<b>Freelancer</b>", "geouser" ).'</label>';?>
    <?php $map_table .= '<div style="clear:both;width:100%;height:5px"></div>'; ?> 
    <?php $map_table .= '<input type="radio" name="geouser-pin" value="freelancer" '.checked("freelancer",$location["type"],false).' />';?>
    <?php $map_table .= '</div>';?>

    <?php $map_table .= '<div style="float:left;display:inline-block;padding:10px">';?>
    <?php $map_table .= '<label>';?>
    <?php $map_table .= '<img width="32" height="32" src="' . plugins_url( 'images/pins/big-red.png', __FILE__ ) . '">';?>
    <?php $map_table .= '<div style="clear:both;width:100%;height:5px;padding-top:10px;"></div>'; ?> 
    <?php $map_table .= __( "<b>WP Bussines</b>", "geouser" ).'</label>';?>
    <?php $map_table .= '<div style="clear:both;width:100%;height:5px"></div>'; ?> 
    <?php $map_table .= '<input type="radio" name="geouser-pin" value="wp-bussines" '.checked("wp-bussines",$location["type"],false).' />';?>
    <?php $map_table .= '</div>';?>

    <?php $map_table .= '<div style="float:left;display:inline-block;padding:10px">';?>
    <?php $map_table .= '<label>';?>
    <?php $map_table .= '<img width="32" height="32" src="' . plugins_url( 'images/pins/big-green.png', __FILE__ ) . '">';?>
    <?php $map_table .= '<div style="clear:both;width:100%;height:5px;padding-top:10px;"></div>'; ?> 
    <?php $map_table .= __( "<b>Agency</b>", "geouser" ).'</label>';?>
    <?php $map_table .= '<div style="clear:both;width:100%;height:5px"></div>'; ?> 
    <?php $map_table .= '<input type="radio" name="geouser-pin" value="agency" '.checked("agency",$location["type"],false).' />';?>
    <?php $map_table .= '</div>';?>

    <?php $map_table .= '<div style="float:left;display:inline-block;padding:10px">';?>
    <?php $map_table .= '<label>';?>
    <?php $map_table .= '<img width="32" height="32" src="' . plugins_url( 'images/pins/big-cyan.png', __FILE__ ) . '">';?>
    <?php $map_table .= '<div style="clear:both;width:100%;height:5px;padding-top:10px;"></div>'; ?> 
    <?php $map_table .= __( "<b>Other Bussines</b>", "geouser" ).'</label>';?>
    <?php $map_table .= '<div style="clear:both;width:100%;height:5px"></div>'; ?> 
    <?php $map_table .= '<input type="radio" name="geouser-pin" value="other-bussines" '.checked("other-bussines",$location["type"],false).' />';?>
    <?php $map_table .= '</div>';?>

    <?php $map_table .= '<div style="float:left;display:inline-block;padding:10px">';?>
    <?php $map_table .= '<label>';?>
    <?php $map_table .= '<img width="32" height="32" src="' . plugins_url( 'images/pins/big-purple.png', __FILE__ ) . '">';?>
    <?php $map_table .= '<div style="clear:both;width:100%;height:5px;padding-top:10px;"></div>'; ?> 
    <?php $map_table .= __( "<b>Universities</b>", "geouser" ).'</label>';?>
    <?php $map_table .= '<div style="clear:both;width:100%;height:5px"></div>'; ?> 
    <?php $map_table .= '<input type="radio" name="geouser-pin" value="universities" '.checked("universities",$location["type"],false).' />';?>
    <?php $map_table .= '</div>';?>

    <?php $map_table .= '<div style="float:left;display:inline-block;padding:10px">';?>
    <?php $map_table .= '<label>';?>
    <?php $map_table .= '<img width="32" height="32" src="' . plugins_url( 'images/pins/big-orange.png', __FILE__ ) . '">';?>
    <?php $map_table .= '<div style="clear:both;width:100%;height:5px;padding-top:10px;"></div>'; ?> 
    <?php $map_table .= __( "<b>Government</b>", "geouser" ).'</label>';?>
    <?php $map_table .= '<div style="clear:both;width:100%;height:5px"></div>'; ?> 
    <?php $map_table .= '<input type="radio" name="geouser-pin" value="government" '.checked("government",$location["type"],false).' />';?>
    <?php $map_table .= '</div>';?>

    <?php $map_table .= '<div style="float:left;display:inline-block;padding:10px">';?>
    <?php $map_table .= '<label>';?>
    <?php $map_table .= '<img width="32" height="32" src="' . plugins_url( 'images/pins/big-lime.png', __FILE__ ) . '">';?>
    <?php $map_table .= '<div style="clear:both;width:100%;height:5px;padding-top:10px;"></div>'; ?> 
    <?php $map_table .= __( "<b>NGO</b>", "geouser" ).'</label>';?>
    <?php $map_table .= '<div style="clear:both;width:100%;height:5px"></div>'; ?> 
    <?php $map_table .= '<input type="radio" name="geouser-pin" value="ngo" '.checked("ngo",$location["type"],false).' />';?>
    <?php $map_table .= '</div>';?>

    <?php $map_table .= '</td>';?>
    <?php $map_table .= '</tr>';?>
    <?php $map_table .= '</table>';?>
    <?php $map_table = apply_filters('geouser_map_pins', array('html' => $map_table, 'user' => $user->ID)); ?>
    <?php echo $map_table['html'];?>
<?php }

add_action( 'personal_options_update', 'geouser_save' );
add_action( 'edit_user_profile_update', 'geouser_save' );

function geouser_save( $user_id ) {

    if ( !current_user_can( 'edit_user', $user_id ) )
        return false;

    if ( !empty( $_POST['lat'] ) && floatval( $_POST['lat'] )
        && !empty( $_POST['lng'] ) && floatval( $_POST['lng'] ) ) {
        $pin = (!empty($_POST['geouser-pin'])) ? $_POST['geouser-pin'] : null;
        update_user_meta( $user_id, 'location', array( $_POST['lat'], $_POST['lng'], $pin ) );
    }
    do_action('geouser_save',$user_id);

}
