function resizePopup() {
	window.top.resizeColorbox({innerHeight: $('.popup-content').height() });
}

$(function(){
	resizePopup();
});


//TAG SELECTION (TEXT)

$(function(){
	$( ".completer" ).each(function(){
		var el = $(this);
		var ul = el.siblings('.selectedtags');
		
		el.autocomplete({
			source: function( request, response ) {
				ajax.genericAction(el.attr('data-route'), {text: request.term}, 
				function(data){
					response(data);
				},
				function(errortxt){
					error(errortxt);
				}
				);
			},
			minLength: 2,
			select: function( event, ui ) {
				var item = ui.item;
				formTagItem(item)
				.appendTo( ul );
				updateTagField(ul);
			}
		});
	});
	
	$('.selectedtags .deletetag').live('click',function(e){
		e.preventDefault();
		var el = $(this);
		var li = el.closest('li');
		var ul = el.closest('ul.selectedtags');
		li.fadeOut('fast',function(){
			$(this).remove();
			
			updateTagField(ul);
		});		
	});
	
	$('.completer').live('keydown', function (e) {
     var el = $(this);
     var ul = el.siblings('.selectedtags');
		if ( e.keyCode == 13 ){
         var item = {value: el.val()};
         formTagItem(item)
			.appendTo( ul );
			updateTagField(ul);
         
         return false;
     }
 });
});

function formTagItem(item) {
	return $( "<li></li>" )
	.attr('data-val',item.add)
	.append( "<span class='name'>" + item.value + "</span>" )
	.append( "<a class='deletetag'></a>" );
}

function updateTagField(ul) {
	var el = ul.siblings('.completer');
	var field = $('#'+el.attr('data-field'));
	
	var tagtexts = new Array;
	ul.find('li').each(function(){
		tagtexts.push($.trim($(this).attr('data-val')));
	});
	field.val(tagtexts.join(','));
	
	el.val('');
	
	resizePopup();
}