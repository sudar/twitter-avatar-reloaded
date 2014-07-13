<?php
/**
Plugin Name: Twitter Avatar Reloaded
Plugin Script: twitter-avatar-reloaded.php
Plugin URI: http://sudarmuthu.com/wordpress/twitter-avatar-reloaded
Description: Stores Twitter username together with comments and replaces gravatar with twitter avatar.
Author: Sudar
Donate Link: http://sudarmuthu.com/if-you-wanna-thank-me
Version: 2.0.1
License: GPL
Author URI: http://sudarmuthu.com/
Text Domain: twitter-avatar-reloaded
Domain Path: languages/

=== RELEASE NOTES ===
Check readme file for full release notes

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
*/

if( ! function_exists( 'twitter_api_get' ) ) {
    require dirname( __FILE__ ) . '/includes/libs/wp-twitter-api/twitter-api.php';
}

if( ! class_exists( 'Twitter_Profile_Image' ) ) {
    require dirname( __FILE__ ) . '/includes/class-twitter-profile-image.php';
}
/**
 * Twitter Avatar Reloaded Plugin Class
 */
class TwitterAvatarReloaded {

	/**
	 * Menu slug
	 */
	const MENU_SLUG = 'twitter-avatar-reloaded';

    /**
     * Version of the Plugin
    */
    const VERSION = '2.0.1';

    /**
     * Initialize the plugin by registering the hooks
     */
    function __construct() {

        // Load localization domain
        load_plugin_textdomain( 'twitter-avatar-reloaded', false, dirname(plugin_basename(__FILE__)) . '/languages' );

        // Settings hooks
        add_action( 'admin_menu', array(&$this, 'register_settings_page') );
        add_action( 'admin_init', array(&$this, 'add_settings') );

        // Display twitter textbox in the comment form
        add_action('comment_form_default_fields', array(&$this, 'add_twitter_field'), 9);
        add_filter('wp_get_current_commenter', array(&$this, 'add_to_comment_data'), 10, 1);

		$options = get_option('twitter-avatar-reloaded-options');
		if ($options && array_key_exists('legacy-support', $options) && $options['legacy-support'] == 1) {
			// Display twitter textbox in the comment form
			add_action('comment_form', array(&$this, 'add_twitter_field_legacy'), 9);

			// Enqueue the script
			add_action('template_redirect', array(&$this, 'add_script'));
		}

        // Display Twitter field in user's profile page
        add_filter('user_contactmethods', array(&$this, 'add_contactmethods'), 10, 1);

        // Save the twitter field
        // priority is very low (50) because we want to let anti-spam plugins have their way first.
        add_filter('comment_post', array(&$this, 'save_twitter_field'), 50);

        //hook the show gravatar function
        add_filter('get_avatar', array(&$this, 'change_avatar'), 10, 5);
        add_filter('get_avatar_comment_types', array(&$this, 'add_avatar_types'));

        // add action links
        $plugin = plugin_basename(__FILE__);
        add_filter("plugin_action_links_$plugin", array(&$this, 'add_action_links'));
    }

    /**
     * Register the settings page
     */
    function register_settings_page() {
        add_options_page( __('Twitter Avatar Reloaded', 'twitter-avatar-reloaded'), __('Twitter Avatar Reloaded', 'twitter-avatar-reloaded'), 'manage_options', self::MENU_SLUG, array(&$this, 'settings_page') );
    }

    /**
     * add options
     */
    function add_settings() {
        // Register options
        register_setting( 'twitter-avatar-reloaded-options', 'twitter-avatar-reloaded-options', array(&$this, 'validate_settings'));

        //Add default Options section
        add_settings_section('gr_global_section', '', array(&$this, 'tar_global_section_text'), self::MENU_SLUG);

		// add setting fields
        add_settings_field('field-class', __('Twitter Field class', 'twitter-avatar-reloaded'), array(&$this, 'tar_field_class_callback'), self::MENU_SLUG, 'gr_global_section');
        add_settings_field('field-label', __('Twitter Field Label', 'twitter-avatar-reloaded'), array(&$this, 'tar_field_label_callback'), self::MENU_SLUG, 'gr_global_section');
        add_settings_field('field-html', __('Twitter Field html', 'twitter-avatar-reloaded'), array(&$this, 'tar_field_html_callback'), self::MENU_SLUG, 'gr_global_section');
        add_settings_field('legacy-support', __('Support for legacy themes', 'twitter-avatar-reloaded'), array(&$this, 'tar_legacy_theme_callback' ), self::MENU_SLUG, 'gr_global_section');

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
     * Add additional avatar types
     * For Tweetbacks and Tweetback Helper functions
     *
     * @param <type> $avatar_types
     * @return <type>
     */
    function add_avatar_types( $avatar_types ) {
        // Tweetbacks Plugins
		if (!in_array('tweetback', $avatar_types)) {
			$avatar_types[] = 'tweetback';
		}

		// Social Comments Plugin
		if (!in_array('twitter_tweets', $avatar_types)) {
			$avatar_types[] = 'twitter_tweets';
		}

		if (!in_array('twitter_retweets', $avatar_types)) {
			$avatar_types[] = 'twitter_retweets';
		}

        return $avatar_types;
    }

	/**
	 * Add author_twitter_field to the comment author data in cookie
	 *
	 * @return void
	 * @author Sudar
	 */
	function add_to_comment_data($commenterData) {
	  if (array_key_exists('comment_author_twitter' . COOKIEHASH, $_COOKIE)) {
		$commenterData['comment_author_twitter'] = $_COOKIE['comment_author_twitter' . COOKIEHASH];
	  }
		return $commenterData;
	}

    /**
     * Add twitter field to the form
     */
    function add_twitter_field($fields) {
		$options = get_option('twitter-avatar-reloaded-options');

		if ($options['field-html'] != '') {
			// if the user has specified the HTML, then use it
			$fields['ta_twitter_field'] = $options['field-html'];

		} else {

			// else try to guess it
			$dom = new DOMDocument();

			// dirty dirty hack - http://stackoverflow.com/a/4880227/24949
			// TODO: Should find a cleaner way
			if (version_compare(PHP_VERSION, '5.3.6') >= 0) {
				$dom->loadHTML($fields['url']);
			} else {
				$dom->loadXML($fields['url']);
			}

			$inputs = $dom->getElementsByTagName('input');
			foreach ($inputs as $input) {
				$input->setAttribute('id', 'ta_twitter_field');
				$input->setAttribute('name', 'ta_twitter_field');
				$input->setAttribute('type', 'text');
				if (array_key_exists('comment_author_twitter' . COOKIEHASH, $_COOKIE)) {
					$input->setAttribute('value', $_COOKIE['comment_author_twitter' . COOKIEHASH]);
				} else {
					$input->setAttribute('value', '');
				}
			}

			$labels = $dom->getElementsByTagName('label');
			foreach ($labels as $label) {
				$label->setAttribute('for', 'ta_twitter_field');
				if ($options['field-label'] != '') {
					$label->nodeValue = $options['field-label'];
				} else {
					$label->nodeValue = __('Twitter', 'twitter-avatar-reloaded');
				}
			}

			$ps = $dom->getElementsByTagName('p');
			foreach ($ps as $p) {
				if ($options['field-class'] != '') {
					$p->setAttribute('class', $options['field-class']);
				} else {
					$p->setAttribute('class', 'comment-form-twitter');
				}
			}

			// dirty dirty hack - http://stackoverflow.com/a/4880227/24949
			// TODO: Should find a cleaner way
			if (version_compare(PHP_VERSION, '5.3.6') >= 0) {
				$fields['ta_twitter_field'] = $dom->saveHTML($p);
			} else {
				$fields['ta_twitter_field'] = $dom->saveHTML();
			}
		}

		return $fields;
    }

	/*========================== Legacy Support ===========================*/
    /**
     * Add twitter field to the form (legacy way)
     * @global <type> $wp_scripts
     */
    function add_twitter_field_legacy() {
        global $wp_scripts;

        if (comments_open() && !is_user_logged_in() && isset($wp_scripts) && $wp_scripts->query('ta')) {
            $options = get_option('twitter-avatar-reloaded-options');
			if ($options['field-html'] != '') {
				echo $options['field-html'];
			} else {
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
	}

    /**
     * Enqueue JavaScript
     */
    function add_script() {
        // Enqueue the script on single page/post
        if (is_singular()) {
            wp_enqueue_script('ta', plugin_dir_url(__FILE__) . 'twitter-avatar-reloaded.js', array('jquery'), self::VERSION, true);
        }
	}

	/*========================== Legacy Support ===========================*/

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

			if ($comment_author_twitter != '') {
				setcookie('comment_author_twitter' . COOKIEHASH, $comment_author_twitter, time()+60*60*24*30);
				update_comment_meta($comment_id, 'comment_author_twitter', $comment_author_twitter);

				$comment_author_profile_image = get_twitter_profile_image($comment_author_twitter);
				if ($comment_author_profile_image != '') {
					update_comment_meta($comment_id, 'comment_author_twitter_profile_image', $comment_author_profile_image);
				}
			}
        }
    }

    /**
     * hook to add action links
     * @param <type> $links
     * @return <type>
     */
    function add_action_links( $links ) {
        // Add a link to this plugin's settings page
        $settings_link = '<a href="options-general.php?page=' . self::MENU_SLUG . '">' . __("Settings", 'twitter-avatar-reloaded') . '</a>';
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
            <h2><?php _e( 'Twitter Avatar Reloaded Settings', 'twitter-avatar-reloaded' ); ?></h2>

            <iframe height = "950" src = "http://sudarmuthu.com/projects/wordpress/twitter-avatar-reloaded/sidebar.php?color=<?php echo get_user_option('admin_color'); ?>&version=<?php echo self::VERSION; ?>"></iframe>

			<div style = "float:left; width:75%">
				<form id="smer_form" method="post" action="options.php">
					<?php settings_fields('twitter-avatar-reloaded-options'); ?>
					<?php do_settings_sections(self::MENU_SLUG); ?>

					<p class="submit">
						<input type="submit" name="twitter-avatar-reloaded-submit" class="button-primary" value="<?php _e('Save Changes', 'twitter-avatar-reloaded') ?>" />
					</p>
				</form>
			</div>
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
        global $comment;

        if (!$comment || !property_exists($comment, 'comment_ID')) {
          return $avatar;
        }

        $comment_author_twitter = '';
        if ($comment && property_exists($comment, 'user_id') && $comment->user_id) {
            $comment_author_twitter = get_user_meta($comment->user_id, 'twitter', true);
        } else {
            $comment_author_twitter = get_comment_meta(get_comment_ID(), 'comment_author_twitter', true);
        }

        $comment_author_twitter = str_ireplace('http://twitter.com/', '', strtolower($comment_author_twitter));

        if ($comment_author_twitter != '') { // Try to get twitter avatar only if comment author twitter is not null
			$image_url = get_comment_author_twitter_profile_image(get_comment_ID(), TRUE);
        } else {
            $image_url = '';
        }

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
            $input['field-label'] = __('Twitter', 'twitter-avatar-reloaded');
        }

        $input['field-class'] = esc_attr($input['field-class']);
        if ($input['field-class'] == '') {
            $input['field-class'] = 'comment-form-twitter';
        }

		//TODO: validate the html input field as well
        return $input;
    }

    /**
     * Print global section text
     */
    function  tar_global_section_text() {
		// Empty as of now
    }

	/**
	 * Callback for printing Feild class Setting
	 *
	 * @return void
	 * @author Sudar
	 */
    function tar_field_class_callback() {
        $options = get_option('twitter-avatar-reloaded-options');
        echo "<input id='field-class' name='twitter-avatar-reloaded-options[field-class]' size='40' type='text' value='{$options['field-class']}' ><br>";
		_e('By default <code>comment-form-twitter</code> will be used', 'twitter-avatar-reloaded');
    }

    /**
     * Callback for printing Field label Setting
     */
    function tar_field_label_callback() {
        $options = get_option('twitter-avatar-reloaded-options');
        echo "<input id='field-label' name='twitter-avatar-reloaded-options[field-label]' size='40' type='text' value='{$options['field-label']}' ><br>";
		_e('By default <code>Twitter</code> will be used', 'twitter-avatar-reloaded');
    }

	/**
	 * Callback for printing the field html setting
	 *
	 * @return void
	 * @author Sudar
	 */
    function tar_field_html_callback() {
        $options = get_option('twitter-avatar-reloaded-options');
        echo "<textarea id='field-html' name='twitter-avatar-reloaded-options[field-html]' cols='40' >{$options['field-html']}</textarea> <br>";
		_e('By default the html for the website field will be cloned.', 'twitter-avatar-reloaded');
    }

    function tar_legacy_theme_callback() {
        $options = get_option('twitter-avatar-reloaded-options');
        echo "<input id='legacy-support' name='twitter-avatar-reloaded-options[legacy-support]' type='checkbox' value = '1' " . checked($options['legacy-support'], 1, FALSE) . "> ", __('Enable support for legacy themes', 'twitter-avatar-reloaded'), "<br>";
		_e("You don't need it if your theme supports the new comment_form hook", 'twitter-avatar-reloaded');
	}
}

// Start this plugin once all other plugins are fully loaded
add_action( 'init', 'TwitterAvatarReloaded' ); function TwitterAvatarReloaded() { global $TwitterAvatarReloaded; $TwitterAvatarReloaded = new TwitterAvatarReloaded(); }

// ---------------------------Template functions ----------------------------------------------------------

/**
 * Get the Twitter id of the comment author
 *
 * @param <int> $comment_ID - ID of the comment - Optional
 * @return <string> - Comment author Twitter id
 */
if (!function_exists('get_comment_author_twitter_id')) {
	function get_comment_author_twitter_id( $comment_ID = 0 ) {
		$comment = get_comment( $comment_ID );

		if ($comment->user_id) {
			$user_profile = get_userdata($comment->user_id);
			$comment_author_twitter = $user_profile->twitter;
		} else {
			$comment_author_twitter = get_comment_meta( $comment->comment_ID, 'comment_author_twitter', true);
		}

		$comment_author_twitter = str_ireplace('http://twitter.com/', '', $comment_author_twitter);

		return apply_filters( 'get_comment_author_twitter_id', $comment_author_twitter, $comment );
	}
}

/**
 * Print the Twitter id of the comment author
 *
 * @param <int> $comment_ID - ID of the comment - Optional
 */
if (!function_exists('comment_author_twitter_id')) {
	function comment_author_twitter_id( $comment_ID = 0 ) {
		$comment = get_comment( $comment_ID );
		echo apply_filters('comment_author_twitter_id', get_comment_author_twitter_id($comment_ID), $comment);
	}
}

/**
 * Get the Twitter profile url of the comment author
 *
 * @param <int> $comment_ID - ID of the comment - Optional
 * @return <string> - Comment author Twitter profile url
 */
if (!function_exists('get_comment_author_twitter_url')) {
	function get_comment_author_twitter_url( $comment_ID = 0 ) {
		$comment = get_comment( $comment_ID );
		$comment_author_twitter_url = 'http://twitter.com/' . get_comment_author_twitter_id($comment_ID);
		return apply_filters( 'get_comment_author_twitter_url', $comment_author_twitter_url, $comment );
	}
}

/**
 * Print the Twitter url of the comment author
 *
 * @param <int> $comment_ID - ID of the comment - Optional
 */
if (!function_exists('comment_author_twitter_url')) {
	function comment_author_twitter_url( $comment_ID = 0 ) {
		$comment = get_comment( $comment_ID );
		echo apply_filters('comment_author_twitter_url', get_comment_author_twitter_url($comment_ID), $comment );
	}
}

/**
 * Returns the twitter profile image url of the comment author
 *
 * @param <int> $comment_ID - ID of the comment - Optional
 * @param <bool> store - Whether to store the profile image url in comment meta - Optional - Default: FALSE
 *
 * @return <url> twitter profile image ulr of a the author of a comment
 * @author Sudar
 */
if (!function_exists('get_comment_author_twitter_profile_image')) {
	function get_comment_author_twitter_profile_image( $comment_ID = 0 , $store = FALSE) {
		$comment = get_comment( $comment_ID );
		$comment_author_twitter_profile_image = get_comment_meta($comment->comment_ID, 'comment_author_twitter_profile_image', TRUE);

		if ($comment_author_twitter_profile_image == '') {
			$comment_author_twitter_profile_image = get_twitter_profile_image(get_comment_author_twitter_id($comment_ID));
			if ($store && $comment_author_twitter_profile_image != '') {
				update_comment_meta($comment_ID, 'comment_author_twitter_profile_image', $comment_author_twitter_profile_image);
			}
		}

		return apply_filters( 'get_comment_author_twitter_profile_image', $comment_author_twitter_profile_image, $comment );
	}
}

/**
 * print the twitter profile image url of the author of a comment
 *
 * @param <int> $comment_ID - ID of the comment - Optional
 * @return void
 * @author Sudar
 */
if (!function_exists('comment_author_twitter_profile_image')) {
	function comment_author_twitter_profile_image( $comment_ID = 0 ) {
		$comment = get_comment( $comment_ID );
		echo apply_filters('comment_author_twitter_profile_image', get_comment_author_twitter_profile_image($comment_ID), $comment );
	}
}

/**
 * Get the twitter profile image of a user using twitter id
 *
 * @param <string> $twitter_id - twitter id of the user
 * @return <url> Twitter profile image url
 * @author Sudar
 */
if (!function_exists('get_twitter_profile_image')) {
	function get_twitter_profile_image( $twitter_id ) {
		if ( $twitter_id ) {
			return Twitter_Profile_Image::get_profile_image($twitter_id);
		}

		return '';
	}
}
?>
