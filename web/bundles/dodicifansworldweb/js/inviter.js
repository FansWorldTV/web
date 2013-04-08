var inviter = {};

inviter.url = {};
inviter.url.long = null;
inviter.url.short = null;

inviter.tabs = {};
inviter.tabs.selected = null


inviter.init = function(url) {
    inviter.url.long = url;

    $.ajax('https://www.googleapis.com/urlshortener/v1/url', {
        'async': false,
        type: 'post',
        contentType: 'application/json',
        data: JSON.stringify({
            "longUrl": inviter.url.long,
            "key": shortenerApi
        }),
        dataType: 'json'
    }).done(function(r) {
        inviter.url.short = r.id;
    });

    inviter.tabs.selected = $("div.invite-modal ul.nav-tabs li.active").attr('data-type');
    inviter.tabs[inviter.tabs.selected]();

    inviter.toggleTabs();
};

inviter.toggleTabs = function() {
    $("div.invite-modal ul.nav-tabs li").click(function() {
        var type = $(this).attr('data-type');
        $("div.invite-modal ul.nav-tabs li.active").removeClass('active');
        $(this).addClass('active');
        $("div.invite-modal div.content-modal").hide();
        $("div.invite-modal div.content-modal[data-type='" + type + "']").show();
        inviter.tabs.selected = type;
        inviter.tabs[type]();
    });
};

inviter.tabs.facebook = function() {
    var $container = $("div.content-modal[data-type ='facebook']");
    FB.getLoginStatus(function(response) {
        if (response.status === 'connected') {
            $container.find('is-logged').show();
        } else {
            $container.find('not-logged').show();
        }
        
        return false;
    });
};

inviter.tabs.email = function() {
};

inviter.tabs.twitter = function() {
    var $container = $("div.content-modal[data-type ='twitter']");
    $container.find('textarea').html('¡ Los invito a Fansworld.TV ! ' + inviter.url.short);
};

/*
 
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
 $(document).ready(function() {
 var ul = $('.invite-commonfriends-list');
 ul.addClass('loading');
 ajax.genericAction({
 route: 'facebook_commonfriends',
 params: {},
 callback: function (response) {
 console.log(response);
 response = {"friends":[
 {id: 1, title: 'Giuliana Piccinini', image: 'http://profile.ak.fbcdn.net/hprofile-ak-ash4/c22.22.269.269/s160x160/418464_10150606263084010_279571319_n.jpg', url: '#'},
 {id: 4, title: 'Marcelito Lopez', image: 'http://profile.ak.fbcdn.net/hprofile-ak-ash4/c66.66.828.828/s160x160/481742_4555486217120_291734252_n.jpg', url: '#'},
 {id: 4, title: 'Denise Suarez', image: 'http://sphotos-f.ak.fbcdn.net/hphotos-ak-prn1/734233_4417054712179_1259783293_n.jpg', url: '#'},
 {id: 6, title: 'Cata Aumann', image: 'http://profile.ak.fbcdn.net/hprofile-ak-ash4/c22.22.269.269/s160x160/418464_10150606263084010_279571319_n.jpg', url: '#'},
 {id: 1, title: 'Mario Rodriguez', image: 'http://profile.ak.fbcdn.net/hprofile-ak-ash4/c102.0.401.401/s160x160/284_17401179476_907_n.jpg', url: '#'},
 {id: 6, title: 'Diana Leon', image: 'http://profile.ak.fbcdn.net/hprofile-ak-ash4/c34.76.425.425/s160x160/401686_4297538789159_704876338_n.jpg', url: '#'}
 ]}
 $.each(response.friends, function() {
 $.when(templateHelper.htmlTemplate('general-friend_element', this))
 .then(function(htmlTemplate) {
 $(htmlTemplate).appendTo(ul);
 })
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
 });
 
 */