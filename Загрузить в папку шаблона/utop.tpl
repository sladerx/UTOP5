<script type="text/javascript" src="{THEME}/js/utop.js"></script>
<link media="screen" href="{THEME}/style/utop.css" type="text/css" rel="stylesheet" />
<div id="utopBox" data-id="<?=$utopBlockId?>" data-tpl="<?=$templateName?>">
	<div id="popular" class="block">
		<div class="dtop">&nbsp;</div>
		<div class="dcont">
			<div class="btl">
				<?php if($utop->config['allow_sort'] == "1") { ?>
				<button onclick="return dropdownmenu(this, event, getUtopSortMenu(<?=$utopBlockId?>), '170px'); return false;" style="width:96px;" type="submit" class="vresult"><span>Упорядочить</span></button>
				<?php } ?>
				<h4>UTOP 5</h4>
			</div>
		<div class="utop-block">
			[ajax]
				<?php foreach ($utop_data as $user) { $number++ ?>
					<div class="utop_user" align="center">
						<span class="hint-data" style="display:none;">
							Группа: <?=$user['user_group']?><br />
							Публикаций: <?=$user['news_num']?><br />
							Комментариев: <?=$user['comm_num']?>
						</span>
						<div class="utop_avatar">
							<div class="utop_user_number"><?=$number?></div>
							<img class="utop_ava" src="<?=$user['foto']?>" alt="Аватар <?=$user['name']?>" />
						</div>
						<div>
							<a href="<?=$user['profile_link']?>" onclick="ShowProfile('<?=$user['url_name']?>', '<?=$user['profile_link']?>', '<?=$member_id['user_group']?>'); return false;">
								<b><?=$user['name_formated']?></b>
							</a>
						</div>
					</div>
				<?php } ?>
			[/ajax]
		</div>
		</div>
		<div class="dbtm">&nbsp;</div>
	</div>
</div>