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
    
    contestParticipateAction: function(contest, callback){
        if(!ajax.active){
            ajax.active = true;
            
            $.ajax({
                url: 'http://' + location.host + Routing.generate( appLocale + '_contest_ajaxparticipate'),
                data: {
                    'contestId' : contest
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