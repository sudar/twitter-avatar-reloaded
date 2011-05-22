<?php
/**
Plugin Name: Twitter Avatar Reloaded
Plugin URI: http://sudarmuthu.com/wordpress/twitter-avatar-reloaded
Description: Stores Twitter username together with comments and replaces gravatar with twitter avatar.
Author: Sudar
Donate Link: http://sudarmuthu.com/if-you-wanna-thank-me
Version: 0.8
Author URI: http://sudarmuthu.com/
Text Domain: twitter-avatar-reloaded

=== RELEASE NOTES ===
2010-03-13 - v0.1 - Initial Release
2010-03-16 - v0.2 - Proper alignment of the Twitter username field
2010-03-16 - v0.3 - Added translation for Hebrew (Thanks Sagive)
2010-08-09 - v0.4 - Removed JavaScript from unncessary pages.
2010-08-10 - v0.5 - Added support for registered users and added option to specify Twitter field label.
2011-02-05 - v0.6 - Added Brazilian Portuguese and Dutch translations
2011-05-11 - v0.7 - Added template functions to display Comment author twitter id and profile url
2011-05-22 - v0.8 - Added German Translations

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

        // Settings hooks
        add_action( 'admin_menu', array(&$this, 'register_settings_page') );
        add_action( 'admin_init', array(&$this, 'add_settings') );

        // Display twitter textbox in the comment form
        add_action('comment_form', array(&$this, 'add_twitter_field'), 9);

        // Display Twitter field in user's profile page
        add_filter('user_contactmethods', array(&$this, 'add_contactmethods'), 10, 1);

        // Save the twitter field
        // priority is very low (50) because we want to let anti-spam plugins have their way first.
        add_filter('comment_post', array(&$this, 'save_twitter_field'), 50);

        //hook the show gravatar function
        add_filter('get_avatar', array(&$this, 'change_avatar'), 10, 5);
        add_filter('get_avatar_comment_types', array(&$this, 'add_avatar_types'));

        // Enqueue the script
        add_action('template_redirect', array(&$this, 'add_script'));

        // add action links
        $plugin = plugin_basename(__FILE__);
        add_filter("plugin_action_links_$plugin", array(&$this, 'add_action_links'));

    }

    /**
     * Register the settings page
     */
    function register_settings_page() {
        add_options_page( __('Twitter Avatar Reloaded', 'twitter-avatar-reloaded'), __('Twitter Avatar Reloaded', 'twitter-avatar-reloaded'), 8, 'twitter-avatar-reloaded', array(&$this, 'settings_page') );
    }

    /**
     * add options
     */
    function add_settings() {
        // Register options
        register_setting( 'twitter-avatar-reloaded-options', 'twitter-avatar-reloaded-options', array(&$this, 'validate_settings'));

        //Global Options section
        add_settings_section('gr_global_section', __('Settings', 'twitter-avatar-reloaded'), array(&$this, 'tar_global_section_text'), __FILE__);
        add_settings_field('field-label', __('Twitter Field Label', 'twitter-avatar-reloaded'), array(&$this, 'tar_field_label_callback'), __FILE__, 'gr_global_section');

    }

    /**
     * Enqueue JavaScript
     */
    function add_script() {
        // Enqueue the script on single page/post
        if (is_singular()) {
            wp_enqueue_script('ta', plugin_dir_url(__FILE__) . 'twitter-avatar-reloaded.js', array('jquery'), '0.1', true);
        }
    }

    /**
     * Add another contact field to the user profile page
     *
     * @param array $contactmethods
     * @return array
     */
    function add_contactmethods( $contactmethods ) {
        // Add Twitter
        $contactmethods['twitter'] = __('Twitter Username', 'twitter-avatar-reloaded');
        return $contactmethods;
    }

    /**
     * Add Tweetbacks to the allowed avatar types
     * For Tweetbacks and Tweetback Helper functions
     *
     * @param <type> $avatar_types
     * @return <type>
     */
    function add_avatar_types( $avatar_types ) {
        // Add Tweetbacks
        $avatar_types[] = 'tweetback';
        return $avatar_types;
    }

    /**
     * Add twitter field to the form
     * @global <type> $wp_scripts
     */
    function add_twitter_field() {
        global $wp_scripts;

        if (comments_open() && !is_user_logged_in() && isset($wp_scripts) && $wp_scripts->query('ta')) {
            $options = get_option('twitter-avatar-reloaded-options');
?>
            <p id="ta_twitter" style="display:block">
                <input type="textbox" id="ta_twitter_field" class="textbox" tabindex="4" size="30" name="ta_twitter_field" value="<?php echo esc_attr($_COOKIE['comment_author_twitter' . COOKIEHASH]); ?>" />
                <label for="ta_twitter_field">
<?php
                    if ($options['field-label'] != '') {
                        echo $options['field-label'];
                    } else {
                        _e('Twitter', 'twitter-avatar-reloaded');
                    }
 ?>
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
     * Adds Footer links.
     *
     * Based on http://striderweb.com/nerdaphernalia/2008/06/give-your-wordpress-plugin-credit/
     */
    function add_footer_links() {
        $plugin_data = get_plugin_data( __FILE__ );
        printf('%1$s ' . __("plugin", 'twitter-avatar-reloaded') .' | ' . __("Version", 'twitter-avatar-reloaded') . ' %2$s | '. __('by', 'twitter-avatar-reloaded') . ' %3$s<br />', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
    }

    /**
     * Dipslay the Settings page
     */
    function settings_page() {
?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e( 'Twitter Avatar Reloaded Settings', 'twitter-avatar-reloaded' ); ?></h2>

            <form id="smer_form" method="post" action="options.php">
                <?php settings_fields('twitter-avatar-reloaded-options'); ?>
        		<?php do_settings_sections(__FILE__); ?>

                <p class="submit">
                    <input type="submit" name="twitter-avatar-reloaded-submit" class="button-primary" value="<?php _e('Save Changes', 'twitter-avatar-reloaded') ?>" />
                </p>
            </form>

            <h3><?php _e('Support', 'twitter-avatar-reloaded'); ?></h3>
            <p><?php _e('If you have any questions/comments/feedback about the Plugin then post a comment in the <a target="_blank" href = "http://sudarmuthu.com/wordpress/twitter-avatar-reloaded">Plugins homepage</a>.','twitter-avatar-reloaded'); ?></p>
            <p><?php _e('If you like the Plugin, then consider doing one of the following.', 'twitter-avatar-reloaded'); ?></p>
            <ul style="list-style:disc inside">
                <li><?php _e('Write a blog post about the Plugin.', 'twitter-avatar-reloaded'); ?></li>
                <li><a href="http://twitter.com/share" class="twitter-share-button" data-url="http://sudarmuthu.com/wordpress/twitter-avatar-reloaded" data-text="Twitter Avatar Reloaded WordPress Plugin" data-count="none" data-via="sudarmuthu">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script><?php _e(' about it.', 'twitter-avatar-reloaded'); ?></li>
                <li><?php _e('Give a <a href = "http://wordpress.org/extend/plugins/twitter-avatar-reloaded/" target="_blank">good rating</a>.', 'twitter-avatar-reloaded'); ?></li>
                <li><?php _e('Say <a href = "http://sudarmuthu.com/if-you-wanna-thank-me" target="_blank">thank you</a>.', 'twitter-avatar-reloaded'); ?></li>
            </ul>

        </div>
<?php
        // Display credits in Footer
        add_action( 'in_admin_footer', array(&$this, 'add_footer_links'));
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

        $comment_author_twitter = '';
        $comment = get_comment(get_comment_ID());
        if ($comment->user_id) {
            $user_profile = get_userdata($comment->user_id);
            $comment_author_twitter = $user_profile->twitter;
        } else {
            $comment_author_twitter = get_metadata('comment', get_comment_ID(), 'comment_author_twitter', true);
        }
        
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

    // ---------------------------Callback functions ----------------------------------------------------------

    /**
     * Validate the options entered by the user
     *
     * @param <type> $input
     * @return <type>
     */
    function validate_settings($input) {
        $input['field-label'] = esc_attr($input['field-label']);
        if ($input['field-label'] == '') {
            $input['field-label'] = 'Twitter';
        }
        return $input;
    }

    /**
     * Print global section text
     */
    function  tar_global_section_text() {
    }

    /**
     * Callback for printing Field label Setting
     */
    function tar_field_label_callback() {
        $options = get_option('twitter-avatar-reloaded-options');
        echo "<input id='field-label' name='twitter-avatar-reloaded-options[field-label]' size='40' type='text' value='{$options['field-label']}' />";
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
        $response = wp_cache_get($url, 'twitter-avatar');
        if ($response == false) {
            // if it is not present in cache, make the request
            $response = wp_remote_request($url);
            // set the response in cache
            wp_cache_add($url, $response, 'twitter-avatar');
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

// ---------------------------Template functions ----------------------------------------------------------

/**
 * Get the Twitter id of the comment author
 *
 * @param <int> $comment_ID - ID of the comment - Optional
 * @return <string> - Comment author Twitter id
 */
function get_comment_author_twitter_id( $comment_ID = 0 ) {
	$comment = get_comment( $comment_ID );

    if ($comment->user_id) {
        $user_profile = get_userdata($comment->user_id);
        $comment_author_twitter = $user_profile->twitter;
    } else {
        $comment_author_twitter = get_metadata('comment', $comment->comment_ID, 'comment_author_twitter', true);
    }

    $comment_author_twitter = str_ireplace('http://twitter.com/', '', $comment_author_twitter);

	return apply_filters( 'get_comment_author_twitter_id', $comment_author_twitter, $comment );
}

/**
 * Print the Twitter id of the comment author
 * 
 * @param <int> $comment_ID - ID of the comment - Optional
 */
function comment_author_twitter_id( $comment_ID = 0 ) {
    $comment = get_comment( $comment_ID );
	echo apply_filters('comment_author_twitter_id', get_comment_author_twitter_id($comment_ID), $comment);
}

/**
 * Get the Twitter profile url of the comment author
 *
 * @param <int> $comment_ID - ID of the comment - Optional
 * @return <string> - Comment author Twitter profile url
 */
function get_comment_author_twitter_url( $comment_ID = 0 ) {
    $comment = get_comment( $comment_ID );
	$comment_author_twitter_url = 'http://twitter.com/' . get_comment_author_twitter_id($comment_ID);
	return apply_filters( 'get_comment_author_twitter_url', $comment_author_twitter_url, $comment );
}

/**
 * Print the Twitter url of the comment author
 *
 * @param <int> $comment_ID - ID of the comment - Optional
 */
function comment_author_twitter_url( $comment_ID = 0 ) {
    $comment = get_comment( $comment_ID );
	echo apply_filters('comment_author_twitter_url', get_comment_author_twitter_url($comment_ID), $comment );
}
?>
