<?php

// Now display the options editing screen
print '<div class="wrap">';
print '<h2>'.__('kPicasa Gallery Configuration', 'kpicasa_gallery').'</h2>';

print '<h3>'.__('Usage', 'kpicasa_gallery').'</h3>';
print '<p>'.__('Create post or a page and write <code>KPICASA_GALLERY</code> <strong>on its own line</strong>.', 'kpicasa_gallery').'</p>';
print '<p>'.sprintf(__('Please refer to the %sWordPress plugin page%s for advanced usage.'), '<a href="http://wordpress.org/extend/plugins/kpicasa-gallery/other_notes/" target="_blank">', '</a>').'</p>';

// Form
print '<form method="post" action="options.php">';
settings_fields('kpicasa_gallery_config');
$config = get_option('kpicasa_gallery_config');
$config['picEngine']      = $config['picEngine'] != null ? $config['picEngine'] : '';
$config['albumPerPage']   = $config['albumPerPage'] > 0 ? $config['albumPerPage'] : '';
$config['photoPerPage']   = $config['photoPerPage'] > 0 ? $config['photoPerPage'] : '';
$config['dateFormat']     = $config['dateFormat'] != null ? $config['dateFormat'] : 'Y-m-d';
$config['albumThumbSize'] = $config['albumThumbSize'] != null ? $config['albumThumbSize'] : 160;
$config['photoThumbSize'] = $config['photoThumbSize'] != null ? $config['photoThumbSize'] : 144;

// General settings
print '<h3>'.__('General settings', 'kpicasa_gallery').'</h3>';
print '<table class="form-table">';

// Username
print '<tr valign="top">';
print '<th scope="row">'.__('Picasa Web Albums Username', 'kpicasa_gallery').':</th>';
print '<td><input name="kpicasa_gallery_config[username]" type="text" value="'.$config['username'].'" size="40" /></td>';
print '</tr>';

// Picture engine
print '<tr valign="top">';
print '<th scope="row">'.__('Engine for full-sized pictures', 'kpicasa_gallery').':</th>';
$chk = $config['picEngine'] == 'highslide' ? ' checked="checked"' : '';
print '<td><input type="radio" name="kpicasa_gallery_config[picEngine]" value="highslide"'.$chk.'> <a href="http://vikjavev.no/highslide/" target="_blank">Highslide</a><br/>';
$chk = $config['picEngine'] == 'shadowbox' ? ' checked="checked"' : '';
print '<input type="radio" name="kpicasa_gallery_config[picEngine]" value="shadowbox"'.$chk.'> <a href="http://www.shadowbox-js.com/" target="_blank">Shadowbox</a><br />';
$chk = $config['picEngine'] == 'fancybox' ? ' checked="checked"' : '';
print '<input type="radio" name="kpicasa_gallery_config[picEngine]" value="fancybox"'.$chk.'> <a href="http://fancybox.net/" target="_blank">Fancybox</a> - '.__('Buggy video playback, author working on a new version.', 'kpicasa_gallery').'<br />';
$chk = $config['picEngine'] == 'thickbox' ? ' checked="checked"' : '';
print '<input type="radio" name="kpicasa_gallery_config[picEngine]" value="thickbox"'.$chk.'> <a href="http://jquery.com/demo/thickbox/" target="_blank">Thickbox</a> - '.__('Buggy video playback in IE, was not really made for this.', 'kpicasa_gallery').'<br />';
$chk = $config['picEngine'] == 'lightbox' ? ' checked="checked"' : '';
print '<input type="radio" name="kpicasa_gallery_config[picEngine]" value="lightbox"'.$chk.'> <a href="http://www.huddletogether.com/projects/lightbox2/" target="_blank">Lightbox</a> - '.__('No video playback.', 'kpicasa_gallery').'<br />';
$chk = $config['picEngine'] == 'slimbox2' ? ' checked="checked"' : '';
print '<input type="radio" name="kpicasa_gallery_config[picEngine]" value="slimbox2"'.$chk.'> <a href="http://www.digitalia.be/software/slimbox2" target="_blank">Slimbox 2</a> - '.__('No video playback.', 'kpicasa_gallery').'<br />';
$chk = $config['picEngine'] == '' ? ' checked="checked"' : '';
print '<input type="radio" name="kpicasa_gallery_config[picEngine]" value=""'.$chk.'> '.__('None', 'kpicasa_gallery').' ('.__('I already have some other kind of mecanism', 'kpicasa_gallery').')</td>';
print '</tr>';

print '</table>';

// Album List
print '<h3>'.__('Album List', 'kpicasa_gallery').'</h3>';
print '<table class="form-table">';

print '<tr valign="top">';
print '<th scope="row">'.__('Number of albums to show per page', 'kpicasa_gallery').':</th>';
print '<td><input name="kpicasa_gallery_config[albumPerPage]" type="text" value="'.$config['albumPerPage'].'" size="3" />';
print '<br/>'.__('Leave empty to show all albums on the same page', 'kpicasa_gallery').'</td>';
print '</tr>';

print '<tr valign="top">';
print '<th scope="row">'.__('Number of albums to show per row', 'kpicasa_gallery').':</th>';
print '<td><input name="kpicasa_gallery_config[albumPerRow]" type="text" value="'.$config['albumPerRow'].'" size="3" /></td>';
print '</tr>';

print '<tr valign="top">';
print '<th scope="row">'.__('Thumbnails size', 'kpicasa_gallery').':</th>';
print '<td><select name="kpicasa_gallery_config[albumThumbSize]">';
foreach(array(32, 48, 64, 72, 144, 160) as $value)
{
	$sel = $config['albumThumbSize'] == $value ? ' selected="selected"' : '';
	print '<option value="'.$value.'"'.$sel.'>'.$value.' '.__('pixels', 'kpicasa_gallery').'</option>';
}
print '</select></td>';
print '</tr>';

print '<tr valign="top">';
print '<th scope="row">'.__('Show extra information', 'kpicasa_gallery').':</th>';
$chk = $config['albumSummary'] == 1 ? ' checked="checked"' : '';
print '<td><input name="kpicasa_gallery_config[albumSummary]" value="1" type="checkbox"'.$chk.'> '.__('Summary').'<br />';
$chk = $config['albumLocation']  == 1 ? ' checked="checked"' : '';
print '<input name="kpicasa_gallery_config[albumLocation]" value="1" type="checkbox"'.$chk.'> '.__('Location').'<br />';
$chk = $config['albumPublished']  == 1 ? ' checked="checked"' : '';
print '<input name="kpicasa_gallery_config[albumPublished]" value="1" type="checkbox"'.$chk.'> '.__('Published date').' ('.__('unfortunately Picasa doesn\'t provide it in the picture list feed').')<br />';
$chk = $config['albumNbPhoto']  == 1 ? ' checked="checked"' : '';
print '<input name="kpicasa_gallery_config[albumNbPhoto]" value="1" type="checkbox"'.$chk.'> '.__('Number of pictures').'<br />';
$chk = $config['albumSlideshow']  == 1 ? ' checked="checked"' : '';
print '<input name="kpicasa_gallery_config[albumSlideshow]" value="1" type="checkbox"'.$chk.'> '.__('Picasa slideshow link (for best experience, select a picture engine that supports video playback)').'<br />';
print '</tr>';

print '<tr valign="top">';
print '<th scope="row">'.__('Date format', 'kpicasa_gallery').' (<a href="http://www.php.net/manual/en/function.date.php#function.date.parameters" target="_blank">'.__('PHP Manual', 'kpicasa_gallery').'</a>):</th>';
print '<td><input name="kpicasa_gallery_config[dateFormat]" type="text" value="'.$config['dateFormat'].'" size="10" /></td>';
print '</tr>';

print '</table>';

// Picture List
print '<h3>'.__('Picture List', 'kpicasa_gallery').'</h3>';
print '<table class="form-table">';

print '<tr valign="top">';
print '<th scope="row">'.__('Number of pictures to show per page', 'kpicasa_gallery').':</th>';
print '<td><input name="kpicasa_gallery_config[photoPerPage]" type="text" value="'.$config['photoPerPage'].'" size="3" />';
print '<br/>'.__('Leave empty to show all pictures on the same page', 'kpicasa_gallery').'</td>';
print '</tr>';
print '<tr valign="top">';
print '<th scope="row">'.__('Number of pictures to show per row', 'kpicasa_gallery').':</th>';
print '<td><input name="kpicasa_gallery_config[photoPerRow]" type="text" value="'.$config['photoPerRow'].'" size="3" /></td>';
print '</tr>';

print '<tr valign="top">';
print '<th scope="row">'.__('Thumbnails size', 'kpicasa_gallery').':</th>';
print '<td><select name="kpicasa_gallery_config[photoThumbSize]">';
foreach(array(72, 144, 288) as $value)
{
	$sel = $config['photoThumbSize'] == $value ? ' selected="selected"' : '';
	print '<option value="'.$value.'"'.$sel.'>'.$value.' '.__('pixels', 'kpicasa_gallery').'</option>';
}
print '</select></td>';
print '</tr>';

print '</table>';

print '<p class="submit">';
print '<input type="submit" class="button-primary" name="Submit" value="'.__('Save Changes').'" />';
print '</p>';

print '</form>';
print '</div>';

?>
