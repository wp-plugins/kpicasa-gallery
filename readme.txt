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

**Please note that as of 0.1.0, the call to kPicasa Gallery has changed. You
must go in Admin / Options / kPicasa Gallery and set your username there. So the
new way to call kPicasa Gallery is now simply `KPICASA_GALLERY` (without any
parenthesis). See the installation and advanced usage for more information**

**Please note that PHP5 is required.**

If enabled and properly configured, kPicasa Gallery will use the WP-Cache 
mechanism.

You have to choice of using either Lightbox or Highslide for displaying the
large version of your photos.

== Installation ==

1. Unzip the archive to your `wp-content/plugins/` folder.
2. Activate the plugin through the `Plugins` menu in WordPress
3. Go in the `Options` menu and select `kPicasa Gallery`. Set your parameters.
4. Create post or a page with `KPICASA_GALLERY` as the only content.
5. Browse to that post or page, voilà!

== Advanced usage ==

Optionally, you can decide to only display specific albums. For this, you 
will have to go to the Picasa Web Albums and log into your account. You will 
then need to find the internal album name, as explained below.

As an example, for an album named "Trip to Europe", the URL of that album
will probably look like this: `http://picasaweb.google.com/YourPicasaUsername/TripToEurope`.
The internal album name is the last portion (`TripToEurope`). You will need to 
find the internal album name for every album you want to display.

You will then need to call kPicasa Gallery like this (this will show 3 specific albums):
`KPICASA_GALLERY(TripToEurope, TripToAsia, TripToAustralia)`

== Frequently Asked Questions ==

= Parse error: syntax error, unexpected T_STRING, expecting T_OLD_FUNCTION or T_FUNCTION or T_VAR or '}' =

If you are getting that error, make sure your server is running PHP 5. This plugin uses features only available since PHP5, it will not work with PHP 4.
