$(function(){
	
	if ($('#form_album').val() == 'NEW') {
		spawnNewAlbumField($('#form_album'));
	}
	
	$('#form_album').change(function(e){
		var el = $(this);
		if (el.val() == 'NEW') {
			spawnNewAlbumField(el);
		} else {
			$('#form_album_new_name').parents('.field').slideUp('fast',function(){
				$(this).remove();
				resizePopup();
			});
		}
	});
});

function spawnNewAlbumField(el) {
	var newfield = $(".templateFieldAlbumName .field").clone();
	newfield.find('input').attr('id', 'form_album_new_name');
	el.parents('.field').after(newfield);
	$('#form_album_new_name').parents('.field').slideDown('fast', function(){
		resizePopup();
	});
}