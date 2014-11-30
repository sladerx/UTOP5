<?php
/*
####################################################
@copyright		(c) 2013 Nevex Group
@name			uTop
@version		5.1
@link			http://nevex.pw/
####################################################
*/

define('UTOP_DIR', ENGINE_DIR . '/modules/utop');

if(! defined( 'DATALIFEENGINE' )) exit();
require_once ENGINE_DIR . "/data/config.php";

header("Content-type: text/html; charset={$config['charset']}");

$config_file = UTOP_DIR . '/config.txt'; // файл конфигрурации модуля
$cache_file_path = UTOP_DIR . '/cache.txt'; // файл кеша
$hidden_users_file = UTOP_DIR . '/hidden_users.txt'; // скрытые пользователи

require_once UTOP_DIR . "/utop.class.php";
require_once UTOP_DIR . "/cache.class.php";

// Сохранение настроек
if($_POST['save'] == "yes") {
	$newCfg = $_POST['utop'];
	$newCfg['sort_list'] = uTop::parseSortList($newCfg['sort_list']);
	$result = uTop::saveConfig($newCfg, $config['charset']);
	echo $result;
	exit();
}

// Очистка кеша
if($_POST['action'] == "clearcache") {
	$utop = new uTop;
	$utop->cache->flush();
	exit("Кеш успешно очищен");
}

if($_POST['action'] == "update") {
	$utop = new uTop;
	$result = @file_get_contents("http://api.nevex.pw/utop/update.php?version={$utop->config['version']}");
	$update = 0;
	if($result){
		if(trim($result) == "ok") $message = "У Вас стоит самая последняя версия.";
		else {
			$message = (strtolower($config['charset']) != "utf-8") ? iconv("UTF-8", $config['charset'], $result) : $result;
			$update = 1;
		}
	} else $message = "Не удалость проверить обновления.";
	echo "{$update}<!>{$message}";
	exit();
}

function loadHiddenList(){
	$data = trim(@file_get_contents(UTOP_DIR . "/hidden_users.txt"));
	if($data){
		$list = explode(",", $data);
	} else {
		$list = array();
	}
	return $list;
}
function saveHiddenList(array $list){
	foreach($hiddenUsers as $index => $userid){
		if(! is_numeric($userid)) unset($hiddenUsers[$index]);
	}
	$f = fopen(UTOP_DIR . "/hidden_users.txt", "w");
	fputs($f, implode(",", $list));
	fclose($f);
}

$hiddenUsers = loadHiddenList();

if($_REQUEST['action'] == "hidden"){
	$json = array();
	if($_POST['do'] == "add"){
		$name = $db->safesql(trim($_POST['name']));
		$user = $db->super_query("SELECT `user_id`, `name` FROM " .  USERPREFIX. "_users WHERE `name` = '{$name}'");
		if($user['user_id']){
			if(! in_array($user['user_id'], $hiddenUsers)) {
				$hiddenUsers[] = $user['user_id'];
				saveHiddenList($hiddenUsers);
				$hStatus = "ok";
				$utop = new uTop;
				$utop->cache->flush();
			} else $hStatus = "Пользователь уже находится в списке.";
		} else $hStatus = "Пользователь {$name} не найден.";
	}
	if($_POST['do'] == "delete"){
		$id = intval($_POST['userid']);
		foreach($hiddenUsers as $index => $userid){
			if($userid == $id){
				unset($hiddenUsers[$index]);
				break;
			}
		}
		saveHiddenList($hiddenUsers);
		$utop = new uTop;
		$utop->cache->flush();
		exit("ok");
	}
}

$hidden_users_list = "";
$hiddenUsers = implode(",", $hiddenUsers);
if($hiddenUsers){
	$db->query("SELECT `name`, `user_id`, `foto`, `user_group` FROM " . USERPREFIX . "_users WHERE `user_id` IN ({$hiddenUsers}) ORDER BY `name` ASC");
	while($row = $db->get_row()){
		
		if(($row['foto'] != "") AND (file_exists(ROOT_DIR . "/uploads/fotos/{$row['foto']}"))) {
			$row['foto'] = "{$config['http_home_url']}uploads/fotos/{$row['foto']}";
		} elseif (file_exists(ROOT_DIR . "/templates/{$config['skin']}/dleimages/noavatar.png")) { // noavatar fix (9.7+)
			$row['foto'] = "{$config['http_home_url']}templates/{$config['skin']}/dleimages/noavatar.png";
		} else {
			$row['foto'] = "{$config['http_home_url']}templates/{$config['skin']}/images/noavatar.png";
		}
		
		$hidden_users_list .= "<span class=\"mini-box\" id=\"u{$row['user_id']}\"><img src=\"{$row['foto']}\" alt=\"\" /><span class=\"del-btn\" onclick=\"deleteHiddenUser({$row['user_id']}); return false;\"></span><div><b>{$row['name']}</b><br />{$user_group[$row['user_group']]['group_name']}</div></span>";
	}
}

if($_REQUEST['action'] == "hidden") {
	echo "{$hStatus}<!>{$hidden_users_list}";
	exit();
}

// Элементы управления
class uTopAdmin {
	public $uconfig = array();

	public function loadConfig() {
		$this->uconfig = uTop::loadConfig();
	}
	
	public function getInput($input_name) {
		$input = "<input class=\"styled big\" name=\"utop[{$input_name}]\" value=\"{$this->uconfig[$input_name]}\" />";
		return $input;
	}
	
	public function getSmallInput($input_name) {
		$input = "<input class=\"styled small numeric\" autocomplete=\"Off\" name=\"utop[{$input_name}]\" value=\"{$this->uconfig[$input_name]}\" />";
		return $input;
	}
	
	public function getTextarea($name) {
		$value = $this->uconfig[$name];
		if($name == "sort_list") $value = uTop::showSortList($value);
		$textarea = "<textarea name=\"utop[{$name}]\" class=\"styled\">{$value}</textarea>";
		return $textarea;
	}
	
	public function getSwitcher($name) {
		if ($this->uconfig[$name] == "1") {
			$checked_yes = 'checked="checked"';
		} else {
			$checked_no = 'checked="checked"';
		}
		$radio = "<div class=\"switcherBox\"><label><input type=\"radio\" name=\"utop[{$name}]\" {$checked_yes} value=\"1\"> Да </label><label><input type=\"radio\" name=\"utop[{$name}]\" {$checked_no} value=\"0\"> Нет </label></div>";
		return $radio;
	}
	
	public function getSelect($options=array(), $name) {
		foreach ($options as $key=>$title) {
			if($this->uconfig[$name] == $key) {$selected = 'selected="selected"';} else {$selected = "";}
			$select .= "<option {$selected} value=\"{$key}\">{$title}</option>";
		}
		$select = "<select class=\"selector\" name=\"utop[{$name}]\">{$select}</select>";
		return $select;
	}
	
	public function getMultipleSelect($options=array(), $name) {
		foreach ($options as $key=>$title) {
			$selected = "";
			foreach ($this->uconfig[$name] as $id=>$value) {
				if($key == $value) {
					$selected = 'selected="selected"';
					break;
				}
			}
			$select_options .= "<option {$selected} value=\"{$key}\">{$title}</option>";
		}
		$select = "<select class=\"multipleSelect\" multiple=\"multiple\" size=\"6\" name=\"utop[{$name}][]\">{$select_options}</select>";
		return $select;
	}
	
	public function getSortOptions() {
		$options_list = (array)explode("\n", $this->uconfig['sort_list']);
		$options_result = array();
		foreach ($options_list as $str) {
			$str = trim($str);
			$arr = (array)explode("=", $str);
			$option_title = trim($arr[1]);
			$option_value = trim($arr[0]);
			if($option_title and $option_value) {
				$options_result[$option_value] = $option_title;
			}
		}
		return $options_result;
	}
}

$uTopAdmin = new uTopAdmin;
$uTopAdmin->loadConfig($config_file);



function showRow($title = "", $description = "", $field = ""){
	$row_html = <<<HTML
	<tr>
	<td class="option">{$title}<br /><div class="descr">{$description}</div></td>
	<td class="optionSetting">{$field}</td>
	</tr>
HTML;
	return $row_html;
}
function showSep($title = "") { return "<tr><th colspan=\"2\" class=\"tdSep\">{$title}</th></tr>"; }
?>
<!DOCTYPE html>
<html>
<head>
<title>Админцентр UTOP</title>
<link rel="stylesheet" type="text/css" media='screen' href="<?=$config['http_home_url']?>engine/modules/utop/admin/styles.css" />
<link rel="shortcut icon" href="<?=$config['http_home_url']?>engine/modules/utop/admin/images/favicon.ico" />
<script type="text/javascript" src="<?=$config['http_home_url']?>engine/modules/utop/admin/jquery-2.0.0.min.js"></script>
<script type="text/javascript" src="<?=$config['http_home_url']?>engine/modules/utop/admin/jquery-ui-1.10.3.min.js"></script>
<script type="text/javascript" src="<?=$config['http_home_url']?>engine/modules/utop/admin/utop.js"></script>
<script type="text/javascript" src="<?=$config['http_home_url']?>engine/modules/utop/admin/jquery-ui.js"></script>
<script type="text/javascript">
var admin_path = "<?=$PHP_SELF?>";
var utop_dir = "<?=$config['http_home_url']?>engine/modules/utop";
</script>
</head>
<body>
	
	<div class="highlight-panel top"></div>
	<div class="highlight-panel bottom"></div>
	<div class="leftPanel">
		<div class="topButtons">
			<a class="button back" href="<?=$PHP_SELF?>?mod=options&amp;action=options" title="Назад в панель DataLife Engine"></a>
			<a class="button site" href="<?=$config['http_home_url']?>" target="_blank" title="Перейти на сайт"></a>
			<a class="button logout" href="<?=$PHP_SELF?>?action=logout" title="Выход"></a>
		</div>
		<div class="utopLogo"></div>
		
		<div class="tabsList">
			<span class="tab" rel="settings">Настройки</span>
			<span class="tab<?=($uTopAdmin->uconfig['enable_hide_users']) ? "" : " disabled"?>" rel="hiddenu">Скрытые пользователи</span>
			<span class="tab" onclick="showAbout(); return false;">О uTop</span>
			<div class="sep"></div>
			<span class="tab" onclick="clearCache(); return false;">Очистить кеш</span>
		</div>
		
		<div class="buttonsBox">
			<button type="button" class="fbutton utopSaveButton forTab settings">Сохранить</button>
			<div class="inputBox forTab hiddenu">
				<input value="" id="addusername" placeholder="Добавить пользователя" />
				<span class="btnFind" onclick="addHiddenUser(); return false;" title="Добавить пользователя"></span>
			</div>
		</div>
	</div>


	<div class="mainContainer">
		<div class="utopTab settings">
			<div class="warn" style="<?=($uTopAdmin->uconfig['online'])? "display: none;" : ""?>">uTop отключен.</div>
			<form action="" method="post" id="utopForm">
				<h1>Настройки</h1>
				<table border="0" cellpadding="0" cellspacing="0" class="settingsTable">
					<tbody>
						<?php
						$sort_options = $uTopAdmin->uconfig['sort_list'];
						$sort_options['utop_rand'] = "Случайный";
						$user_groups = array();
						foreach ($user_group as $group) {
							if($group['id'] == 5) continue;
							$user_groups[$group['id']] = $group['group_name'];
						}

						echo showSep("Основные");
						echo showRow("Включить uTop", "Отключение модуля позволяет скрыть все блоки, не требуя правки шаблона.", $uTopAdmin->getSwitcher("online") );
						echo showRow("Количество пользователей, отображаемых в блоке", "", $uTopAdmin->getSmallInput("max_user"));

						echo showSep("Фильтры");
						echo showRow("Скрывать пользователей, которые не посещали сайт более n дней", "Для отключения фильтра введите: 0", $uTopAdmin->getSmallInput("last_visit_period") . " дней");
						echo showRow("Пользователей каких групп выводить в блоке", "", $uTopAdmin->getMultipleSelect($user_groups, "show_groups") );
						echo showRow("Показывать забаненых пользователей", "", $uTopAdmin->getSwitcher("show_banned") );
						echo showRow("Функция \"Скрытые пользователи\"", "Позволяем вам скрыть определённых пользователей из топа.", $uTopAdmin->getSwitcher("enable_hide_users") );
						echo showRow("Разрешить пользователям отказываться от участия в топе", "Пользователь может отказаться от участия в топе (в настройках профиля).", $uTopAdmin->getSwitcher("allow_leave_top") );

						echo showSep("Формат дат");
						echo showRow("Формат даты регистрации", "<a href=\"#\" onclick=\"dateHelp(); return false;\">Помощь</a>", $uTopAdmin->getInput("regdate_format"));
						echo showRow("Формат даты последнего посещения", "<a href=\"#\" onclick=\"dateHelp(); return false;\">Помощь</a>", $uTopAdmin->getInput("lastdate_format"));
						echo showRow("Относительные даты", "При включении этой опции сегодняшняя дата будет заменена на \"Сегодня\" а вчерашняя на \"Вчера\"", $uTopAdmin->getSwitcher("offest_date_format") );

						$sort_order_options = array(
						'ASC' => "По возрастанию (А-Я)",
						'DESC' => "По убыванию (Я-А)",
						);

						echo showSep("Сортировка");
						echo showRow("Порядок сортировки", "", $uTopAdmin->getSelect($sort_order_options, "sort_order"));
						echo showRow("Вариант сортировки по умолчанию", "Выберите вариант сортировки, который будет использоваться при выводе блока. (Варианты сортировки настраиваются ниже.)", $uTopAdmin->getSelect($sort_options, "sort_type") . "<br /><a href=\"#\" onclick=\"reloadSort(); return false;\">Обновить список</a>");
						echo showRow("Варианты сортировки", "Здесь вы можете настроить варианты сортировки. Каждый вариант пишется с новой строки в формате:<br /><b>поле_таблицы=Название варианта сортировки</b>", $uTopAdmin->getTextarea("sort_list"));
						echo showRow("Разрешить пользователям переключать параметр сортировки", "Пользователи смогут переключать параметр сортировки через меню блока", $uTopAdmin->getSwitcher("allow_sort") );
						
						echo showSep("Настройка запроса SQL (для опытных)");
						echo showRow("Используемые  поля таблиц", "Введите через запятую, какие поля будут выбираться из таблицы пользователей и подключённых таблиц (если есть). Для выбора всех полей введите: <b>*</b>.", $uTopAdmin->getInput("sql_rows"));
						echo showRow("Подключение дополнительных таблиц", "Вы можете подключить дополнительные таблицы со статистикой активности пользователей. Для этого в этом поле надо прописать запрос на подключение таблиц.", $uTopAdmin->getInput("sql_join"));

						echo showSep("Кеширование");

						echo showRow("Включить кеширование?", "Включение кеширования снижает нагрузку на MySQL", $uTopAdmin->getSwitcher("cache") );
						echo showRow("Время жизни кеша", "По прошествии какого времени кеш будет считаться устаревшим.", $uTopAdmin->getSmallInput("cache_max_time") . " мин.");

						echo showSep("Ники пользователей");

						$nick_format_mode_options = array(
						'none' => "Отключено",
						'color' => "Выделение цветом",
						'group_settings' => "Использовать префиксы и суффиксы групп",
						);

						echo showRow("Режим выделения ников цветом", "Здесь вы можете включить функцию выделения ников пользователей в зависимости от их группы.<br />Приведённые ниже настройки будут использоваться только при выборе варианта \"{$nick_format_mode_options['color']}\"", $uTopAdmin->getSelect($nick_format_mode_options, "nick_format_mode"));

						foreach ($user_group as $group) {
							if($group['id'] == 5) continue; // не выводить группу гостей
							echo showRow("Цвет группы \"{$group['group_name']}\"", "Например: <b>#2A8000</b>, <b>rgb(42,128,0)</b> или <b>green</b>", $uTopAdmin->getInput("group{$group['id']}_color") );
						}
						?>
					</tbody>
				</table>
				<input type="hidden" name="save" value="yes" />
			</form>
		</div>

		<div class="utopTab hiddenu">
			<h1>Скрытые пользователи</h1>
			Здесь Вы можете настроить список пользователей, которые не будут выводится в блоке. Для этого всего лишь нужно добавить пользователя в этот список.
			<br /><br />
			<div id="hiddenUsersBox" class="hidden_users_box"><?=$hidden_users_list?></div><br />
		</div>
	</div>	

<div id="utopAbout" style="display:none;" title="">
	<div align="center">
		<img src="<?=$config['http_home_url']?>engine/modules/utop/admin/images/utop.png" alt="uTop logo" /><br />
		<b>Версия <?=$uTopAdmin->uconfig['version']?></b>
	</div>
	<br />
	<div id="utopUpdates"></div>
	<br />
	uTop (User Top) – это бесплатное решение для составления различных TOP'ов пользователей.
	<br /><br />
	<div class="copyright">&copy; 2013 <a href="http://nevex.pw/" target="_blank">Nevex Group</a>.</div>
</div>
	
</body>
</html>