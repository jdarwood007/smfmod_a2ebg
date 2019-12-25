<?php

function template_easyban_above()
{
	global $context, $txt;

	// Only allow selecting a ban group if it is new.
	if ($context['ban']['is_new'] && !empty($context['ban_group_suggestions']))
	{
		echo '
			<div class="cat_bar">
				<h3 class="catbg">', $txt['aebg_add_existing'], '</h3>
			</div>
			<div class="windowbg noup">			
						<select name="ban_group" onchange="disableOtherFields();" id="ban_group">
							<option value="-1" selected="selected">', $txt['aebg_new_ban_group'], '</option>';

		foreach ($context['ban_group_suggestions'] as $id_ban_group => $ban_name)
			echo '
							<option value="', $id_ban_group, '" onselect="disableOtherFields();">', $ban_name, '</option>';
		echo '
						</select>
			</div>';
	}

}

function template_easyban_below()
{
	global $context, $modSettings;

	// Only allow selecting a ban group if it is new.
	if ($context['ban']['is_new'] && !empty($context['ban_group_suggestions']))
	{
		echo '
		<script type="text/javascript">
		function disableOtherFields()
		{
			var visibility = $("#ban_group").val() == "-1" ? true : false;
			$("#manage_bans .windowbg>.settings").toggle(visibility);
			$("#manage_bans .windowbg>.ban_settings").toggle(visibility);';

		// Do we want to auto select some options?
		if (!empty($modSettings['aebg_auto_select']))
		{
			// Incase it isn't an array.
			$allOptions = array_flip(array('main_ip_check', 'hostname_check', 'email_check', 'user_check'));
			if (!empty($modSettings['disableHostnameLookup']))
				unset($allOptions['hostname_check']);

			$autoSelects = is_array($modSettings['aebg_auto_select']) ? $modSettings['aebg_auto_select'] : json_decode($modSettings['aebg_auto_select'], true);
			foreach ($allOptions as $elID => $dummy)
				echo '
			$("#', $elID, '").prop("checked", ', (in_array($elID, $autoSelects) ? 'true' : 'false'), ');';
		}
		
		echo '
		}
		</script>';
	}
}

function template_easyban_edits_above()
{
}

function template_easyban_edits_below()
{
	global $context, $txt;

	echo '
			<div class="cat_bar">
				<h3 class="catbg">', $txt['aebg_add_existing'], '</h3>
			</div>
			<div class="windowbg noup">			
				<dl class="settings">
						<dt>
							<strong>', $txt['aebg_ban_group'], ':</strong><br />
							<span class="smalltext">', $txt['aebg_ban_group_desc'], '</span>
						</dt>
						<dd>
							<input type="checkbox" id="easy_ban_group" value="1" class="input_check"', !empty($context['ban']['easy_bg']) ? ' checked="checked"' : '', ' />
						</dd>
				</dl>
			</div>

				<input type="submit" name="', $context['ban']['is_new'] ? 'add_ban' : 'modify_ban', '" value="', $context['ban']['is_new'] ? $txt['ban_add'] : $txt['ban_modify'], '" class="button" onclick="A2ebgSave()">

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

