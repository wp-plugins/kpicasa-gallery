=== kPicasa Gallery ===
Tags: picasa, gallery, photo
Requires at least: 2.2
Tested up to: 2.7.1
Stable tag: 0.1.6

Display your Picasa Web Galleries in a post or in a page.

== Description ==

This plugin displays your Picasa Web Galleries in a post or in a page simply by
creating a post or a page with a special keyword. All the images are kept on the
Picasa Web Gallery server.

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

== Show a single ablum ==
Optionally, you can decide to only display specific albums. For this, you
will have to go to the Picasa Web Albums and log into your account. You will
then need to find the internal album name, as explained below.

As an example, for an album named "Trip to Europe", the URL of that album
will probably look like this: `http://picasaweb.google.com/YourPicasaUsername/TripToEurope`.
The internal album name is the last portion (`TripToEurope`). You will need to
find the internal album name for every album you want to display.

You will then need to call kPicasa Gallery like this (this will show 3 specific albums):
`KPICASA_GALLERY(TripToEurope, TripToAsia, TripToAustralia)`

== Show a single private album ==
You can also list a private album. For this, you will need its `authkey`.
You will find it in the URL of your private album on the Picasa Web Albums website.

If the URL to your private album is: `http://picasaweb.google.com/ghebert/TripToEurope?authkey=Gv1sRgCILA9ebdxLyZaQ`.
You can see the authkey (20 characters long) is the last portion of the URL.
Be careful not to include the # character that sometimes appear at the end of the URL.

Then you will have to use the following syntax:
`KPICASA_GALLERY(TripToEurope#Gv1sRgCILA9ebdxLyZaQ)`

**Please note that this feature only works for single albums.**