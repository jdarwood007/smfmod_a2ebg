<?php
/**
 * The Main class for Add to Existing Ban Group
 * @package A2EBG
 * @author SleePy <sleepy @ simplemachines (dot) org>
 * @copyright 2024
 * @license 3-Clause BSD https://opensource.org/licenses/BSD-3-Clause
 * @version 2.1
 */

#namespace SMF\Mod;

use SMF\Config;
use SMF\Db\DatabaseApi as Db;
use SMF\Lang;
use SMF\Theme;
use SMF\User;
use SMF\Utils;

class a2ebg
{
	public static function loadLanguage(): void
	{
		Lang::load('Add2ExistingBanGroup');
	}

	public static function addToGeneralModSettings(array &$config_vars): void
	{
		self::loadLanguage();

		$config_vars[] = [
				'select', 'aebg_auto_select',
				[
					'main_ip_check' => Lang::$txt['ban_on_ip'],
					'hostname_check' => Lang::$txt['ban_on_hostname'],
					'email_check' => Lang::$txt['ban_on_email'],
					'user_check' => Lang::$txt['ban_on_username']
				],
				'multiple' => true,
			];
	}

	// This is called when we edit a ban regardless of if its new or not.
	// This also gets called during saving the edit and we do some work here.
	public static function addToEditBans(array &$ban_info, int $isNew): void
	{
		$ban_info['easy_ban_group'] = empty($_POST['easy_ban_group']) ? '0' : '1';

		// We are adding or modifying a ban normally.
		if (empty($_POST['ban_group']))
		{
			if (!empty($_POST['bg']))
			{
				$request = Db::$db->query('', '
					SELECT
						bg.id_ban_group, bg.easy_bg
					FROM {db_prefix}ban_groups AS bg
					WHERE bg.id_ban_group = {int:current_ban}',
					[
						'current_ban' => (int) $_REQUEST['bg'],
					]
				);
				$row = Db::$db->fetch_assoc($request);
				Db::$db->free_result($request);

				Utils::$context['ban']['id'] = (int) $row['id_ban_group'];
				Utils::$context['ban']['easy_bg'] = $row['easy_bg'];
			}
			else
				Utils::$context['ban']['easy_bg'] = 0;

			Utils::$context['easy_ban_group'] = $ban_info['easy_ban_group'];
			return;
		}

		// This occurs when we are "adding" a ban.
		$request = Db::$db->query('', '
			SELECT
				bg.id_ban_group, bg.name, bg.ban_time, COALESCE(bg.expire_time, 0) AS expire_time, bg.reason, bg.notes, bg.cannot_access, bg.cannot_register, bg.cannot_login, bg.cannot_post, bg.easy_bg
			FROM {db_prefix}ban_groups AS bg
			WHERE bg.id_ban_group = {int:current_ban}',
			[
				'current_ban' => (int) $_REQUEST['ban_group'],
			]
		);
		$row = Db::$db->fetch_assoc($request);
		Db::$db->free_result($request);

		Utils::$context['ban'] = [
			'id' => (int) $row['id_ban_group'],
			'easy_bg' => $row['easy_bg'],
			'name' => $row['name'],
			'expiration' => [
				'status' => empty($row['expire_time']) ? 'never' : ($row['expire_time'] < time() ? 'expired' : 'one_day'),
				'days' => (int) ($row['expire_time'] > time() ? ($row['expire_time'] - time() < 86400 ? 1 : ceil(($row['expire_time'] - time()) / 86400)) : 0)
			],
			'reason' => $row['reason'],
			'notes' => $row['notes'],
			'cannot' => [
				'access' => !empty($row['cannot_access']),
				'post' => !empty($row['cannot_post']),
				'register' => !empty($row['cannot_register']),
				'login' => !empty($row['cannot_login']),
			],
			'is_new' => false,
			'hostname' => '',
			'email' => '',
		];

		// Setup info for later.
		$ban_info = Utils::$context['ban'];
		$ban_info['db_expiration'] = (int) ($ban_info['expiration']['status'] == 'never' ? 'NULL' : ($ban_info['expiration']['status'] == 'one_day' ? time() + 24 * 60 * 60 * $ban_info['expire_date'] : 0));
		$ban_info['cannot']['access'] = $ban_info['cannot']['access'] ? 1 : 0;
		$ban_info['cannot']['post'] = $ban_info['cannot']['post'] ? 1 : 0;
		$ban_info['cannot']['register'] = $ban_info['cannot']['register'] ? 1 : 0;
		$ban_info['cannot']['login'] = $ban_info['cannot']['login'] ? 1 : 0;

		// Fake it till you make it.
		$_REQUEST['bg'] = $ban_info['id'];
	}

	// Called when we edit a existing ban group
	public static function addToBanEditList(): void
	{
		self::loadLanguage();
		Theme::loadTemplate('Add2ExistingBanGroup');
		Utils::$context['template_layers'][] = 'easyban_edits';
	}

	// Called when we do a new ban group but not calling with a from user.
	public static function addToBanEditNew(): void
	{
		self::loadLanguage();
		Theme::loadTemplate('Add2ExistingBanGroup');

		// Normal way of doing a new one? Skip.
		if (empty(Utils::$context['ban']['from_user']))
		{
			Utils::$context['template_layers'][] = 'easyban_edits';
			return;
		}

		// Find our ban groups we can append.
		$request = Db::$db->query('', '
			SELECT id_ban_group, name
			FROM {db_prefix}ban_groups
			WHERE easy_bg = {int:one}
			ORDER BY name',
			[
				'one' => '1',
			]
		);
		while ($row = Db::$db->fetch_assoc($request))
			Utils::$context['ban_group_suggestions'][(int) $row['id_ban_group']] = $row['name'];
		Db::$db->free_result($request);

		Utils::$context['ban_group_auto_selects'] = is_array(Config::$modSettings['aebg_auto_select']) ? Config::$modSettings['aebg_auto_select'] : Utils::jsonDecode(Config::$modSettings['aebg_auto_select']);

		// Onions and layers...
		self::loadLanguage();
		Theme::loadTemplate('Add2ExistingBanGroup');
		Utils::$context['template_layers'][] = 'easyban';
	}

	// We are saving a ban.  Lets update that info if needed.
	public static function addToEditBansPost(): void
	{
		if (!isset(Utils::$context['easy_ban_group']))
			return;

		Db::$db->query('', '
			UPDATE {db_prefix}ban_groups
			SET
				easy_bg = {int:easy_bg}
			WHERE id_ban_group = {int:id_ban_group}',
			[
				'easy_bg' => Utils::$context['easy_ban_group'],
				'id_ban_group' => (int) Utils::$context['ban']['id'],
			]
		);
	}

	// Get our ban info for the modify ban
	public static function addToBanList(): void
	{
		// Main page seems to call this as well.
		if (empty(Utils::$context['ban']['id']))
			return;

		$request = Db::$db->query('', '
			SELECT
				bg.easy_bg
			FROM {db_prefix}ban_groups AS bg
			WHERE bg.id_ban_group = {int:current_ban}',
			[
				'current_ban' => (int) Utils::$context['ban']['id'],
			]
		);
		if (Db::$db->num_rows($request) == 0)
			ErrorHandler::fatalLang('ban_not_found', false);

		$row = Db::$db->fetch_assoc($request);
		Utils::$context['ban']['easy_bg'] = $row['easy_bg'];
		Db::$db->free_result($request);
	}
}
