<?php

if ( !class_exists('KPicasaGallery') )
{
	class KPicasaGallery
	{
		private $username;
		private $showOnlyAlbums;
		private $cacheTimeout;
		private $config;

		public function __construct($username = null, $showOnlyAlbums)
		{
			global $kpg_config;
			$this->config          = $kpg_config;
			$this->username        = $username != null ? $username : $kpg_config['username'];
			$this->showOnlyAlbums  = is_array( $showOnlyAlbums ) ? $showOnlyAlbums : array();
			$this->cacheTimeout    = 60 * 60 * 1;

			if ( !strlen( $this->username ) )
			{
				if ( $this->checkError( new WP_Error('kpicasa_gallery-username-required', '<strong>'.__('Error', 'kpicasa_gallery').':</strong> '.__('You must go to the admin section and set your Picasa Web Album Username in the Options section.', 'kpicasa_gallery') ) ) )
				{
					return false;
				}
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
				$data = wp_remote_get( $url, array('kind' => 'album') );
				if ( is_wp_error($data) )
				{
					return $data;
				}
				$data = $data['body'];
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

			if ( $this->config['albumPerPage'] > 0 )
			{
				$start = ($page - 1) * $this->config['albumPerPage'];
				$stop  = $start + $this->config['albumPerPage'] - 1;
			}
			else
			{
				$start = 0;
				$stop  = count( $xml->entry ) - 1;
			}

			// Set the class, depending on how many albums per row
			$class = $this->config['albumPerRow'] == 1 ? 'kpg-thumb-onePerRow' : 'kpg-thumb-multiplePerRow';

			//----------------------------------------
			// Loop through the albums
			//----------------------------------------
			print '<table cellpadding="0" cellspacing="0" border="0" width="100%" id="kpg-albums">';

			$i = -1; $j = -1;
			foreach( $xml->entry as $album )
			{
				if ( count($this->showOnlyAlbums) && !in_array((string) $album->gphoto_name, $this->showOnlyAlbums) )
				{
					continue;
				}
				if ( $this->config['showGooglePlus'] == 0 && in_array((string) $album->gphoto_name, array('ScrapbookPhotos', 'ProfilePhotos')) )
				{
					continue;
				}

				$i++;
				if ($i < $start || $i > $stop)
				{
					continue;
				}
				$j++;

				if ( $j % $this->config['albumPerRow'] == 0 )
				{
					$remainingWidth = 100;
					if ($j > 0)
					{
						print '</tr>';
					}
					print '<tr>';
				}

				// if last cell of the row, simply put remaining width
				$width = ( ($j+1) % $this->config['albumPerRow'] == 0 ) ? $remainingWidth : round( 100 / $this->config['albumPerRow'] );
				$remainingWidth -= $width;
				print "<td width='$width%'>";

				$name      = (string) $album->gphoto_name;
				$title     = wp_specialchars( (string) $album->title );
				$summary   = wp_specialchars( (string) $album->summary );
				$location  = wp_specialchars( (string) $album->gphoto_location );
				$published = wp_specialchars( date($this->config['dateFormat'], strtotime( $album->published ))); // that way it keeps the timezone
				$nbPhotos  = (string) $album->gphoto_numphotos;
				$albumURL  = add_query_arg('album', $name, $url);
				$thumbURL  = (string) $album->media_group->media_thumbnail['url'];
				$thumbW    = (string) $album->media_group->media_thumbnail['width'];
				$thumbH    = (string) $album->media_group->media_thumbnail['height'];

				if ( $this->config['albumThumbSize'] != null && $this->config['albumThumbSize'] != 160 )
				{
					$thumbURL = str_replace('/s160-c/', '/s'.$this->config['albumThumbSize'].'-c/', $thumbURL);
					$thumbH   = floor( ($this->config['albumThumbSize'] / 160) * $thumbH );
					$thumbW   = floor( ($this->config['albumThumbSize'] / 160) * $thumbW );
				}

				print "<a href='$albumURL'><img src='$thumbURL' height='$thumbH' width='$thumbW' alt='".str_replace("'", "&#39;", $title)."' class='kpg-thumb $class' /></a>";
				print "<div class='kpg-title'><a href='$albumURL'>$title</a></div>";
				if ( $this->config['albumSummary'] == true && strlen($summary) )
				{
					print "<div class='kpg-summary'>$summary</div>";
				}
				if ( $this->config['albumLocation'] == true && strlen($location) )
				{
					print "<div class='kpg-location'>$location</div>";
				}
				if ( $this->config['albumPublished'] == true )
				{
					print "<div class='kpg-published'>$published</div>";
				}
				if ( $this->config['albumNbPhoto'] == 1 )
				{
					print '<div class="kpg-nbPhotos">'.sprintf(__ngettext('%d photo', '%d photos', $nbPhotos, 'kpicasa_gallery'), $nbPhotos).'</div>';
				}
				print '</td>';
			}

			// never leave the last row with insuficient cells
			if ($this->config['photoPerRow'] > 0)
			{
				while ($j % $this->config['photoPerRow'] > 0)
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
			$nbItems = count($this->showOnlyAlbums) > 0 ? count($this->showOnlyAlbums) : $i + 1;
			$this->paginator( $page, 'kpgp', $this->config['albumPerPage'], $nbItems );
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
				$data = wp_remote_get( $url, array('kind' => 'photo') );
				if ( is_wp_error($data) )
				{
					return $data;
				}
				$data = $data['body'];
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
			//$albumPublished = wp_specialchars( date($this->config['dateFormat'], strtotime( $xml->published ))); // that way it keeps the timezone
			$albumNbPhotos  = (string) $xml->gphoto_numphotos;
			$albumSlideshow = (string) $xml->link[2]['href'];

			print '<div id="kpg-album-description">';
			print "<div id='kpg-title'>$albumTitle</div>";
			if ( $this->config['albumSummary'] == true && strlen($albumSummary) )
			{
				print "<div id='kpg-summary'>$albumSummary</div>";
			}
			if ( $this->config['albumLocation'] == true && strlen($albumLocation) )
			{
				print "<div id='kpg-location'>$albumLocation</div>";
			}
			if ( $this->config['albumPublished'] == true )
			{
				//print "<div id='kpg-published'>$albumPublished</div>";
			}
			if ( $this->config['albumNbPhoto'] == 1 )
			{
				print '<div id="kpg-nbPhotos">'.sprintf(__ngettext('%d photo', '%d photos', $albumNbPhotos, 'kpicasa_gallery'), $albumNbPhotos).'</div>';
			}
			if ( $this->config['albumSlideshow'] == 1 )
			{
				print "<div id='kpg-slideshow'><a href='$albumSlideshow'>".__('Slideshow', 'kpicasa_gallery')."</a></div>";
			}
			print '</div>';

			//----------------------------------------
			// Prepare some variables
			//----------------------------------------
			$page = isset($_GET['kpap']) && intval($_GET['kpap']) > 1 ? intval($_GET['kpap']) : 1; // kpap = kPicasa Album Page

			if ( $this->config['photoPerPage'] > 0 )
			{
				$start = ($page - 1) * $this->config['photoPerPage'];
				$stop  = $start + $this->config['photoPerPage'] - 1;
			}
			else
			{
				$start = 0;
				$stop = count( $xml->entry ) - 1;
			}

			//----------------------------------------
			// Loop through the pictures
			//----------------------------------------
			print '<table cellpadding="0" cellspacing="0" border="0" width="100%" id="kpg-pictures">';
			$i = -1; $j = -1;
			foreach( $xml->entry as $photo )
			{
				$i++;
				if ($i < $start || $i > $stop)
				{
					continue;
				}
				$j++;

					if ($j % $this->config['photoPerRow'] == 0)
					{
						$remainingWidth = 100;
						if ($j > 0)
						{
							print '</tr>';
						}
						print '<tr>';
					}

					// if last cell, simply put remaining width
					$width = ( ($j+1) % $this->config['photoPerRow'] == 0 ) ? $remainingWidth : round( 100 / $this->config['photoPerRow'] );
					$remainingWidth -= $width;
					print "<td width='$width%'>";

					$isVideo = (string) $photo->media_group->media_content[1]['medium'] == 'video' ? true : false;

					$summary  = wp_specialchars( (string) $photo->summary );
					$thumbURL = (string) $photo->media_group->media_thumbnail[1]['url'];
					$thumbW   = (string) $photo->media_group->media_thumbnail[1]['width'];
					$thumbH   = (string) $photo->media_group->media_thumbnail[1]['height'];

					if ( $this->config['photoThumbSize'] != null && $this->config['photoThumbSize'] != 144 )
					{
						$thumbURL = str_replace('/s144/', '/s'.$this->config['photoThumbSize'].'/', $thumbURL);
						$thumbH   = floor( ($this->config['photoThumbSize'] / 144) * $thumbH );
						$thumbW   = floor( ($this->config['photoThumbSize'] / 144) * $thumbW );
					}

					if ( $isVideo == true )
					{
						$fullURL     = (string) $photo->media_group->media_content[1]['url'];
						$fullURL     = 'http://video.google.com/googleplayer.swf?videoUrl='.urlencode($fullURL).'&autoplay=yes';
						$videoWidth  = (string) $photo->media_group->media_content[0]['width'];
						$videoHeight = (string) $photo->media_group->media_content[0]['height'];

						if ( in_array($this->config['picEngine'], array('thickbox', 'shadowbox', 'fancybox')) )
						{
							if ( in_array($this->config['picEngine'], array('thickbox', 'fancybox')) )
							{
								print '<div id="kpicasa_gallery_video_'.$i.'" style="width: '.$videoWidth.'px; height: '.$videoHeight.'px; display: none;">'."\n";
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

								if ( $this->config['picEngine'] == 'shadowbox' )
								{
									// foo=bar because of a Thickbox bug: http://groups.google.com/group/jquery-plugins/browse_thread/thread/079abdf9b068ddce?pli=1
									$fullURL = "#TB_inline?foo=bar&width=$videoWidth&height=".(5 + $videoHeight)."&inlineId=kpicasa_gallery_video_$i";
									$markup = "class='thickbox' rel='kpicasa_gallery'";
								}
								elseif ( $this->config['picEngine'] == 'fancybox' )
								{
									$fullURL = "#kpicasa_gallery_video_$i";
									$markup = "class='fancybox-kpicasa_gallery' rel='kpicasa_gallery' id='kpicasa_gallery_video_link_$i'";
								}
							}
							elseif ( $this->config['picEngine'] == 'shadowbox' )
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
						if ( $this->config['photoSize'] != null )
						{
							$fullURL = str_replace('/s144/', '/s'.$this->config['photoSize'].'/', $fullURL);
						}
						else
						{
							$fullURL = str_replace('/s144/', '/s800/', $fullURL);
						}

						if ( in_array($this->config['picEngine'], array('lightbox', 'slimbox2', 'thickbox', 'shadowbox', 'fancybox')) )
						{
							if ( $this->config['picEngine'] == 'lightbox' )
							{
								$markup = "rel='lightbox[kpicasa_gallery]'";
							}
							elseif ( $this->config['picEngine'] == 'slimbox2' )
							{
								$markup = "rel='lightbox-kpicasa_gallery'";
							}
							elseif ( $this->config['picEngine'] == 'thickbox' )
							{
								$markup = "class='thickbox' rel='kpicasa_gallery'";
							}
							elseif ( $this->config['picEngine'] == 'shadowbox' )
							{
								$markup = "rel='shadowbox[kpicasa_gallery]'";
							}
							elseif ( $this->config['picEngine'] == 'fancybox' )
							{
								$markup = "class='fancybox-kpicasa_gallery' rel='kpicasa_gallery'";
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
			}

			// never leave the last row with insuficient cells
			if ($this->config['photoPerRow'] > 0)
			{
				while ($j % $this->config['photoPerRow'] > 0)
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
			$this->paginator( $page, 'kpap', $this->config['photoPerPage'], count($xml->entry), $extraArgs );
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
