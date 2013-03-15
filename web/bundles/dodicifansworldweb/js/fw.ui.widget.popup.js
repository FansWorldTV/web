/*global $, jQuery, alert, console, error, success, endless, ajax, templateHelper, Routing, appLocale, exports, module, require, define*/
/*jslint nomen: true */
/* Tolerate dangling _ in identifiers */
/*jslint vars: true */
/* Tolerate many var statements per function */
/*jslint white: true */
/*jslint browser: true */
/*jslint devel: true */
/* Assume console, alert, ... */
/*jslint windows: true */
/* Assume Windows */
/*jslint maxerr: 100 */
/* Maximum number of errors */
/*
 * library dependencies:
 *      jquery 1.8.3
 *      jquery tokeninput 9
 *      fos-routing
 * external dependencies:
 *      appLocale
 */
// FansWorld widget plugin 1.0 initial
$(document).ready(function () {

    "use strict";
    // Create the defaults once
    var pluginName = "fwWidget";
    var defaults = {
        title: "title",
        isPoped: true,
        target: null,
    };

    // The actual plugin constructor

    function Plugin(element, options) {
        this.element = element;
        // jQuery has an extend method which merges the contents of two or
        // more objects, storing the result in the first object. The first object
        // is generally empty as we don't want to alter the default options for
        // future instances of the plugin
        this.options = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;
        this.init();
    }
    Plugin.prototype = {
        init: function () {
            var that = this;
            $(that.element).find('.widget-title').text(that.options.title);
            $(that.element).attr('id', 'mywidget');
            if (that.options.isPoped) {
                $(that.element).animate({
                    opacity: 0
                });
                that.options.isPoped = false;
            }
            // attach scrollbars
            $(that.element).find('.widget-inner').jScrollPane();
            // Bind close button
            $('.close-share').on("click", function (event) {
                $(that.element).animate({
                    opacity: 0
                });
                that.options.isPoped = false;
            });
        },
        popIn: function(event) {
            var that = this;
            $(that.element).animate({
                opacity: 1
            });
            that.options.isPoped = true;
        },
        popOut: function(event) {
            var that = this;
            $(that.element).animate({
                opacity: 0
            });
            that.options.isPoped = false;
        },
        toggle: function (event) {
            var that = this;
            that.options.target = event.target;
            // Get target window positioning
            var offset = $(event.target).offset();
            offset.top -= $(that.element).height() + $(event.target).height() + 10;
            offset.left -= ($(that.element).width() / 2) - ($(event.target).width() / 2);
            // Set popup position
            $(that.element).offset({
                top: offset.top,
                left: offset.left
            });

            // Toggle visibility
            if (!that.options.isPoped) {
                that.popIn(event);
            }
            else {
                that.popOut(event);
            }
        }
    };
    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[pluginName] = function (options) {
        // If the first parameter is an object (options), or was omitted,
        // instantiate a new instance of the plugin.
        if (typeof options === "object" || !options) {
            return this.each(function () {
                // Only allow the plugin to be instantiated once.
                if (!$.data(this, pluginName)) {
                    // Pass options to Plugin constructor, and store Plugin
                    // instance in the elements jQuery data object.
                    $.data(this, pluginName, new Plugin(this, options));
                }
            });
        }
    };
});

$(document).ready(function () {
    $('.widget-container').fwWidget({title: "hola mundo"});
    $('#feed').on("click", function(e) {
        $('.widget-container').data('fwWidget').toggle(e);
    });
})