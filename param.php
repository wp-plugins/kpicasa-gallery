<?php

$username     = get_option( 'kpg_username' );
$albumPerPage = get_option( 'kpg_albumPerPage' );
$photoPerPage = get_option( 'kpg_photoPerPage' );
$picEngine    = get_option( 'kpg_picEngine' );

// See if the user has posted us some information
// If they did, this hidden field will be set to 'Y'
if( $_POST[ 'kpg_save' ] == 'Y' )
{
	// Read their posted value
	$username     = $_POST[ 'kpg_username' ];
	$albumPerPage = intval( $_POST[ 'kpg_albumPerPage' ] );
	$photoPerPage = intval( $_POST[ 'kpg_photoPerPage' ] );
	$picEngine    = $_POST[ 'kpg_picEngine' ];
	
	$albumPerPage = $albumPerPage > 0 ? $albumPerPage : 0;
	$photoPerPage = $photoPerPage > 0 ? $photoPerPage : 0;
	$picEngine    = in_array($picEngine, array('lightbox', 'highslide', '')) ? $picEngine : 'highslide';

	// Save the posted value in the database
	update_option( 'kpg_username',     $username );
	update_option( 'kpg_albumPerPage', $albumPerPage );
	update_option( 'kpg_photoPerPage', $photoPerPage );
	update_option( 'kpg_picEngine',    $picEngine );

	// Put an options updated message on the screen
	print '<div id="message" class="updated fade"><p><strong>Options saved.</strong></p></div>';
}

$albumPerPage = $albumPerPage > 0 ? $albumPerPage : '';
$photoPerPage = $photoPerPage > 0 ? $photoPerPage : '';

// Now display the options editing screen
print '<div class="wrap">';
print '<h2>kPicasa Gallery Plugin Options</h2>';

// Form
print '<form name="form1" method="post" action="'.str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'">';
print '<input type="hidden" name="kpg_save" value="Y">';

print '<p class="submit">';
print '<input type="submit" name="Submit" value="Update Options &raquo;" />';
print '</p>';

print '<table class="optiontable">';
print '<tr valign="top">';
print '<th scope="row">Picasa Web Albums Username:</th>';
print '<td><input name="kpg_username" type="text" id="kpg_username" value="'.htmlentities($username).'" size="40" /></td>';
print '</tr>';

print '<tr valign="top">';
print '<th scope="row">Number of albums to show per page:</th>';
print '<td><input name="kpg_albumPerPage" type="text" id="kpg_albumPerPage" value="'.$albumPerPage.'" size="3" />';
print '<br/>Leave empty to show all albums on the same page</td>';
print '</tr>';

print '<tr valign="top">';
print '<th scope="row">Number of pictures to show per page:</th>';
print '<td><input name="kpg_photoPerPage" type="text" id="kpg_photoPerPage" value="'.$photoPerPage.'" size="3" />';
print '<br/>Leave empty to show all pictures on the same page</td>';
print '</tr>';

print '<tr valign="top">';
print '<th scope="row">Engine to show full-size pictures:</th>';
$chk = $picEngine == 'highslide' ? ' checked="checked"' : '';
print '<td><input type="radio" name="kpg_picEngine" value="highslide" id="kpg_picEngine_highslide"'.$chk.'> <label for="kpg_picEngine_highslide">Highslide</label> (<a href="http://vikjavev.no/highslide/" target="_blank">visit homepage</a>)<br/>';
$chk = $picEngine == 'lightbox' ? ' checked="checked"' : '';
print '<input type="radio" name="kpg_picEngine" value="lightbox" id="kpg_picEngine_lightbox"'.$chk.'> <label for="kpg_picEngine_lightbox">Lightbox</label> (<a href="http://www.huddletogether.com/projects/lightbox2/" target="_blank">visit homepage</a>)<br />';
$chk = $picEngine == '' ? ' checked="checked"' : '';
print '<input type="radio" name="kpg_picEngine" value="" id="kpg_picEngine_none"'.$chk.'> <label for="kpg_picEngine_none">None</label> (I already have some other kind of mecanism)</td>';
print '</tr>';
print '</table>';

print '<p class="submit">';
print '<input type="submit" name="Submit" value="Update Options &raquo;" />';
print '</p>';

print '</form>';
print '</div>';

?>
