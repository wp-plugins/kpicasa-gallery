<?php
/*
Plugin Name: kPicasa Gallery
Plugin URI: http://www.boloxe.com/techblog/
Description: Display your Picasa Web Galleries in a post or in a page.
Version: 0.2.3
Author: Guillaume Hébert

Version History
---------------------------------------------------------------------------
2007-07-14	0.0.1		First release
2007-08-23	0.0.2		Bug fix (conflicted with TinyMCE)
2007-12-07	0.0.3		More robust error handling (no new features)
2007-12-13	0.0.4		If allow_url_fopen is not enabled, will now try cURL
2008-01-05	0.0.5		Added UTF-8 support, hopefully better detection of PHP4
						(instead of crashing), optional pagination, and optional
						selection of specific albums to be displayed.
2007-01-12	0.1.0		Changed the way kPicasa is called: created an
						configuration page in "Admin -> Options" where the
						parameters are set, hoping it is less confusing for new
						users. Corrected a bug that affected those who chose to
						display their album in a post (instead of a page). Added
						the option to use Lightbox or Highslide.
2007-01-25	0.1.1		Fixed the error messages. Fixed a pagination bug. Moved
						the inline styles in favor of a CSS file. Commented the
						code. Added the option to use no full-size picture
						engine (for those running their own). Added album
						descriptions. This fixes almost all the requests I
						received.
2007-01-25	0.1.2		Fixed a bug where kPicasa was sending content and
						breaking PHP redirections.
2007-01-25	0.1.3		Improved error messages. Easier to visually customize
						via a CSS file, instead of inline styling. Ability to
						display galleries from more than one Picasa account,
						each in a different page/post. New options to change the
						number of albums to display per line, and the number of
						pictures per line. Now displays the album description if
						it exists. It is now possible to not use any engine to
						display the full-sized picture.
2007-02-13	0.1.4		Improved the routine to connect to Picasa. Some users
						reported errors since 0.1.0.
2007-07-21	0.1.5		Compatible with Wordpress 2.6.0. Updated the look and
						feel of the configuration page.
2008-04-04	0.1.6		Compatible with Wordpress 2.7.x. Can now show individual
						unlisted albums. Can now select thumbnail sizes. Text
						can now be typed around the album.
2009-04-05	0.1.7		Fixed a nasty bug from 0.1.6
2009-04-06	0.1.8		Refreshed Javascript libraries
2009-04-07	0.1.9		Added Slimbox, Thickbox, fixed a problem with Highslide.
						Also fixed a bug where a selecting anything but a 144px
						picture thumbnail would make a picture of the wrong size
						appear in the javascript popup
2009-09-01	0.2.0		Added video support. Integrated with Highslide. Other
						engines are opening a new window to the Picasa
						original URL
2009-09-16	0.2.1		Maintenance release. Fixed a CSS annoyance. Now using
						WP_PLUGIN_URL if it's defined (WP 2.6+)
2009-10-06	0.2.2		In an attempt to resolve a conflict with the "Google
						Analytics for WordPress" plugin, kPicasa is now loaded
						earlier (priority 9 instead of 10). Added dirname()
						to the kpg.class.php require_once() call.
2009-11-10	0.2.3		Fixed a HTML validation problem. The date can now be
						shown, but it's hidden by default in the CSS file. Now
						including CSS files with wp_enqueue_style(). Fixed a bug
						where my version of Thickbox was always called. Now
						calling SWFObject with the standard mechanism. Moved the
						configuration sub-menu under the Plugins menu. Made
						the plugin ready to be translated ( _e() and __() ).
						Enabled embedded videos for Thickbox. Added Shadowbox.
						Added slideshow to Highslide.

Todo
---------------------------------------------------------------------------
- Multiple private albums, can it be done without too much trouble?

Licence
---------------------------------------------------------------------------
    Copyright 2007, 2008, 2009  Guillaume Hébert (email : kag@boloxe.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( version_compare(PHP_VERSION, '5.0.0', '<') )
{
	print 'kPicasa Gallery requires PHP version 5 or greater. You are running PHP version '.PHP_VERSION;
	exit;
}
if ( !function_exists('add_action') || !function_exists('add_action')
  || !function_exists('add_filter') || !function_exists('wp_enqueue_script')
  || !function_exists('wp_enqueue_script') )
{
	print '<strong>'.__('Error', 'kpicasa_gallery').':</strong> '.__('Error: Your WordPress installation is missing some required functions. Please upgrade your WordPress installation.', 'kpicasa_gallery');
	exit;
}

if ( !defined('KPICASA_GALLERY_DIR') )
{
	if ( defined('WP_PLUGIN_URL') )
	{
		define('KPICASA_GALLERY_DIR', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)));
	}
	else
	{
		define('KPICASA_GALLERY_DIR', get_bloginfo('wpurl').'/wp-content/plugins/'.dirname(plugin_basename(__FILE__)));
	}
}
if ( !defined('KPICASA_GALLERY_FILTER_PRIORITY') )
{
	define('KPICASA_GALLERY_FILTER_PRIORITY', 9);
}
if ( !defined('KPICASA_GALLERY_VERSION') )
{
	define('KPICASA_GALLERY_VERSION', '0.2.3');
}

$kpg_picEngine = get_option( 'kpg_picEngine' );

if ( !is_admin() )
{
	add_action('wp_head', 'initKPicasaGallery');
	add_filter('the_content', 'loadKPicasaGallery', KPICASA_GALLERY_FILTER_PRIORITY);

	wp_enqueue_style('kpicasa', KPICASA_GALLERY_DIR.'/kpicasa_gallery.css', false, KPICASA_GALLERY_VERSION, 'screen');

	if ( $kpg_picEngine == 'highslide' )
	{

		$highslide_version = '4.1.8';
		wp_enqueue_script('highslide', KPICASA_GALLERY_DIR.'/highslide/highslide.js', array('swfobject'), $highslide_version);
		wp_enqueue_style('highslide', KPICASA_GALLERY_DIR.'/highslide/highslide.css', false, $highslide_version, 'screen');

		// really it should be "if < IE7", but I'm too lazy
		// Based on: http://www.useragentstring.com/pages/Internet%20Explorer/
		if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.') !== false )
		{
			wp_enqueue_style('highslide-ie6', KPICASA_GALLERY_DIR.'/highslide/highslide-styles-ie6.css', false, $highslide_version, 'screen');
		}
	}
	elseif ( $kpg_picEngine == 'lightbox' )
	{
		$lightbox_version = '2.04';
		wp_enqueue_script('lightbox2', KPICASA_GALLERY_DIR.'/lightbox2/js/lightbox.js', array('prototype', 'scriptaculous-effects', 'scriptaculous-builder'), $lightbox_version);
		wp_enqueue_style('lightbox2', KPICASA_GALLERY_DIR.'/lightbox2/css/lightbox.css', false, $lightbox_version, 'screen');
	}
	elseif ( $kpg_picEngine == 'slimbox2' )
	{
		$slimbox2_version = '2.02';
		wp_enqueue_script('slimbox2', KPICASA_GALLERY_DIR.'/slimbox2/js/slimbox2.js', array('jquery'), $slimbox2_version);
		wp_enqueue_style('slimbox2', KPICASA_GALLERY_DIR.'/slimbox2/css/slimbox2.css', false, $slimbox2_version, 'screen');
	}
	elseif ( $kpg_picEngine == 'thickbox' )
	{
		wp_enqueue_script('thickbox');
		wp_enqueue_style('thickbox', get_bloginfo('wpurl').'/wp-includes/js/thickbox/thickbox.css', false, false, 'screen');
	}
	elseif ( $kpg_picEngine == 'shadowbox' )
	{
		$shadowbox_version = '3.0rc1';
		wp_enqueue_script('shadowbox', KPICASA_GALLERY_DIR.'/shadowbox/shadowbox.js', array('jquery', 'swfobject'), $shadowbox_version);
		wp_enqueue_style('shadowbox', KPICASA_GALLERY_DIR.'/shadowbox/shadowbox.css', false, $shadowbox_version, 'screen');
	}
}
else
{
	add_action('admin_menu', 'adminKPicasaGallery');
}

function initKPicasaGallery()
{
	global $kpg_picEngine;

	if ( $kpg_picEngine == 'highslide' )
	{
		$picEngineDir = KPICASA_GALLERY_DIR.'/highslide';

		print "<script type='text/javascript'>\n";
		print "	hs.graphicsDir = '$picEngineDir/graphics/';\n";
		print "	hs.align       = 'center';\n";
		print "	hs.transitions = ['expand', 'crossfade'];\n";
		print "	hs.outlineType = 'rounded-white';\n";
		print "	hs.showCredits = false;\n";
		print "	hs.fadeInOut   = true;\n";
		print "	hs.addSlideshow({ interval: 5000, repeat: false, useControls: true, fixedControls: 'fit', overlayOptions: { opacity: .75, position: 'bottom center', hideOnMouseOut: true } });\n";
		print "</script>\n";
	}
	elseif ( $kpg_picEngine == 'lightbox' )
	{
		$picEngineDir = KPICASA_GALLERY_DIR.'/lightbox2';

		print "<script type='text/javascript'>\n";
		print "	LightboxOptions.fileLoadingImage        = '$picEngineDir/images/loading.gif';\n";
		print "	LightboxOptions.fileBottomNavCloseImage = '$picEngineDir/images/closelabel.gif';\n";
		print "</script>\n";
	}
	elseif ( $kpg_picEngine == 'shadowbox' )
	{
		$picEngineDir = KPICASA_GALLERY_DIR.'/shadowbox';

		print "<script type='text/javascript'>\n";
		print "	Shadowbox.init();\n";
		print "</script>\n";
	}
}

function loadKPicasaGallery ( $content = '' )
{
	$tmp = strip_tags(trim($content));
	//$regex = '/^KPICASA_GALLERY[\s]*(\(.*\))?$/';
	$regex = '/^[\s]*KPICASA_GALLERY[\s]*(\(.*\))?[\s]*$/m';

	if ( preg_match($regex, $tmp, $matches) )
	{
		$showOnlyAlbums = array();
		$username       = null;

		if ( isset($matches[1]) )
		{
			$args = explode(',', substr( substr($matches[1], 0, strlen($matches[1])-1), 1 ));
			if ( count($args) > 0 )
			{
				foreach( $args as $value )
				{
					$value = str_replace(' ', '', $value);
					if ($username == null && 'username:' == substr($value, 0, 9) && strlen($value) > 9)
					{
						$username = substr($value, 9);
					}
					else
					{
						$showOnlyAlbums[] = $value;
					}
				}
			}
		}

		require_once(dirname(__FILE__).'/kpg.class.php');

		ob_start();
		$gallery = new KPicasaGallery($username, $showOnlyAlbums);
		$buffer  = ob_get_clean();
		return str_replace($matches[0], $buffer, $content);
	}

	return $content;
}

function adminKPicasaGallery()
{
	if ( !function_exists('add_submenu_page') )
	{
		print '<strong>'.__('Error', 'kpicasa_gallery').':</strong> '.__('Error: Your WordPress installation is missing some required functions. Please upgrade your WordPress installation.', 'kpicasa_gallery');
		exit;
	}

	add_submenu_page('plugins.php', 'kPicasa Gallery Plugin Options', 'kPicasa Gallery', 'manage_options', dirname(__FILE__).'/param.php');
}

?>
