<?php
error_reporting(E_ALL);

// Hopefully we have the goodies.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
{
	$using_ssi = true;
	require_once(dirname(__FILE__) . '/SSI.php');
}
elseif (!defined('SMF'))
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

global $db_prefix, $modSettings, $func, $smcFunc;

if (version_compare('2.0 RC2', $modSettings['smfVersion']) > 0)
	exit('<b>Error:</b> Cannot install - Your SMF version is not sufficient enough.  Please upgrade to SMF 2.0 RC2 or higher');

// Our column.
$smcFunc['db_add_column']($db_prefix . "ban_groups", array('name'=> 'easy_bg', 'type'=>'smallint', 'size' => '3'));

if(!empty($using_ssi))
	echo 'If no errors, Success!';
?>