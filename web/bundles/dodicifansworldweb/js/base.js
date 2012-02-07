/**
 * 
 */


var ajax = {
    active: false,
  
    init: function(){
        $.ajaxSetup({
            type: 'POST',
            dataType: 'json'
        });
    },
  
    searchAction: function(query, page, callback) {
        if(!ajax.active) {
            ajax.active = true;
            
            $.ajax({
                url: Routing.generate( appLocale + '_user_search'),
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