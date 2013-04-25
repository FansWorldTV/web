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

/*

NOTIFICATIONS:
notifications.handleNewNotification({t: "n", id: "1", p: "videos"})
$.ajax({url: Routing.generate('es_user_ajaxnotificationnumber'), data: {}}).then(function(r){console.log(r)})
$.ajax({url: Routing.generate('es_user_ajaxnotification'), data: {'id' : 1}}).then(function(r){console.log(r)})
$.ajax({url: Routing.generate('es_user_ajaxnotifications'), data: {}}).then(function(r){console.log(r)})
$.ajax({url: Routing.generate('es_user_ajaxgetnotifications_typecounts'), data: {}}).then(function(r){console.log(r)})
$.ajax({url: Routing.generate('es_notification_getlatest'), data: {'parentName': 1}}).then(function(r){console.log(r)})


proceso para obtener notificaciones:

1) Obtener el total de notificaciones pendientes [user_ajaxnotificationnumber]
2) Obtener el tipo de notificaciones pendientes segun el tipo [user_ajaxgetnotifications_typecounts]
    2.1) El resultado es un array de objetos [
            {"type":"6","cnt":"165","parent":"videos"},
            {"type":"12","cnt":"1","parent":"fans"},
            {"type":"16","cnt":"3","parent":"photos"}
        ]
3) Del resultado del punto 2.1 pasar el parent a [notification_getlatest (con parent)]


 */



////////////////////////////////////////////////////////////////////////////////
// FansWorld Meteor listener handler                                          //
////////////////////////////////////////////////////////////////////////////////
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
            // Event Listeners
            this.listeners = {};
            this.channel = "notification_" + this.guidGenerator();
            this.total = 0;
            this.typecounts = {};
            this.notificacion = null;
            this.notificacions = null;
            this.latest = null;
            // Listen for Meteor messages
            this.join();
            // Get total unreaded notifications
            this.getTotal();
            // Get a limited set of unreaded notifications to populate activity widget
            this.getNotifications();
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
                url: Routing.generate(appLocale + '_user_ajaxnotificationnumber'),
                data: {},
                type: 'GET'
            })
            .then(function(response) {
                that.total = parseInt(response.number, 10);
                that.fire({type: "ongettotal", result: that.total});
                deferred.resolve(that.total);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                deferred.reject(new Error(jqXHR));
            });
            return deferred.promise();
        };
        NOTIFICACION.prototype.getTypeCounts = function() {
            var that = this;
            var deferred = new jQuery.Deferred();
            $.ajax({
                url: Routing.generate(appLocale + '_user_ajaxgetnotifications_typecounts'),
                data: {},
                type: 'GET'
            })
            .then(function(response) {
                that.typecounts = response;
                that.fire({type: "ongettypecounts", result: response});
                deferred.resolve(that.typecounts);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                deferred.reject(new Error(jqXHR));
            });
            return deferred.promise();
        };
        NOTIFICACION.prototype.getNotification = function(id) {
            var that = this;
            var deferred = new jQuery.Deferred();
            $.ajax({
                url: Routing.generate(appLocale + '_user_ajaxnotification'),
                data: {
                    'id': id
                },
                type: 'GET'
            })
            .then(function(response) {
                that.notificacion = response;
                that.fire({type: "ongetnotification", result: response});
                deferred.resolve(that.notificacion);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                deferred.reject(new Error(jqXHR));
            });
            return deferred.promise();
        };
        NOTIFICACION.prototype.getLatest = function(parentname) {
            var that = this;
            var deferred = new jQuery.Deferred();
            $.ajax({
                url: Routing.generate(appLocale + '_notification_getlatest'),
                data: {
                    'parentName': parentname
                },
                type: 'GET'
            })
            .then(function(response) {
                that.latest = response;
                that.fire({type: "ongetlatest", result: response});
                deferred.resolve(that.latest);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                deferred.reject(new Error(jqXHR));
            });
            return deferred.promise();
        };
        NOTIFICACION.prototype.getNotifications = function(parentname) {
            var that = this;
            var deferred = new jQuery.Deferred();
            $.ajax({
                url: Routing.generate(appLocale + '_user_ajaxnotifications'),
                data: {},
                type: 'GET'
            })
            .then(function(response) {
                that.notificacions = response.notificacions;
                that.fire({type: "ongetnotifications", result: response});
                deferred.resolve(that.notificacions);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                deferred.reject(new Error(jqXHR));
            });
            return deferred.promise();
        };
        NOTIFICACION.prototype.delete = function(id) {
            var that = this;
            var deferred = new jQuery.Deferred();
            $.ajax({
                url: Routing.generate(appLocale + '_user_ajaxdeletenotification'),
                data: {id: id},
                type: 'GET'
            })
            .then(function(response){
                console.log('Notification deleted: ' + id + ' => ' + response);
                deferred.resolve(response);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                deferred.reject(new Error(jqXHR));
            });
            return deferred.promise();
        };
        ////////////////////////////////////////////////////////////////////////
        //  CUSTOM EVENT HANDLERS                                             //
        ////////////////////////////////////////////////////////////////////////
        NOTIFICACION.prototype.addListener = function(type, listener){
            if (typeof this.listeners[type] === "undefined"){
                this.listeners[type] = [];
            }
            this.listeners[type].push(listener);
        };
        NOTIFICACION.prototype.removeListener = function(type, listener){
            if (this.listeners[type] instanceof Array){
                var listeners = this.listeners[type];
                var i, len;
                for (i = 0, len = listeners.length; i < len; i += 1){
                    if (listeners[i] === listener){
                        listeners.splice(i, 1);
                        break;
                    }
                }
            }
        };
        NOTIFICACION.prototype.fire = function(event){
            var i, len;
            if (typeof event === "string"){
                event = { type: event };
            }
            if (!event.target){
                event.target = this;
            }

            if (!event.type){  //falsy
                throw new Error("Event object missing 'type' property.");
            }

            if (this.listeners[event.type] instanceof Array){
                var listeners = this.listeners[event.type];
                for (i = 0, len = listeners.length; i < len; i += 1){
                    listeners[i].call(this, event);
                }
            }
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




////////////////////////////////////////////////////////////////////////////////
// FansWorld widget plugin 1.0 initial                                        //
////////////////////////////////////////////////////////////////////////////////
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
                function(event, isScrollable) {
                    //console.log('Handle jsp-initialised', this, 'isScrollable=', isScrollable);
                }
            )
            .bind(
                'jsp-scroll-y',
                function(event, scrollPositionY, isAtTop, isAtBottom) {
                    //console.log('Handle jsp-scroll-y', this, 'scrollPositionY=', scrollPositionY, 'isAtTop=', isAtTop, 'isAtBottom=', isAtBottom);
                    if(isAtBottom) {
                        $(this).find('.widget-app ul').append('<li><img title="" width="32" src="/uploads/media/default/0001/01/thumb_3_default_small_square_7c6b7e0426a40d52bf972747dac702eea26f5650.jpg">Contenido apendeado</li>');
                        var pane = $(that.element).find('.widget-inner');
                        var api = pane.data('jsp');
                        api.reinitialise();
                    }
                }
            );
            // Get latest unread notifications
            fansworld.notificacion.addListener('ongetnotifications', function(response){
                console.log('GOT LATEST')
                console.log(response)
                //$(that).find('.widget-app ul').empty();
                $(that.element).find('.widget-app ul').empty();
                var i;
                for(i in response.result.notifications) {
                    if (response.result.notifications.hasOwnProperty(i)) {
                        var notification = response.result.notifications[i].view;
                        $(that.element).find('.widget-app ul').append('<li>' + notification + '</li>');
                    }
                }
            });
            // Bind close button
            $('.close-share').on("click", function(event) {
                that.popOut(event);
            });
        },
        loadNews: function() {

        },
        ////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////
        setPosition: function(target) {
            var that = this;
            // Get target window positioning
            var offset = $(target).offset();
            offset.top -= $(that.element).height() + $(target).height() + 10;
            offset.left -= parseInt(($(that.element).width() / 2) - ($(target).width() / 2), 10);
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
        ////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////
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
    /*
    $('.widgets button').on("click", function(e) {
        $('.widget-container').data('fwWidget').toggle(e);
    });
    */
});

////////////////////////////////////////////////////////////////////////////////
// FansWorld footer news buttons plugin 1.0 initial                           //
////////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
    "use strict";
    // Create the defaults once
    var pluginName = "fwFooterNews";
    var defaults = {
        footer: null,
        buttons: [],
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
            console.log('fwFooterNews')
            that.options.footer = $('footer');
            that.options.buttons.push({
                id: that.guidGenerator(),
                node: that.makeButton(parseInt((Math.random()*0x10), 10), 'cosas'),
                count: 0
            });
            console.log(that.options.buttons);
            that.options.footer.find('.widgets').append(that.options.buttons[0].node);
            /*
            setInterval(function() {
                that.updateLabel('id', 101)
            }, 2500);
            */
            fansworld.notificacion.addListener('ongettotal', function(response){
                that.updateLabel('id', response.result);
            });
            /*
            $.when(fansworld.notificacion.getTotal()).then(function(total) {
                console.log('GOT TOTAL NOTIFICATIONS')
                that.updateLabel('id', fansworld.notificacion.total);
            });
            */
        },
        makeButton: function(id, name) {
            var that = this;
            var button = document.createElement("button");
            button.setAttribute('id', id);
            button.setAttribute('data-toggle', 'dropdown');
            button.setAttribute('title', name);
            button.setAttribute('rel', 'tooltip');
            button.setAttribute('type', 'button');
            button.setAttribute('data-original-title', 'Notificaciones');
            button.className = "btn btn-info dropup";
            button.innerText = name;

            var span = document.createElement("span");
            span.className = "caret";
            button.appendChild(span);

            var label = that.makeLabel('id', 0);
            button.insertBefore(label, button.firstChild);

            $(button).on("click", function(e) {
                $('.widget-container').data('fwWidget').toggle(e);
            });
            return button;
        },
        makeLabel: function(id, count) {
            var span = document.createElement("span");
            span.setAttribute('id', id);
            span.className = "label label-important label-footer";
            span.innerText = count;
            return span;
        },
        updateLabel: function(id, message) {
            var that = this;
            $(that.options.buttons[0].node).find('#id').html(message);
            $(that.options.buttons[0].node).find('#id').effect("highlight", {color: "#a0c882"}, 2000);
        },
        guidGenerator: function() {
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
        },
        getVersion: function() {

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
    $('footer').fwFooterNews({});
});