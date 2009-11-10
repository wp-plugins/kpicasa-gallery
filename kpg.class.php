<?php

if ( !class_exists('KPicasaGallery') )
{
	class KPicasaGallery
	{
		private $username;
		private $picEngine;
		private $showOnlyAlbums;
		private $cacheTimeout;

		private $nbAlbumsPerPage;
		private $nbAlbumsPerRow;
		private $albumThumbSize;
		private $albumSummary;
		private $albumLocation;
		private $albumPublished;
		private $albumNbPhoto;

		private $nbPhotosPerPage;
		private $nbPhotosPerRow;
		private $photoThumbSize;

		public function __construct($username = null, $showOnlyAlbums)
		{
			$this->username        = $username != null ? $username : get_option( 'kpg_username' );
			$this->picEngine       = get_option( 'kpg_picEngine' );
			$this->showOnlyAlbums  = is_array( $showOnlyAlbums ) ? $showOnlyAlbums : array();
			$this->cacheTimeout    = 60 * 60 * 1;

			$this->nbAlbumsPerPage = get_option( 'kpg_albumPerPage' );
			$this->nbAlbumsPerRow  = get_option( 'kpg_albumPerRow' );
			$this->albumThumbSize  = get_option( 'kpg_albumThumbSize' );
			$this->albumSummary    = get_option( 'kpg_albumSummary' );
			$this->albumLocation   = get_option( 'kpg_albumLocation' );
			$this->albumPublished  = get_option( 'kpg_albumPublished' );
			$this->albumNbPhoto    = get_option( 'kpg_albumNbPhoto' );

			$this->nbPhotosPerPage = get_option( 'kpg_photoPerPage' );
			$this->nbPhotosPerRow  = get_option( 'kpg_photoPerRow' );
			$this->photoThumbSize  = get_option( 'kpg_photoThumbSize' );

			if ( !strlen( $this->username ) )
			{
				if ( $this->checkError( new WP_Error('kpicasa_gallery-username-required', '<strong>'.__('Error', 'kpicasa_gallery').':</strong> '.__('You must go to the admin section and set your Picasa Web Album Username in the Options section.', 'kpicasa_gallery') ) ) )
				{
					return false;
				}
			}

			// Set default values, if necessary
			if ( intval( $this->nbAlbumsPerPage ) < 0 )
			{
				$this->nbAlbumsPerPage = 0;
			}
			if ( intval( $this->nbAlbumsPerRow ) < 1 )
			{
				$this->nbAlbumsPerRow = 1;
			}
			if ( intval( $this->nbPhotosPerPage ) < 0 )
			{
				$this->nbPhotosPerPage = 0;
			}
			if ( intval( $this->nbPhotosPerRow ) < 1 )
			{
				$this->nbPhotosPerRow = 2;
			}
			if ( $this->albumSummary === null )
			{
				$this->albumSummary = 1;
			}
			if ( $this->albumLocation === null )
			{
				$this->albumLocation = 1;
			}
			if ( $this->albumPublished === null )
			{
				$this->albumPublished = 1;
			}
			if ( $this->albumNbPhoto === null )
			{
				$this->albumNbPhoto = 1;
			}

			if ( count($this->showOnlyAlbums) == 1 || (isset($_GET['album']) && strlen($_GET['album'])) )
			{
				if ( count($this->showOnlyAlbums) == 1 )
				{
					$tmp     = explode('#', $this->showOnlyAlbums[0]);
					$album   = $tmp[0];
					$direct  = true;
					$authKey = isset($tmp[1]) ? $tmp[1] : '';
				}
				else
				{
					$album   = $_GET['album'];
					$direct  = false;
					$authKey = '';
				}

				if ( $this->checkError( $this->displayPictures($album, $direct, $authKey) ) )
				{
					return false;
				}
			}
			else
			{
				if ( $this->checkError( $this->displayAlbums() ) )
				{
					return false;
				}
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
				$url  = 'http://picasaweb.google.com/data/feed/api/user/'.urlencode($this->username).'?kind=album';
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
				return new WP_Error( 'kpicasa_gallery-invalid-response', '<strong>'.__('Error', 'kpicasa_gallery').':</strong> '.__('the communication with Picasa Web Albums didn\'t go as expected. Here\'s what Picasa Web Albums said', 'kpicasa_gallery').':<br /><br />'.$data );
			}

			//----------------------------------------
			// Prepare some variables
			//----------------------------------------
			$page = isset($_GET['kpgp']) && intval($_GET['kpgp']) > 1 ? intval($_GET['kpgp']) : 1; // kpgp = kPicasa Gallery Page

			$url = get_permalink();
			if ( $page > 1 )
			{
				$url = add_query_arg('kpgp', $page, $url);
			}

			if ( $this->nbAlbumsPerPage > 0 )
			{
				$start = ($page - 1) * $this->nbAlbumsPerPage;
				$stop  = $start + $this->nbAlbumsPerPage - 1;
			}
			else
			{
				$start = 0;
				$stop  = count( $xml->entry ) - 1;
			}

			// Set the class, depending on how many albums per row
			$class = $this->nbAlbumsPerRow == 1 ? 'kpg-thumb-onePerRow' : 'kpg-thumb-multiplePerRow';

			//----------------------------------------
			// Loop through the albums
			//----------------------------------------
			print '<table cellpadding="0" cellspacing="0" border="0" width="100%" id="kpg-albums">';
			$i = 0; $j = 0;
			foreach( $xml->entry as $album )
			{
				if ($i >= $start && $i <= $stop &&
				( !count($this->showOnlyAlbums) || in_array((string) $album->gphoto_name, $this->showOnlyAlbums) ))
				{
					if ( $j % $this->nbAlbumsPerRow == 0 )
					{
						$remainingWidth = 100;
						if ($j > 0)
						{
							print '</tr>';
						}
						print '<tr>';
					}

					// if last cell of the row, simply put remaining width
					$width = ( ($j+1) % $this->nbAlbumsPerRow == 0 ) ? $remainingWidth : round( 100 / $this->nbAlbumsPerRow );
					$remainingWidth -= $width;
					print "<td width='$width%'>";

					$name      = (string) $album->gphoto_name;
					$title     = wp_specialchars( (string) $album->title );
					$summary   = wp_specialchars( (string) $album->summary );
					$location  = wp_specialchars( (string) $album->gphoto_location );
					$published = wp_specialchars( date('Y-m-d', strtotime( $album->published ))); // that way it keeps the timezone
					$nbPhotos  = (string) $album->gphoto_numphotos;
					$albumURL  = add_query_arg('album', $name, $url);
					$thumbURL  = (string) $album->media_group->media_thumbnail['url'];
					$thumbW    = (string) $album->media_group->media_thumbnail['width'];
					$thumbH    = (string) $album->media_group->media_thumbnail['height'];

					if ( $this->albumThumbSize != false && $this->albumThumbSize != 160 )
					{
						$thumbURL = str_replace('/s160-c/', '/s'.$this->albumThumbSize.'-c/', $thumbURL);
						$thumbH   = round( ($this->albumThumbSize / $thumbH) * $thumbH );
						$thumbW   = round( ($this->albumThumbSize / $thumbW) * $thumbW );
					}

					print "<a href='$albumURL'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='".str_replace("'", "&#39;", $title)."' class='kpg-thumb $class' /></a>";
					print "<div class='kpg-title'><a href='$albumURL'>$title</a></div>";
					if ( $this->albumSummary == 1 && strlen($summary) )
					{
						print "<div class='kpg-summary'>$summary</div>";
					}
					if ( $this->albumLocation == 1 && strlen($location) )
					{
						print "<div class='kpg-location'>$location</div>";
					}
					if ( $this->albumPublished == 1 )
					{
						print "<div class='kpg-published'>$published</div>";
					}
					if ( $this->albumNbPhoto == 1 )
					{
						print '<div class="kpg-nbPhotos">'.sprintf(__ngettext('%d photo', '%d photos', $nbPhotos, 'kpicasa_gallery'), $nbPhotos).'</div>';
					}
					print '</td>';
					$j++;
				}
				$i++;
			}

			// never leave the last row with insuficient cells
			if ($this->nbPhotosPerRow > 0)
			{
				while ($j % $this->nbPhotosPerRow > 0)
				{
					print '<td>&nbsp;</td>';
					$j++;
				}
			}

			print '</tr>';
			print '</table>';
			print '<br style="clear: both;" />';

			//----------------------------------------
			// Paginator
			//----------------------------------------
			$nbItems = count($this->showOnlyAlbums) > 0 ? count($this->showOnlyAlbums) : count($xml->entry);
			$this->paginator( $page, 'kpgp', $this->nbAlbumsPerPage, $nbItems );
			return true;
		}

		private function displayPictures($album, $direct = false, $authKey = '')
		{
			//----------------------------------------
			// Get the XML
			//----------------------------------------
			$data = wp_cache_get('kPicasaGallery_'.$album, 'kPicasaGallery');
			if ( false === $data )
			{
				$url = 'http://picasaweb.google.com/data/feed/api/user/'.urlencode($this->username).'/album/'.urlencode($album).'?kind=photo';
				if ( strlen($authKey) > 0 )
				{
					$url .= '&authkey='.$authKey;
				}
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
				return new WP_Error( 'kpicasa_gallery-invalid-response', '<strong>'.__('Error', 'kpicasa_gallery').':</strong> '.__('the communication with Picasa Web Albums didn\'t go as expected. Here\'s what Picasa Web Albums said', 'kpicasa_gallery').':<br /><br />'.$data );
			}

			//----------------------------------------
			// Display "back" link
			//----------------------------------------
			if ( !$direct )
			{
				$backURL = remove_query_arg('album');
				$backURL = remove_query_arg('kpap', $backURL);
				print "<div id='kpg-backLink'><a href='$backURL'>&laquo; ".__('Back to album list', 'kpicasa_gallery').'</a></div>';
			}

			//----------------------------------------
			// Display album information
			//----------------------------------------
			$albumTitle     = wp_specialchars( (string) $xml->title );
			$albumSummary   = wp_specialchars( (string) $xml->subtitle );
			$albumLocation  = wp_specialchars( (string) $xml->gphoto_location );
			//$albumPublished = wp_specialchars( date('Y-m-d', strtotime( $xml->published ))); // that way it keeps the timezone
			$albumNbPhotos  = (string) $xml->gphoto_numphotos;

			print '<div id="kpg-album-description">';
			print "<div id='kpg-title'>$albumTitle</div>";
			if ( $this->albumSummary == 1 && strlen($albumSummary) )
			{
				print "<div id='kpg-summary'>$albumSummary</div>";
			}
			if ( $this->albumLocation == 1 && strlen($albumLocation) )
			{
				print "<div id='kpg-location'>$albumLocation</div>";
			}
			if ( $this->albumPublished == 1 )
			{
				//print "<div id='kpg-published'>$albumPublished</div>";
			}
			if ( $this->albumNbPhoto == 1 )
			{
				print '<div id="kpg-nbPhotos">'.sprintf(__ngettext('%d photo', '%d photos', $albumNbPhotos, 'kpicasa_gallery'), $albumNbPhotos).'</div>';
			}
			print '</div>';

			//----------------------------------------
			// Prepare some variables
			//----------------------------------------
			$page = isset($_GET['kpap']) && intval($_GET['kpap']) > 1 ? intval($_GET['kpap']) : 1; // kpap = kPicasa Album Page

			if ( $this->nbPhotosPerPage > 0 )
			{
				$start = ($page - 1) * $this->nbPhotosPerPage;
				$stop  = $start + $this->nbPhotosPerPage - 1;
			}
			else
			{
				$start = 0;
				$stop = count( $xml->entry ) - 1;
			}

			$thumbIndex = 1; // 144px
			if ( $this->photoThumbSize == 72 )
			{
				$thumbIndex = 0;
			}
			elseif ( $this->photoThumbSize == 288 )
			{
				$thumbIndex = 2;
			}

			//----------------------------------------
			// Loop through the pictures
			//----------------------------------------
			print '<table cellpadding="0" cellspacing="0" border="0" width="100%" id="kpg-pictures">';
			$i = 0; $j = 0;
			foreach( $xml->entry as $photo )
			{
				if ($i >= $start && $i <= $stop)
				{
					if ($j % $this->nbPhotosPerRow == 0)
					{
						$remainingWidth = 100;
						if ($j > 0)
						{
							print '</tr>';
						}
						print '<tr>';
					}

					// if last cell, simply put remaining width
					$width = ( ($j+1) % $this->nbPhotosPerRow == 0 ) ? $remainingWidth : round( 100 / $this->nbPhotosPerRow );
					$remainingWidth -= $width;
					print "<td width='$width%'>";

					$isVideo = (string) $photo->media_group->media_content[1]['medium'] == 'video' ? true : false;

					$summary  = wp_specialchars( (string) $photo->summary );
					$thumbURL = (string) $photo->media_group->media_thumbnail[$thumbIndex]['url'];
					$thumbW   = (string) $photo->media_group->media_thumbnail[$thumbIndex]['width'];
					$thumbH   = (string) $photo->media_group->media_thumbnail[$thumbIndex]['height'];

					if ( $isVideo == true )
					{
						$fullURL     = (string) $photo->media_group->media_content[1]['url'];
						$fullURL     = 'http://video.google.com/googleplayer.swf?videoUrl='.urlencode($fullURL).'&autoplay=yes';
						$videoWidth  = (string) $photo->media_group->media_content[0]['width'];
						$videoHeight = (string) $photo->media_group->media_content[0]['height'];

						if ( $this->picEngine == 'highslide' )
						{
							$onclick  = "onclick='return hs.htmlExpand(this, ";
							$onclick .= "{ objectType: \"swf\", ";
							$onclick .= "width: $videoWidth, height: ".(75 + $videoHeight).", objectWidth: $videoWidth, objectHeight: $videoHeight, ";
							$onclick .= "wrapperClassName: \"draggable-header no-footer\", ";
							$onclick .= "allowSizeReduction: false, preserveContent: false, ";
							$onclick .= "maincontentText: \"".__('You need to upgrade your Flash player', 'kpicasa_gallery')."\" } )'";

							if ( strlen($summary) )
							{
								print "<a href='$fullURL' $onclick class='highslide'><img src='$thumbURL' height='$thumbH' width='$thumbW' title='".str_replace("'", "&#39;", $summary)."' alt='".str_replace("'", "&#39;", $summary)."' class='kpg-thumb' /></a>";
								print "<div class='kpg-summary'>$summary</div>";
								print "<div class='highslide-caption'>$summary</div>";
							}
							else
							{
								print "<a href='$fullURL' $onclick class='highslide'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='' class='kpg-thumb' /></a>";
							}
						}
						elseif ( $this->picEngine == 'thickbox' || $this->picEngine == 'shadowbox' )
						{
							if ( $this->picEngine == 'thickbox' )
							{
								print '<div id="kpicasa_gallery_video_'.$i.'" style="display: none;">'."\n";
								print '	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="'.$videoWidth.'" height="'.$videoHeight.'" id="kpg_'.$i.'">'."\n";
								print '		<param name="movie" value="'.$fullURL.'" />'."\n";
								if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') === false )
								{
									print '		<object type="application/x-shockwave-flash" data="'.$fullURL.'" width="'.$videoWidth.'" height="'.$videoHeight.'">'."\n";
								}
								print '			<a href="http://www.adobe.com/go/getflashplayer">'."\n";
								print '			<img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" />'."\n";
								print '			</a>'."\n";
								if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') === false )
								{
									print '		</object>'."\n";
								}
								print '	</object>'."\n";
								print '</div>'."\n";

								// foo=bar because of a Thickbox bug: http://groups.google.com/group/jquery-plugins/browse_thread/thread/079abdf9b068ddce?pli=1
								$fullURL = '#TB_inline?foo=bar&height='.(5 + $videoHeight).'&width='.$videoWidth.'&inlineId=kpicasa_gallery_video_'.$i;
								$markup = "class='thickbox' rel='kpicasa_gallery'";
							}
							elseif ( $this->picEngine == 'shadowbox' )
							{
								$markup = "rel='shadowbox[kpicasa_gallery];height=$videoHeight;width=$videoWidth'";
							}

							if ( strlen($summary) )
							{
								print "<a href='$fullURL' title='".str_replace("'", "&#39;", $summary)."' $markup><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='".str_replace("'", "&#39;", $summary)."' class='kpg-thumb' /></a>";
								print "<div class='kpg-summary'>$summary</div>";
							}
							else
							{
								print "<a href='$fullURL' $markup><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='' class='kpg-thumb' /></a>";
							}
						}
						else
						{
							$fullURL = (string) $photo->link[1]['href'];

							if ( strlen($summary) )
							{
								print "<a href='$fullURL' title='".str_replace("'", "&#39;", $summary)."' target='_blank'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='".str_replace("'", "&#39;", $summary)."' class='kpg-thumb' /></a>";
								print "<div class='kpg-summary'>$summary</div>";
							}
							else
							{
								print "<a href='$fullURL' target='_blank'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='' class='kpg-thumb' /></a>";
							}
						}
					}
					else
					{
						$fullURL = (string) $photo->media_group->media_thumbnail[1]['url'];
						$fullURL = str_replace('/s144/', '/s800/', $fullURL);

						if ( $this->picEngine == 'highslide' )
						{
							if ( strlen($summary) )
							{
								print "<a href='$fullURL' rel='highslide' class='highslide'><img src='$thumbURL' height='$thumbH' width='$thumbW' title='".str_replace("'", "&#39;", $summary)."' alt='".str_replace("'", "&#39;", $summary)."' class='kpg-thumb' /></a>";
								print "<div class='kpg-summary'>$summary</div>";
								print "<div class='highslide-caption'>$summary</div>";
							}
							else
							{
								print "<a href='$fullURL' rel='highslide' class='highslide'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='' class='kpg-thumb' /></a>";
							}
						}
						elseif ( $this->picEngine == 'lightbox' || $this->picEngine == 'slimbox2' || $this->picEngine == 'thickbox' || $this->picEngine == 'shadowbox' )
						{
							if ( $this->picEngine == 'lightbox' )
							{
								$markup = "rel='lightbox[kpicasa_gallery]'";
							}
							elseif ( $this->picEngine == 'slimbox2' )
							{
								$markup = "rel='lightbox-kpicasa_gallery'";
							}
							elseif ( $this->picEngine == 'thickbox' )
							{
								$markup = "class='thickbox' rel='kpicasa_gallery'";
							}
							elseif ( $this->picEngine == 'shadowbox' )
							{
								$markup = "rel='shadowbox[kpicasa_gallery]'";
							}

							if ( strlen($summary) )
							{
								print "<a href='$fullURL' title='".str_replace("'", "&#39;", $summary)."' $markup><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='".str_replace("'", "&#39;", $summary)."' class='kpg-thumb' /></a>";
								print "<div class='kpg-summary'>$summary</div>";
							}
							else
							{
								print "<a href='$fullURL' $markup><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='' class='kpg-thumb' /></a>";
							}
						}
						else
						{
							if ( strlen($summary) )
							{
								print "<a href='$fullURL' title='".str_replace("'", "&#39;", $summary)."'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='".str_replace("'", "&#39;", $summary)."' class='kpg-thumb' /></a>";
								print "<div class='kpg-summary'>$summary</div>";
							}
							else
							{
								print "<a href='$fullURL'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='' class='kpg-thumb' /></a>";
							}
						}
					}
					print '</td>';
					$j++;
				}
				$i++;
			}

			// never leave the last row with insuficient cells
			if ($this->nbPhotosPerRow > 0)
			{
				while ($j % $this->nbPhotosPerRow > 0)
				{
					print '<td>&nbsp;</td>';
					$j++;
				}
			}

			print '</tr>';
			print '</table>';
			print '<br style="clear: both;" />';

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

					print '<div id="kpg-paginator">'.__('Page', 'kpicasa_gallery').':&nbsp;&nbsp;';
					for($i = 1; $i <= $nbPage; $i++)
					{
						$pageURL = add_query_arg($argName, $i, $url);
						if ($i == $page)
						{
							print " <span class='kpg-on'>$i</span>";
						}
						else
						{
							print " <a href='$pageURL'>$i</a>";
						}
					}
					print '</div>';
				}
			}
		}

		private function fetch( $url )
		{
			$fopen_failed = false;
			$curl_failed  = false;

			// todo: add timeouts?

			if ( ini_get('allow_url_fopen') == '1' )
			{
				$data = @file_get_contents($url);
				if ($data !== false)
				{
					return $data;
				}
				$fopen_failed = true;
			}
			if ( function_exists('curl_init') )
			{
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$data = curl_exec($ch);
				curl_close($ch);
				if ($data !== false)
				{
					return $data;
				}
				$curl_failed = true;
			}

			if ($fopen_failed && $curl_failed)
			{
				return new WP_Error( 'kpicasa_gallery-cant-open-url', '<strong>'.__('Error', 'kpicasa_gallery').':</strong> '.__('your PHP configuration reports that it allows kPicasa Gallery to connect to Picasa Web Albums, but in fact it seems your web host is blocking outgoing requests. Please ask your administrator to allow outgoing requests via file_get_contents() or cURL.', 'kpicasa_gallery') );
			}
			elseif ($fopen_failed)
			{
				return new WP_Error( 'kpicasa_gallery-cant-open-url', '<strong>'.__('Error', 'kpicasa_gallery').':</strong> '.__('kPicasa Gallery tried to connect to Picasa Web Albums using file_get_contents() and failed. Your web host is probably blocking outgoing requests.', 'kpicasa_gallery') );
			}
			elseif ($curl_failed)
			{
				return new WP_Error( 'kpicasa_gallery-cant-open-url', '<strong>'.__('Error', 'kpicasa_gallery').':</strong> '.__('kPicasa Gallery tried to connect to Picasa Web Albums using cURL and failed. Your web host is probably blocking outgoing requests.', 'kpicasa_gallery') );
			}
			else
			{
				return new WP_Error( 'kpicasa_gallery-cant-open-url', '<strong>'.__('Error', 'kpicasa_gallery').':</strong> '.__('your PHP configuration does not allow kPicasa Gallery to connect to Picasa Web Albums. Please ask your administrator to enable allow_url_fopen or cURL.', 'kpicasa_gallery') );
			}
		}

		private function checkError( $error )
		{
			if ( is_wp_error($error) )
			{
				//wp_die( $error, 'kPicasa Gallery Error' );
				foreach( $error->get_error_messages() as $message )
				{
					print $message;
				}
				print '<br /><br />';
				return true;
			}
			return false;
		}
	}
}

?>
