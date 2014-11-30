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
		<div style="padding:0px 6px; margin-top:6px;">
		<table class="userstop" width="100%">
			<thead>
				<tr>
					<td align="center" width="20">#</td>
					<td align="center" width="10">Ник</td>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($utop_data as $user) { $number++ ?>
				<tr class="utopHintContainer">
					<td align="center">
						<?=$number?>
						<!-- для хинта -->
						<span style="display:none" class="utopTableHintData">
							<b>Комментариев:</b>  <?=$user['comm_num']?><br />
							<b>Публикаций:</b> <?=$user['news_num']?><br />
							<b>Группа:</b> <?=$user['user_group']?><br />
						</span>
					</td>
					<td align="center">
						<a href="<?=$user['profile_link']?>" onclick="ShowProfile('<?=$user['url_name']?>', '<?=$user['profile_link']?>', '<?=$member_id['user_group']?>'); return false;">
							<b><?=$user['name_formated']?></b>
						</a>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		</div>
		</div>
		<div class="dbtm">&nbsp;</div>
	</div>
</div>