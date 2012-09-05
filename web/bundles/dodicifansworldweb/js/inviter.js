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
	
	$('.invite-commonfriends-facebook').live('click', function(){
		FB.ui({
		   method: 'permissions.request',
		   'perms': window.FBperms,
		   'display': 'popup',
		   'response_type': 'signed_request',
		   'fbconnect': 1,
		   'next': 'http://' + location.host + Routing.generate( appLocale + '_' + 'facebook_jstoken', {callback: 'invite'})
		  },
		  function(response) {
		    alert(response);
		  }
		);
	});
});

function callbackfbtokeninvite()
{
	var ul = $('.invite-commonfriends-list');
	ul.addClass('loading');
	ajax.genericAction('facebook_commonfriends', {
        
    }, function(r){
    	$.each(r.friends, function(){
			formFBCommonFriendItem(this)
			.appendTo( ul );
		});
		ul.removeClass('loading');
    }, function(error){
    	ul.removeClass('loading');
    	error(error);
    });
}

function formFBCommonFriendItem(item) {
	return $( "<li></li>" )
	.attr('data-iduser', item.id)
	.append( "<a target='_blank' href='"+item.url+"'><img alt='' src='"+item.image+"' /> <span class='name'>" + item.title + "</span></a>" );
}