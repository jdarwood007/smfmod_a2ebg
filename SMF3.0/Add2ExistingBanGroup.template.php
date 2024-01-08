<?php

use SMF\Config;
use SMF\Utils;
use SMF\Lang;

function template_easyban_above()
{
	// Only allow selecting a ban group if it is new.
	if (!Utils::$context['ban']['is_new'] || empty(Utils::$context['ban_group_suggestions']))
		return;

	echo '
	<script type="text/javascript">
	function disableOtherFields()
	{
		const BanValue = $("#temp_ban_group").val();

		$("#admin_form_wrapper ban_group").remove();
		
		if (BanValue > -1)
			$("#admin_form_wrapper").append("<input type=\'hidden\' name=\'ban_group\' value=\'" + BanValue + "\' />");
  
		var visibility = $("#ban_group").val() == "-1" ? true : false;
		$("#manage_bans .windowbg>.settings").toggle(visibility);
		$("#manage_bans .windowbg>.ban_settings").toggle(visibility);';

	// Do we want to auto select some options?
	if (!empty(Config::$modSettings['aebg_auto_select']))
	{
		// Incase it isn't an array.
		$allOptions = array_flip(['main_ip_check', 'hostname_check', 'email_check', 'user_check']);
		if (!empty(Config::$modSettings['disableHostnameLookup']))
			unset($allOptions['hostname_check']);

		$autoSelects = is_array(Config::$modSettings['aebg_auto_select']) ? Config::$modSettings['aebg_auto_select'] : json_decode(Config::$modSettings['aebg_auto_select'], true);
		foreach ($allOptions as $elID => $dummy)
			echo '
		$("#', $elID, '").prop("checked", ', (in_array($elID, $autoSelects) ? 'true' : 'false'), ');';
	}
	
	echo '
	}
	</script>

		<div class="cat_bar">
			<h3 class="catbg">', Lang::$txt['aebg_add_existing'], '</h3>
		</div>
		<div class="windowbg noup">			
					<select id="temp_ban_group" name="ban_group" onchange="disableOtherFields()" id="ban_group">
						<option value="-1" selected="selected">', Lang::$txt['aebg_new_ban_group'], '</option>';

	foreach (Utils::$context['ban_group_suggestions'] as $id_ban_group => $ban_name)
		echo '
						<option value="', $id_ban_group, '" onselect="disableOtherFields()">', $ban_name, '</option>';
	echo '
					</select>
		</div>';
}

function template_easyban_below()
{
}

function template_easyban_edits_above()
{
}

function template_easyban_edits_below()
{
	echo '
			<div class="cat_bar">
				<h3 class="catbg">', Lang::$txt['aebg_add_existing'], '</h3>
			</div>
			<div class="windowbg noup">			
				<dl class="settings">
						<dt>
							<strong>', Lang::$txt['aebg_ban_group'], ':</strong><br />
							<span class="smalltext">', Lang::$txt['aebg_ban_group_desc'], '</span>
						</dt>
						<dd>
							<input type="checkbox" id="easy_ban_group" value="1" class="input_check"', !empty(Utils::$context['ban']['easy_bg']) ? ' checked="checked"' : '', ' />
						</dd>
				</dl>
			</div>

				<input type="submit" name="', Utils::$context['ban']['is_new'] ? 'add_ban' : 'modify_ban', '" value="', Utils::$context['ban']['is_new'] ? $txt['ban_add'] : Lang::$txt['ban_modify'], '" class="button" onclick="A2ebgSave()">

		<script type="text/javascript">
			$("#admin_form_wrapper").find("input[type=submit]").hide();

			function A2ebgSave()
			{
				if ($("#easy_ban_group").is(":checked"))
					$("<input>").attr({
						type: "hidden",
						name: "easy_ban_group",
						value: 1
					}).appendTo("#admin_form_wrapper");

				$("#admin_form_wrapper").find("input[type=submit]").trigger("click");
			}
		</script>
		';
}