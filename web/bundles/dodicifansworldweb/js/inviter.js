$(function(){
	$('.invite-facebook').live('click',function(e){
		e.preventDefault();
		var url = $(this).attr('href');
		FB.ui({
			method: 'send',
			name: 'Unite a Fansworld',
			link: url,
			description: 'Unite a Fansworld y compartamos nuestra pasión con otros fans'
		});
	});

	$(".invite-facebook").live('click', function(){
		FB.ui({
			method: 'apprequests',
			message: 'Unite a Fansworld y compartamos nuestra pasión con otros fans'
		});
	});

});