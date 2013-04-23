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
            $(that.element).find('.widget-inner')
            .bind(
                'jsp-initialised',
                function(event, isScrollable)
                {
                    console.log('Handle jsp-initialised', this,
                                'isScrollable=', isScrollable);
                }
            )
            .bind(
                'jsp-scroll-y',
                function(event, scrollPositionY, isAtTop, isAtBottom)
                {
                    //console.log('Handle jsp-scroll-y', this, 'scrollPositionY=', scrollPositionY, 'isAtTop=', isAtTop, 'isAtBottom=', isAtBottom);
                    if(isAtBottom) {
                        $(this).find('.widget-app ul').append('<li><img title="" width="32" src="/uploads/media/default/0001/01/thumb_3_default_small_square_7c6b7e0426a40d52bf972747dac702eea26f5650.jpg">Contenido apendeado</li>');
                        var pane = $(that.element).find('.widget-inner');
                        var api = pane.data('jsp');
                        api.reinitialise();
                    }
                }
            );
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
                    if (event.target.id === "widget-popup" || $(event.target).parents("#widget-popup").size()) {
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
            // Set new title
            var title = $(event.target).attr("data-original-title");
            that.setTitle(title);
            // deselect
            $(that.options.target).removeClass('active');
            // select
            $(event.target).addClass('active');
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
    "use strict";
    $('.widget-container').fwWidget({});
    $('.widgets button').on("click", function(e) {
        $('.widget-container').data('fwWidget').toggle(e);
    });
});


/*  TIPICA NOTIFICACION
<div class="avatar">
    <img title="IYTZf6I3O" width="50" src="/uploads/media/default/0001/02/thumb_1275_default_avatar_52744a5474d78da695ee0f7ecb79d613a40d172e.jpg" />
</div>
<div class="info" data-notifId="1" data-parent="videos">Tu vídeo <a class="notice-link" href="/app_dev.php/tv/24/calcio-italiano-pasion-futbolera">Calcio italiano, pasión futbolera</a>  <span class="notice-text">se terminó de procesar</span>

</div>

*/

 /*

NOTIFICATIONS:
notifications.handleNewNotification({t: "n", id: "1", p: "videos"})
$.ajax({url: Routing.generate('es_user_ajaxnotification'), data: {'id' : 1}}).then(function(r){console.log(r)})
$.ajax({url: Routing.generate('es_user_ajaxgetnotifications_typecounts'), data: {}}).then(function(r){console.log(r)})
$.ajax({url: Routing.generate('es_notification_getlatest'), data: {'parentName': 1}}).then(function(r){console.log(r)})

 */


(function (root, factory) {
    "use strict";
    if (typeof exports === 'object') {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like enviroments that support module.exports,
        // like Node.
        module.exports = factory(require('jQuery'), require('Routing'), require('templateHelper'), require('ajax'), require('error'), require('Meteor'), require('notificationChannel'));
    } else if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jQuery', 'Routing', 'templateHelper', 'ajax', 'Meteor', 'notificationChannel'], factory);
    } else {
        // Browser globals (root is window)
        root.NOTIFICACION = factory(root.jQuery, root.Routing, root.templateHelper, root.ajax, error, root.Meteor, root.notificationChannel);
    }
}(this, function (jQuery, Routing, templateHelper, ajax, error, Meteor, notificationChannel) {
    "use strict";
    var NOTIFICACION = (function() {
        function NOTIFICACION() {
            ///////////////////
            // Internal init //
            ///////////////////
            var that = this;
            this.jQuery = jQuery;
            this.version = '1.0';
            this.channel = "notification_" + this.guidGenerator();
            this.total = 0;
            // Listen for Meteor messages
            this.join();
        }
        NOTIFICACION.prototype.join = function() {
            var that = this;
            // ADD NOTIFICATION CHANNEL
            if ((typeof Meteor != 'undefined') && (typeof notificationChannel != 'undefined')) {
                console.log('Esta todo bien');
                Meteor.registerEventCallback("process", that.handleData);
                Meteor.joinChannel(that.channel);
                Meteor.connect();
                console.log('Escuchando notifications..');
            }
        };
        NOTIFICACION.prototype.leave = function() {
            var that = this;
            // REMOVE NOTIFICATION CHANNEL
            if ((typeof Meteor != 'undefined') && (typeof notificationChannel != 'undefined')) {
                Meteor.disconnect();
                Meteor.leaveChannel(that.channel);
            }
        };

        NOTIFICACION.prototype.handleData = function(response) {
            var that = this;
            var response = JSON.parse(response);
            console.log('Notification has arrived');
            console.log(response);
            if (response) {
                if (response.t == 'n') {
                    //notifications.handleNewNotification(response);
                }
            }
        };
        NOTIFICACION.prototype.getTotal = function() {
            var that = this;
            var deferred = new jQuery.Deferred();
            $.ajax({
                url: Routing.generate(appLocale + '_user_ajaxgetnotifications_typecounts'),
                data: {},
                type: 'GET'
            })
            .then(function(response) {
                var result = response[0];
                that.total = parseInt(result.cnt, 10);
                deferred.resolve(parseInt(result.cnt, 10));
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                deferred.reject(new Error(jqXHR));
            });
            return deferred.promise();
        };
        NOTIFICACION.prototype.readNotification = function(id) {
            var that = this;
            var deferred = new jQuery.Deferred();
            $.ajax({
                url: Routing.generate(appLocale + '_user_ajaxdeletenotification'),
                data: {id: id},
                type: 'GET'
            })
            .then(function(response){
                console.log('ReadNotification: ' + id + ' => ' + response);
                deferred.resolve(response);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                deferred.reject(new Error(jqXHR));
            });
            return deferred.promise();
        };
        ////////////////////////////////////////////////////////////////////////
        // Create and return a "version 4" RFC-4122 UUID string.              //
        ////////////////////////////////////////////////////////////////////////
        NOTIFICACION.prototype.guidGenerator = function() {
            var s = [];
            var itoh = '0123456789ABCDEF';
            var i = 0;
            // Make array of random hex digits. The UUID only has 32 digits in it, but we
            // allocate an extra items to make room for the '-'s we'll be inserting.
            for (i = 0; i < 36; i += 1) {
                s[i] = Math.floor(Math.random()*0x10);
            }
            // Conform to RFC-4122, section 4.4
            s[14] = 4;  // Set 4 high bits of time_high field to version
            s[19] = (s[19] && 0x3) || 0x8;  // Specify 2 high bits of clock sequence
            // Convert to hex chars
            for (i = 0; i < 36; i += 1) {
                s[i] = itoh[s[i]];
            }
            // Insert '-'s
            s[8] = s[13] = s[18] = s[23] = '-';

            return s.join('');
        };
        //$.ajax({url: Routing.generate('es_user_ajaxgetnotifications_typecounts'), data: {}}).then(function(r){console.log(r)})
        NOTIFICACION.prototype.getVersion = function() {
            console.log(this.version);
            return this.version;
        };
        return NOTIFICACION;
    }());
    // Just return a value to define the module export.
    // This example returns an object, but the module
    // can return a function as the exported value.
    return NOTIFICACION;
}));

// implicit init that adds module to global scope
// TODO: refactor inside curl
$(document).ready(function () {
    "use strict";
    window.fansworld = window.fansworld || {};
    window.fansworld.notificacion = new window.NOTIFICACION();
    return;
});