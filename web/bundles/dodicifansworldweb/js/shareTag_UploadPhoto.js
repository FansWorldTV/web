var tagEngine = {};
tagEngine.selectedTeams = [];
tagEngine.selectedIdols = [];
tagEngine.selectedTexts = [];

tagEngine.bindTokenizer = function() {
    $("#form_tagtextac").tokenInput(Routing.generate(appLocale+'_tag_ajaxmatch'), {
        theme: 'fansworld',
        queryParam: 'text',
        preventDuplicates: true,
        propertyToSearch: 'label',
        onAdd: function(item) {
            tagEngine.addEntityItem(item);
        },
        onDelete: function(item) {
            tagEngine.deleteEntityItem(item);
        }
    }); 
};

tagEngine.addEntityItem = function(item) {
    switch (item.result.type) {
        case 'team':
            tagEngine.selectedTeams.push(item);
            tagEngine.updateInput('#form_tagteam', tagEngine.selectedTeams);
            break;
        case 'idol':
            tagEngine.selectedIdols.push(item);
            tagEngine.updateInput('#form_tagidol', tagEngine.selectedIdols);
            break;
        default:
            tagEngine.selectedTexts.push(item);
            tagEngine.updateInput('#form_tagtext', tagEngine.selectedTexts);
    }
};

tagEngine.deleteEntityItem = function(item) {
    switch (item.result.type) {
        case 'team':
            pos = tagEngine.selectedTeams.indexOf(item);
            tagEngine.selectedTeams.splice(pos, 1);
            tagEngine.updateInput('#form_tagteam', tagEngine.selectedTeams);
            break;
        case 'idol':
            pos = tagEngine.selectedIdols.indexOf(item);
            tagEngine.selectedIdols.splice(pos, 1);
            tagEngine.updateInput('#form_tagidol', tagEngine.selectedIdols);
            break;
        default:
            pos = tagEngine.selectedTexts.indexOf(item);
            tagEngine.selectedTexts.splice(pos, 1);
            tagEngine.updateInput('#form_tagtext', tagEngine.selectedTexts);
    }
};

tagEngine.updateInput = function(inputSelector, list) {
    var str = '';
    for (var i in list) {
        str += list[i].result.id + ',';
    }
    $(inputSelector).val(str);
};

var shareEngine = {};
shareEngine.fb = false;
shareEngine.tw = false;
shareEngine.fw = true;
shareEngine.selectedTeams = [];
shareEngine.selectedIdols = [];
shareEngine.selectedUsers = [];

shareEngine.init = function() {
    $(".btn-checkbox").on('click', function() {
        $(this).toggleClass('active');
        if ($(this).hasClass('fb')) {
            if ($(this).hasClass('active')) {
                shareEngine.fb = true;
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
            } else {
                shareEngine.fb = false;
            }
        }
        if ($(this).hasClass('tw')) {
            if($(this).hasClass('active')) {
                shareEngine.tw = true;
                window.open(Routing.generate(appLocale + '_' + 'twitter_redirect'), 'fw_twit_link', 'menubar=no,status=no,toolbar=no,width=500,height=300');
            } else {
                shareEngine.tw = false;
            }
        }
        if ($(this).hasClass('fw')) {
            if($(this).hasClass('active')) {
                shareEngine.fw = true;
            } else {
                shareEngine.fw = false;
            }
        }
        shareEngine.updateInputValues();
    });
    shareEngine.bindTokenizer();
    shareEngine.updateInputValues();
};

shareEngine.bindTokenizer = function() {
    $("input[data-token-input]").tokenInput(Routing.generate(appLocale+'_share_ajaxwith'), {
        theme: 'fansworld',
        queryParam: 'text',
        preventDuplicates: true,
        propertyToSearch: 'label',
        onAdd: function(item) {
            shareEngine.addEntityItem(item);
        },
        onDelete: function(item) {
            shareEngine.deleteEntityItem(item);
        }
    }); 
};

shareEngine.updateInputValues = function() {
    $('#form_fb').val(shareEngine.fb);
    $('#form_tw').val(shareEngine.tw);
    $('#form_fw').val(shareEngine.fw);
};

shareEngine.addEntityItem = function(item) {
    switch (item.result.type) {
        case 'team':
            shareEngine.selectedTeams.push(item);
            tagEngine.updateInput('#form_shareteam', shareEngine.selectedTeams);
            break;
        case 'idol':
            shareEngine.selectedIdols.push(item);
            tagEngine.updateInput('#form_shareidol', shareEngine.selectedIdols);
            break;
        default:
            shareEngine.selectedUsers.push(item);
            tagEngine.updateInput('#form_shareuser', shareEngine.selectedUsers);
    }
};

shareEngine.deleteEntityItem = function(item) {
    switch (item.result.type) {
        case 'team':
            pos = shareEngine.selectedTeams.indexOf(item);
            shareEngine.selectedTeams.splice(pos, 1);
            tagEngine.updateInput('#form_shareteam', shareEngine.selectedTeams);
            break;
        case 'idol':
            pos = shareEngine.selectedIdols.indexOf(item);
            shareEngine.selectedIdols.splice(pos, 1);
            tagEngine.updateInput('#form_shareidol', shareEngine.selectedIdols);
            break;
        default:
            pos = shareEngine.selectedUsers.indexOf(item);
            shareEngine.selectedUsers.splice(pos, 1);
            tagEngine.updateInput('#form_shareuser', shareEngine.selectedUsers);
    }
};