<?php
/**
 * Retrieves Twitter Profile Image of a screenname
 *
 * @package    Twitter_Avatar_Reloaded
 * @subpackage Twitter
 * @author     Sudar
 * @since      2.0
 */
class Twitter_Profile_Image {
    /**
     * get the profileImage of the passed in screen_name
     *
     * @static
     * @param  string $screen_name Screen name of the Twitter user
     * @return string             Path to the profile Image
     */
    static function get_profile_image( $screen_name ) {
        if ( ! twitter_api_configured() ) {
            return '';
        }

        $response = twitter_api_get( 'users/show', array( 'screen_name' => $screen_name ) );

        if ( is_array( $response ) && array_key_exists( 'profile_image_url', $response ) ) {
            return $response['profile_image_url'];
        } else {
            return '';
        }
    }
} // END static class Twitter_Profile_Image
?>
