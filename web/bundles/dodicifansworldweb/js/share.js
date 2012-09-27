var share = {};

share.init = function(){
    $(".btn.share, i.close-share").on('click', function(){
        $(".btn.share").toggleClass('active');
        $(".share-box").slideToggle();
    });
    
    $(".btn-checkbox").on('click', function(){
        $(this).toggleClass('active');
        
        if($(this).hasClass('fb')){
            if($(this).hasClass('active')){
                FB.ui({
                    method: 'permissions.request',
                    'perms': window.FBperms,
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
    share.autocomplete();
    share.it();
};

share.selectedList = [];
share.autocomplete = function(){
    $("input[data-token-input]").tokenInput(Routing.generate(appLocale+'_share_ajaxwith'), {
        theme: 'fansworld',
        queryParam: 'term',
        preventDuplicates: true,
        propertyToSearch: 'label',
        onAdd: function(item){
            share.selectedList.push(item);
        }
    }); 
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
   
        var shareWith = $("input[data-token-input]").tokenInput('get');

        params['share-list'] = {};
        for(var i in shareWith){
            var ele = shareWith[i];
            var finded = share.findIntoTheArray(ele.id, share.selectedList);
            params['share-list'][finded.result.id] = finded.result.type;
        }

        
        if(!$(".btn-checkbox").hasClass('active')){
            error("Seleccione un canal para compartir.");
            return false;
        }
        
        ajax.genericAction('share_ajax', params, function(r){
            if(r){
                if(r.error){
                    console.log(r.msg);
                }else{
                    success("Contenido compartido!");
                    $(".share-box").slideToggle();
                    $(".btn.share").toggleClass('active');
                    $("input.wywtsay").val("");
                    $(".btn-checkbox").removeClass('active');
                    $(".btn-checkbox.fw").addClass('active');
                }
            }
        }, function(msg){
            error(msg);
        });
    });
};

share.findIntoTheArray = function(id, foo){
    for(var i in foo){
        obj = foo[i];
        if(obj.id == id){
            return obj;
        }
                
        return false;
    }
};

$(document).ready(function(){
    share.init();
});