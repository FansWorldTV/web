var share = {};

share.init = function(){
    $(".btn.share").on('click', function(){
        $(this).toggleClass('active');
        $(".share-box").slideToggle();
    });
    
    $(".btn-checkbox").on('click', function(){
        $(this).toggleClass('active');
        
        if($(this).hasClass('fb')){
            if($(this).hasClass('active')){
                FB.ui({
                    method: 'permissions.request',
                    'perms': 'email,user_birthday,user_location,publish_actions',
                    'display': 'popup',
                    'response_type': 'signed_request',
                    'fbconnect': 1,
                    'next': 'http://' + location.host + Routing.generate( appLocale + '_' + 'facebook_jstoken')
                },
                function(response) {
                    console.log(response);
                });
            }
        }
        if($(this).hasClass('tw')){
            if($(this).hasClass('active')){
                window.open(Routing.generate(appLocale + '_' + 'twitter_redirect'), 'fw_twit_link', 'menubar=no,status=no,toolbar=no,width=500,height=300');
            }
        }
    });
    
    share.it();
};

share.it = function(){
    $("#share_it").on('click', function(){
        var params = {};
        params['tw'] = $(".btn-checkbox.tw").hasClass('active');
        params['fw'] = $(".btn-checkbox.fw").hasClass('active');
        params['fb'] = $(".btn-checkbox.fb").hasClass('active');
        params['message'] = $("input.wywtsay").val();
        params['entity-type'] = $("a.btn.share").attr('data-type');
        params['entity-id'] = $("a.btn.share").attr('data-id');
        
        ajax.genericAction('share_ajax', params, function(r){
            if(r){
                if(r.error){
                    console.log(r.msg);
                }else{
                    success("Contenido compartido!");
                    $(".share-box").slideToggle();
                    $(".btn.share").toggleClass('active');
                }
            }
        }, function(msg){
            error(msg);
        });
    });
};

    
//$(".share-box").find('form').submit(function(){
//    var text = $(".share-box input.wywtsay").val();
//    $("h4.wywtsay").html($("input.wywtsay").val());
//    $(".top form").remove();
//        
//    return false;
//});
    
$(document).ready(function(){
    share.init();
});