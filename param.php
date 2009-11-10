<?php

$username       = get_option( 'kpg_username' );
$picEngine      = get_option( 'kpg_picEngine' );
$albumPerPage   = get_option( 'kpg_albumPerPage' );
$albumPerRow    = get_option( 'kpg_albumPerRow' );
$albumThumbSize = get_option( 'kpg_albumThumbSize' );
$albumSummary   = get_option( 'kpg_albumSummary' );
$albumLocation  = get_option( 'kpg_albumLocation' );
$albumPublished = get_option( 'kpg_albumPublished' );
$albumNbPhoto   = get_option( 'kpg_albumNbPhoto' );
$photoPerPage   = get_option( 'kpg_photoPerPage' );
$photoPerRow    = get_option( 'kpg_photoPerRow' );
$photoThumbSize = get_option( 'kpg_photoThumbSize' );

// See if the user has posted us some information
// If they did, this hidden field will be set to 'Y'
if( $_POST[ 'kpg_save' ] == 'Y' )
{
	// Read their posted value
	$username       = $_POST[ 'kpg_username' ];
	$picEngine      = $_POST[ 'kpg_picEngine' ];
	$albumPerPage   = intval( $_POST[ 'kpg_albumPerPage' ] );
	$albumPerRow    = intval( $_POST[ 'kpg_albumPerRow' ] );
	$albumThumbSize = intval( $_POST[ 'kpg_albumThumbSize' ] );
	$albumSummary   = isset( $_POST[ 'kpg_albumSummary' ] ) ? 1 : 0;
	$albumLocation  = isset( $_POST[ 'kpg_albumLocation' ] ) ? 1 : 0;
	$albumPublished = isset( $_POST[ 'kpg_albumPublished' ] ) ? 1 : 0;
	$albumNbPhoto   = isset( $_POST[ 'kpg_albumNbPhoto' ] ) ? 1 : 0;
	$photoPerPage   = intval( $_POST[ 'kpg_photoPerPage' ] );
	$photoPerRow    = intval( $_POST[ 'kpg_photoPerRow' ] );
	$photoThumbSize = intval( $_POST[ 'kpg_photoThumbSize' ] );

	$picEngine      = in_array($picEngine, array('highslide', 'lightbox', 'slimbox2', 'thickbox', 'shadowbox', '')) ? $picEngine : 'highslide';
	$albumPerPage   = $albumPerPage > 0 ? $albumPerPage : 0;
	$albumPerRow    = $albumPerRow  > 0 ? $albumPerRow  : 1;
	$albumThumbSize = in_array($albumThumbSize, array(32, 48, 64, 72, 144, 160)) ? $albumThumbSize : 160;
	$photoPerPage   = $photoPerPage > 0 ? $photoPerPage : 0;
	$photoPerRow    = $photoPerRow  > 0 ? $photoPerRow  : 2;
	$photoThumbSize = in_array($photoThumbSize, array(72, 144, 288)) ? $photoThumbSize : 144;

	// Save the posted value in the database
	update_option( 'kpg_username',       $username );
	update_option( 'kpg_picEngine',      $picEngine );
	update_option( 'kpg_albumPerPage',   $albumPerPage );
	update_option( 'kpg_albumPerRow',    $albumPerRow );
	update_option( 'kpg_albumThumbSize', $albumThumbSize );
	update_option( 'kpg_albumSummary',   $albumSummary );
	update_option( 'kpg_albumLocation',  $albumLocation );
	update_option( 'kpg_albumPublished', $albumPublished );
	update_option( 'kpg_albumNbPhoto',   $albumNbPhoto );
	update_option( 'kpg_photoPerPage',   $photoPerPage );
	update_option( 'kpg_photoPerRow',    $photoPerRow );
	update_option( 'kpg_photoThumbSize', $photoThumbSize );

	// Put an options updated message on the screen
	print '<div id="message" class="updated fade"><p><strong>'.__('Settings saved.', 'kpicasa_gallery').'</strong></p></div>';
}

$albumPerPage = $albumPerPage > 0 ? $albumPerPage : '';
$photoPerPage = $photoPerPage > 0 ? $photoPerPage : '';

// Now display the options editing screen
print '<div class="wrap">';
print '<h2>'.__('kPicasa Gallery Plugin Options', 'kpicasa_gallery').'</h2>';

// Form
print '<form name="form1" method="post" action="'.str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'">';
print '<input type="hidden" name="kpg_save" value="Y">';

// General settings
print '<h3>'.__('General settings', 'kpicasa_gallery').'</h3>';
print '<table class="form-table">';

// Username
print '<tr valign="top">';
print '<th scope="row">'.__('Picasa Web Albums Username', 'kpicasa_gallery').':</th>';
print '<td><input name="kpg_username" type="text" id="kpg_username" value="'.htmlentities($username).'" size="40" /></td>';
print '</tr>';

// Picture engine
print '<tr valign="top">';
print '<th scope="row">'.__('Engine to show full-sized pictures', 'kpicasa_gallery').':</th>';
$chk = $picEngine == 'highslide' ? ' checked="checked"' : '';
print '<td><input type="radio" name="kpg_picEngine" value="highslide"'.$chk.'> <a href="http://vikjavev.no/highslide/" target="_blank">Highslide</a><br/>';
$chk = $picEngine == 'shadowbox' ? ' checked="checked"' : '';
print '<input type="radio" name="kpg_picEngine" value="shadowbox"'.$chk.'> <a href="http://www.shadowbox-js.com/" target="_blank">Shadowbox</a><br />';
$chk = $picEngine == 'thickbox' ? ' checked="checked"' : '';
print '<input type="radio" name="kpg_picEngine" value="thickbox"'.$chk.'> <a href="http://jquery.com/demo/thickbox/" target="_blank">Thickbox</a> - '.__('Video playback is buggy under IE, was not really made for this.', 'kpicasa_gallery').'<br />';
$chk = $picEngine == 'lightbox' ? ' checked="checked"' : '';
print '<input type="radio" name="kpg_picEngine" value="lightbox"'.$chk.'> <a href="http://www.huddletogether.com/projects/lightbox2/" target="_blank">Lightbox</a> - '.__('No video playback.', 'kpicasa_gallery').'<br />';
$chk = $picEngine == 'slimbox2' ? ' checked="checked"' : '';
print '<input type="radio" name="kpg_picEngine" value="slimbox2"'.$chk.'> <a href="http://www.digitalia.be/software/slimbox2" target="_blank">Slimbox 2</a> - '.__('No video playback.', 'kpicasa_gallery').'<br />';
$chk = $picEngine == '' ? ' checked="checked"' : '';
print '<input type="radio" name="kpg_picEngine" value=""'.$chk.'> '.__('None', 'kpicasa_gallery').' ('.__('I already have some other kind of mecanism', 'kpicasa_gallery').')</td>';
print '</tr>';

print '</table>';

// Album List
print '<h3>'.__('Album List', 'kpicasa_gallery').'</h3>';
print '<table class="form-table">';

print '<tr valign="top">';
print '<th scope="row">'.__('Number of albums to show per page', 'kpicasa_gallery').':</th>';
print '<td><input name="kpg_albumPerPage" type="text" id="kpg_albumPerPage" value="'.$albumPerPage.'" size="3" />';
print '<br/>'.__('Leave empty to show all albums on the same page', 'kpicasa_gallery').'</td>';
print '</tr>';

print '<tr valign="top">';
print '<th scope="row">'.__('Number of albums to show per row', 'kpicasa_gallery').':</th>';
print '<td><input name="kpg_albumPerRow" type="text" id="kpg_albumPerRow" value="'.$albumPerRow.'" size="3" /></td>';
print '</tr>';

print '<tr valign="top">';
print '<th scope="row">'.__('Thumbnails size', 'kpicasa_gallery').':</th>';
print '<td><select name="kpg_albumThumbSize" id="kpg_albumThumbSize">';
foreach(array(32, 48, 64, 72, 144, 160) as $value)
{
	$sel = ($albumThumbSize == false && $value == 160) || $albumThumbSize == $value ? ' selected="selected"' : '';
	print '<option value="'.$value.'"'.$sel.'>'.$value.' '.__('pixels', 'kpicasa_gallery').'</option>';
}
print '</select></td>';
print '</tr>';

print '<tr valign="top">';
print '<th scope="row">'.__('Show extra information', 'kpicasa_gallery').':</th>';
$chk = $albumSummary !== 0 ? ' checked="checked"' : '';
print '<td><label for="kpg_albumSummary"><input name="kpg_albumSummary" id="kpg_albumSummary" value="1" type="checkbox"'.$chk.'> '.__('Summary').'</label><br />';
$chk = $albumLocation !== 0 ? ' checked="checked"' : '';
print '<label for="kpg_albumLocation"><input name="kpg_albumLocation" id="kpg_albumLocation" value="1" type="checkbox"'.$chk.'> '.__('Location').'</label><br />';
$chk = $albumPublished !== 0 ? ' checked="checked"' : '';
print '<label for="kpg_albumPublished"><input name="kpg_albumPublished" id="kpg_albumPublished" value="1" type="checkbox"'.$chk.'> '.__('Published date').' ('.__('unfortunately Picasa doesn\'t provide it in the picture list feed').')</label><br />';
$chk = $albumNbPhoto !== 0 ? ' checked="checked"' : '';
print '<label for="kpg_albumNbPhoto"><input name="kpg_albumNbPhoto" id="kpg_albumNbPhoto" value="1" type="checkbox"'.$chk.'> '.__('Number of pictures').'</label><br />';
print '</tr>';

print '</table>';

// Picture List
print '<h3>'.__('Picture List', 'kpicasa_gallery').'</h3>';
print '<table class="form-table">';

print '<tr valign="top">';
print '<th scope="row">'.__('Number of pictures to show per page', 'kpicasa_gallery').':</th>';
print '<td><input name="kpg_photoPerPage" type="text" id="kpg_photoPerPage" value="'.$photoPerPage.'" size="3" />';
print '<br/>'.__('Leave empty to show all pictures on the same page', 'kpicasa_gallery').'</td>';
print '</tr>';
print '<tr valign="top">';
print '<th scope="row">'.__('Number of pictures to show per row', 'kpicasa_gallery').':</th>';
print '<td><input name="kpg_photoPerRow" type="text" id="kpg_photoPerRow" value="'.$photoPerRow.'" size="3" /></td>';
print '</tr>';

print '<tr valign="top">';
print '<th scope="row">'.__('Thumbnails size', 'kpicasa_gallery').':</th>';
print '<td><select name="kpg_photoThumbSize" id="kpg_photoThumbSize">';
foreach(array(72, 144, 288) as $value)
{
	$sel = ($photoThumbSize == false && $value == 144) || $photoThumbSize == $value ? ' selected="selected"' : '';
	print '<option value="'.$value.'"'.$sel.'>'.$value.' '.__('pixels', 'kpicasa_gallery').'</option>';
}
print '</select></td>';
print '</tr>';

print '</table>';


print '<p class="submit">';
print '<input type="submit" name="Submit" value="'.__('Save Changes', 'kpicasa_gallery').'" />';
print '</p>';

print '</form>';
print '</div>';

?>
