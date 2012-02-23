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
  
    searchAction: function(query, page, callback) {
        if(!ajax.active) {
            ajax.active = true;
            
            $.ajax({
                url: 'http://'+ location.host + Routing.generate( appLocale + '_user_ajaxsearch'),
                data: { 
                    'query': query,
                    'page': page
                },
                success: function(response){
                    ajax.active = false;
                    if( typeof(callback) !== 'undefined' ){
                        callback(response);
                    }
                }
            });
        }
    },
    
    friendsAction: function(query, page, callback) {
        if(!ajax.active) {
            ajax.active = true;
            
            $.ajax({
                url: 'http://'+ location.host + Routing.generate( appLocale + '_user_ajaxfriends'),
                data: { 
                    'query': query,
                    'page': page
                },
                success: function(response){
                    ajax.active = false;
                    if( typeof(callback) !== 'undefined' ){
                        callback(response);
                    }
                }
            });
        }
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
        $.ajax({
            url: 'http://' + location.host + Routing.generate( appLocale + '_user_ajaxpendingfriends' ),
            data: {
                'page' : page,
                'limit' : limit
            },
            success: function(r){
                if(typeof(callback) !== 'undefined'){
                    callback(r);
                }
            }
        });
    },
    
    
    numberPendingRequests: function(callback){
        $.ajax({
            url: 'http://' + location.host + Routing.generate( appLocale + '_user_ajaxnumberofpendingrequests' ),
            success: function(response){
                if(typeof(callback) !== 'undefined'){
                   callback(response);
                }
            }
        });
    },
    
    acceptRequestAction: function(friendshipId, callback){
        $.ajax({
           url: 'http://' + location.host + Routing.generate( appLocale + '_user_ajaxacceptrequest'),
           data: {
               'id' : friendshipId
           },
           success: function(response){
               if(typeof(callback) !== 'undefined'){
                   callback(response);
               }
           }
        });
    },
    
    likeToggleAction: function(type, id, callback, errorcallback){
        $.ajax({
           url: 'http://' + location.host + Routing.generate( appLocale + '_like_ajaxtoggle'),
           data: {
               'id' : id,
               'type' : type
           },
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
    
    shareAction: function(type, id, callback, errorcallback){
        $.ajax({
           url: 'http://' + location.host + Routing.generate( appLocale + '_share_ajax'),
           data: {
               'id' : id,
               'type' : type
           },
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