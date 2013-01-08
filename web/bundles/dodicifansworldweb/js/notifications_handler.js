var notifications = {};
notifications.total = 0;

notifications.init = function() {
    if ($('[data-notif-total]').size() > 0) {
        notifications.initCounts();
        notifications.showUnread();
        notifications.delegateReadedEvent();
        notifications.listen();
    }
};

notifications.listen = function() {
    function handleData(response) {
        response = JSON.parse(response);
        console.log(response);
        if (response) {
            if (response.t == 'n') {
                notifications.handleNewNotification(response);
            }
        }
    }
    if ((typeof Meteor != 'undefined') && (typeof notificationChannel != 'undefined')) {
        Meteor.registerEventCallback("process", handleData);   
        Meteor.joinChannel(notificationChannel);
        Meteor.connect();
        console.log('Escuchando notifications..');
    }
};

notifications.handleNewNotification = function(response) {
    id = response.id; entity = response.p;
    notifications.updateBubbleCount(entity, 1, false);
    //ajax.getNotification(id, 
    ajax.genericAction('user_ajaxnotification', { 'id' : id}, 
    function(response) {
        if (response) {
            entityContent = $('[data-entity]').data('entity');
            if ('total' === entityContent) {
                $('[data-notification]').prepend(notifications.getClassicNotificationTemplate(response));
                $('[data-sidebaralert]:first').effect("bounce", {times:2}, 300);
            } else {
                if (entityContent  === entity) {
                    if ($('[data-sidebaralert]').size() >= 4) $('[data-sidebaralert]:last').fadeOut("slow").remove();
                    $('[data-notification]').prepend(notifications.getClassicNotificationTemplate(response));
                    $('[data-sidebaralert]:first').effect("bounce", {times:2}, 300);
                } else {
                    notice(notifications.getBubbleNotificationTemplate(response));
                }  
            } 
        }
    });
};

notifications.showCounts = function(entity, value) {
    if (value > 0) {
        if ($('[data-notif-' + entity + ']').children("span").length > 0) {
            $('[data-notif-' + entity + ']').children("span").eq(0).html(value);
        } else {
            $('[data-notif-' + entity + ']').prepend(getBadgeTemplate(value));
        }
    } else {
        if ($('[data-notif-' + entity + ']').children("span").length > 0) $('[data-notif-' + entity + ']').children("span").eq(0).remove();
    }
    if ('total' === entity && $('[data-notif-count]').size()) $('[data-notif-count]').html(value);
    function getBadgeTemplate(number) {
        return '<span class="label label-warning label-toolbar">' + number + '</span>';
    }
};

notifications.initCounts = function () {
    ajax.genericAction('user_ajaxgetnotifications_typecounts', {}, 
        function(response) {
            if (response) {
                for (i in response) {
                    result = response[i];
                    notifications.total += parseInt(result.cnt);
                    $('[data-notif-' + result.parent + ']').data('notif-' + result.parent).count = parseInt(result.cnt);
                    notifications.showCounts(result.parent, result.cnt);
                }
                notifications.showCounts("total", notifications.total);
            }
        }
    );
};

notifications.updateBubbleCount = function(entity, value, parentOfNotification) {
    // newCant = $('[data-notif-' + entity + ']').data('notif-' + entity).count += value;
    actualCant = $('[data-notif-' + entity + ']').data('notif-' + entity).count;
    newCant = parseInt(actualCant) + parseInt(value);
    $('[data-notif-' + entity + ']').data('notif-' + entity).count = newCant;
    notifications.total += value;
    notifications.showCounts(entity, newCant);
    notifications.showCounts("total", notifications.total);
    $('[data-notif-' + entity + '] span').effect("highlight", {color: "#a0c882"}, 2000);
    if ('total' != entity) $('[data-notif-' + entity + ']').effect("bounce", { times: 4 }, 1200);
    if ('total' === entity) {
         newCant = $('[data-notif-' + parentOfNotification + ']').data('notif-' + parentOfNotification).count += value;
         notifications.showCounts(parentOfNotification, newCant);
    }
};

notifications.showUnread = function () {
    if ($('[data-entity]').size() > 0) {
        entity = $('[data-entity]').data('entity');
        ajax.genericAction('notification_getlatest', { 'parentName' : entity}, 
            function(response) {
                console.log('Notifications unread for ' + entity + ': ' + response);
                nCounts = response.length;
                if (nCounts > 4) nCounts = 4;
                for (j = 0; j < nCounts; j++) {
                    id = response[j];
                    ajax.getNotification(id, function(response) {
                    if (response) {
                        if ($('[data-entity]').size() > 0) {
                            //if ($('[data-sidebaralert]').size() >= 4) $('[data-sidebaralert]:last').fadeOut("slow").remove();
                            $('[data-notification]').prepend(notifications.getClassicNotificationTemplate(response));
                        }
                    }});
                }
            }
        )
    }
};

notifications.readNotification = function(id) {
    ajax.genericAction({route: 'user_ajaxdeletenotification', params: { id: id }, callback: function(response) {
        console.log('ReadNotification: ' + id + ' => ' + response);
        return response;
    }});
};

notifications.acceptFriendship = function(id) {
    ajax.genericAction({route: 'friendship_accept', params: { id: id }, callback: function(response) {
        console.log('acceptFriendship: ' + id + ' => ' + response);
        return response;
    }});
};

notifications.rejectFriendship = function(id) {
    ajax.genericAction({route: 'friendship_reject', params: { id: id }, callback: function(response) {
        console.log('rejectFriendship: ' + id + ' => ' + response);
        return response;
    }});
};

notifications.delegateReadedEvent = function () {
    $('[data-notification]').on( 'mouseenter', '[data-sidebaralert]', function() {
        readed = $(this).data('sidebaralert');
        if (!readed) {
            $(this).data('sidebaralert', true);
            entity = $('[data-entity]').data('entity');
            notificationId = $(this).find('[data-notifid]').data('notifid');
            parentOfNotification = $(this).find('[data-notifid]').data('parent');
            if ($(this).find('[data-friendship-pending]').size() == 0) {
                notifications.readNotification(notificationId);
                notifications.updateBubbleCount(entity, -1, parentOfNotification);
            }
        }
    });

    $('[data-friendship-container]').on("click", '[data-accept-friendship]', function() {
        var response = confirm("Aceptar solicitud?");
        if (response) {
            friendshipId = $(this).data('accept-friendship');
            notifications.acceptFriendship(friendshipId);
            notifications.updateBubbleCount('total', -1, 'fans');
            $(this).parent('[data-friendship-container]').html(' | Solicitud Aceptada');
        }
    });

    $('[data-friendship-container]').on("click", '[data-reject-friendship]', function() {
        var response = confirm("Cancelar solicitud?");
        if (response) {
            friendshipId = $(this).data('reject-friendship');
            notifications.rejectFriendship(friendshipId);
            notifications.updateBubbleCount('total', -1, 'fans');
            $(this).parent('[data-friendship-container]').html(' | Solicitud Denegada');
        }
    });
};

notifications.getBubbleNotificationTemplate = function(htmlResponse) {
    return '<div class="bubbleNotification" data-bubblealert>' + htmlResponse + '</div>';
};

notifications.getClassicNotificationTemplate = function (htmlResponse) {
    btnClose = '<button type="button" class="close" data-dismiss="alert">&times;</button>';
    return '<div class="alert alert-success" data-sidebaralert=false>'+ btnClose + htmlResponse + '</div>';
};

notifications.sendToMeteor = function (type) {
    ajax.genericAction('notification_setfake', {'type': type}, 
        function(response){}
    );
};

notifications.test = function(id, parent) {
    var r = {}; r.id = id; r.p = parent;
    notifications.handleNewNotification(r);
};

$(document).ready(function() {
    notifications.init();
});