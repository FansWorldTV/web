/*global $, jQuery, alert, console, error, success, endless, ajax, Routing, appLocale, exports, module, require, define*/
/*jslint nomen: true */ /* Tolerate dangling _ in identifiers */
/*jslint vars: true */ /* Tolerate many var statements per function */
/*jslint white: true */
/*jslint browser: true */
/*jslint devel: true */ /* Assume console, alert, ... */
/*jslint windows: true */ /* Assume Windows */
/*jslint maxerr: 100 */ /* Maximum number of errors */

/*
 * library dependencies:
 *      jquery 1.8.3
 * external dependencies:
 *      base.js ajax.genericAction()
 */

// fansWorld Teamship plugin 1.0

$(document).ready(function () {
    "use strict";
    var pluginName = "fwTeamship";
    var defaults = {
        propertyName: "fansworld",
        teamId: null,
        // custom callback events
        onError: function(error) {},
        onAddTeam: function(data) {},
        onRemoveTeam: function(data) {}
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
            that.options.teamId = self.attr('data-team-id');
            self.addClass(that._name);
            
            if (window.isLoggedIn) {
                self.on('click', that.addTeam);
            }
            else {
                $('[data-login-btn]').click();
            }             
        },
        addTeam: function(event) {
            var that = this;
            var self = $(this);
            $(this).off();      // remove event listeners
            var plugin = $(this).data(pluginName);
            plugin.toggleTeamship($(this).attr('data-team-id'));
        },
        toggleTeamship: function(teamId) {
            var that = this;
            var self = $(that.element);
            self.addClass('loading-small');
            ajax.genericAction(
                'teamship_ajaxtoggle',
                { 'team': teamId },
                function(responseJSON) {
                    if(responseJSON) {
                        if(responseJSON.isFan) {
                            that.onAddTeam(responseJSON);
                        } else {
                            that.onRemoveTeam(responseJSON);
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
        onAddTeam: function(data) {
            var that = this;
            return that.options.onAddTeam(this, data);
        },
        onRemoveTeam: function(data){
            var that = this;
            var self = $(that.element);
            notice(data.message);
            console.log("onRemoveTeam: " + JSON.stringify(data));
            return that.options.onRemoveTeam(data);
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
    /*
    $(".btn_teamship.add:not('.loading-small')").fwTeamship();
    $(".btn_teamship.remove:not('.loading-small')").fwTeamship();
    */
    $("[data-teamship-add]:not('[data-override]')").fwTeamship({
        onAddTeam: function(plugin, data) {
            var self = $(plugin.element);
            self.addClass('disabled');
            self.removeClass('add');
            self.text("YA ERES FAN");
        }
    });
});