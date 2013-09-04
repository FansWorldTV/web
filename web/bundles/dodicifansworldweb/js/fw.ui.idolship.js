/*
 * library dependencies:
 *      jquery 1.8.3
 * external dependencies:
 *      base.js ajax.genericAction()
 */

// fansWorld idolship plugin 1.0


$(document).ready(function () {
    "use strict";
    var pluginName = "fwIdolship";
    var defaults = {
        propertyName: "fansworld",
        idolId: null,
        // custom callback events
        onError: function(error) {},
        onAddIdol: function(data) {},
        onRemoveIdol: function(data) {}
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
            that.options.idolId = self.attr('data-idol-id');
            self.addClass(that._name);

            self.on('click', that.addIdol);
        },
        addIdol: function(event) {
            var that = this;
            var self = $(this);
            event.preventDefault();
            if (!window.isLoggedIn) {
                $('[data-login-btn]').click();
                return false;
            }
            var plugin = $(this).data('fwIdolship');
            plugin.toggleIdolship($(this).attr('data-idol-id'));
        },
        toggleIdolship: function(idolId) {
            var that = this;
            var self = $(that.element);
            ajax.genericAction(
                'idolship_ajaxtoggle',
                { 'idol-id': idolId },
                function(responseJSON) {
                    if(responseJSON) {
                        if(responseJSON.isFan) {
                            that.onAddIdol(responseJSON);
                        } else {
                            that.onRemoveIdol(responseJSON);
                        }
                    }
                },
                function(error) {
                    window.error(error.responseText);
                    return that.options.onError(error);
                });
        },
        onAddIdol: function(data) {
            var that = this;
            window.notice(data.message);
            return that.options.onAddIdol(this, data);
        },
        onRemoveIdol: function(data){
            var that = this;
            window.notice(data.message);
            return that.options.onRemoveIdol(this, data);
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
    $("[data-idolship-add]:not('[data-override]')").fwIdolship({
        onAddIdol: function(plugin, data) {
            var self = $(plugin.element);
            self.addClass('unfan');
            self.removeClass('befun');
            self.get(0).lastChild.nodeValue = "Eres Fan";
        },
        onRemoveIdol: function(plugin, data) {
            var self = $(plugin.element);
            self.addClass('befun');
            self.removeClass('unfan');
            self.get(0).lastChild.nodeValue = "Ser Fan";
        }
    });

    $(".btn_idolship:not('.remove')").fwIdolship({
        onAddIdol: function(plugin, data) {
            var number = Number($('.numbers-info .fans-info .numero').text()) + 1;
            $('.numbers-info .fans-info .numero').text(number);
        }
    });

    $(".btn_idolship.remove").fwIdolship({
        onRemoveIdol: function(plugin, data) {
            window.location.reload();
        }
    });
});