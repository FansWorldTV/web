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
                    console.log('miraaaa:');
                    console.log(response);

                    $.each(response.friends, function() {
                        var jsonData = {'title': this.name, 'image': 'https://graph.facebook.com/' + this.id + '/picture?type=square', 'canFriend': false, 'location': ''};
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

        } else {
            $container.removeClass('loading');
            $container.find('.not-logged').show();
        }

        return false;
    });
};

inviter.tabs.email = function() {
    var $container = $("div.content-modal[data-type ='email']");
    $container.find('form').submit(function() {
        var mailList = $container.find('form input').val();
        var submitBtn = $(this).find('.btn-success').addClass('loading-small');

        ajax.genericAction('invite_generateInvitation', {'users': mailList}, function(r) {
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
    $container.find('textarea').html('¡ Los invito a Fansworld.TV ! ' + inviter.url.short);
};
