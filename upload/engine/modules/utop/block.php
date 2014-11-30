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
define( 'UTOP_DIR', ENGINE_DIR . '/modules/utop' );

$templateName = $template ? $template : "utop.tpl";

if(preg_match("/^utop\.tpl$/i", $templateName) or preg_match("/^utop_[a-z0-9_\-]+\.tpl$/i", $templateName) or preg_match("/^utop\/[a-z0-9_\-]+\.tpl$/i", $templateName)){

	if(! class_exists("uTop")) require_once UTOP_DIR . '/utop.class.php';
	if(! class_exists("nCache")) require_once UTOP_DIR . '/cache.class.php';

	$templateFile = ROOT_DIR . "/templates/{$config['skin']}/{$templateName}";

	if(!file_exists($templateFile)) echo("Шаблон <b>/templates/{$config['skin']}/{$templateName}</b> не найден.");

	if(class_exists("uTop")) $utop = new uTop;
	
	if(empty($sortBy) or in_array($sortBy, array_keys($utop->config['sort_list']))){
		if(is_object($utop) and ($utop->config['online'] == "1")) {
			$utop_data = $utop->getData($limit, $showGroups, $showBanned, $sortBy, $sortOrder, $cacheTime, $lastVisitPeriod);
			
			global $utopBlockId;
			if(! $utopBlockId) $utopBlockId = 0;
			$utopBlockId++;
			
			if(! defined('UTOPJS')){
				// список вариантов сортиновки (js)
				$utopJsSort = "var utopSortList = [];\n";
				foreach ($utop->getSortList() as $key => $value) {
					$utopJsSort .= "utopSortList['{$key}'] = \"" . addslashes($value) . "\";\n";
				}
				$utopJsSort = "<script type=\"text/javascript\">\n{$utopJsSort}</script>";
				define('UTOPJS', true);
			}
			
			ob_start();
			include $templateFile;
			$block = ob_get_contents();
			ob_end_clean();
			if($utopAjax){
				preg_match("#\[ajax\](.*?)\[/ajax\]#is", $block, $matches);
				$block = $matches[1];
			} else {
				$block = preg_replace("#\[ajax\](.*?)\[/ajax\]#is", "<div id=\"utop-ajax\">\\1</div>", $block);
			}
			echo $utopJsSort;
			echo $block;
		}
	} else echo "Параметр {$sortBy} отсутствует в списке вариантов сортировки.";
} else echo "Недопустимое имя шаблона: <b>{$templateName}</b>";

?>