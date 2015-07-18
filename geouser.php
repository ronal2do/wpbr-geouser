<?php

/**
 * The GeoUser plugin is a plugin created by the WordPress Brasil community 
 * to add georeference information fields to user profile.
 *
 * @package GeoUser
 */

/**
 * Plugin Name: GeoUser
 * Plugin URI:  http://github.com/WP-Brasil/geouser
 * Description: Add georeference information fields to user profile.
 * Author:      Ricardo Moraleida, Vinicius Massuchetto
 * Author URI:  http://github.com/WP-Brasil/geouser
 * Version:     1.1
 * Text Domain: geouser
 * License:     GPLv2 or later (license.txt)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists('GeoUser') ) : 

    /**
    * The Main GeoUser Class
    *
    * Highly inspired by BuddyPress and bbPress plugins
    *
    * @since GeoUser (1.0)
    */
    class GeoUser {

        /**
         * Single Instance
         *
         * @since GeoUser (1.0)
         * 
         * @return instance
         */
        public static function instance() {

            // Store the instance locally to avoid private static replication
            static $instance = null;

            // Only run these methods if they haven't been run previously
            if ( null === $instance ) {
                $instance = new GeoUser;
                $instance->setup_globals();
                $instance->setup_actions();
            }

            // Always return the instance
            return $instance;
        }

        /**
         * A dummy constructor to prevent GeoUser from being loaded more than once.
         *
         * @since GeoUser (1.0)
         */
        private function __construct() { /* Do nothing here */ }

        /** Private Methods *******************************************************/

        /**
         * Defining Brazil Coordinates
         *
         * @since GeoUser (1.0)
         * 
         * @access private
         */
        private function setup_globals() {

            /** Paths *************************************************************/
            $this->file       = __FILE__;
            $this->plugin_dir = plugin_dir_path( $this->file );
            $this->plugin_url = plugin_dir_url( $this->file );

            /** Constansts *********************************************************/
            if ( !defined( 'GEOUSER_INITIAL_LAT' ) || !GEOUSER_INITIAL_LAT
                || !defined( 'GEOUSER_INITIAL_LNG' ) || !GEOUSER_INITIAL_LNG ) {
                
                // Brazil
                define( 'GEOUSER_INITIAL_LAT', -15 );
                define( 'GEOUSER_INITIAL_LNG', -55 );
            }
        }

        /**
         * Adding the GeoUser Actions and Filter
         * 
         * @since GeoUser (1.0)
         *
         * @uses add_action()
         * 
         * @access private
         */
        private function setup_actions() {
            add_action( 'wp_enqueue_scripts', array( $this, 'geouser_scripts' ) );

            add_action( 'edit_user_profile', array( $this, 'geouser_fields' ) );
            add_action( 'show_user_profile', array( $this, 'geouser_fields' ) );

            add_action( 'personal_options_update', array( $this, 'geouser_save' ), 10, 2 );
            add_action( 'edit_user_profile_update', array( $this, 'geouser_save' ), 10, 2 );

            // Removes the Admin bar
            if ( isset( $_GET['embed'] ) ) {
                add_filter('show_admin_bar', '__return_false');
            }
        }

        /**
         * Adds the needed js for the Map to appear with the proper coordinates
         *
         * @since GeoUser (1.0)
         *
         * @uses wp_enqueue_script() Loads the scripts
         * @uses wp_localize_script() Localize global variables
         *
         * @return array An array of scripts files
         */
        public function geouser_scripts() {

            global $pagenow;

            if ( !in_array( $pagenow, array( 'profile.php', 'user-edit.php' ) ) )
                return false;

            // wp_enqueue_script( 'google-maps-v3', '//maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&language=pt_br' );
            wp_enqueue_script( 'google-maps-v3', 'http://maps.google.com/maps/api/js?sensor=false' );
            wp_enqueue_script( 'geouser', $this->plugin_url . 'js/geouser.js' );

            $params_1 = array(
                'initial_lat' => GEOUSER_INITIAL_LAT,
                'initial_lng' => GEOUSER_INITIAL_LNG
            );

            wp_localize_script( 'geouser', 'geouser', $params_1 );
        }

        /**
         * Fetches the users to put on the map with the required information
         *
         * @since GeoUser (1.0)
         *
         * @uses get_transient()
         * @uses set_transient()
         * @uses bp_get_profile_field_data()
         * 
         * @return array An array of users
         */
        public function get_map_users() {

            // if ( $users = get_transient( 'map_users' ) )
                // return $users;

            $args = array(
                'orderby'   => 'ID',
                'meta_key'  => 'location',
                'number'    => 5000, // Putting this value to not go hard on the server. Unlimited numbers is bad for SQL performance for large databases
            );

            $user_query = new WP_User_Query( $args );

            if ( ! empty( $user_query->results ) ) {
                
                $users = array();    
                foreach ( $user_query->results as $user ) {

                    $user_id = $user->user_id;

                    // Fetching location info
                    $location = get_user_meta( $user_id );

                    $loc = unserialize( $location['location'][0] );

                    if ( empty( $loc[0] ) || empty( $loc[1] ) )
                        continue;

                    $marker = !empty($loc[2]) ? get_stylesheet_directory_uri() . '/img/'.'pins/'.$loc[2].'.png' : $params['imgbase'] . 'marker.png';

                    $users[] = array(
                        'ID'                => $user_id,
                        'display_name'      => $user_id->display_name,
                        'bp_avatar'         => md5( $user_id->user_email ),
                        'lat'               => $loc[0],
                        'lng'               => $loc[1],
                        'marker'            => $marker
                    );
                }
            }

            // set_transient( 'map_users', $users, 3600 * 24 );

            return $users;
        }

        /**
         * Outputs the total number of users fetching by the location metadata
         *
         * @since GeoUser (1.0)
         *
         * @uses WP_User_Query() For fetching the users signed today
         * @uses wp_cache_set() Caching the WP_User_Query
         * 
         * @return int
         */
        public function total_number() {

            // WP_User_Query arguments
            $total_number_geouser_args = array (
                'order'          => 'ID',
                'meta_key'       => 'location',
                'number'         => 5000 // Putting this value to not go hard on the server. Unlimited numbers is bad for SQL performance for large databases
            );

            // The User Query
            $geouser_query = new WP_User_Query( $total_number_geouser_args );

            // Inspired by BuddyPress
            
            // WP_User_Query doesn't cache the data it pulls from wp_users,
            // and it does not give us a way to save queries by fetching
            // only uncached users. So we set it here.
            foreach ( $geouser_query->results as $u ) {
                wp_cache_set( 'geouser_userdata_' . $u->ID, $u, 'geouser' );
            }

            // Reindex for easier matching
            $r = array();
            foreach ( $geouser_query->results as $u ) {
                $r[ $u->ID ] = $u;
            }
            
            $n = (int) $geouser_query->get_total();

            return $n;
        }

        /**
         * Fetches the total number of geousers (users with location added)
         *
         * @since GeoUser (1.0)
         *
         * @return int
         */
        public function geousers_total() {
            return $this->total_number();
        }

        /**
         * Outputs the needed fields in user profile page
         *
         * @since GeoUser (1.0)
         *
         * @return string
         */
        public function geouser_fields( $user ) {

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
            <?php echo $map_table['html']; ?>
        <?php }

        /**
         * Saves the GeoUser Informantion in the user metadata
         *
         * @since GeoUser (1.0)
         *
         * @param $field_id int ID of a saved field
         * @param $value string New value of a field
         * 
         * @return bool
         */
        public function geouser_save( $user_id ) {

            // Bail if user is not able to edit his profile
            if ( ! current_user_can( 'edit_user', $user_id ) )
                return false;

            if ( !empty( $_POST['lat'] ) && floatval( $_POST['lat'] )
                && !empty( $_POST['lng'] ) && floatval( $_POST['lng'] ) ) {

                $pin = ( ! empty( $_POST['geouser-pin'] ) ) ? $_POST['geouser-pin'] : null;

                update_user_meta( $user_id, 'location', array( $_POST['lat'], $_POST['lng'], $pin ) );
            }

            // Updates user with GeoUser info
            do_action('geouser_save', $user_id);
            
            // Deletes the GeoUser Transient
            // delete_transient( 'map_users' );
        }
    }
endif;

/**
 * The main function responsible for returning the one true GeoUser Instance
 *
 * @since GeoUser (1.0)
 */
function GeoUser() {
    return GeoUser::instance();
}
add_action( 'plugins_loaded', 'GeoUser');

// That's it!
