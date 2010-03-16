<?php
/**
Plugin Name: Twitter Avatar Reloaded
Plugin URI: http://sudarmuthu.com/wordpress/twitter-avatar-reloaded
Description: Stores Twitter username together with comments and replaces gravatar with twitter avatar.
Author: Sudar
Version: 0.2
Author URI: http://sudarmuthu.com/
Text Domain: twitter-avatar-reloaded

=== RELEASE NOTES ===
2010-03-13 - v0.1 - Initial Release
2010-03-16 - v0.2 - Proper alignment of the Twitter username field

/*  Copyright 2009  Sudar Muthu  (email : sudar@sudarmuthu.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

Uses code from the following places

http://wordpress.org/extend/plugins/twitter-avatar/
http://wordpress.org/extend/plugins/openid/
http://www.digimantra.com
http://github.com/sudar/rolopress-core/blob/master/library/extensions/twitter-image.php
 
*/

/**
 * Twitter Avatar Reloaded Plugin Class
 */
class TwitterAvatarReloaded {

    /**
     * Initalize the plugin by registering the hooks
     */
    function __construct() {

        // Load localization domain
        load_plugin_textdomain( 'twitter-avatar-reloaded', false, dirname(plugin_basename(__FILE__)) . '/languages' );

        // Register hooks
        // Display twitter textbox in the comment form
        add_action('comment_form', array(&$this, 'add_twitter_field'), 9);

        // Save the twitter field
        // priority is very low (50) because we want to let anti-spam plugins have their way first.
        add_filter('comment_post', array(&$this, 'save_twitter_field'), 50);

        //hook the show gravatar function
        add_filter('get_avatar', array(&$this, 'change_avatar'), 10, 5);

        // Enqueue the script
        add_action('template_redirect', array(&$this, 'add_script'));

//        $plugin = plugin_basename(__FILE__);
//        add_filter("plugin_action_links_$plugin", array(&$this, 'add_action_links'));

    }

    /**
     * Enqueue the Retweet script
     */
    function add_script() {
        // Enqueue the script
        wp_enqueue_script('ta', plugin_dir_url(__FILE__) . 'twitter-avatar-reloaded.js', array('jquery'), '0.1', true);
    }

    /**
     * hook to add action links
     * @param <type> $links
     * @return <type>
     */
    function add_action_links( $links ) {
        // Add a link to this plugin's settings page
        $settings_link = '<a href="options-general.php?page=twitter-avatar-reloaded">' . __("Settings", 'twitter-avatar-reloaded') . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Adds Footer links. Based on http://striderweb.com/nerdaphernalia/2008/06/give-your-wordpress-plugin-credit/
     */
    function add_footer_links() {
        $plugin_data = get_plugin_data( __FILE__ );
        printf('%1$s ' . __("plugin", 'twitter-avatar-reloaded') .' | ' . __("Version", 'twitter-avatar-reloaded') . ' %2$s | '. __('by', 'twitter-avatar-reloaded') . ' %3$s<br />', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
    }

    /**
     * Add twitter field to the form
     * @global <type> $wp_scripts
     */
    function add_twitter_field() {
        global $wp_scripts;

        if (comments_open() && !is_user_logged_in() && isset($wp_scripts) && $wp_scripts->query('ta')) {
?>
            <p id="ta_twitter" style="display:block">
                <input type="textbox" id="ta_twitter_field" class="textbox" tabindex="4" size="30" name="ta_twitter_field" value="<?php echo esc_attr($_COOKIE['comment_author_twitter' . COOKIEHASH]); ?>" />
                <label for="ta_twitter_field">
                    <?php _e('Twitter', 'twitter-avatar-reloaded'); ?>
                </label>
            </p>
<?php
        }
    }

    /**
     * Save the twitter field to the database
     *
     * @param <type> $comment_id
     */
    function save_twitter_field($comment_id) {
        if( isset($_POST['ta_twitter_field']) && !empty($_POST['ta_twitter_field']) && $_POST['ta_twitter_field'] != '') {

            $comment_author_twitter = $_POST['ta_twitter_field'];

            // Strip the twitter url if present
            $comment_author_twitter = str_ireplace("http://twitter.com/", "", $comment_author_twitter);

            setcookie('comment_author_twitter' . COOKIEHASH, $comment_author_twitter, time()+60*60*24*30);
            update_metadata('comment', $comment_id, 'comment_author_twitter', $comment_author_twitter);
        }
    }

    /**
     * Change the avatar
     *
     * @param <type> $avatar
     * @param <type> $id_or_email
     * @param <type> $size
     * @param <type> $default
     * @param <type> $alt
     */
    function change_avatar($avatar, $id_or_email, $size, $default, $alt) {
        $comment_author_twitter = get_metadata('comment', get_comment_ID(), 'comment_author_twitter', true);
        $comment_author_twitter = str_ireplace('http://twitter.com/', '', $comment_author_twitter);

        $t = new twitterImage($comment_author_twitter, $default); //create instance of the class and pass the username
        $image_url = $t->get_profile_image();

        if ($image_url != '') {
            if ( false === $alt)
                $safe_alt = '';
            else
                $safe_alt = esc_attr( $alt );

            $avatar = "<img alt='{$safe_alt}' src='{$image_url}' class='avatar avatar-{$size} photo avatar-default' height='{$size}' width='{$size}' />";
        }
        return $avatar;
    }

    // PHP4 compatibility
    function TwitterAvatarReloaded() {
        $this->__construct();
    }
}

// Start this plugin once all other plugins are fully loaded
add_action( 'init', 'TwitterAvatarReloaded' ); function TwitterAvatarReloaded() { global $TwitterAvatarReloaded; $TwitterAvatarReloaded = new TwitterAvatarReloaded(); }

/*
 * Please do not remove author's information.
 * Created by Sachin Khosla
 * URL : http://www.digimantra.com
 * Date : August 17,2009
 *
 * Modifyed by Sudar <http://sudarmuthu.com/rolopress> to make it more comapatible with WordPress API
 * Works only in version greater than WordPress 2.9
 */

class TwitterImage {
    var $user='';
    var $image='';
    var $displayName='';
    var $url='';
    var $format='json';
    var $requestURL='http://twitter.com/users/show/';
    var $imageNotFound=''; //any generic image/avatar. It will display when the twitter user is invalid
    var $noUser=true;

    function __construct($user, $generic_image = '') {
        $this->user = $user;
        $this->imageNotFound = $generic_image;
        if ($user == '') {
            $this->image = $this->imageNotFound;
        } else {
            $this->__init();
        }
    }

    /*
     * fetches user info from twitter
     * and populates the related vars
     */
    private function __init() {
        $data=json_decode($this->get_data($this->requestURL.$this->user.'.'.$this->format)); //gets the data in json format and decodes it
        if(strlen($data != '' && $data->error)<=0) {
            //check if the twitter profile is valid
            $this->image=$data->profile_image_url;
            $this->displayName=$data->name;
            $this->url=(strlen($data->url)<=0)?'http://twitter.com/'.$this->user:$data->url;
            $this->location=$data->location;
        } else {
            $this->image = $this->imageNotFound;
        }
    }

    /**
     * creates image tag
     *
     * @params
     * passing linked true -- will return an image which will link to the user's url defined on twitter profile
     * passing display true -- will render the image, else return
     */
    function print_profile_image($linked=false,$display=false) {
        $img="<img src='$this->image' border='0' alt='$this->displayName' />";
        $linkedImg="<a href='$this->url' rel='nofollow' title='$this->displayName'>$img</a>";
        if(!$linked && !$display) //the default case
            return $img;

        if($linked && $display)
            echo $linkedImg;

        if($linked && !$display)
            return $linkedImg;

        if($display && !$linked)
            echo $img;
    }

    /**
     * Return the profile image
     *
     * @return <type>
     */
    function get_profile_image() {
        return $this->image;
    }

    /**
     * gets the data from a URL
     * @param <string> $url
     * @return <string> the reponse content
     *
     */
    private function get_data($url) {
        $response = wp_cache_get($url, 'rolopress');
        if ($response == false) {
            // if it is not present in cache, make the request
            $response = wp_remote_request($url);
            // set the response in cache
            wp_cache_add($url, $response, 'rolopress');
        }
        
        if (is_a($response, 'WP_Error')) {
            return '';
        } else {
            return $response['body'];
        }
    }

    // PHP4 compatibility
    function TwitterImage() {
        $this->__construct();
    }
}
?>