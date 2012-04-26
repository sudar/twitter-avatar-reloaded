=== Twitter Avatar Reloaded ===
Contributors: sudar 
Tags: twitter, gravatar, avatar
Requires at least: 2.9
Donate Link: http://sudarmuthu.com/if-you-wanna-thank-me
Tested up to: 3.3.2
Stable tag: 1.3

Stores Twitter username together with comments and replaces gravatar with twitter avatar.

== Description ==

Twitter avatar reloaded Plugin adds a new field to the comment form to get the user's Twitter usrename and stores it together with comments and using it replaces gravatar with twitter avatar when the comment is displayed.
This Plugin works seamlessly and you don't need to edit your theme files to add the new field to the comment form. It automatically adds it when activated.

### Template functions

This Plugin provides 4 template functions which you can use in your theme to customize the way the comment author's twitter id/profile should be displayed.

*   get_comment_author_twitter_id($comment_id) - Get the Twitter id of the comment author
*   comment_author_twitter_id($comment_id) - Print the Twitter id of the comment author
*   get_comment_author_twitter_url($comment_id) - Get the Twitter profile url of the comment author
*   comment_author_twitter_url($comment_id) - Print the Twitter url of the comment author
*   get_comment_author_twitter_profile_image($comment_id) - Get the twitter profile image url of the comment author 
*   comment_author_twitter_profile_image($comment_id) - Print the twitter profile image url of the comment author 
*   get_twitter_profile_image($twitter_username) - Get the twitter profile image of a user using twitter id

### Translation

*   Hebrew (Thanks Sagive)
*   Dutch (Thanks Rene of [WordPress WPwebshop][4])
*   Brazilian Portuguese (Thanks Marcelo of [Criacao de Sites em Ribeirao Preto][5])
*   German (Thanks Jenny Beelens of [professionaltranslation.com][7])
*   Spanish (Thanks Brian Flores of [InMotion Hosting][8])   
*   Bulgarian (Thanks Nikolay Nikolov of [Health Blog][9])   
*   Lithuanian (Thanks  Vincent G , from [http://www.host1free.com][10])

The pot file is available with the Plugin. If you are willing to do translation for the Plugin, use the pot file to create the .po files for your language and let me know. I will add it to the Plugin after giving credit to you.

### Support

Support for the Plugin is available from the [Plugin's home page][1]. If you have any questions or suggestions, do leave a comment there or contact me in [twitter][6].

### Links

*   [Plugin home page][1]
*   [Author's Blog][2]
*   [Other Plugins by the author][3]

[1]: http://sudarmuthu.com/wordpress/twitter-avatar-reloaded
[2]: http://sudarmuthu.com/blog
[3]: http://sudarmuthu.com/wordpress/
[4]: http://wpwebshop.com/premium-wordpress-plugins/
[5]: http://www.techload.com.br/
[6]: http://twitter.com/sudarmuthu
[7]: http://www.professionaltranslation.com
[8]: http://www.inmotionhosting.com/
[9]: http://healthishblog.com/ 
[10]: http://www.host1free.com

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

== Screenshots ==

1. Comment form with the new Twitter Field

== Changelog ==

###v0.1 (2010-03-13)

*   first version

###v0.2 (2010-03-16)

*   Proper alignment of the Twitter Username field

###v0.3 (2010-03-20)

*   Added translation for Hebrew (Thanks Sagive)

###v0.4 (2010-08-09)

*   Removed JavaScript from unnecessary pages.

###v0.5 (2010-08-10)

*   Added support for registered users
*   Ability to configure Twitter field label.

###v0.6 (2011-02-05)

*   Added Dutch translations
*   Added Brazilian Portuguese translations

###v0.7 (2011-05-11)

*   Added template functions to display Comment author twitter id and profile url

###v0.8 (2011-05-22)

*   Added German translations

###v1.0 (2011-09-11)
*   Using transient api for storing cache and also improve performance

###v1.1 (2011-11-13)
*   Added French translations.

###v1.2 (2012-02-05)
*   Added Bulgarian translations.

###v1.3 (2012-04-26) (Dev time: 8 hours)

    - Rewrote the way comment field was handled.
    - Rewrote the way the Twitter profile image was retrieved.
    - Started storing the Twitter profile image url in comment meta
    - Revamped the admin UI
    - Added Lithuanian translations

==Readme Generator==

This Readme file was generated using <a href = "http://sudarmuthu.com/wordpress/wp-readme">wp-readme</a>, which generates readme files for WordPress Plugins.
