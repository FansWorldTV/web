var share = {};

share.init = function() {
    console.log("SHARE INIT")
    $("[data-share-button], i.close-share").on('click', function(event) {
        $("[data-share-button]").toggleClass('active');
        $("[data-sharebox-gral]").toggle();
    });

    $(".sharer-container .share-buttons [data-share-content]").fwModalDialog();

/*
    $(".btn-checkbox").on('click', function() {
        $(this).toggleClass('active');

        if ($(this).hasClass('fb')) {
            if ($(this).hasClass('active')) {
                FB.ui({
                    method: 'permissions.request',
                    'perms': window.FBperms,
                    'display': 'popup',
                    'response_type': 'signed_request',
                    'fbconnect': 1,
                    'next': 'http://' + location.host + Routing.generate(appLocale + '_' + 'facebook_jstoken')
                },
                function(response) {
                    console.log(response);
                });
            }
        }
        if ($(this).hasClass('tw')) {
            if ($(this).hasClass('active')) {
                window.open(Routing.generate(appLocale + '_' + 'twitter_redirect'), 'fw_twit_link', 'menubar=no,status=no,toolbar=no,width=500,height=300');
            }
        }
    });
*/
    share.autocomplete();
    share.it();
};

share.selectedList = [];
share.autocomplete = function() {

    /*
     $("input[data-token-input]").tokenInput(Routing.generate(appLocale+'_share_ajaxwith'), {
     theme: 'fansworld',
     queryParam: 'term',
     preventDuplicates: true,
     propertyToSearch: 'label',
     onAdd: function(item){
     share.selectedList.push(item);
     }
     });
     */
    $("input[data-token-input]").fwTagify({action: 'share', suggestionsOnly: true});
};

share.it = function() {
    $("#share_it:not('.disabled')").live('click', function() {
        var self = $(this);
        var params = {};

        $(self).addClass('disabled');

        /*params['tw'] = $(".btn-checkbox.tw").hasClass('active');
        params['fb'] = $(".btn-checkbox.fb").hasClass('active');*/
        params['fw'] = true;

        params['message'] = $("input.wywtsay").val();
        params['entity-type'] = $("[data-share-button]").attr('data-type');
        params['entity-id'] = $("[data-share-button]").attr('data-id');

        var shareWith = $("input[data-token-input]").data('fwTagify').getAllTags();

        params['share-list'] = {};

        // User can only share with idol, team and user entities
        var idols = shareWith['idol'].selected;
        var team = shareWith['team'].selected;
        // var text = shareWith['text'].selected; do now use custom tags
        var user = shareWith['user'].selected;

        for (var i in idols) {
            params['share-list'][idols[i].result.id] = idols[i].result.type;
        }
        for (var i in user) {
            params['share-list'][user[i].result.id] = user[i].result.type;
        }
        for (var i in team) {
            params['share-list'][team[i].result.id] = team[i].result.type;
        }
        /*
         for(var i in shareWith){
         var ele = shareWith[i];
         var finded = share.findIntoTheArray(ele.id, share.selectedList);
         console.log(finded);
         params['share-list'][finded.result.id] = finded.result.type;
         }
         */


        /*if (!$(".btn-checkbox").hasClass('active')) {
            error("Seleccione un canal para compartir.");
            self.removeClass('disabled');
            return false;
        }*/

        console.log(params);
        console.log("before ajax call")
        ajax.genericAction('share_ajax', params, function(r) {
            if (r) {
                if (r.error) {
                    console.log(r.msg);
                } else {
                    success("Contenido compartido!");
                    $("[data-sharebox-gral]").slideToggle();
                    $("[data-share-button]").toggleClass('active');
                    $("input.wywtsay").val("");
                    $(".btn-checkbox").removeClass('active');
                    $(".btn-checkbox.fw").addClass('active');
                }
            }
            self.removeClass('disabled');
        }, function(msg) {
            error(msg);
            self.removeClass('disabled');
        });
    });
};

share.findIntoTheArray = function(id, foo) {
    for (var i in foo) {
        obj = foo[i];
        if (obj.id == id) {
            return obj;
        }

        return false;
    }
};

$(document).ready(function() {
    share.init();
});