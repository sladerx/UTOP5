<?php
/*
####################################################
@copyright		(c) 2013 Nevex Group
@name			uTop
@version		5.0
@link			http://nevex.pw/
####################################################
*/	

if(!defined('DATALIFEENGINE')) exit();

class uTop {

	private $hiddenUsers;
	public $config;
	private $db;
	private $dle_config;
	public $cache;
	
	function __construct() {
		global $db, $config;
		$this->db = $db;
		$this->dle_config = $config;
		$this->config = $this->loadConfig();
		if(! class_exists("nCache")) include_once ENGINE_DIR . "/modules/utop/cache.class.php";
		if(! class_exists("nCache")){
			exit("[UTOP][Ошибка] Класс nCache не загружен. Проверьте наличие и доступ к файлу: engine/modules/utop/cache.class.php");
		}
		$this->cache = new nCache(ENGINE_DIR . "/modules/utop/cache", $this->config['cache_max_time']);
		if(! $this->config['cache']) $this->cache->enable = false;
		if(($this->dle_config['cache_type'] == "1") and $this->dle_config['memcache_server']) $this->cache->enableMemcache($this->dle_config['memcache_server']);
		
		$husersdata = file_get_contents(ENGINE_DIR . "/modules/utop/hidden_users.txt");
		$hiddenUsersArr = explode(",", $husersdata);
		$this->hiddenUsers = $hiddenUsersArr;
	}
	
	public static function parseSortList($sorlList) {
		$options_list = explode("\n", $sorlList);
		$options_result = array();
		foreach ($options_list as $str) {
			$str = trim($str);
			$arr = explode("=", $str);
			$option_title = trim($arr[1]);
			$option_value = trim($arr[0]);
			if($option_title and $option_value) {
				$options_result[$option_value] = $option_title;
			}
		}
		return $options_result;
	}
	public static function showSortList(array $sorlList) {
		$options_result = array();
		foreach ($sorlList as $key => $value) {
			$options_result[] = "{$key}={$value}";
		}
		return implode("\n", $options_result);
	}
	
	public static function parseXfields($xfields) {
		if( $xfields == "" ) return;
		$xfieldsdata = explode( "||", $xfields );
		foreach ( $xfieldsdata as $xfielddata ) {
			list ( $xfielddataname, $xfielddatavalue ) = explode( "|", $xfielddata );
			$xfielddataname = str_replace( "&#124;", "|", $xfielddataname );
			$xfielddataname = str_replace( "__NEWL__", "\r\n", $xfielddataname );
			$xfielddatavalue = str_replace( "&#124;", "|", $xfielddatavalue );
			$xfielddatavalue = str_replace( "__NEWL__", "\r\n", $xfielddatavalue );
			$data = array();
			$data[$xfielddataname] = $xfielddatavalue;
		}
		return $data;
	}
	
	private function sqlFilters($showGroups, $showBanned, $lastVisitPeriod) {
		$sql_filters = array();
		$lastVisitPeriod = $lastVisitPeriod ? intval($lastVisitPeriod) : intval($this->config['last_visit_period']);

		foreach($showGroups as $key => $value){
			if(trim($value) == "") unset($showGroups[$key]);
		}
		
		// фильтр по группам
		if(count($showGroups) > 0) {
			$sql_where = implode(",", $showGroups);
			$sql_where = "`user_group` IN ({$sql_where})";
			$sql_filters[] = $sql_where;
		}
		
		// скрытие пользователей, не желающих участвовать в топе
		if($this->config['allow_leave_top'] == "1") $sql_filters[] = "`utop_join` = '1'";
		
		// скрытые пользователи
		if($this->config['enable_hide_users'] == "1") {
			foreach ($this->hiddenUsers as $key=>$user_id) {
				if($user_id == "") unset($this->hiddenUsers[$key]);
			}
			$sql_hidden = implode(",", $this->hiddenUsers);
			$sql_hidden = "`user_id` NOT IN ({$sql_hidden})";
			if(count($this->hiddenUsers) > 0) $sql_filters[] = $sql_hidden;
		}
		
		// фильтр забаненых
		if(! $showBanned) $sql_filters[] = "`banned` != 'yes'";

		// те, кто не посещал сайт более n дней
		if($lastVisitPeriod > 0){
			$lastVisit = (time() + $this->dle_config['date_adjust'] * 60) - ($lastVisitPeriod * 86400);
			$sql_filters[] = "`lastdate` >= {$lastVisit}";
		}
		
		$result_query = "";
		if(count($sql_filters) > 0) {
		$result_query = implode(" AND ", $sql_filters);
		$result_query = "WHERE " . $result_query;
		}
		return $result_query;
		
	}
	
	public function formatDate($date_format, $date) {
		$day_start = mktime('00', '00', '00', date("m"),  date("d"),  date("Y"));
		$day_end = mktime('23', '59', '59', date("m"),  date("d"),  date("Y"));
		$day_yesterday = $day_start - (3600 * 24);
		if(($date >= $day_start) AND ($date <= $day_end) AND ($this->config['offest_date_format'] == "1")) {
		$return = "Сегодня, " . langdate("H:i", $date);
		} elseif(($date >= $day_yesterday) AND ($this->config['offest_date_format'] == "1")) {
		$return = "Вчера, " . langdate("H:i", $date);
		} else {
		$return = langdate($date_format, $date);
		}
		return $return;
	}
	
	public function getSortList(){ return $this->config['sort_list']; }
	
	// Получаем отсортированный список пользователей из БД
	private function loadFromDB($limit, $showGroups, $showBanned, $sortBy, $sortOrder, $lastVisitPeriod) {
		$sql_where = $this->sqlFilters($showGroups, $showBanned, $lastVisitPeriod);
		$data = array();
		$this->db->query("SELECT {$this->config['sql_rows']} FROM ".PREFIX."_users AS users {$this->config['sql_join']} {$sql_where} ORDER BY `{$sortBy}` {$sortOrder} LIMIT 0, {$limit}");
		while($row = $this->db->get_row()) {
			// Удаляем ненужную информацию о пользователе
			unset($row['password'], $row['allow_mail'], $row['hash'], $row['signature'], $row['logged_ip'], $row['favorites'], $row['pm_all'], $row['pm_unread'], $row['time_limit'], $row['allowed_ip'], $row['restricted_days'], $row['restricted_date']);
			$row['xfields'] = $this->parseXfields($row['xfields']);
			$data[] = $row;
		}
		return $data;
	}

	// Получение массива с пользователями из кеша/БД
	public function getData($limit, $showGroups, $showBanned, $sortBy, $sortOrder, $cacheTime, $lastVisitPeriod) {
		global $user_group;
		$limit = $limit ? intval($limit) : $this->config['max_user'];
		if($showGroups){
			foreach(explode(",", $showGroups) as $key => $value) $showGroups[$key] = intval($value);
		} else $showGroups = $this->config['show_groups'];
		$showBanned = $showBanned ? intval($showBanned) : $this->config['show_banned'];
		$sortBy = $sortBy ? $sortBy : $this->config['sort_type'];
		if($sortBy == "utop_rand"){
			$sorts = $this->config['sort_list'];
			unset($sorts['utop_rand']);
			$sorts = array_keys($sorts);
			$index = rand(0, count($sorts)-1);
			$sortBy = $sorts[$index];
		}
		$sortOrder = (($sortOrder == "ASC") or ($sortOrder == "DESC")) ? $sortOrder : $this->config['sort_order'];
		if($cacheTime) $cacheTime = intval($cacheTime);
		else $cacheTime = NULL;
		
		$cacheName = md5("{$limit}-" . implode(",", $showGroups) . "-{$showBanned}-{$sortBy}-{$sortOrder}-{$lastVisitPeriod}");
		
		if(! $data = $this->cache->get("block_{$cacheName}")){
			// загрузка из БД
			$data = $this->loadFromDB($limit, $showGroups, $showBanned, $sortBy, $sortOrder, $lastVisitPeriod);
			
			foreach ($data as $num=>$userinfo) {

				// ссылка на аватар
				if(($userinfo['foto'] != "") AND (file_exists(ROOT_DIR . "/uploads/fotos/{$userinfo['foto']}"))) {
					$userinfo['foto'] = "{$this->dle_config['http_home_url']}uploads/fotos/{$userinfo['foto']}";
				} elseif (count(explode("@", $userinfo['foto'])) == 2) { // gravatar support
					$userinfo['foto'] = 'http://www.gravatar.com/avatar/' . md5(trim($userinfo['foto']));
				} elseif (file_exists(ROOT_DIR . "/templates/{$this->dle_config['skin']}/dleimages/noavatar.png")) { // noavatar fix (9.7+)
					$userinfo['foto'] = "{$this->dle_config['http_home_url']}templates/{$this->dle_config['skin']}/dleimages/noavatar.png";
				} else {
					$userinfo['foto'] = "{$this->dle_config['http_home_url']}templates/{$this->dle_config['skin']}/images/noavatar.png";
				}
				
				$userinfo['group_id'] = $userinfo['user_group'];
				$userinfo['user_group'] = $user_group[$userinfo['group_id']]['group_name'];
				$userinfo['user_group_formated'] = $user_group[$userinfo['group_id']]['group_prefix'] . $user_group[$userinfo['group_id']]['group_name'] . $user_group[$userinfo['group_id']]['group_suffix'];
				$userinfo['url_name'] = urlencode($userinfo['name']);
				$userinfo['profile_link'] = ($this->dle_config['allow_alt_url'] == "yes") ? "{$this->dle_config['http_home_url']}user/{$userinfo['url_name']}/" : "{$this->dle_config['http_home_url']}index.php?subaction=userinfo&user={$userinfo['url_name']}";				

				switch($this->config['nick_format_mode']) {
					case "color" :
						if($this->config["group{$userinfo['group_id']}_color"] != "") {
							$userinfo['name_formated'] = "<span style=\"color:" . $this->config["group{$userinfo['group_id']}_color"] . "\">{$userinfo['name']}</span>";
						} else {
							$userinfo['name_formated'] = $userinfo['name'];
						}
					break;
					
					case "group_settings" :
						$userinfo['name_formated'] = $user_group[$userinfo['group_id']]['group_prefix'] . $userinfo['name'] . $user_group[$userinfo['group_id']]['group_suffix'];
					break;
					
					default :
						$userinfo['name_formated'] = $userinfo['name'];
					break;
				}
				
				$data[$num] = $userinfo;
			}
			
			if($cacheTime) $this->cache->set("block_{$cacheName}", $data, $cacheTime);
			else $this->cache->set("block_{$cacheName}", $data);
		}
		
		foreach ($data as $num=>$userinfo) {
			$userinfo['reg_date'] = $this->formatDate($this->config['regdate_format'], $userinfo['reg_date']);
			$userinfo['lastdate'] = $this->formatDate($this->config['lastdate_format'], $userinfo['lastdate']);
			$data[$num] = $userinfo;
		}
		return $data;
	}

	static function loadConfig(){
		include ENGINE_DIR . "/modules/utop/config.php";
		return $uconf;
	}
	
	static function convertCharset($value, $inCharset, $outCharset){
		if(is_array($value)){
			foreach($value as $key => $val) $value[$key] = uTop::convertCharset($val, $charset);
		} else {
			$value = iconv($inCharset, $outCharset, $value);
		}
		return $value;
	}
	
	static function saveConfig(array $newConfig, $charset){
		$configFile = ENGINE_DIR . "/modules/utop/config.php";
		if(file_exists($configFile) and (!is_writable($configFile))){
			return "<b>Ошибка:</b> файл <b>{$configFile}</b> недоступен для записи.";
		}
		$newConfig['version'] = "5.1";
		if($f = @fopen($configFile, "w+")){
			$contents = "<?php\n# uTop Config File\n# (c) 2013 Nevex Group\n\n\$uconf = " . var_export($newConfig, true) . ";\n";
			fputs($f, ($charset) ? uTop::convertCharset($contents, "utf-8", $charset) : $contents);
			fclose($f);
			return 1;
		} else return "<b>Ошибка:</b> не удалось произвести запись в файл <b>{$configFile}</b>. Проверьте права доступа.";
	}

}

