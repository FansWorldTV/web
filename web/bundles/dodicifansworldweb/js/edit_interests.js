jQuery.fn.refreshCategory = function() {
	var el = this;
	var idcategory = el.attr('data-idcategory');
	var iduser = el.parents('ul.categories').attr('data-iduser');
	
	var ul = el.find('.currentinterests');
	ul.empty().addClass('loading');
	
	
	ajax.genericAction('interest_ajaxget', {idcategory: idcategory, iduser: iduser}, 
	function(data){
		$.each(data, function(){
			formInterestItem(this)
			.appendTo( ul );
		});
		ul.removeClass('loading');
	},
	function(errortxt){
		ul.removeClass('loading');
		error(errortxt);
	}
	);
	
	return this;
};

$(function(){
	$( ".interest-chooser" ).each(function(){
		var el = $(this);
		var iduser = el.parents('ul.categories').attr('data-iduser');
		el.autocomplete({
			source: function( request, response ) {
				ajax.genericAction('interest_ajaxget', {idcategory: el.attr('data-idcategory'), text: request.term, iduser: iduser, excludeuser: true}, 
				function(data){
					response(data);
				},
				function(errortxt){
					error(errortxt);
				}
				);
			},
			minLength: 0,
			select: function( event, ui ) {
				var item = ui.item;
				el.addClass('loading');
				ajax.genericAction('interest_ajaxadd', {id: item.id}, 
				function(data){
					el.removeClass('loading');
					var ul = el.parents('.interestcategory').find('.currentinterests');
					success(data.message);
					formInterestItem(data)
					.appendTo( ul );
				},
				function(errortxt){
					el.removeClass('loading');
					error(errortxt);
				}
				);
			}
		})
		.data( "autocomplete" )._renderItem = function( ul, item ) {
			return $( "<li></li>" )
				.data( "item.autocomplete", item )
				.append( "<a><img alt='' src='"+item.image+"' /> <span class='name'>" + item.title + "</span></a>" )
				.appendTo( ul );
		}
		;
	});
	
	$('.interestcategory .deleteinterest:not(.loading)').live('click',function(e){
		e.preventDefault();
		var el = $(this);
		var li = el.closest('li');
		var idinterest = li.attr('data-idinterest');
		el.addClass('loading');
		ajax.genericAction('interest_ajaxdelete', {id: idinterest}, 
		function(data){
			success(data.message);
			li.fadeOut('fast',function(){
				$(this).remove();
			});
		},
		function(errortxt){
			el.removeClass('loading');
			error(errortxt);
		}
		);
	});
	
	$('.interestcategory').each(function(){
		$(this).refreshCategory();
	});
});

function formInterestItem(item) {
	return $( "<li></li>" )
	.attr('data-idinterest', item.id)
	.append( "<img alt='' src='"+item.image+"' /> <span class='name'>" + item.title + "</span>" )
	.append( "<a class='deleteinterest'></a>" );
}