<?php

@error_reporting ( E_ALL ^ E_WARNING ^ E_NOTICE );
@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', false );
@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE );

@session_start();

define( 'DATALIFEENGINE', true );
define( 'ROOT_DIR', '.' );
define( 'ENGINE_DIR', './engine' );

require_once ENGINE_DIR . '/classes/mysql.php';
require_once ENGINE_DIR . '/data/dbconfig.php';
require_once ENGINE_DIR . '/data/config.php';
require_once ENGINE_DIR . '/modules/functions.php';
require_once ENGINE_DIR . '/modules/sitelogin.php';

header("Content-type: text/html; charset={$config['charset']}");

$title = "Установка UTOP 5.0";

function RemoveDir($path){
	if(file_exists($path) && is_dir($path)){
		$dirHandle = opendir($path);
		while (false !== ($file = readdir($dirHandle))) {
			if ($file!='.' && $file!='..'){
				$tmpPath=$path.'/'.$file;
				@chmod($tmpPath, 0777);
				if (is_dir($tmpPath)){
					RemoveDir($tmpPath);
			   	} else { 
	  				if(file_exists($tmpPath)){
	  					@unlink($tmpPath);
					}
	  			}
			}
		}
		closedir($dirHandle);
		if(file_exists($path)){
			@rmdir($path);
		}
	}
}

function checkUtopVersion() {
	global $db;
	$return = array();
	$return['ver'] = false; 
	
	// рание версии
	$old_config_path = ENGINE_DIR . "/data/utop_config.php";
	if(file_exists($old_config_path)) {
		$admin_link = $db->super_query("SELECT COUNT(*) AS count FROM " . USERPREFIX . "_admin_sections WHERE name = 'utop_adm'");
		include $old_config_path;
		if((count($utop_cfg) > 1) and ($admin_link['count'] > 0)) {
			$return['ver'] = 3;
			$return['config'] = $utop_cfg;
		}
	}

	// 4.0+
	$new_config_path = ENGINE_DIR . "/modules/utop/config.txt";
	if(file_exists($new_config_path)) {
		$admin_link = $db->super_query("SELECT COUNT(*) AS count FROM " . USERPREFIX . "_admin_sections WHERE name = 'utop'");
		$new_config_arr = (array)unserialize(file_get_contents($new_config_path));
		if((count($new_config_arr) > 1) and ($admin_link['count'] > 0)) {
			$return['ver'] = 4;
			if(file_exists(ENGINE_DIR . "/modules/utop/version.txt")) $return['ver'] = 4.2;
			$return['config'] = $new_config_arr;
		}
	}
	
	return $return;
}


$defaultConfig = array(
  'online' => '1',
  'max_user' => '12',
  'last_visit_period' => '30',
  'show_groups' => 
  array (
    0 => '1',
    1 => '2',
    2 => '3',
    3 => '4',
  ),
  'show_banned' => '1',
  'enable_hide_users' => '1',
  'allow_leave_top' => '1',
  'regdate_format' => 'j F Y G:i',
  'lastdate_format' => 'j F Y G:i',
  'offest_date_format' => '1',
  'sort_order' => 'DESC',
  'sort_type' => 'news_num',
  'sort_list' => 
  array (
    'news_num' => 'По публикациям',
    'comm_num' => 'По комментариям',
    'reg_date' => 'По регистрации',
    'lastdate' => 'По посл. посещению',
  ),
  'allow_sort' => '1',
  'sql_rows' => '*',
  'sql_join' => '',
  'cache' => '1',
  'cache_max_time' => '10',
  'nick_format_mode' => 'group_settings',
  'group1_color' => 'red',
  'group2_color' => 'blue',
  'group3_color' => 'green',
  'group4_color' => '',
);


$check_version = checkUtopVersion();
if($check_version['ver'] > 1) {
$update_message_box = <<<HTML
<br /><br />
<div class="messageBox">
Обнаружена предыдущая версия. Будет выполнено обновление модуля.
</div>
HTML;
}
if($_REQUEST['step']){ 
	$step = intval($_REQUEST['step']);
	$next = 1;
	$back = 1;
	######## НАЧАЛО ########
	if($step == 1){
		$content = <<<HTML
		<h1>Начало</h1>
		Добро пожаловать в мастер установки "UTOP 5". Данный мастер проверит совместимось модуля и выполнит установку/обновление модуля.
		<br /><br />
		Для продолжения нажмите кнопку "Далее". 
HTML;
		$back = 0;
	}
	
	######## ПРОВЕРКА ########
	if($step == 2){

		$uTop_files = array();
		$uTop_files[] = array("", true);
		$uTop_files[] = array("ajax.php", false);
		$uTop_files[] = array("block.php", false);
		$uTop_files[] = array("cache", true);
		$uTop_files[] = array("utop.class.php", true);
		$uTop_files[] = array("cache.class.php", false);
		$uTop_files[] = array("admin/panel.php", false);
		
		$fatal_error = false;
		$warning = false;
		
		$filesCheck = "";
		
		foreach ($uTop_files as $file) {
			$server_path = ROOT_DIR . "/engine/modules/utop/" . $file[0];

			if(file_exists($server_path)) {
				$result = '<span class="ok">ОК</span>';
				if((! is_writable($server_path)) and $file[1]){
					$warning = true;
					$file_exists = '<span class="error">Недоступен для записи</span>';
				}
			} else {
				$fatal_error = true;
				$result = '<span class="error">Отсутствует</span>';
			}
			
			$filesCheck .= "engine/modules/utop/{$file[0]} – {$result}<br />";
		}

		if(ini_get('short_open_tag')){
			$phpShortTag = "<span class=\"ok\">Включен</span>";
		} else {
			$warning = true;
			$phpShortTag = "<span class=\"error\">Отключен</span>";
		}

		$content = <<<HTML
		<h1>Проверка</h1>
		<b>Файлы:</b><br />
		{$filesCheck}
		<br />
		<b>Конфигурация PHP:</b><br />
		short_open_tag – {$phpShortTag}
HTML;

		if($fatal_error) $next = 0;
		$isLocalHost = ($_SERVER['REMOTE_ADDR'] == "127.0.0.1") ? 1 : 0;
		$result = @file_get_contents("http://api.nevex.pw/utop/install.php?domain={$_SERVER['HTTP_HOST']}&local={$isLocalHost}");
		if(! $result){
		$content = <<<HTML
		<h1>Ошибка</h1>
		Не удалось выполнить проверку сайта. Попробуйте позже.
HTML;
		
} elseif($result == "badsite"){
		RemoveDir(ENGINE_DIR . "/modules/utop");
		@unlink(ENGINE_DIR . "/inc/utop.php");
		@unlink(__FILE__);
		$content = <<<HTML
		<h1>Ой...</h1>
		Установка модуля на данный сайт запрещена разработчиком.
HTML;
		$next = 0;
		$back = 0;
		
}

		
		
	}
	######## УСТАНОВКА ########
	if($step == 3){
		$tplExists = file_exists(ROOT_DIR . "/templates/{$config['skin']}/utop.tpl");
		$versionCheck = checkUtopVersion();
		$versionCheck = $versionCheck['ver'] ? "<b>Обнаружена предыдущая версия {$versionCheck['ver']}. Будет выполнено обновление.</b><br /></br>" : "";
		
		$content = <<<HTML
		<h1>Установка</h1>
		Всё готово к установке.<br /><br />
		{$versionCheck}
		Чтобы выполнить установку, нажмите кнопку "Далее". 
HTML;

	}
	
	######## ЗАВЕРШЕНИЕ ########
	if($step == 4){
		
		##############################
			$db->query("DELETE FROM " . PREFIX . "_admin_sections WHERE name IN ('utop', 'utop_adm')");
			$db->query("INSERT INTO " . PREFIX . "_admin_sections (id, name, title, descr, icon, allow_groups) VALUES (NULL, 'utop', 'UTOP 5', '&copy; 2013 Nevex Group', 'utop5.png', '1')");

		// конвертация конфига предыдущих версий.
		if($check_version['ver'] < 4) {
		if($check_version['config']['utop_type']) {
			if($check_version['config']['utop_type'] == "on") {
				$defaultConfig['online'] = "1";
			} else {
				$defaultConfig['online'] = "0";
			}
		}

		if($check_version['config']['sort_type']) $defaultConfig['sort_type'] = $check_version['config']['sort_type'];
		if($check_version['config']['max_user']) $defaultConfig['max_user'] = $check_version['config']['max_user'];
		if($check_version['config']['date_reg']) {
			$defaultConfig['regdate_format'] = $check_version['config']['date_reg'];
			$defaultConfig['lastdate_format'] = $check_version['config']['date_reg'];
		}
		$show_user_group = array();
		for($i = 1; $i<=4; $i++) {
			if($check_version['config']["group{$i}_where"] == $i) {
				$show_user_group[] = $i;
			}
			
			if($check_version['config']["group{$i}_color"]) {
				$defaultConfig["group{$i}_color"] = $check_version['config']["group{$i}_color"];
			}
		}
		if(count($show_user_group) > 0) $defaultConfig['show_groups'] = $show_user_group;

		$hidden_users_list = array();
		$sql_hidden = $db->query("SELECT user_id, name FROM " . USERPREFIX . "_users WHERE name IN ('{$db->safesql($check_version['config']['user1_del'])}', '{$db->safesql($check_version['config']['user2_del'])}', '{$db->safesql($check_version['config']['user3_del'])}', '{$db->safesql($check_version['config']['user4_del'])}', '{$db->safesql($check_version['config']['user5_del'])}')");
		while($row = $db->get_row($sql_hidden)) {
			$hidden_users_list[] = $row['user_id'];
		}

		$hidden_users_file = fopen(ENGINE_DIR . "/modules/utop/hidden_users.txt", "w+" );
		fwrite($hidden_users_file, implode(",", $hidden_users_list));
		fclose($hidden_users_file);

		} elseif($check_version['ver'] >= 4) {
			$defaultConfig = $defaultConfig + $check_version['config'];
		}
		// ========================================


		$defaultConfigig_file = ENGINE_DIR . '/modules/utop/config.php'; // файл конфигрурации модуля
		$utop_cfg_file = fopen($defaultConfigig_file, "w+" );
		$defaultConfig['version'] = "5.0";
		$contents = "<?php\n# uTop Config File\n# (c) 2013 Nevex Group\n\n\$uconf = " . var_export($defaultConfig, true) . ";\n";
		fputs($utop_cfg_file, $contents);
		fclose($utop_cfg_file);

		// защита от повторного создания поля таблицы
		$alter_table = true;
		$db->query("SHOW COLUMNS FROM " . USERPREFIX . "_users");
		while($row = $db->get_row()) {
			if($row['Field'] == "utop_join") {
				$alter_table = false;
				break;
			}
		}

		if($alter_table) $db->query("ALTER TABLE " . USERPREFIX . "_users ADD utop_join INT( 1 ) NOT NULL DEFAULT '1'");

		$next = 0;
		$back = 0;
		
		$content = <<<HTML
		<h1>Завершение</h1>
		Поздравляем! Установка выполнена успешно.<br />
		Теперь у Вас стоит самая последняя версия UTOP.<br />
		<br />
		<b>Не забудь удалить файл installutop.php!</b><br /><br />
		<a href="{$config['http_home_url']}{$config['admin_path']}?mod=utop" target="_blank">Админцентр UTOP</a>.
HTML;

		$installCode = @file_get_contents("http://api.nevex.pw/utop/install.php?domain={$_SERVER['HTTP_HOST']}&act=install");
		if(trim($installCode) != "error"){
		
			$installCode = trim($installCode);
			$utopClass = ltrim(@file_get_contents("http://api.nevex.pw/utop/install.php?key={$installCode}"));
			$f = fopen(ENGINE_DIR . "/modules/utop/utop.class.php", "w");
			fputs($f, $utopClass);
			fclose($f);
		
		} elseif($installCode){
			exit("error");
		} else {
		
		$content = <<<HTML
		<h1>Ошибка</h1>
		Произошла ошибка при установке. Попробуйте ещё раз.
HTML;
		$back = 1;
		}

		##############################
		
	}
	
	echo "{$back}<!>{$next}<!>{$content}";
	exit();
} 
?><!DOCTYPE html>
<html>
<head>
<title><?=$title?></title>
<style type="text/css">
@import url(http://fonts.googleapis.com/css?family=Open+Sans:300,600&subset=latin,cyrillic);

html, body, div, span, object, iframe,
h1, h2, h3, h4, h5, h6, p, blockquote, pre,
a, abbr, big, code, em, img, q, s,
small, strike, strong, sub, sup,
b, u, i, center,
dl, dt, dd, ol, ul, li,
fieldset, form, label,
table, tbody, tfoot, thead, tr, th, td,
article, aside, details, embed, footer, header, hgroup, nav {
	margin: 0;
	padding: 0;
	border: 0;
	font-size: 100%;
	font: inherit;
	vertical-align: baseline;
}
/* HTML5 display-role reset for older browsers */
article, aside, footer, header, hgroup, menu, nav {
	display: block;
}
body {
	line-height: 1;
	font: normal 14px/16px 'Open Sans', tahoma, arial;
	background: #587793 url('engine/modules/utop/admin/images/bg.jpg');
	position: relative;
}
b { font-weight: bold; }
i { font-style: italic; }
ol, ul {
	list-style: none;
}
blockquote:before, blockquote:after,
q:before, q:after {
	content: '';
	content: none;
}
table {
	border-collapse: collapse;
	border-spacing: 0;
}

a, a:visited { color: #1A4871; text-decoration: none; font-weight: bold; }
a:hover { color: #6889A8; }

.wrapper {
	width: 651px;
	margin: 0 auto;
	padding-top: 100px;
}

.header {
	color: #fff;
	font-size: 22px;
	line-height: 22px;
	font-weight: bold;
	text-shadow: 0 1px 1px rgba(0,0,0,0.4);
	padding-bottom: 15px;
}

.mainBox {
	background: #fff;
	box-shadow: 1px 4px 10px rgba(0,0,0,0.3);
	overflow: hidden;
}

h1 {
	color: #1A4871;
	font-size: 24px;
	display: block;
	margin-bottom: 10px;
	line-height: normal;
}

input.styled, textarea.styled {
	background: url('engine/modules/utop/admin/images/bg-inputtext.png') repeat-x #FFFFFF;
	border: 1px solid #A6A6A6;
	border-radius: 2px;
	-moz-border-radius: 2px;
	-webkit-border-radius: 2px;
	color: #5E5E5E;
	font-family: "lucida grande","lucida sans unicode",arial,verdana,tahoma,sans-serif;
	font-size: 10pt;
	padding: 3px;
	transition:all 0.2s ease;
	-moz-transition:all 0.2s ease;
	-webkit-transition:all 0.2s ease;
}
.styled:hover, textarea.styled:hover {border: 1px solid #7A7A7A;}

.styled:focus, textarea.styled:focus {
	border: 1px solid #007EBF;
	box-shadow: 0 0 7px #007EBF;
	-moz-box-shadow: 0 0 7px #007EBF;
	-webkit-box-shadow: 0 0 7px #007EBF;
}

.styled.error {
	border: 1px solid #BF0005;
	box-shadow: 0 0 7px #BF0005;
	-moz-box-shadow: 0 0 7px #BF0005;
	-webkit-box-shadow: 0 0 7px #BF0005;
}

textarea.styled {width:90%; height:100px; padding:0px; resize:none;}
input.small {width:100px; text-align:center;}
input.big {width:80%;}

.fbutton, .ui-button {
	width: auto;
	min-width:60px;
	border: 1px solid #212121;
	text-shadow:0px -1px 0px #000;
	color: #fff;
	outline: none;
	-webkit-border-radius: 2px;
	-moz-border-radius: 2px;
	border-radius: 2px;
	cursor: pointer;
	background:url('engine/modules/utop/admin/images/highlight_faint.png') repeat-x #323232;
	-moz-transition:all 0.2s ease;
	-webkit-transition:all 0.2s ease;
	transition:all 0.2s ease;
	font: inherit;
	position: relative;
	padding: 4px 12px;
	box-shadow: 0 1px 2px rgba(0,0,0,0.4);
}
.fbutton:hover, .ui-button:hover { background-color: #535353; }
.fbutton:active, .ui-button:active { background-color: #323232; top: 1px; }
.buttonPane {padding: 12px; border-top: 1px solid #F5F5F5; text-align: right; }

.fbutton.disabled, .fbutton.disabled:hover, .fbutton.disabled:active {
	color: #676767;
	cursor: default;
	text-shadow: none;
	background-color: #323232;
}

.loaderLabel {
	background: url('images/loader.gif') no-repeat 4px 50%;
	padding: 5px;
	padding-left: 24px;
	color: #8E8E8E;
}
.loaderLabel.complete { background-image: url('engine/modules/utop/admin/images/icon_check.png'); }

.switcher {
	width: 100px;
	height: 22px;
	background: #EDEDED;
	cursor: pointer;
}
.switcher .slider {
	display: block;
	width: 50px;
	height: 22px;
	line-height: 22px;
	text-align: center;
	color: #fff;
	background: #B2B2B2;
	font-weight: bold;
	font-size: 12px;
	text-transform: uppercase;
	transition: all 0.2s ease;
	-moz-transition: all 0.2s ease;
	-webkit-transition: all 0.2s ease;
}
.switcher.enabled .slider {
	margin-left: 50px;
	background: #557792;
}

.warn {
	background: #F5E8E8;
	border: 1px solid #EED3D7;
	color: #B94A48;
	padding: 8px;
	text-shadow: 0 1px 0 rgba(255,255,255,0.6);
	margin-bottom: 10px;
}

.ui-dialog {
	background: #fff;
	border: 2px solid #B9B9B9;
	box-shadow: 0 1px 10px rgba(0,0,0,0.3);
}
.ui-dialog-titlebar {
	color: #1A4871;
	font-size: 22px;
	line-height: normal;
	padding: 4px 8px;
	padding-bottom: 0;
}
.ui-dialog-titlebar-close { display: none; }
.ui-dialog-content { padding: 12px; }
.ui-dialog-buttonset { text-align: right; padding: 8px; padding-top: 0; }
.ui-dialog-buttonset .ui-button { min-width: 70px; margin-left: 4px; }
.ui-widget-overlay {
	position: fixed !important;
	top: 0;
	left: 0;
	background: rgba(0,0,0,0.5);
	z-index: 99;
}

.steps {
	height: 36px;
}
.steps > span {
	display: block;
	float: left;
	width: 162px;
	height: 36px;
	line-height: 36px;
	text-indent: 10px;
	color: #8E8E8E;
	position: relative;
	border-right: 1px solid #F5F5F5;
}
.steps > span.active { color: #272727; }
.steps > span.active:after {
	content: "";
	display: block;
	height: 5px;
	width: auto;
	position: absolute;
	bottom: -5px;
	left: 0;
	right: 0;
	background: #557792;
}
.steps .num { font-size: 1.3em; font-weight: bold; }
.steps > span:last-child { border-right: none; }

.progress {
	font-size: 0;
	background: #EDEDED;
	width: 100%;
	height: 5px;
}
.boxContent { padding: 12px 20px; min-height: 250px; }
.copyright { margin-top: 5px; }

span.ok { color: #00BD39; font-weight: bold; }
span.error { color: #FF2800; font-weight: bold; }
</style>
<script type="text/javascript">
phpSelf = "<?=$_SERVER['PHP_SELF']?>";
</script>
</head>
<body>
	<div class="wrapper">
		<div class="header"><?=$title?></div>
		<div class="mainBox">
			<div class="steps">
				<span class="step1"><span class="num">1</span> Начало</span>
				<span class="step2"><span class="num">2</span> Проверка</span>
				<span class="step3"><span class="num">3</span> Установка</span>
				<span class="step4"><span class="num">4</span> Завершение</span>
			</div>
			<div class="progress"></div>
			<div class="boxContent">
				<div class="contentAjax"><h1>Подождите...</h1></div>
			</div>
			<div class="buttonPane">
				<button class="fbutton goBack" style="display: none;" type="button">Назад</button>
				<button class="fbutton goNext" type="button">Далее</button>
			</div>
		</div>
		<div class="copyright">
			<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJYAAAAYCAYAAAAYuwRKAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAadEVYdFNvZnR3YXJlAFBhaW50Lk5FVCB2My41LjEwMPRyoQAAC8hJREFUaEPtmglQVdcZx3Ej6nMBXOpGXNKauDStYWxpUlPbjE21sUNNa6YmxTRGm3RqOzp1TBw7RmNtYlqVxhh1nOqMxipURRQlolQUQSNKKQYFQQV3BUURELf2939zLr1e7+M9mjaS9N2ZM/e8e7Zv+Z//950DISHBJ2iBoAWCFvhMWqC2tnbVP10evr/fUIWuX78ed+fOnWpKLeWWNe2NGzeWN3SuYP/PtgVaLViwIMoNWDdv3ryAai0boF5LgFV069ata5Tq27dv11JuArDb8+bNe6yBczVg2WDXxmiBJgjVtbS0NNkNXOXl5XGBCn3x4sU3YaYymK6CdyWlCnDWnDhxYqPWoGit4PN/ZAHP5MmTh7oBC3CcxA4PBGCL0Kqqqo9qamrOU8pgrssA7IoANmnSpG8xvnUAcwS7fM4s4GWtM2fO7HAD14ULF2b70/fcuXOTANQJwHWmurr6HOUCv8tLSkoSGdslyFb+LPj5bffExcXF+Ejij6F2aD2qh1ZWVqZeu3atmHcJ75MWwObPnz+8Praib3/ysNNal1zsDqEzzb4O7cP5fN20V/D7G1Y731oC3pfJ5wpgxhVO+Wh7Q/mdxrKGZPuSLx10gIFl33VrZ5M8pfbG5HrsO1R2k82MbW4h45LGJKMlS1Mq3WGnTDdwwUhTfAl98uTJWBTNu3LlSsHVq1flwOMU0rbSDYatNLfb0xRn/iMtLe1FGvtPmDDhSX4fr6io8Dq4sLCwJ8arSk5OjlX7/v37pwCiMurN1E7fbUeOHHl5586dL1y6dOmvfLIfNB5ArndiYmKi+vXrNwj54wFIBn3cZGnFmgm0ZxcUFHzTIWizw4cPj0SvtXxv1Rgch22nYpdrmZmZE9FNh6KBU6dO/Q4RZ5HDBo1BXK8MbdesWTPWDVjs/nza3VirOU5ZA6hyeX9MOUK9EOWLVq1aNYoxnnq0U1sEJZLSkdIjKSlpDA7+u5wIyOcBjj9Q70VROO0FgLPz8vKeoa6crT2l69atW8cYYNkdr7xQ7V9QH0pvc8p1k0drbQDAz7P2Lgf4muXk5MS4zH9fnHbo0KH+bK5L0dHRXzR2kd3CjX1ky0Dy4U9ddjFBJCfBHDdwnTp16hWnRMePH/8BIMrE8AcpuZcvX86j5MNW64yyXnbxA67mpr3FrFmzBjNPEr87AOacGTNmfI26BYawoqKiubDPm3zTvAJXy3ocX2fkYcOGRQLKA/Rv6yJLKwEnMTExdteuXU9ZjGn63QMshWMiUIUJQbdhzmnqi8PzAWYP+/zo8IoVonyNo89zzlDLXEcV6h2yNsXWybm5uRP4rnVa2NqVJ9fpy/hc5JqjUAm7ieVDNJ9SDVv4RNzqF6w5+H7VaRvNY31TXf11T2nT3TV9cPN3OxjgV27AYtIchzLNAeH7OCWjrKzsI+rZAiXAyt24ceNPbIDwg6265ibnz5+fjXOf5Usn8qYzvLUjrWuK0IyMjOcVtvhmsadfRoFBe+O8PevXr/+eD5m8wNq3b58YtivzL+TQ8bQbsPjeEwNfTElJ+SntAyZOnPi4mJCxMQB+OrovtcnbAufmrF69uh9M/LDbOEL5CPq3Q77927Zt8+aA2GA6TP0Lqs5N0BqbFLuAymlfD/1KkOvyqFGjHouNjR2gNQDvQeRLGTFihDbrgE2bNo1VSEXuYVqLuoBlX9PDmEPGZt45dUeJPj/S+MWLF8fwuwK2/3kgDhZ7PCjWcQMXd1LPWZOQAw3BaR8ycTplD/dYWQIYbKV8RCHIH1vdJY9onnUXGjCFAWSF33a2Ts2zsrKepY/yKSvs+QSW/S8KsEXq+PHjH2Gca44lYIn5zHpiy3Qjv31+D/2WHjx4cCxtPY2ckQI7eq8dPHhwXxmf716mEZgAVoFswYZzHYcuf9E8K1asiEbGzTNnzgwjn/uAbwr9ztSjDXJ9zHeFvrrH7ifaf0NDGwFg+/btijAPUtqTj0VjDx3CelOUGnSg9MKe43Tw0pyMqeR9F7BMCqSI4Z1z+fLlP7Tp3pPUaSSRIJNvdva0i3dXPWz37t3T3YDFJFn0FPiaAqQ5lK0gfge77G+UXfzOSE1NVbLdxufsLg0HDhxobUKQjCZFtFtKTd0a0Yww8GNHvuMLWAKenNN33LhxQ0nA32UHn/Yhk5exDLC0GSK2bNkyCAfrz1D2+cPFQD5yULF5JODIBHi6t2sC2P7Iuq9R7+xnnPQV+N7DfstWrlzZn9/2DWWJ7WYT9VOOOvDs2bNJMOob1DsC8IsCjnF4s9OnT7/Ohtem7WyzgTZAJLIJrF38AKstG72QftpQFuAFpu5mfH25dN2SGtATwxa5GZGT03DY5VGckYAymykpgCtVAEOxlTISxcqbfPjy358V+1nrT3xRzhRmWtqIsQBFJ9sEzTn5zCavm8k3v4xl+mg+ydOLNbaT+CscOh8nsMRqndBvTn5+/vdtOVxHgHMEmRQ6BtrKl6k/pHWys7NfNSdIha2SIUOGiCHC/YyTU1ofO3bsbcJR5ciRI7vx2+2vFG1glyzJ5FBA4OrCxk40tulo2N5itlDs9lsDLGdyH2ZYsJs/xjJz6kBkf9rbWM2vr9UhnOP9W27AwnBp7IDXUCSedyJlE4InC2Ac/X/G2AaxFTv19WnTpskIFqi0vocdv57ye5u0obBY8bp1677Nt4BzLOOkcBgxjbClezBniHYCS0sKuB0ARLLCr2HJTuj4Z8CpZF2yyqFWETi0mbqKGclTnxaQNYfs4WdcCDlaHxxbVVxcPI9xuqZxezz45FVscNhNB9nLACvCgMBikdDNmzc/aQB0F7AA21jsksJ83cRI8fHxShesxwPQr8sXKgJeenr6o3bB0OvX+E8HtYAYS2PluN4YttQNXCiRxClxNUlmPO91AhhKLWOMqDZgtmIHxs2dO1e7wLkTWiUkJDyjO1PW+K4EwrlxxjhiMStXcg2FGOklwrZ3Z4sRGfs7cxgQIJx5lhuwNDQcEEfh6D0GWOGLFi16QjLBzkq6Q1inm3I57PR1Y/BwJci6j9u7d+9LfPOeXP2Ma6ITMI/uCgcqQef0q4tl5yO79pAsAq+lnzoRRYYqdbCA5WARLwPrGgd7r8UeskEIebEun2s3bNggXTqj0wfMuQN/RqgP9W3yPW3aPG0VXgFXOetHabxsIFssXLhQd38NuuOLYHe+5+OESNQrWQniBa41CJNAgihDBsxWIH2K29zm6C1jdBM7WLfnfD8xevTorxhFdbzPdY43R+YWOGkQxj9mjsV3dP81ZswYhSwngGUjX8CSDJ1x1lu656KuXdlFgJFDrBtv2mYZvb1XIEpwTVjpzm9vPlrfOOz4SxwudlP+0nXJkiWPy4HU3Q4/cnIfEzZrLP11kXz06NEFGk+JcAlP7aKioh7RlYt13cAaVwz4JadHhw+Ns2zGBvkQuXT48AJLdfpPsv4dijVrzHit6esSXPa95xFt9tHf/twAoNMfRlkhgKGoEkMxSaBsJUZUf3uuonpfOUHOpii5VO6ib0podSkoJfTIycpF+lG+ShlM0bFa9zthFF1TKL/ROPXpYxtrpqh7aS0xrcY5nSkZtI7WVT8vk1N0NaD19NbJy9qx3g1BUUKtC0vr8TXOYms5V3WNlw4KoW53bppPckofhS3JoPKw+aYx3g1g3jYRvLmm7Cl7WLL3om6FR/nDsrXm1iaWzb2hEFAqBGtz2nWXLer7c599/bvqHaHZ5W7Agv4L2c1LAdYycoQXXRTxOalpkED2PMWq22lVBwnru5MNpbBzvOUMOUj97TlQffJoTV/XI5LTLpM2j31dp2G1thsofI2Tjvb//vA13i6/m+3s+ZOvnMduM8noJAK7zSSTNY/HsKByYbvugRLJPbaXsA+RM5S7gYtkcyn5wDv0EUP8x4v4Q2Cw/b5bwAJWwEl6IBJ3ItQp6bvnIbHN44itPwv8VxcMRKhgn0/VAr7C6ycSQnmGYq3ymCdsJZq6YnKDToKfSJLg4Ptpgf8JeQhcbvmQdX9zPxUOrt1ILPAvhYodJXOJ84wAAAAASUVORK5CYII=" alt="" />
		</div>
	</div>
<script type="text/javascript" src="http://code.jquery.com/jquery-2.0.0.min.js"></script>
<script type="text/javascript">
currentStep = 1;
function loadStep(number){
	var page = $(".contentAjax");
	var direction = (number >= currentStep) ? 1 : 2;
	var direction1 = (direction == 1) ? "-=50px" : "+=50px";
	var direction2 = (direction == 2) ? "-=50px" : "+=50px";
	page.stop(true, true).animate({marginLeft: direction1, opacity: 0}, "normal", function(){
		$.get(phpSelf, {step: number}, function(data){
			data = data.split("<!>");
			$(".steps > [class^='step']").removeClass('active');
			$(".steps .step"+number).addClass('active');
			currentStep = number;
			if(data[0] == "1"){
				$(".goBack").show();
			} else {
				$(".goBack").hide();
			}
			if(data[1] == "1"){
				$(".goNext").show();
			} else {
				$(".goNext").hide();
			}
			$(".contentAjax").css({marginLeft: 0}).html(data[2]).css({marginLeft: direction2}).animate({marginLeft: 0, opacity: 1}, "normal");
		});
	});
}
$(function(){
	loadStep(1);
	$(".goNext").click(function(){
		loadStep(currentStep+1);
	});
	$(".goBack").click(function(){
		if(currentStep == 1) return false;
		loadStep(currentStep-1);
	});
});
</script>
</body>
</html>