=== kPicasa Gallery ===
Tags: picasa, gallery, photo
Requires at least: 2.2
Tested up to: 2.3.1
Stable tag: trunk

Display your Picasa Web Galleries in a post or in a page.

== Description ==

This plugin displays your Picasa Web Galleries in a post or in a page simply by
creating a post or a page with a special keyword. All the images are kept on the
Picasa Web Gallery server.

**Please note that PHP5 is required.**

If enabled and properly configured, kPicasa Gallery will use the WP-Cache mechanism.

This plugin uses Lightbox and requires that prototype and scriptaculous are already installed.
WordPress 2.2 and up come with both libraries already installed.

== Installation ==

1. Unzip the archive to your `wp-content/plugins/` folder.
1. Activate the plugin through the `Plugins` menu in WordPress
1. Create post or a page with `KPICASA_GALLERY(YourPicasaUsername)` as the only content.
   For example, if your Picasa username is john123, you would create a post or page with `KPICASA_GALLERY(john123)`
1. Browse to that post or page, voilà!

== Frequently Asked Questions ==

= Parse error: syntax error, unexpected T_STRING, expecting T_OLD_FUNCTION or T_FUNCTION or T_VAR or '}' =

If you are getting that error, make sure your server is running PHP 5. This plugin uses features only available since PHP5, it will not work with PHP 4.
