<?php
$login_groups = $module->getSetting('login_groups');
$login_groups = !empty($login_groups) ? unserialize($login_groups) : array();

$admin_groups = $module->getSetting('admin_groups');
$admin_groups = !empty($admin_groups) ? unserialize($admin_groups) : array();

$enabled_groups = $module->getSetting('enabled_groups');
$enabled_groups = !empty($enabled_groups) ? unserialize($enabled_groups) : array();

$access_groups = $module->getSetting('access_groups');
$access_groups = !empty($access_groups) ? unserialize($access_groups) : array();

$TBGgroups = TBGTeam::getAll();


echo '<p>', __('Use this page to set up the connection details for your SMF Authenication. It is highly recommended that you read the online help before use, as misconfiguration may prevent you from accessing configuration pages to rectify issues.'), '</p>
<div class="rounded_box yellow" style="margin-top: 5px">
	<div class="header">', __('Important information'), '</div>
	<p>', __('When you enable SMF Authenication as your authentication backend in Authentication configuration, you will lose access to all accounts which do not also exist in your SMF installation. This may mean you lose administrative access.'), '</p>
	<p style="font-weight: bold; padding-top: 5px">', __('To resolve this issue, create a user with the same username as exists in SMF and make that one an administrator on TBG.'), '</p>
</div>
<form accept-charset="', TBGContext::getI18n()->getCharset(), '" action="', make_url('configure_module', array('config_module' => $module->getName())), '" enctype="multipart/form-data" method="post">
	<div class="rounded_box borderless mediumgrey', $access_level == TBGSettings::ACCESS_FULL ? ' cut_bottom' : '', '" style="margin: 10px 0 0 0; width: 700px;', $access_level == TBGSettings::ACCESS_FULL ? ' border-bottom: 0;' : '', '">
		<div class="header">', __('Connection details'), '</div>
		<table style="width: 680px;" class="padded_table" cellpadding=0 cellspacing=0 id="smf_settings_table">
			<tr>
				<td style="padding: 5px;"><label for="ssi_location">', __('SSI Location'), '</label></td>
				<td><input type="text" name="ssi_location" id="ssi_location" value="', $module->getSetting('ssi_location'), '" style="width: 100%;"></td>
			</tr>
			<tr>
				<td class="config_explanation" colspan="2">', __('Location of your SMF 2.0+ installtion as absolute path on your server ie: /home/user/public_html/smf'), '</td>
			</tr>

			<tr>
				<td style="padding: 5px;"><label for="password_salt">', __('Password Salt'), '</label></td>
				<td><input type="text" name="password_salt" id="password_salt" value="';

$salt = $module->getSetting('password_salt');
if (empty($salt) && isset($new_salt))
	echo $new_salt;
else
	echo $salt;

	echo '" style="width: 100%;"></td>
			</tr>
			<tr>
				<td class="config_explanation" colspan="2">', __('A random unique set of characters'), '</td>
			</tr>


			<tr>
				<td style="padding: 5px;"><label for="login_groups">', __('Login Groups'), '</label></td>
				<td><select name="login_groups[]" id="login_groups" multiple="multiple" style="width: 100%; height: 10em;">
					<option value="0"', (empty($login_groups) || in_array(0, $login_groups)  ? ' selected="selected"' : ''), '>All groups</option>';

foreach ($smf_groups as $id_group => $name)
	echo '
					<option value="', $id_group, '"', (!empty($login_groups) && in_array($id_group, $login_groups) ? ' selected="selected"' : ''), '>', $name, '</option>';

echo '</select></td>
			</tr>
			<tr>
				<td class="config_explanation" colspan="2">', __('Limit logins to selected groups, all others are annoymous users'), '</td>
			</tr>


			<tr>
				<td style="padding: 5px;"><label for="admin_groups">', __('Administrator Groups'), '</label></td>
				<td><select name="admin_groups[]" id="admin_groups" multiple="multiple" style="width: 100%; height: 10em;">';

foreach ($smf_groups as $id_group => $name)
	echo '
					<option value="', $id_group, '"', (!empty($admin_groups) && in_array($id_group, $admin_groups) ? ' selected="selected"' : ''), '>', $name, '</option>';

echo '</select></td>
			</tr>
			<tr>
				<td class="config_explanation" colspan="2">', __('Limit logins to selected groups, all others are annoymous users'), '</td>
			</tr>


			<tr>
				<td style="padding: 5px;"><label for="enabled_groups">', __('Enabled Access Groups'), '</label></td>
				<td><select name="enabled_groups[]" id="enabled_groups" multiple="multiple" style="width: 100%; height: 10em;">';

foreach ($smf_groups as $id_group => $name)
	echo '
					<option value="', $id_group, '"', (!empty($enabled_groups) && in_array($id_group, $enabled_groups) ? ' selected="selected"' : ''), '>', $name, '</option>';

echo '</select></td>
			</tr>
			<tr>
				<td class="config_explanation" colspan="2">', __('Select groups you wish to grant groups/teams on TBG'), '</td>
			</tr>
		</table>
	</div>';

if ($access_level == TBGSettings::ACCESS_FULL)
	echo '
	<div class="rounded_box iceblue borderless cut_top" style="margin: 0 0 5px 0; width: 700px; border-top: 0; padding: 8px 5px 2px 5px; height: 25px;">
		<div style="float: left; font-size: 13px; padding-top: 2px;">', __('Click "%save%" to save the settings', array('%save%' => __('Save'))), '</div>
		<input type="submit" id="submit_settings_button" style="float: right; padding: 0 10px 0 10px; font-size: 14px; font-weight: bold;" value="', __('Save'), '">
	</div>';

echo '
	<div class="rounded_box borderless mediumgrey', $access_level == TBGSettings::ACCESS_FULL ? ' cut_bottom' : '', '" style="margin: 10px 0 0 0; width: 700px;', $access_level == TBGSettings::ACCESS_FULL ? ' border-bottom: 0;' : '', '">
		<div class="header">', __('Group Access'), '</div>
		<table style="width: 680px;" class="padded_table" cellpadding=0 cellspacing=0 id="smf_settings_table">';

if (!empty($enabled_groups))
foreach ($enabled_groups as $smf_id)
{
	// Doesn't exist anymore, we can't save those settings.
	if (!isset($smf_groups[$smf_id]))
		continue;

	echo '
			<tr>
				<td style="padding: 5px;"><label for="access_groups[', $smf_id, ']">SMF: ', $smf_groups[$smf_id], '</label></td>
				<td><select name="access_groups[', $smf_id, '][]" id="access_groups[', $smf_id, ']" multiple="multiple" style="width: 100%; height: 10em;">';

	foreach ($TBGgroups as $id_group => $group)
		echo '
					<option value="', $id_group, '"', (!empty($access_groups[$smf_id]) && in_array($id_group, $access_groups[$smf_id]) ? ' selected="selected"' : ''), '>', $group->getName(), '</option>';

	echo '</select></td>
			</tr>';
}

echo '
		</table>
	</div>';

if ($access_level == TBGSettings::ACCESS_FULL)
	echo '
	<div class="rounded_box iceblue borderless cut_top" style="margin: 0 0 5px 0; width: 700px; border-top: 0; padding: 8px 5px 2px 5px; height: 25px;">
		<div style="float: left; font-size: 13px; padding-top: 2px;">', __('Click "%save%" to save the settings', array('%save%' => __('Save'))), '</div>
		<input type="submit" id="submit_settings_button" style="float: right; padding: 0 10px 0 10px; font-size: 14px; font-weight: bold;" value="', __('Save'), '">
	</div>';

echo '
</form>';