utopBlock = $("#utop-ajax");

function utopChangeSort(sort, blockid) {
	ShowLoading("");
	var tpl = "";
	if(blockid){
		var block = $("#utopBox[data-id='"+blockid+"']");
		if(block.length > 0){
			var tpl = block.attr('data-tpl');
			utopBlock = block.find("#utop-ajax");
		}
	}
	
	$.get(dle_root+"engine/modules/utop/ajax.php", {sortBy: sort, skin: dle_skin, template: tpl}, function(data){
		HideLoading();
		utopBlock.stop(true, true).hide('slide', {direction: 'left'}, "normal", function(){
			$(this).html(data).show('slide', {direction: 'right'}, "normal");
			utopHintInit(".utop_user", ".hint-data", -15, -40);
		});
	});
}

function getUtopSortMenu(blockid) {
	var menu = new Array();
	var i = 0;
	for(var key in utopSortList){
		menu[i++] = '<a href="#" onclick="utopChangeSort(\''+key+'\', '+blockid+'); return false;">'+utopSortList[key]+'</a>';
	}
	return menu;
}

function utopHintInit(parentName, hintDataName, posLeft, posTop) {
	$("#utop-hint").remove();
	$("body").append('<div id="utop-hint" style="display:none;"></div>');
	if(! $(parentName).hasClass('utopHintLoaded')) {
		$(parentName).addClass('utopHintLoaded').bind('hover', function(){
			var hintData = $(this).find(hintDataName).html();
			var posX = $(this).offset().left + $(this).width() + posLeft;
			var posY = $(this).offset().top + posTop;
			$("#utop-hint").css({top:posY+"px", left:posX+"px"}).html(hintData).stop(true, true).fadeIn('fast');
		}).bind('mouseleave', function(){
			$("#utop-hint").stop(true, true).fadeOut('normal');
		});
	}
}

$(function(){
	utopHintInit(".utop_user", ".hint-data", -15, -40);
});
