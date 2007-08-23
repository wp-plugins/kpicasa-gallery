<?php
/*
Plugin Name: kPicasa Gallery
Plugin URI: http://www.boloxe.com/kpicasa_gallery/
Description: Display your Picasa Web Galleries in a post or in a page.
Version: 0.0.1
Author: Guillaume Hébert
Author URI: http://www.boloxe.com/

Version History
---------------------------------------------------------------------------
2007-07-14	0.0.1		First release

TODO
---------------------------------------------------------------------------
Find out more about the following format:
  http://groups.google.com/group/Google-Picasa-Data-API/browse_thread/thread/22ba3936e4edbacf#msg_d2c3e29af488a09b

*/

/*  Copyright 2007  Guillaume Hébert  (email : kag@boloxe.com)

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

if ( !defined(KPICASA_GALLERY_DIR) ) {
	define('KPICASA_GALLERY_DIR', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)));
}

if ( !class_exists('KPicasaGallery') ) {
	class KPicasaGallery {
		private $username;

		function __construct($username) {
			$this->username = $username;
			$this->cacheTimeout = 60 * 60 * 24;

			if ( isset($_GET['photo']) && strlen($_GET['photo']) ) {
				$this->showPhoto($_GET['photo']);
			} elseif ( isset($_GET['album']) && strlen($_GET['album']) ) {
				$this->showAlbum($_GET['album']);
			} else {
				$this->showGallery();
			}
		}

		public function showGallery() {
			$data = wp_cache_get('kPicasaGallery', 'kPicasaGallery');
			if ( false == $data ) {
				$url  = "http://picasaweb.google.com/data/feed/api/user/".urlencode($this->username)."?kind=album";
				$data = file_get_contents($url);
				$data = str_replace('gphoto:', 'gphoto_', $data);
				$data = str_replace('media:', 'media_', $data);
				wp_cache_set('kPicasaGallery', $data, 'kPicasaGallery', $this->cacheTimeout);
			}
			$xml = simplexml_load_string($data);

			print '<br />';
			foreach( $xml->entry as $album ) {
				$name      = (string) $album->gphoto_name;
				$title     = htmlentities(utf8_decode( (string) $album->title ));
				$location  = htmlentities(utf8_decode( (string) $album->gphoto_location ));
				$nbPhotos  = (string) $album->gphoto_numphotos;
				$albumURL  = add_query_arg('album', $name);
				$thumbURL  = (string) $album->media_group->media_thumbnail['url'];
				$thumbH    = (string) $album->media_group->media_thumbnail['height'];
				$thumbW    = (string) $album->media_group->media_thumbnail['width'];

				print "<p><a href='$albumURL'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='".str_replace("'", "&#39;", $title)."' align='left' style='border: solid 1px black; margin-right: 10px;' /></a>";
				print "<a href='$albumURL' style='font-weight: bold;'>$title</a><br />";
				if ( strlen($location) ) {
					print "$location<br />";
				}
				print "<br />$nbPhotos photo".($nbPhotos > 1 ? 's' : '').'<br /></p>';
				print '<div style="clear: both;" />&nbsp;</div>';
			}
		}

		public function showAlbum($album) {
			$backURL = remove_query_arg('album');
			print "<a href='$backURL'>&laquo; Back</a><br /><br />";

			$data = wp_cache_get('kPicasaGallery_'.$album, 'kPicasaGallery');
			if ( false == $data ) {
				$url = "http://picasaweb.google.com/data/feed/api/user/".urlencode($this->username)."/album/".urlencode($album)."?kind=photo";
				$data = file_get_contents($url);
				$data = str_replace('gphoto:', 'gphoto_', $data);
				$data = str_replace('media:', 'media_', $data);
				wp_cache_set('kPicasaGallery_'.$album, $data, 'kPicasaGallery', $this->cacheTimeout);
			}
			$xml = simplexml_load_string($data);

			$albumTitle    = htmlentities(utf8_decode( (string) $xml->title ));
			$albumLocation = htmlentities(utf8_decode( (string) $xml->gphoto_location ));
			$albumNbPhotos = (string) $xml->gphoto_numphotos;

			print '<div style="padding: 10px; background-color: #FFFFE1; border: solid 1px #CECF8E;">';
			print "<strong>$albumTitle</strong><br />";
			if ( strlen($albumLocation) ) {
				print "$albumLocation<br />";
			}
			print "$albumNbPhotos photo".($albumNbPhotos > 1 ? 's' : '').'<br /></div><br /><br />';

			$i = 0;
			foreach( $xml->entry as $photo ) {
				$summary  = htmlentities(utf8_decode( (string) $photo->summary ));
				$thumbURL = (string) $photo->media_group->media_thumbnail[1]['url'];
				$thumbH   = (string) $photo->media_group->media_thumbnail[1]['height'];
				$thumbW   = (string) $photo->media_group->media_thumbnail[1]['width'];
				$fullURL  = str_replace('s144', 's800', $thumbURL);

				print '<div style="float: left; width: 45%; text-align: center;">';
				if ( strlen($summary) ) {
					print "<a href='$fullURL' rel='lightbox[kpicasa_gallery]' title='".str_replace("'", "&#39;", $summary)."'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='".str_replace("'", "&#39;", $summary)."' style='border: solid 1px black;' /></a><br />";
					print "$summary<br />";
				} else {
					print "<a href='$fullURL' rel='lightbox[kpicasa_gallery]'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='' style='border: solid 1px black;' /></a><br />";
				}
				print '<br /></div>';

				if ( $i % 2 == 1 ) {
					print '<div style="clear: both;" />&nbsp;</div>';
				} else {
					print '<div style="float: left; width: 10%;">&nbsp;</div>';
				}
				$i++;
			}
		}
	}
}

if ( function_exists('is_admin') ) {
	if ( !is_admin() ) {
		if ( function_exists('add_action') ) {
			add_action('wp_head', 'initKPicasaGallery');
		}
		if ( function_exists('add_filter') ) {
			add_filter('the_content', 'loadKPicasaGallery');
		}
		if ( function_exists('wp_enqueue_script') ) {
			wp_enqueue_script('lightbox2', KPICASA_GALLERY_DIR.'/lightbox2/js/lightbox.js', array('prototype', 'scriptaculous-effects'), '2.03.3');
		}
	}
}

function initKPicasaGallery() {
	$lightboxDir = get_bloginfo('wpurl').KPICASA_GALLERY_DIR.'/lightbox2';
	print "<link rel='stylesheet' href='$lightboxDir/css/lightbox.css' type='text/css' media='screen' />";

	print '<script type="text/javascript">';
	print "	fileLoadingImage = '$lightboxDir/images/loading.gif';";
	print "	fileBottomNavCloseImage = '$lightboxDir/images/closelabel.gif';";
	print '</script>';
}

function loadKPicasaGallery ($content = '') {
	$tmp = strip_tags(trim($content));
	$regex = '/^KPICASA_GALLERY\((.*)\)$/';

	if ( "KPICASA_GALLERY" == substr($tmp, 0, 15) && preg_match($regex, $tmp, $matches) ) {
		ob_start();
		$gallery = new KPicasaGallery($matches[1]);
		return ob_get_clean();
	}

	return $content;
}

?>
