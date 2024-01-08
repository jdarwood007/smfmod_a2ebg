<?php

error_reporting(E_ALL);

use SMF\Db\DatabaseApi as Db;

// Hopefully we have the goodies.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF')) {
	$using_ssi = true;

	require_once dirname(__FILE__) . '/SSI.php';
} elseif (!defined('SMF')) {
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');
}

// Our column.
Db::$db->add_column(
	'{db_prefix}ban_groups',
	[
		'name' => 'easy_bg',
		'type' => 'smallint',
		'size' => '3',
		'default' => 0,
	],
);

if (!empty($using_ssi)) {
	echo 'If no errors, Success!';
}
