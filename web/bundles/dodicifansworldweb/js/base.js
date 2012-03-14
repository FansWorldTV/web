/**
 * 
 */


var ajax = {
    active: false,
  
    init: function(){
        $.ajaxSetup({
            type: 'post',
            dataType: 'json'
        });
    },
    
    setCallback: function(callback, errorcallback){
        $.ajaxSetup({
            success: function(response){
                if(typeof(callback) !== 'undefined'){
                    callback(response);
                }
            },
            error:function (xhr, ajaxOptions, thrownError){
                if(typeof(errorcallback) !== 'undefined'){
                    errorcallback(xhr.responseText);
                }
            }  
        });
    },
    
    search: function(method, params, callback){
        if(!ajax.active) {
            ajax.active = true;
            
            $.ajax({
                url: 'http://'+ location.host + Routing.generate( appLocale + '_' + method),
                data: params,
                success: function(response){
                    ajax.active = false;
                    if( typeof(callback) !== 'undefined' ){
                        callback(response);
                    }
                }
            });
        }
    },
  
    searchAction: function(query, page, callback) {
        ajax.search('search_ajaxsearch', {'query': query, 'page': page}, callback);
    },
    
    friendsAction: function(query, page, callback) {
        ajax.search('search_ajaxfriends', {'query': query, 'page': page}, callback);
    },
    
    searchIdolsAction: function(query, page, isIdol, callback){
        ajax.search('search_ajaxidols', {'query': query, 'page': page, 'isIdol': isIdol}, callback);
    },
    
    contestsListAction: function(page, filter, callback) {
        if(!ajax.active) {
            ajax.active = true;
            
            if(!filter || typeof(filter) == 'undefined'){
                filter = null;
            }
            
            $.ajax({
                url: 'http://' + location.host + Routing.generate( appLocale + '_contest_ajaxlist'),
                data: {
                    'filter': filter,
                    'page': page
                },
                success: function(response){
                    ajax.active = false;
                    if(typeof(callback) !== 'undefined'){
                        callback(response);
                    }
                }
            });
        }
    },
    
    contestParticipateAction: function(contest, text, photo, video, callback){
        if(!ajax.active){
            ajax.active = true;
            
            if(typeof(text) == 'undefined'){
                text = false;
            }
            if(typeof(photo) == 'undefined'){
                photo = false;
            }
            if(typeof(video) == 'undefined'){
                video = false;
            }
            
            $.ajax({
                url: 'http://' + location.host + Routing.generate( appLocale + '_contest_ajaxparticipate'),
                data: {
                    'contestId' : contest,
                    'text' : text,
                    'photo' : photo,
                    'video' : video
                },
                success: function(r){
                    if(typeof(callback) !== 'undefined'){
                        callback(r);
                    }
                    ajax.active=false;
                }
            });
        }
    },
    
    contestAddCommentAction: function(content, contestId, callback){
        if(!ajax.active){
            ajax.active = true;
            $.ajax({
                url: 'http://' + location.host + Routing.generate( appLocale + '_contest_ajaxaddcomment'),
                data: {
                    'content' : content,
                    'contestId' : contestId
                },
                success: function(r){
                    if(typeof(callback) !== 'undefined'){
                        callback(r);
                    }
                    ajax.active = false;
                }
            });
        }
    },
    
    pendingFriendsAction: function(page, limit, callback){
        ajax.setCallback(callback);
        $.ajax({
            url: 'http://' + location.host + Routing.generate( appLocale + '_user_ajaxpendingfriends' ),
            data: {
                'page' : page,
                'limit' : limit
            }
        });
    },
    
    
    numberPendingRequests: function(callback){
        ajax.setCallback(callback);
        $.ajax({
            url: 'http://' + location.host + Routing.generate( appLocale + '_user_ajaxnumberofpendingrequests' )
        });
    },
    
    acceptRequestAction: function(friendshipId, callback){
        ajax.setCallback(callback);
        $.ajax({
            url: 'http://' + location.host + Routing.generate( appLocale + '_user_ajaxacceptrequest'),
            data: {
                'id' : friendshipId
            }
        });
    },
    
    denyRequestAction: function(id, callback){
        ajax.setCallback(callback);
        $.ajax({
            url: 'http://' + location.host + Routing.generate( appLocale + '_user_ajaxdenyrequest'),
            data: {
                'id' : id
            }
        });
    },
    
    notificationNumberAction: function(callback){
        ajax.setCallback(callback);
        $.ajax({
            url: 'http://' + location.host + Routing.generate( appLocale + '_user_ajaxnotificationnumber' )
        });
    },
    
    getNotifications: function(callback){
        ajax.setCallback(callback);
        $.ajax({
            url: 'http://' + location.host + Routing.generate( appLocale + '_user_ajaxnotifications' )
        });
    },
    
    deleteNotification: function(id, callback){
        ajax.setCallback(callback);
        $.ajax({
            url: 'http://' + location.host + Routing.generate( appLocale + '_user_ajaxdeletenotification'),
            data: {
                'id' : id
            }
        });
    },
    
    likeToggleAction: function(type, id, callback, errorcallback){
        ajax.setCallback(callback, errorcallback);
        $.ajax({
            url: 'http://' + location.host + Routing.generate( appLocale + '_like_ajaxtoggle'),
            data: {
                'id' : id,
                'type' : type
            }
        });
    },
    
    shareAction: function(type, id, callback, errorcallback){
        ajax.setCallback(callback, errorcallback);
        $.ajax({
            url: 'http://' + location.host + Routing.generate( appLocale + '_share_ajax'),
            data: {
                'id' : id,
                'type' : type
            }
        });
    },
    
    globalCommentAction: function(type, id, content, privacy, ispin, callback, errorcallback){
        ajax.setCallback(callback, errorcallback);
        $.ajax({
            url: 'http://' + location.host + Routing.generate( appLocale + '_comment_ajaxpost'),
            type: 'POST',
            data: {
                'id' : id,
                'type' : type,
                'content' : content,
                'privacy' : privacy,
                'ispin' : ispin
            }
        });
    },
    
    getPhotosAction: function(userid, page, renderpin, callback, errorcallback){
        ajax.setCallback(callback, errorcallback);
        $.ajax({
            url: 'http://' + location.host + Routing.generate( appLocale + '_photo_get'),
            data: {
                'userId' : userid,
                'page' : page,
                'renderpin' : renderpin
            }
        });
    },
    
    getAlbumsAction: function(userid, page, callback){
        ajax.setCallback(callback);
        $.ajax({
            url: 'http://' + location.host + Routing.generate( appLocale + '_album_get'),
            data: {
                'userId' : userid,
                'page' : page
            }
        });
    },
    
    addFriendAction: function(targetId, friendGroups, callback){
        ajax.setCallback(callback);
        $.ajax({
           url: 'http://'  + location.host + Routing.generate( appLocale + '_friendship_ajaxaddfriend'),
           data: {
               'target': targetId,
               'friendgroups': friendGroups
           }
        });
    },
    
    cancelFriendAction: function(friendshipId, callback){
        ajax.setCallback(callback);
        $.ajax({
           url: 'http://' + location.host + Routing.generate( appLocale + '_friendship_ajaxcancelfriend'),
           data: {
               'friendship': friendshipId
           }
        });
    },
    
    videosSearchAction: function(params, callback, errorCallback){
        ajax.setCallback(callback, errorCallback);
        $.ajax({
           url: 'http://' + location.host + Routing.generate( appLocale + '_video_ajaxsearch'),
           data: params
        });
    }
  
};

function trim (myString) {
    return myString.replace(/^\s+/g,'').replace(/\s+$/g,'')
}

$(document).ready(function(){
    $("form").each(function(){
        $(this).attr("novalidate", "true"); 
    });
}); 

function goLogIn(){
    window.location.href = Routing.generate('_security_check');
}

function onFbInit() {
    if (typeof(FB) != 'undefined' && FB != null ) {
        FB.Event.subscribe('auth.statusChange', function(response) {
            if (response.session || response.authResponse) {
                setTimeout(goLogIn, 500);
            } else {
                window.location.href = Routing.generate('_security_logout');
            }
        });
    }
}


/* Wrapper functions for Toast messages */
function notice (message) {
    $().toastmessage('showNoticeToast', message);
}
function warning (message) {
    $().toastmessage('showWarningToast', message);
}
function error (message) {
    $().toastmessage('showToast', {
        text     : message,
        sticky   : true,
        type     : 'error'
    });
}
function success (message) {
    $().toastmessage('showSuccessToast', message);
}