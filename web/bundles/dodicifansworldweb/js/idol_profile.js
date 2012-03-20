$(function(){
	$('.btn_idolship:not(.loading)').live('click',function(e){
		e.preventDefault();
		var el = $(this);
		el.addClass('loading');
		ajax.genericAction('idolship_ajaxtoggle', {iduser: el.attr('data-iduser')}, 
		function(response){
			success(response.message);
			el.text(response.buttontext);
			el.removeClass('loading');
		},
		function(errortxt){
			ul.removeClass('loading');
			error(errortxt);
		}
		);
	});
});