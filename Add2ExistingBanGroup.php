<?php
global $modSettings;

/**
 * The Main class for Add to Existing Ban Group
 * @package A2EBG
 * @author SleePy <sleepy @ simplemachines (dot) org>
 * @copyright 2019
 * @license 3-Clause BSD https://opensource.org/licenses/BSD-3-Clause
 * @version 2.0
 */
class a2ebg
{
	public static function loadLanguage(): void
	{
		loadLanguage('Add2ExistingBanGroup');
	}

	public static function hook_general_mod_settings(array &$config_vars): void
	{
		global $txt;

		self::loadLanguage();

		$config_vars[] = array(
				'select', 'aebg_auto_select',
				array('main_ip_check' => $txt['ban_on_ip'], 'hostname_check' => $txt['ban_on_hostname'], 'email_check' => $txt['ban_on_email'], 'user_check' => $txt['ban_on_username']),
				'multiple' => true,
			);
	}

	// This is called when we edit a ban regardless of if its new or not.
	// This also gets called during saving the edit and we do some work here.
	public static function hook_edit_bans(array &$ban_info, int $isNew): void
	{
		global $context, $smcFunc;

		$ban_info['easy_ban_group'] = empty($_POST['easy_ban_group']) ? '0' : '1';

		// We are adding or modifying a ban normally.
		if (empty($_POST['ban_group']))
		{
			if (!empty($_POST['bg']))
			{
				$request = $smcFunc['db_query']('', '
					SELECT
						bg.id_ban_group, bg.easy_bg
					FROM {db_prefix}ban_groups AS bg
					WHERE bg.id_ban_group = {int:current_ban}',
					array(
						'current_ban' => (int) $_REQUEST['bg'],
					)
				);
				$row = $smcFunc['db_fetch_assoc']($request);
				$smcFunc['db_free_result']($request);

				$context['ban']['id'] = $row['id_ban_group'];
				$context['ban']['easy_bg'] = $row['easy_bg'];
			}
			else
				$context['ban']['easy_bg'] = 0;

			$context['easy_ban_group'] = $ban_info['easy_ban_group'];
			return;
		}

		// This occurs when we are "adding" a ban.
		$request = $smcFunc['db_query']('', '
			SELECT
				bg.id_ban_group, bg.name, bg.ban_time, COALESCE(bg.expire_time, 0) AS expire_time, bg.reason, bg.notes, bg.cannot_access, bg.cannot_register, bg.cannot_login, bg.cannot_post, bg.easy_bg
			FROM {db_prefix}ban_groups AS bg
			WHERE bg.id_ban_group = {int:current_ban}',
			array(
				'current_ban' => (int) $_REQUEST['ban_group'],
			)
		);
		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		$context['ban'] = array(
			'id' => $row['id_ban_group'],
			'easy_bg' => $row['easy_bg'],
			'name' => $row['name'],
			'expiration' => array(
				'status' => empty($row['expire_time']) ? 'never' : ($row['expire_time'] < time() ? 'expired' : 'one_day'),
				'days' => $row['expire_time'] > time() ? ($row['expire_time'] - time() < 86400 ? 1 : ceil(($row['expire_time'] - time()) / 86400)) : 0
			),
			'reason' => $row['reason'],
			'notes' => $row['notes'],
			'cannot' => array(
				'access' => !empty($row['cannot_access']),
				'post' => !empty($row['cannot_post']),
				'register' => !empty($row['cannot_register']),
				'login' => !empty($row['cannot_login']),
			),
			'is_new' => false,
			'hostname' => '',
			'email' => '',
		);

		// Setup info for later.
		$ban_info = $context['ban'];
		$ban_info['db_expiration'] = $ban_info['expiration']['status'] == 'never' ? 'NULL' : ($ban_info['expiration']['status'] == 'one_day' ? time() + 24 * 60 * 60 * $ban_info['expire_date'] : 0);
		$ban_info['cannot']['access'] = $ban_info['cannot']['access'] ? 1 : 0;
		$ban_info['cannot']['post'] = $ban_info['cannot']['post'] ? 1 : 0;
		$ban_info['cannot']['register'] = $ban_info['cannot']['register'] ? 1 : 0;
		$ban_info['cannot']['login'] = $ban_info['cannot']['login'] ? 1 : 0;

		// Fake it till you make it.
		$_REQUEST['bg'] = $ban_info['id'];
	}

	// Called when we edit a existing ban group
	public static function hook_ban_edit_list(): void
	{
		global $context;

		self::loadLanguage();
		loadTemplate('Add2ExistingBanGroup');
		$context['template_layers'][] = 'easyban_edits';
	}

	// Called when we do a new ban group but not calling with a from user.
	public static function hook_ban_edit_new(): void
	{
		global $context, $smcFunc, $modSettings;

		self::loadLanguage();
		loadTemplate('Add2ExistingBanGroup');

		// Normal way of doing a new one? Skip.
		if (empty($context['ban']['from_user']))
		{
			$context['template_layers'][] = 'easyban_edits';
			return;
		}

		// Find our ban groups we can append.
		$request = $smcFunc['db_query']('', '
			SELECT id_ban_group, name
			FROM {db_prefix}ban_groups
			WHERE easy_bg = {int:one}
			ORDER BY name',
			array(
				'one' => '1',
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['ban_group_suggestions'][$row['id_ban_group']] = $row['name'];
		$smcFunc['db_free_result']($request);

		$context['ban_group_auto_selects'] = is_array($modSettings['aebg_auto_select']) ? $modSettings['aebg_auto_select'] : $smcFunc['json_decode']($modSettings['aebg_auto_select']);

		// Onions and layers...
		self::loadLanguage();
		loadTemplate('Add2ExistingBanGroup');
		$context['template_layers'][] = 'easyban';
	}

	// We are saving a ban.  Lets update that info if needed.
	public static function hook_edit_bans_post(): void
	{
		global $context, $smcFunc;

		if (!isset($context['easy_ban_group']))
			return;

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}ban_groups
			SET
				easy_bg = {int:easy_bg}
			WHERE id_ban_group = {int:id_ban_group}',
			array(
				'easy_bg' => $context['easy_ban_group'],
				'id_ban_group' => $context['ban']['id'],
			)
		);
	}

	// Get our ban info for the modify ban
	public static function hook_ban_list(): void
	{
		global $smcFunc, $context;

		// Main page seems to call this as well.
		if (empty($context['ban']['id']))
			return;

		$request = $smcFunc['db_query']('', '
			SELECT
				bg.easy_bg
			FROM {db_prefix}ban_groups AS bg
			WHERE bg.id_ban_group = {int:current_ban}
	',
			array(
				'current_ban' => $context['ban']['id'],
			)
		);
		if ($smcFunc['db_num_rows']($request) == 0)
			fatal_lang_error('ban_not_found', false);

		$row = $smcFunc['db_fetch_assoc']($request);
		$context['ban']['easy_bg'] = $row['easy_bg'];
		$smcFunc['db_free_result']($request);
	}
}
