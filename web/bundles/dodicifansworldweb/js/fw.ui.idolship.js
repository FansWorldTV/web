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
            $(this).off();      // remove event listeners
            var plugin = $(this).data('fwIdolship');
            plugin.toggleIdolship($(this).attr('data-idol-id'));
        },
        toggleIdolship: function(idolId) {
            var that = this;
            var self = $(that.element);
            self.addClass('loading-small');
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
                    self.removeClass('loading-small');
                },
                function(error) {
                    window.error(error.responseText);
                    self.removeClass('loading-small');
                    return that.options.onError(error);
                });
        },
        onAddIdol: function(data) {
            var that = this;
            return that.options.onAddIdol(this, data);
        },
        onRemoveIdol: function(data){
            var that = this;
            var self = $(that.element);
            console.log("onRemoveIdol: " + JSON.stringify(data))
            return that.options.onRemoveIdol(data);
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
            self.addClass('disabled');
            self.removeClass('add');
            self.text("YA ERES FAN");
        }
    });
});