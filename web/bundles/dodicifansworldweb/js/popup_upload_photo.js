$(function(){
	$('#form_album').change(function(e){
		var el = $(this);
		if (el.val() == 'NEW') {
			var newfield = $(".templateFieldAlbumName .field").clone();
			newfield.find('input').attr('id', 'form_album_new_name');
			el.parents('.field').after(newfield);
			$('#form_album_new_name').parents('.field').slideDown('fast', function(){
				resizePopup();
			});
		} else {
			$('#form_album_new_name').parents('.field').slideUp('fast',function(){
				$(this).remove();
				resizePopup();
			});
		}
	});
});