<?php

if ( !class_exists('KPicasaGallery') )
{
	class KPicasaGallery 
	{
		private $username;
		private $nbAlbumsPerPage;
		private $nbPhotosPerPage;
		private $picEngine;
		private $showOnlyAlbums;
		private $cacheTimeout;

		public function __construct($showOnlyAlbums) 
		{
			$this->username        = get_option( 'kpg_username' );
			$this->nbAlbumsPerPage = get_option( 'kpg_albumPerPage' );
			$this->nbPhotosPerPage = get_option( 'kpg_photoPerPage' );
			$this->picEngine       = get_option( 'kpg_picEngine' );
			$this->showOnlyAlbums  = is_array( $showOnlyAlbums ) ? $showOnlyAlbums : array();
			$this->cacheTimeout    = 60 * 60 * 1;

			if ( !strlen( $this->username ) ) 
			{
				$this->verifyError( new WP_Error('kpicasa_gallery-username-required', "Error: you must go to the admin section and set your Picasa Web Album Username in the Options section.") );
			}
			
			if ( count($this->showOnlyAlbums) == 1 || (isset($_GET['album']) && strlen($_GET['album'])) ) 
			{
				if ( count($this->showOnlyAlbums) == 1 ) 
				{
					$album = $this->showOnlyAlbums[0];
					$direct = true;
				} 
				else 
				{
					$album = $_GET['album'];
					$direct = false;
				}
				
				$this->verifyError( $this->displayPictures($album, $direct) );
			}
			else
			{
				$this->verifyError( $this->displayAlbums() );
			}
		}

		private function displayAlbums() 
		{
			//----------------------------------------
			// Get the XML
			//----------------------------------------
			$data = wp_cache_get('kPicasaGallery', 'kPicasaGallery');
			if ( false === $data ) 
			{
				$url  = "http://picasaweb.google.com/data/feed/api/user/".urlencode($this->username)."?kind=album";
				$data = $this->fetch($url);
				if ( is_wp_error($data) ) 
				{
					return $data;
				}
				$data = str_replace('gphoto:', 'gphoto_', $data);
				$data = str_replace('media:', 'media_', $data);
				wp_cache_set('kPicasaGallery', $data, 'kPicasaGallery', $this->cacheTimeout);
			}
			$xml = @simplexml_load_string($data);
			if ( $xml === false ) 
			{
				return new WP_Error( 'kpicasa_gallery-invalid-response', "<strong>Error:</strong> the communication with Picasa Web Albums didn't go as expected. Here's what Picasa Web Albums said:<br /><br />".$data );
			}

			//----------------------------------------
			// Prepare some variables
			//----------------------------------------
			$page = isset($_GET['kpgp']) && intval($_GET['kpgp']) > 1 ? intval($_GET['kpgp']) : 1; // kpgp = kPicasa Gallery Page
			
			$url = get_permalink();
			if ($page > 1) 
			{
				$url = add_query_arg('kpgp', $page, $url);
			}
			
			if ($this->nbAlbumsPerPage > 0) 
			{
				$start = ($page - 1) * $this->nbAlbumsPerPage;
				$stop  = $start + $this->nbAlbumsPerPage - 1;
			}
			else 
			{
				$start = 0;
				$stop  = count( $xml->entry ) - 1;
			}
			
			//----------------------------------------
			// Loop through the albums
			//----------------------------------------
			print '<br />';
			$i = 0;
			foreach( $xml->entry as $album ) 
			{
				if ($i >= $start && $i <= $stop && 
				( !count($this->showOnlyAlbums) || in_array((string) $album->gphoto_name, $this->showOnlyAlbums) )) 
				{
					$name      = (string) $album->gphoto_name;
					$title     = wp_specialchars( (string) $album->title );
					$summary   = wp_specialchars( (string) $album->summary );
					$location  = wp_specialchars( (string) $album->gphoto_location );
					$nbPhotos  = (string) $album->gphoto_numphotos;
					$albumURL  = add_query_arg('album', $name, $url);
					$thumbURL  = (string) $album->media_group->media_thumbnail['url'];
					$thumbH    = (string) $album->media_group->media_thumbnail['height'];
					$thumbW    = (string) $album->media_group->media_thumbnail['width'];

					print "<p><a href='$albumURL'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='".str_replace("'", "&#39;", $title)."' align='left' class='kpicasa-gallery-album-cover' /></a>";
					print "<a href='$albumURL' class='kpicasa-gallery-album-title'>$title</a><br />";
					if ( strlen($summary) )
					{
						print "$summary<br />";
					}
					if ( strlen($location) ) 
					{
						print "<em>$location</em><br />";
					}
					print "<br />$nbPhotos photo".($nbPhotos > 1 ? 's' : '').'<br /></p>';
					print '<div style="clear: both;" />&nbsp;</div>';
				}
				$i++;
			}
			
			//----------------------------------------
			// Paginator
			//----------------------------------------
			$nbItems = count($this->showOnlyAlbums) > 0 ? count($this->showOnlyAlbums) : count($xml->entry);
			$this->paginator( $page, 'kpgp', $this->nbAlbumsPerPage, $nbItems );
			return true;
		}

		private function displayPictures($album, $direct = false) 
		{
			//----------------------------------------
			// Get the XML
			//----------------------------------------
			$data = wp_cache_get('kPicasaGallery_'.$album, 'kPicasaGallery');
			if ( false === $data ) 
			{
				$url = "http://picasaweb.google.com/data/feed/api/user/".urlencode($this->username)."/album/".urlencode($album)."?kind=photo";
				$data = $this->fetch($url);
				if ( is_wp_error($data) ) 
				{
					return $data;
				}
				$data = str_replace('gphoto:', 'gphoto_', $data);
				$data = str_replace('media:', 'media_', $data);
				wp_cache_set('kPicasaGallery_'.$album, $data, 'kPicasaGallery', $this->cacheTimeout);
			}
			$xml = @simplexml_load_string($data);
			if ( $xml === false ) 
			{
				return new WP_Error( 'kpicasa_gallery-invalid-response', __("<strong>Error:</strong> the communication with Picasa Web Albums didn't go as expected. Here's what Picasa Web Albums said:<br /><br /> ".$data) );
			}

			//----------------------------------------
			// Display "back" link
			//----------------------------------------
			if ( !$direct ) 
			{
				$backURL = remove_query_arg('album');
				$backURL = remove_query_arg('kpap', $backURL);
				print "<a href='$backURL'>&laquo; Back to album list</a><br /><br />";
			}

			//----------------------------------------
			// Display album information
			//----------------------------------------
			$albumTitle    = wp_specialchars( (string) $xml->title );
			$albumSummary  = wp_specialchars( (string) $xml->subtitle );
			$albumLocation = wp_specialchars( (string) $xml->gphoto_location );
			$albumNbPhotos = (string) $xml->gphoto_numphotos;

			print '<div class="kpicasa-gallery-album-description">';
			print "<strong>$albumTitle</strong><br />";
			if ( strlen($albumSummary) ) 
			{
				print "$albumSummary<br />";
			}
			if ( strlen($albumLocation) ) 
			{
				print "<em>$albumLocation</em><br />";
			}
			print "$albumNbPhotos photo".($albumNbPhotos > 1 ? 's' : '').'<br />';
			print '</div><br /><br />';
			
			//----------------------------------------
			// Prepare Highslide if needed
			//----------------------------------------
			if ( $this->picEngine == 'highslide' ) 
			{
				print '<div id="controlbar" class="highslide-overlay controlbar">';
				print '<a href="#" class="previous" onclick="return hs.previous(this)" title="Previous (left arrow key)"></a>';
				print '<a href="#" class="next" onclick="return hs.next(this)" title="Next (right arrow key)"></a>';
				print '<a href="#" class="highslide-move" onclick="return false" title="Click and drag to move"></a>';
				print '<a href="#" class="close" onclick="return hs.close(this)" title="Close"></a>';
				print '</div>';
			}

			//----------------------------------------
			// Prepare some variables
			//----------------------------------------
			$page = isset($_GET['kpap']) && intval($_GET['kpap']) > 1 ? intval($_GET['kpap']) : 1; // kpap = kPicasa Album Page
			
			if ($this->nbPhotosPerPage > 0) 
			{
				$start = ($page - 1) * $this->nbPhotosPerPage;
				$stop  = $start + $this->nbPhotosPerPage - 1;
			}
			else 
			{
				$start = 0;
				$stop = count( $xml->entry ) - 1;
			}
			
			//----------------------------------------
			// Loop through the pictures
			//----------------------------------------
			$i = 0; $j = 0;
			foreach( $xml->entry as $photo ) 
			{
				if ($i >= $start && $i <= $stop) 
				{				
					$summary  = wp_specialchars( (string) $photo->summary );
					$thumbURL = (string) $photo->media_group->media_thumbnail[1]['url'];
					$thumbH   = (string) $photo->media_group->media_thumbnail[1]['height'];
					$thumbW   = (string) $photo->media_group->media_thumbnail[1]['width'];
					$fullURL  = str_replace('s144', 's800', $thumbURL);

					print '<div style="float: left; width: 45%; text-align: center;">';
					if ( $this->picEngine == 'lightbox' ) 
					{
						if ( strlen($summary) ) 
						{
							print "<a href='$fullURL' rel='lightbox[kpicasa_gallery]' title='".str_replace("'", "&#39;", $summary)."'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='".str_replace("'", "&#39;", $summary)."' class='kpicasa-gallery-thumbnail' /></a><br />";
							print "$summary<br />";
						}
						else 
						{
							print "<a href='$fullURL' rel='lightbox[kpicasa_gallery]'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='' class='kpicasa-gallery-thumbnail' /></a><br />";
						}
					}
					elseif ( $this->picEngine == 'highslide' ) 
					{
						if ( strlen($summary) ) 
						{
							print "<a href='$fullURL' rel='highslide' class='highslide'><img src='$thumbURL' height='$thumbH' width='$thumbW' title='".str_replace("'", "&#39;", $summary)."' alt='".str_replace("'", "&#39;", $summary)."' class='kpicasa-gallery-thumbnail' /></a><br />";
							print "$summary<br />";
							print "<div class='highslide-caption'>$summary</div>";
						} 
						else
						{
							print "<a href='$fullURL' rel='highslide' class='highslide'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='' class='kpicasa-gallery-thumbnail' /></a><br />";
						}
					} 
					else
					{
						if ( strlen($summary) ) 
						{
							print "<a href='$fullURL' title='".str_replace("'", "&#39;", $summary)."'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='".str_replace("'", "&#39;", $summary)."' class='kpicasa-gallery-thumbnail' /></a><br />";
							print "$summary<br />";
						}
						else
						{
							print "<a href='$fullURL'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='' class='kpicasa-gallery-thumbnail' /></a><br />";
						}
					}
					print '<br /></div>';

					//----------------------------------------
					// We break to a new line every 2 pictures
					//----------------------------------------
					if ( $j % 2 == 1 ) 
					{
						print '<div style="clear: both;" />&nbsp;</div>';
					}
					else
					{
						print '<div style="float: left; width: 10%;">&nbsp;</div>';
					}
					$j++;
				}
				$i++;
			}
			if ( $j % 2 == 1 )
			{
				print '<div style="clear: both;" />&nbsp;</div>';
			}
			
			//----------------------------------------
			// Paginator
			//----------------------------------------
			$extraArgs = array('album' => $album);
			if (isset($_GET['kpgp']) && intval($_GET['kpgp']) > 1)
			{
				$extraArgs['kpgp'] = intval($_GET['kpgp']);
			}
			$this->paginator( $page, 'kpap', $this->nbPhotosPerPage, count($xml->entry), $extraArgs );
			return true;
		}
		
		private function paginator ($page, $argName, $perPage, $nbItems, $extraArgs = array()) 
		{
			if ($perPage > 0) 
			{
				$nbPage = ceil( $nbItems / $perPage );
				if ($nbPage > 1) 
				{
					$url = get_permalink();
					foreach($extraArgs as $key => $value) 
					{
						$url = add_query_arg($key, $value, $url);
					}
					
					print '<p align="center" class="kpicasa-gallery-paginator">Page:&nbsp;';
					for($i = 1; $i <= $nbPage; $i++) 
					{
						$pageUrl = add_query_arg($argName, $i, $url);
						if ($i == $page) 
						{
							print "&nbsp;<span class='kpicasa-gallery-paginator-on'>$i</span>";
						}
						else
						{
							print "&nbsp;<a href='$pageUrl' class='kpicasa-gallery-paginator-off'>$i</a>";
						}
					}
					print '</p>';
				}
			}
		}
		
		private function fetch( $url )
		{
			if ( ini_get('allow_url_fopen') == '1' )
			{
				$data = file_get_contents($url);
				if ($data === false)
				{
					return new WP_Error( 'kpicasa_gallery-cant-open-url', __("<strong>Error:</strong> kPicasa Gallery tried to connect to Picasa Web Albums using file_get_contents() and failed. Your web host is probably blocking outgoing requests.") );
				}
				return $data;
			}
			elseif ( function_exists('curl_init') )
			{
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$data = curl_exec($ch);
				curl_close($ch);
				if ($data === false)
				{
					return new WP_Error( 'kpicasa_gallery-cant-open-url', __("<strong>Error:</strong> kPicasa Gallery tried to connect to Picasa Web Albums using cURL and failed. Your web host is probably blocking outgoing requests.") );
				}
				return $data;
			}
			return new WP_Error( 'kpicasa_gallery-cant-open-url', __("<strong>Error:</strong> your PHP configuration does not allow kPicasa Gallery to connect to Picasa Web Albums. Please ask your administrator to enable allow_url_fopen or cURL.") );
		}
		
		private function verifyError( $error )
		{
			if ( is_wp_error($error) )
			{
				foreach( $error->get_error_messages() as $message ) 
				{
					print $message;
				}
				print '<br /><br />';
			}
		}
	}
}

?>

