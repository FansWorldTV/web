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
	$('[data-fbinvite]').live('click', function(){
		callbackfbtokeninvite();
	});
});

function callbackfbtokeninvite() {
	var ul = $('.invite-commonfriends-list');
	ul.addClass('loading');

	ajax.genericAction({
    	route: 'facebook_commonfriends',
    	params: {},

    	callback: function (response) {
    		console.log(response);
    		response = {"friends":[
    			{id: 10200306484481114, title: 'Giuliana Agustina Piccinini', image: ' http://profile.ak.fbcdn.net/hprofile-ak-prn1/c170.50.621.621/s160x160/535906_10200306484481114_366570825_n.jpg', url: '#'},
    			{id: 4571799340698, title: 'Denise Macarena Suarez', image: 'http://sphotos-f.ak.fbcdn.net/hphotos-ak-prn1/734233_4417054712179_1259783293_n.jpg', url: '#'}
    		]}
    		$.each(response.friends, function() {
				formFBCommonFriendItem(this)
				.appendTo(ul);
			});
			ul.removeClass('loading');
    	},

    	errorCallback: function (errorResponse) {
    		console.log('Error');
    		console.log(errorResponse);
    		ul.removeClass('loading');
    		alert('Error');
    	}
	});
}

function formFBCommonFriendItem(item) {
	return $( "<li></li>" )
	.attr('data-iduser', item.id)
	.append( "<a target='_blank' href='"+item.url+"'><img alt='' src='"+item.image+"' /> <span class='name'>" + item.title + "</span></a>" );
}