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
    			{id: 1, title: 'Giuliana Piccinini', image: 'http://profile.ak.fbcdn.net/hprofile-ak-prn1/c170.50.621.621/s160x160/535906_10200306484481114_366570825_n.jpg', url: '#'},
    			{id: 4, title: 'Marcelito Lopez', image: 'http://profile.ak.fbcdn.net/hprofile-ak-ash4/c66.66.828.828/s160x160/481742_4555486217120_291734252_n.jpg', url: '#'},
    			{id: 4, title: 'Denise Suarez', image: 'http://sphotos-f.ak.fbcdn.net/hphotos-ak-prn1/734233_4417054712179_1259783293_n.jpg', url: '#'},
    			{id: 6, title: 'Cata Aumann', image: 'http://profile.ak.fbcdn.net/hprofile-ak-ash4/c22.22.269.269/s160x160/418464_10150606263084010_279571319_n.jpg', url: '#'},
    			{id: 1, title: 'Mario Rodriguez', image: 'http://profile.ak.fbcdn.net/hprofile-ak-ash4/c102.0.401.401/s160x160/284_17401179476_907_n.jpg', url: '#'},
    			{id: 6, title: 'Diana Leon', image: 'http://profile.ak.fbcdn.net/hprofile-ak-ash4/c34.76.425.425/s160x160/401686_4297538789159_704876338_n.jpg', url: '#'}
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
	.append( "<a target='_blank' href='" + item.url + "'><img alt='' src='" + item.image + "' width='40' /> <span class='name'>" + item.title + "</span>" +
		"</br><input type='button' class='btn btn-primary' data-befan='" + item.id + "' value='Ser Fan'></a>");
}