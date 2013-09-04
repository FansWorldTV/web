var inviter = {};

inviter.url = {};
inviter.url.long = null;
inviter.url.short = null;

inviter.tabs = {};
inviter.tabs.selected = null

inviter.linkfacebook = false;
inviter.linktwitter = false;


inviter.init = function(url, linkfacebook, linktwitter) {
    inviter.url.long = url;
    inviter.linkfacebook = linkfacebook;
    inviter.linktwitter = linktwitter;

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

    $("div.invite-modal div.content-modal[data-type='email'] input").tagit({
        placeholderText: 'Email'
    });

    inviter.tabs.selected = $("div.invite-modal ul.nav-tabs li.active").attr('data-type');
    console.log(inviter.tabs.selected);
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
    $container.addClass('loading');
    $container.find('.fan-friends ul').html('');
    $container.find('.invite-friends ul').html('');

    if (inviter.linkfacebook) {
        FB.getLoginStatus(function(response) {
            if (response.status === 'connected') {

                $container.removeClass('loading');
                $container.find('.is-logged').show();
                var $inviteBlock = $container.find('.invite-block');
                $inviteBlock.find('.fan-friends').addClass('loading');
                $inviteBlock.find('.invite-friends').addClass('loading');

                ajax.genericAction({
                    route: 'facebook_commonfriends',
                    params: {},
                    callback: function(response) {
                        $.each(response.friends, function() {
                            this.canFriend = true;
                            $.when(templateHelper.htmlTemplate('fans-invite_element', this))
                                    .then(function(htmlTemplate) {
                                $(htmlTemplate).appendTo($inviteBlock.find('.fan-friends ul'));
                            })
                        });

                        $inviteBlock.find('.fan-friends').removeClass('loading');
                    },
                    errorCallback: function(errorResponse) {
                        console.log('Error');
                        console.log(errorResponse);
                        $inviteBlock.find('.fan-friends').removeClass('loading');
                        error('Error');
                    }
                });

                ajax.genericAction({
                    route: 'facebook_notcommonfriends',
                    params: {},
                    callback: function(response) {
                        $.each(response.friends, function() {
                            var location = null;

                            if (typeof(this.hometown) != 'undefined') {
                                location = this.hometown.name;
                            } else {
                                location = '';
                            }

                            var jsonData = {
                                'id': this.id,
                                'title': this.name,
                                'image': 'https://graph.facebook.com/' + this.id + '/picture?type=square',
                                'canInvite': true,
                                'canFriend': false,
                                'location': location
                            };

                            $.when(templateHelper.htmlTemplate('fans-invite_element', jsonData))
                                    .then(function(htmlTemplate) {
                                $(htmlTemplate).appendTo($inviteBlock.find('.invite-friends ul'));
                            })
                        });

                        $inviteBlock.find('.invite-friends').removeClass('loading');
                    },
                    errorCallback: function(errorResponse) {
                        console.log('Error');
                        console.log(errorResponse);
                        $inviteBlock.find('.invite-friends').removeClass('loading');
                    }
                });

                $("button.submit-invite").click(function() {
                    var friendsToInvite = [];

                    $inviteBlock.find('.invite-checkbox.active').each(function(index, el) {
                        friendsToInvite.push($(this).attr('data-invite-user'));
                    });

                    $.modalPopup('close');

                    FB.ui({method: 'apprequests',
                        message: 'My Great Request',
                        to: friendsToInvite
                    });
                });

            } else {
                $container.removeClass('loading');
                $container.find('.not-logged').show();
            }

            return false;
        });
    } else {
        $container.removeClass('loading');
        $container.find('.not-logged').show();
    }
};

inviter.tabs.email = function() {
    var $container = $("div.content-modal[data-type ='email']");
    $container.find('form').submit(function() {
        var mailList = $("div.invite-modal div.content-modal[data-type='email'] input").tagit("assignedTags");
        var msg = $container.find('textarea.msg').val();

        var submitBtn = $(this).find('.btn-success').addClass('loading-small');

        ajax.genericAction('invite_generateInvitation', {'users': mailList, 'msg': msg}, function(r) {
            submitBtn.removeClass('loading-small');
            success('Invitación enviada');

            $container.find('form').trigger('reset');
        }, function(e) {
            console.log(e);
            submitBtn.removeClass('loading-small');
        });

        return false;
    });
};

inviter.tabs.twitter = function() {
    var $container = $("div.content-modal[data-type ='twitter']");
    $container.find('textarea').html('¡Los invito a Fansworld.TV! ' + inviter.url.short);
    if(inviter.linktwitter){
        $container.find('.is-logged').show();
    }else{
        $container.find('.not-logged').show();
    }
};
