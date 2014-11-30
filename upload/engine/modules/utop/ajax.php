<?php 
/*
####################################################
@copyright		(c) 2013 Nevex Group
@name			uTop
@version		5.1
@link			http://nevex.pw/
####################################################
*/

@error_reporting ( E_ALL ^ E_WARNING ^ E_NOTICE );
@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', false );
@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE );

define( 'DATALIFEENGINE', true );
define( 'ROOT_DIR', '../../..' );
define( 'ENGINE_DIR', '../..' );
define( 'UTOP_DIR', '.' );

include ENGINE_DIR . '/data/config.php';

require_once ENGINE_DIR . '/classes/mysql.php';
require_once ENGINE_DIR . '/data/dbconfig.php';
require_once ENGINE_DIR . '/modules/functions.php';

header("Content-type: text/html; charset={$config['charset']}");

dle_session();

$templateName = $_REQUEST['template'] ? $_REQUEST['template'] : "utop.tpl";
$limit = intval($_REQUEST['limit']);
$showGroups = $_REQUEST['showGroups'];
$showBanned = intval($_REQUEST['showBanned']);
$sortBy = $_REQUEST['sortBy'];
$sortOrder = $_REQUEST['sortOrder'];
$cacheTime = intval($_REQUEST['cacheTime']);
$lastVisitPeriod = intval($_REQUEST['lastVisitPeriod']);

if(! preg_match("/^[0-9,]*$/", $showGroups)) exit("Недопустимое значение параметра showGroups");
$showGroups = explode(",", trim($showGroups));

$_REQUEST['skin'] = trim(totranslit($_REQUEST['skin'], false, false));

if( $_REQUEST['skin'] == "" OR !@is_dir( ROOT_DIR . '/templates/' . $_REQUEST['skin'] ) ) {
	die( "Hacking attempt!" );
}

$config['skin'] = $_REQUEST['skin'] ? $_REQUEST['skin'] : $config['skin'];

//################# Определение групп пользователей
$user_group = get_vars( "usergroup" );
if( ! $user_group ) {
	$user_group = array();
	$db->query("SELECT * FROM " . USERPREFIX . "_usergroups ORDER BY id ASC");
	while ( $row = $db->get_row() ) {
		$user_group[$row['id']] = array ();
		foreach ( $row as $key => $value ) {
			$user_group[$row['id']][$key] = stripslashes($value);
		}
	}
	set_vars( "usergroup", $user_group );
	$db->free();
}

$utopAjax = true;

require_once UTOP_DIR . "/block.php";

exit();
