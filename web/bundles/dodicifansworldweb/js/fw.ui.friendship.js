/*
 * library dependencies:
 *      jquery 1.8.3
 * external dependencies:
 *      base.js ajax.genericAction()
 */

// fansWorld freindship plugin 1.0


$(document).ready(function () {
    "use strict";
    var pluginName = "fwFriendship";
    var defaults = {
        propertyName: "fansworld",
        userId: null,
        // custom callback events
        onError: function(error) {},
        addFriend: function(data) {},
        onRemoveFriend: function(data) {}
    };
    function Plugin(element, options) {
        this.element = element;
        this.options = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;
        this.init();
    }
    Plugin.prototype = {
        init: function () {
            var that = this;
            var self = $(that.element);
            that.options.userId = self.attr('data-user-id');
            self.addClass(that._name);

            if (window.isLoggedIn) {
                self.on('click', that.addFriend);
            } else {
                self.on('click',function(event) {
                    $('[data-login-btn]').click();
                });
            }
        },
        addFriend: function(event) {
            var that = this;
            var self = $(this);
            event.preventDefault();
            if (!window.isLoggedIn) {
                $('[data-login-btn]').click();
                return false;
            }
            var plugin = $(this).data('fwFriendship');
            plugin.toggleFriendship($(event.srcElement).attr('data-user-id'));
        },
        toggleFriendship: function(userId) {
            var that = this;
            var self = $(that.element);
            var targetId = self.attr('data-user-id');
            var friendGroups = [];
            $("ul.friendgroupsList li input:checkbox:checked").each(function(k, el){
                friendgroups[k] = $(el).val();
            });
            ajax.genericAction(
                'friendship_ajaxaddfriend',
                {
                    'target': targetId,
                    'friendgroups': friendGroups
                },
                function(responseJSON) {
                    if(responseJSON.error) {
                        window.error(responseJSON.message);
                        return that.options.onError(error);
                    }
                    if(responseJSON) {
                        if(responseJSON.friendship) {
                            that.onAddFriend(responseJSON);
                        }
                    }
                },
                function(error) {
                    window.error(error.responseText);
                    window.notice(data.message);
                    return that.options.onError(error);
                });
        },
        onAddFriend: function(data) {
            var that = this;
            window.notice(data.message);
            return that.options.onAddFriend(that, data);
        },
        onRemoveFriend: function(data){
            var that = this;
            window.notice(data.message);
            return that.options.onRemoveFriend(that, data);
        },
        destroy: function() {
            var that = this;
            $(that.element).unbind("destroyed", that.teardown);
            that.teardown();
        },
        teardown: function() {
            var that = this;
            $.removeData($(that.element)[0], that._name);
            $(that.element).removeClass(that._name);
            that.unbind();
            that.element = null;
        },
        bind: function() { },
        unbind: function() { }
    };
    $.fn[pluginName] = function (options) {
        return this.each(function () {
            if (!$.data(this, pluginName)) {
                $.data(this, pluginName, new Plugin(this, options));
            }
        });
    };
});
//Attach plugin to all matching element
$(document).ready(function () {
    "use strict";

    $("[data-friendship-add]:not('[data-override]')").fwFriendship({
        onAddFriend: function(plugin, data) {
            var self = $(plugin.element);
            self.addClass('unfan');
            self.removeClass('befun');
            self.get(0).lastChild.nodeValue = "Eres Fan";
        },
        onRemoveFriend: function(plugin, data) {
            var self = $(plugin.element);
            self.addClass('befun');
            self.removeClass('unfan');
            self.get(0).lastChild.nodeValue = "Ser Fan";
        }
    });

    $("[data-friendship-remove]:not('[data-override]')").fwFriendship({
        onRemoveFriend: function(plugin, data) {
            window.location.reload();
        }
    });
});