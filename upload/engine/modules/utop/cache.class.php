<?php
/*
####################################################
@copyright		(c) 2013 Nevex Group
@name			uTop
@version		5.1
@link			http://nevex.pw/
####################################################
*/
if( ! defined("DATALIFEENGINE")) exit();

class nCache {
	private $memcache;
	private $cacheDir;
	public $enable = true;
	public $defaultLife = 1440;
	private $ststsFile = "cacheStats.txt";
	private $mcachePrefix = null;
	
	public function __construct($cache_dir, $default_life){
		global $conf;
		$this->cacheDir = $cache_dir;
		if($default_life) $this->defaultLife = $default_life;
		$this->mcachePrefix = md5($_SERVER['HTTP_HOST']) . "_";
	}
	
	public function enableMemcache($host) {
		if(class_exists("Memcache") and $host) {
			$this->memcache = new Memcache;
			list($ip, $port) = explode(":", trim($host));
			$port = $port ? $port : "11211";
			if(! $this->memcache->connect($ip, $port)) $this->memcache = null;
		}		
	}
	
	public function set($name, $data, $expire) {
		if(! $this->enable) return false;
		$expire = $expire ? $expire : $this->defaultLife;
		$expire *= 60;
		if($this->memcache) {
			$data = array('data' => $data);
			$txt_data = serialize($data);
			$result = $this->memcache->set($this->mcachePrefix . $name, $txt_data, MEMCACHE_COMPRESSED, $expire);
			$this->statsAdd($name, strlen($txt_data), time() + $expire);
			return $result;
		} else {
			$expireDate = time() + $expire;
			$dataArr = array(
				'expire' => $expireDate,
				'data' => $data,
			);
			$txt_data = serialize($dataArr);
			if($f = fopen("{$this->cacheDir}/{$name}.tmp", "w")) {
				fputs($f, $txt_data);
				fclose($f);
				$strLength = strlen($txt_data);
				return true;
			} else {
				return false;
			}
		}
	}
	
	public function get($name) {
		if(! $this->enable) return false;
		if($this->memcache) {
			$data = $this->memcache->get($this->mcachePrefix . $name);
			if($data) {
				$array = (array)unserialize($data);
				return $array['data'];
			} else {
				return false;
			}
		} else {
			$cacheFile = "{$this->cacheDir}/{$name}.tmp";
			if(file_exists($cacheFile)){
				$fileData = (array)unserialize(@file_get_contents($cacheFile));
				if($fileData['expire'] < time()) {
					@unlink($cacheFile);
					return false;
				} else {
					return $fileData['data'];
				}
			} else {
				return false;
			}
		}
	}
	
	public function clear($name) {
		if($this->memcache) {
			$this->memcache->delete($this->mcachePrefix . $name);
			$this->statsDelete($name);
		}
		@unlink("{$this->cacheDir}/{$name}.tmp");
	}
	
	public function flush() {
		if($this->memcache) {
			$this->memcache->flush();
		}
		foreach(scandir($this->cacheDir) as $file) {
			if(preg_match("/^(.+?)\.tmp$/", $file)) {
				@unlink($this->cacheDir . "/" . $file);
			}
		}
		$this->updateStatsFile(array());		
	}
	
	// логгер
	public function getStats(){
		$data = ($this->memcache) ? $this->memcache->get($this->mcachePrefix . "cache_stats") : @file_get_contents($this->cacheDir . "/" . $this->ststsFile);
		$data = (array)json_decode($data, true);
		return $data;
	}
	
	private function updateStatsFile($newData){
		foreach($newData as $key=>$value) {
			if($value['expire'] < time()) unset($newData[$key]);
		}
		$txt_data = json_encode($newData);
		if($this->memcache) {
			$this->memcache->set($this->mcachePrefix . "cache_stats", $txt_data, MEMCACHE_COMPRESSED, 3600 * 72);
		} else {
			$f = @fopen($this->cacheDir . "/" . $this->ststsFile, "w+");
			fwrite($f, $txt_data);
			fclose($f);
		}		
	} 
	
	private function statsAdd($name, $size, $expire) {
		$data = $this->getStats();
		$data[$name] = array(
			'name' => $name,
			'size' => $size,
			'expire' => $expire,
			'update' => time(),
		);
		$this->updateStatsFile($data);
	}
	
	private function statsDelete($name) {
		$data = $this->getStats();
		unset($data[$name]);
		$this->updateStatsFile($data);
	}

}
