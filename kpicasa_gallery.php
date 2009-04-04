<?php
/*
Plugin Name: kPicasa Gallery
Plugin URI: http://www.boloxe.com/techblog/
Description: Display your Picasa Web Galleries in a post or in a page.
Version: 0.1.6
Author: Guillaume Hébert
Author URI: http://www.boloxe.com/techblog/

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

TODO
---------------------------------------------------------------------------
- Find out more about the following format:
  http://groups.google.com/group/Google-Picasa-Data-API/browse_thread/thread/22ba3936e4edbacf#msg_d2c3e29af488a09b


Licence
---------------------------------------------------------------------------
    Copyright 2007, 2008  Guillaume Hébert  (email : kag@boloxe.com)

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
if ( !defined(KPICASA_GALLERY_DIR) )
{
	define('KPICASA_GALLERY_DIR', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)));
}

$kpg_picEngine = get_option( 'kpg_picEngine' );

if ( function_exists('is_admin') )
{
	if ( !is_admin() )
	{
		if ( function_exists('add_action') )
		{
			add_action('wp_head', 'initKPicasaGallery');
		}
		if ( function_exists('add_filter') )
		{
			add_filter('the_content', 'loadKPicasaGallery');
		}

		if ( function_exists('wp_enqueue_script') )
		{
			if ( $kpg_picEngine == 'lightbox' )
			{
				wp_enqueue_script('lightbox2', KPICASA_GALLERY_DIR.'/lightbox2/js/lightbox.js', array('prototype', 'scriptaculous-effects'), '2.04');
			}
			elseif ( $kpg_picEngine == 'highslide' )
			{
				wp_enqueue_script('highslide', KPICASA_GALLERY_DIR.'/highslide/highslide.js', array(), '3.3.22');
			}
		}
	}
	else
	{
		if ( function_exists('add_action') )
		{
			add_action('admin_menu', 'adminKPicasaGallery');
		}
	}
}

function initKPicasaGallery()
{
	global $kpg_picEngine;
	$baseDir = get_bloginfo('wpurl').KPICASA_GALLERY_DIR;

	print "<link rel='stylesheet' href='$baseDir/kpicasa_gallery.css' type='text/css' media='screen' />";

	if ( $kpg_picEngine == 'lightbox' )
	{
		$lightboxDir = "$baseDir/lightbox2";
		print "<link rel='stylesheet' href='$lightboxDir/css/lightbox.css' type='text/css' media='screen' />";

		print '<script type="text/javascript">';
		print "	fileLoadingImage = '$lightboxDir/images/loading.gif';";
		print "	fileBottomNavCloseImage = '$lightboxDir/images/closelabel.gif';";
		print '</script>';
	}
	elseif ( $kpg_picEngine == 'highslide' )
	{
		$highslideDir = "$baseDir/highslide";
		print '<script type="text/javascript">';
		print "	hs.graphicsDir = '$highslideDir/graphics/';";
		print "	hs.showCredits = false;";
		print "	hs.outlineType = 'rounded-white';";
		print "if (hs.registerOverlay) {";
			print "	hs.registerOverlay( { thumbnailId: null, overlayId: 'controlbar', position: 'top right', hideOnMouseOut: true } );";
		print "}";
		print '</script>';
		print "<link rel='stylesheet' href='$highslideDir/highslide.css' type='text/css' media='screen' />";
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

		require_once('kpg.class.php');

		ob_start();
		$gallery = new KPicasaGallery($username, $showOnlyAlbums);
		$buffer  = ob_get_clean();
		return str_replace($matches[0], $buffer, $content);
	}

	return $content;
}

function adminKPicasaGallery()
{
	if ( function_exists('add_options_page') )
	{
		add_options_page('kPicasa Gallery Plugin Options', 'kPicasa Gallery', 8, dirname(__FILE__).'/param.php');
	}
}

?>
