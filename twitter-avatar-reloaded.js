/**
 * Properly integrate the twitter field into the comment form.
 * This will move the field below the Website field
 *
 * @credits http://wordpress.org/extend/plugins/openid/
 *
 */

jQuery(document).ready(function () {
	jQuery('label[for="url"]').parent('p').after(jQuery('#ta_twitter'));
});
