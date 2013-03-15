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
        isPoped: false,
        target: null
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
            $(that.element).attr('id', 'widget-popup');
            // attach scrollbars
            $(that.element).find('.widget-inner').jScrollPane();
            // Bind close button
            $('.close-share').on("click", function(event) {
                that.popOut(event);
            });
        },
        setPosition: function(target) {
            var that = this;
            // Get target window positioning
            var offset = $(target).offset();
            offset.top -= $(that.element).height() + $(target).height() + 10;
            offset.left -= ($(that.element).width() / 2) - ($(target).width() / 2);
            // Set popup position
            $(that.element).offset({
                top: offset.top,
                left: offset.left
            });
        },
        popIn: function(event) {
            var that = this;
            that.setPosition(event.target);
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
        setTitle: function(title) {
            var that = this;
            $(that.element).find('.widget-title').text(title);
        },
        checkBounds: function(check) {
            var that = this;
            if(check) {
                $("body").on('click', function(event) {
                    if (event.target.id == "widget-popup" || $(event.target).parents("#widget-popup").size()) {
                        //alert("Inside div");
                    } else {
                        that.popOut(event);
                        $(this).off();
                    }
                });
            } else {
                $("body").off();
            }
        },
        toggle: function (event) {
            var that = this;
            event.preventDefault();
            if(!$(event.target).hasClass('active') && that.options.isPoped) {
                that.popOut(event);
                $(that.options.target).removeClass('active');
                //$(event.target).toggleClass('active');
                return;
            }
            var title = $(event.target).attr("data-original-title");
            that.setTitle(title);
            that.options.target = event.target;
            $(that.options.target).toggleClass('active');
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
    "use strict";
    $('.widget-container').fwWidget({});
    $('.widgets button').on("click", function(e) {
        $('.widget-container').data('fwWidget').toggle(e);
    });
});