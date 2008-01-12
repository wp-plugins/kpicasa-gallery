<?php

if ( !class_exists('KPicasaGallery') ) {
	class KPicasaGallery {
		private $username;
		private $nbAlbumsPerPage;
		private $nbPhotosPerPage;
		private $picEngine;
		private $showOnlyAlbums;
		private $cacheTimeout;

		public function __construct($showOnlyAlbums) {
			$this->username        = get_option( 'kpg_username' );
			$this->nbAlbumsPerPage = get_option( 'kpg_albumPerPage' );
			$this->nbPhotosPerPage = get_option( 'kpg_photoPerPage' );
			$this->picEngine       = get_option( 'kpg_picEngine' );
			$this->showOnlyAlbums  = is_array( $showOnlyAlbums ) ? $showOnlyAlbums : array();
			$this->cacheTimeout    = 60 * 60 * 1;

			if ( !strlen( $this->username ) ) {
				$return_code = new WP_Error( 'kpicasa_gallery-username-required', __("Error: you must go to the admin section and set your Picasa Web Album Username in the Options section.") );
				foreach( $return_code->get_error_messages() as $message ) {
					print $message;
				}
			}
			
			if ( count($this->showOnlyAlbums) == 1 || (isset($_GET['album']) && strlen($_GET['album'])) ) {
				if ( count($this->showOnlyAlbums) == 1 ) {
					$album = $this->showOnlyAlbums[0];
					$direct = true;
				} else {
					$album = $_GET['album'];
					$direct = false;
				}
				$return_code = $this->showAlbum($album, $direct);
				
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
				$data = $this->fetch($url);
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
					$albumURL  = add_query_arg('album', $name, get_permalink());
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

		private function showAlbum($album, $direct = false) {
			if ( !$direct ) {
				$backURL = remove_query_arg('album');
				$backURL = remove_query_arg('kpap', $backURL);
				print "<a href='$backURL'>&laquo; Back to album list</a><br /><br />";
			}

			$data = wp_cache_get('kPicasaGallery_'.$album, 'kPicasaGallery');
			if ( false == $data ) {
				$url = "http://picasaweb.google.com/data/feed/api/user/".urlencode($this->username)."/album/".urlencode($album)."?kind=photo";
				$data = $this->fetch($url);
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
			
			if ( $this->picEngine == 'highslide' ) {
				print '<div id="controlbar" class="highslide-overlay controlbar">';
				print '<a href="#" class="previous" onclick="return hs.previous(this)" title="Previous (left arrow key)"></a>';
				print '<a href="#" class="next" onclick="return hs.next(this)" title="Next (right arrow key)"></a>';
				print '<a href="#" class="highslide-move" onclick="return false" title="Click and drag to move"></a>';
				print '<a href="#" class="close" onclick="return hs.close(this)" title="Close"></a>';
				print '</div>';
			}

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
					if ( $this->picEngine == 'lightbox' ) {
						if ( strlen($summary) ) {
							print "<a href='$fullURL' rel='lightbox[kpicasa_gallery]' title='".str_replace("'", "&#39;", $summary)."'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='".str_replace("'", "&#39;", $summary)."' style='border: solid 1px black;' /></a><br />";
							print "$summary<br />";
						} else {
							print "<a href='$fullURL' rel='lightbox[kpicasa_gallery]'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='' style='border: solid 1px black;' /></a><br />";
						}
					} else {
						if ( strlen($summary) ) {
							print "<a href='$fullURL' rel='highslide' class='highslide'><img src='$thumbURL' height='$thumbH' width='$thumbW' title='".str_replace("'", "&#39;", $summary)."' alt='".str_replace("'", "&#39;", $summary)."' style='border: solid 1px black;' /></a><br />";
							print "$summary<br />";
							print "<div class='highslide-caption'>$summary</div>";
						} else {
							print "<a href='$fullURL' rel='highslide' class='highslide'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='' style='border: solid 1px black;' /></a><br />";
						}
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
						$pageUrl = add_query_arg($argName, $i, get_permalink());
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
		
		private function fetch($url) {
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
	}
}

?>

