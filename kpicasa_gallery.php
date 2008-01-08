<?php
/*
Plugin Name: kPicasa Gallery
Plugin URI: http://www.boloxe.com/kpicasa_gallery/
Description: Display your Picasa Web Galleries in a post or in a page.
Version: 0.0.5
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

TODO
---------------------------------------------------------------------------
- Switch to Highslide <http://vikjavev.no/highslide/>
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

if ( !defined(KPICASA_GALLERY_DIR) ) {
	define('KPICASA_GALLERY_DIR', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)));
}

if ( !class_exists('KPicasaGallery') ) {
	class KPicasaGallery {
		private $username;
		private $nbAlbumsPerPage;
		private $nbPhotosPerPage;
		private $cacheTimeout;
		private $showOnlyAlbums;

		public function __construct($username, $nbAlbumsPerPage, $nbPhotosPerPage, $showOnlyAlbums) {
			$this->username        = $username;
			$this->nbAlbumsPerPage = intval( $nbAlbumsPerPage );
			$this->nbPhotosPerPage = intval( $nbPhotosPerPage );
			$this->showOnlyAlbums  = is_array( $showOnlyAlbums ) ? $showOnlyAlbums : array();
			$this->cacheTimeout    = 60 * 60 * 24;

			if ( isset($_GET['album']) && strlen($_GET['album']) ) {
				$return_code = $this->showAlbum($_GET['album']);
				if( is_wp_error($return_code) ) {
					foreach( $return_code->get_error_messages() as $message ) {
						print $message;
					}
				}
			} else {
				$return_code = $this->showGallery();
				if( is_wp_error($return_code) ) {
					foreach( $return_code->get_error_messages() as $message ) {
						print $message;
					}
				}
			}
		}
		
		public function KPicasaGallery() {
			$return_code = new WP_Error( 'kpicasa_gallery-php5-required', __("Error: it seems that you are not running PHP 5 or higher, kPicasa Gallery needs at least PHP 5 to function properly. Please ask your administrator to upgrade to PHP 5.") );
			foreach( $return_code->get_error_messages() as $message ) {
				print $message;
			}
		}

		private function showGallery() {
			$data = wp_cache_get('kPicasaGallery', 'kPicasaGallery');
			if ( false == $data ) {
				$url  = "http://picasaweb.google.com/data/feed/api/user/".urlencode($this->username)."?kind=album";
				$data = kPicasaFetch($url);
				if ($data == false) {
					return new WP_Error( 'kpicasa_gallery-cant-open-url', __("Error: your PHP configuration does not allow kPicasa Gallery to connect to Picasa Web Albums. Please ask your administrator to enable allow_url_fopen or cURL.") );
				}
				$data = str_replace('gphoto:', 'gphoto_', $data);
				$data = str_replace('media:', 'media_', $data);
				wp_cache_set('kPicasaGallery', $data, 'kPicasaGallery', $this->cacheTimeout);
			}
			$xml = simplexml_load_string($data);

			print '<br />';
			$page  = isset($_GET['kpgp']) && intval($_GET['kpgp']) > 1 ? intval($_GET['kpgp']) : 1; // kpgp = kPicasa Gallery Page
			if ($this->nbAlbumsPerPage > 0) {
				$start = ($page - 1) * $this->nbAlbumsPerPage;
				$stop  = $start + $this->nbAlbumsPerPage - 1;
			} else {
				$start = 0;
				$stop = count( $xml->entry ) - 1;
			}
			$i = 0;
			foreach( $xml->entry as $album ) {
				if ($i >= $start && $i <= $stop && 
				( !count($this->showOnlyAlbums) || in_array((string) $album->gphoto_name, $this->showOnlyAlbums) )) {
					$name      = (string) $album->gphoto_name;
					$title     = wp_specialchars( (string) $album->title );
					$location  = wp_specialchars( (string) $album->gphoto_location );
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
				$i++;
			}
			
			$nbItems = count($this->showOnlyAlbums) > 0 ? count($this->showOnlyAlbums) : count($xml->entry);
			$this->paginator( $page, 'kpgp', $this->nbAlbumsPerPage, $nbItems );
			return true;
		}

		private function showAlbum($album) {
			$backURL = remove_query_arg('album');
			$backURL = remove_query_arg('kpap', $backURL);
			print "<a href='$backURL'>&laquo; Back</a><br /><br />";

			$data = wp_cache_get('kPicasaGallery_'.$album, 'kPicasaGallery');
			if ( false == $data ) {
				$url = "http://picasaweb.google.com/data/feed/api/user/".urlencode($this->username)."/album/".urlencode($album)."?kind=photo";
				$data = kPicasaFetch($url);
				if ($data == false) {
					return new WP_Error( 'kpicasa_gallery-cant-open-url', __("Error: your PHP configuration does not allow kPicasa Gallery to connect to Picasa Web Albums. Please ask your administrator to enable allow_url_fopen or cURL.") );
				}
				$data = str_replace('gphoto:', 'gphoto_', $data);
				$data = str_replace('media:', 'media_', $data);
				wp_cache_set('kPicasaGallery_'.$album, $data, 'kPicasaGallery', $this->cacheTimeout);
			}
			$xml = simplexml_load_string($data);

			$albumTitle    = wp_specialchars( (string) $xml->title );
			$albumLocation = wp_specialchars( (string) $xml->gphoto_location );
			$albumNbPhotos = (string) $xml->gphoto_numphotos;

			print '<div style="padding: 10px; background-color: #FFFFE1; border: solid 1px #CECF8E;">';
			print "<strong>$albumTitle</strong><br />";
			if ( strlen($albumLocation) ) {
				print "$albumLocation<br />";
			}
			print "$albumNbPhotos photo".($albumNbPhotos > 1 ? 's' : '').'<br /></div><br /><br />';

			$page  = isset($_GET['kpap']) && intval($_GET['kpap']) > 1 ? intval($_GET['kpap']) : 1; // kpap = kPicasa Album Page
			if ($this->nbPhotosPerPage > 0) {
				$start = ($page - 1) * $this->nbPhotosPerPage;
				$stop  = $start + $this->nbPhotosPerPage - 1;
			} else {
				$start = 0;
				$stop = count( $xml->entry ) - 1;
			}
			$i = 0;
			$j = 0;
			foreach( $xml->entry as $photo ) {
				if ($i >= $start && $i <= $stop) {				
					$summary  = wp_specialchars( (string) $photo->summary );
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

					if ( $j % 2 == 1 ) {
						print '<div style="clear: both;" />&nbsp;</div>';
					} else {
						print '<div style="float: left; width: 10%;">&nbsp;</div>';
					}
					$j++;
				}
				$i++;
			}
			if ( $j % 2 == 1 ) {
				print '<div style="clear: both;" />&nbsp;</div>';
			}
			
			$this->paginator( $page, 'kpap', $this->nbPhotosPerPage, count($xml->entry) );
			return true;
		}
		
		private function paginator ($page, $argName, $perPage, $nbItems) {
			if ($perPage > 0) {
				$nbPage = ceil( $nbItems / $perPage );
				if ($nbPage > 1) {
					print '<p align="center"><strong>Page:&nbsp;';
					for($i = 1; $i <= $nbPage; $i++) {
						$pageUrl = add_query_arg($argName, $i);
						if ($i == $page) {
							print "&nbsp;<span style='border: solid 1px #C0C0C0; padding: 4px;'>$i</span>";
						} else {
							print "&nbsp;<a href='$pageUrl' style='border: solid 1px #F0F0F0; padding: 4px;'>$i</a>";
						}
					}
					print '</strong></p>';
				}
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
		$args = explode(',', $matches[1]);
		$username        = trim( $args[0] );
		$nbAlbumsPerPage = isset( $args[1] ) ? trim( $args[1] ) : 0;
		$nbPhotosPerPage = isset( $args[2] ) ? trim( $args[2] ) : 0;

		$showOnlyAlbums  = array();
		if ( count($args) > 3 ) {
			for ( $i = 3; $i < count($args); $i++) {
				$showOnlyAlbums[] = trim( $args[$i] );
			}
		}
		//print_r($args); print "<br>"; print "$username, $nbAlbumsPerPage, $nbPhotosPerPage";exit;
		$gallery = new KPicasaGallery($username, $nbAlbumsPerPage, $nbPhotosPerPage, $showOnlyAlbums);
		return ob_get_clean();
	}

	return $content;
}

function kPicasaFetch($url) {
	$data = false;
	if (ini_get('allow_url_fopen') == '1') {
		$data = file_get_contents($url);
	} elseif (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		curl_close($ch);
	}
	return $data;
}

?>
