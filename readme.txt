=== kPicasa Gallery ===
Tags: picasa, gallery, photo
Requires at least: 2.2
Tested up to: 3.2.1
Stable tag: trunk

Display your Picasa Web Galleries in a post or in a page.

== Description ==

**ATTENTION: because of licencing issues, Highslide has been removed from kPicasa
Gallery. I apologize to everyone for the inconvenience.**

This plugin displays your Picasa Web Galleries in a post or in a page simply by
creating a post or a page with a special keyword. All the images are kept on the
Picasa Web Gallery server.

**Please note that PHP5 is required.**

If enabled and properly configured, kPicasa Gallery will use the WP-Cache
mechanism.

You have to choice of using either Lightbox, Slimbox, Thickbox or
Fancybox for displaying the large version of your photos.

Slimbox support playing videos directly from your site. Selecting another
engine will open a new window to the original Picasa URL.

== Installation ==

1. Unzip the archive to your `wp-content/plugins/` folder.
2. Activate the plugin through the `Plugins` menu in WordPress
3. Go in the `Options` menu, select `kPicasa Gallery` and set your parameters.
4. Create post or a page and write `KPICASA_GALLERY` **on its own line**.
5. Browse to that post or page, voilà!

== Advanced usage ==

= Show specific albums =
Browse to the [Picasa web site](http://picasaweb.google.com/) and log yourself
in. You will then need to find the internal album name, as explained below.

For an album named "Trip to Europe", the URL of that album will probably look
like this: `http://picasaweb.google.com/YourPicasaUsername/TripToEurope`.
The internal album name is the last portion (`TripToEurope`). You will need to
find the internal album name for every album you want to display.

You will then need to call kPicasa Gallery like this (this will show 3 specific albums):
`KPICASA_GALLERY(TripToEurope, TripToAsia, TripToAustralia)`

= Show a private album =
Browse to the [Picasa web site](http://picasaweb.google.com/) and log yourself
in. You will then need to find the internal album name and its `authkey`, as
explained below.

Let's say the URL to your private album is: `http://picasaweb.google.com/ghebert/TripToEurope?authkey=Gv1sRgCILA9ebdxLyZaQ`.
The `authkey` is the last portion of the URL and is always 20 characters long.
Be careful not to include the # character that sometimes appear at the end of that URL.

You would have to use the following syntax:
`KPICASA_GALLERY(TripToEurope#Gv1sRgCILA9ebdxLyZaQ)`

= Show albums from an alternative Picasa account =
**Please note that you can't combine multiple accounts into the same post or page.**

If you have more than one Picasa account, you can show the albums from another
account by calling kPicasa Gallery like this:
`KPICASA_GALLERY(username:YourOtherUsername)`