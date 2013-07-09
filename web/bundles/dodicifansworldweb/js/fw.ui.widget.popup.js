/*global
    $,
    jQuery,
    alert,
    console,
    error,
    success,
    endless,
    ajax,
    templateHelper,
    Routing,
    appLocale,
    exports,
    module,
    require,
    define
*/
/*jslint nomen: true */                 /* Tolerate dangling _ in identifiers */
/*jslint vars: true */           /* Tolerate many var statements per function */
/*jslint white: true */                       /* tolerate messy whithe spaces */
/*jslint browser: true  */                                  /* Assume browser */
/*jslint devel: true */                         /* Assume console, alert, ... */
/*jslint windows: true */               /* Assume window object (for browsers)*/
/*jslint maxerr: 100 */                           /* Maximum number of errors */

/*
 * library dependencies:
 *      jquery 1.8.3
 *      fos-routing
 * external dependencies:
 *      appLocale
 */


// WARNING GLOBAL VARIABLE
// EventEmitter is taken from packery but can be download from https://github.com/Wolfy87/EventEmitter
$(document).ready(function () {
    "use strict";
    window.fansWorldEvents = window.fansWorldEvents || new EventEmitter();
});
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// NOTIFICATIONS API:                                                                                                        //
// $.get('/bench/meteor/send').then(function(r){console.log(r)})                                                             //
// notifications.handleNewNotification({t: "n", id: "1", p: "videos"})                                                       //
// $.ajax({url: Routing.generate('es_user_ajaxnotificationnumber'), data: {}}).then(function(r){console.log(r)})             //
// $.ajax({url: Routing.generate('es_user_ajaxnotification'), data: {'id' : 1}}).then(function(r){console.log(r)})           //
// $.ajax({url: Routing.generate('es_user_ajaxnotifications'), data: {}}).then(function(r){console.log(r)})                  //
// $.ajax({url: Routing.generate('es_user_ajaxgetnotifications_typecounts'), data: {}}).then(function(r){console.log(r)})    //
// $.ajax({url: Routing.generate('es_notification_getlatest'), data: {'parentName': 1}}).then(function(r){console.log(r)})   //
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// proceso para obtener notificaciones:                                                                                      //
//                                                                                                                           //
// 1) Obtener el total de notificaciones pendientes [user_ajaxnotificationnumber]                                            //
// 2) Obtener el tipo de notificaciones pendientes segun el tipo [user_ajaxgetnotifications_typecounts]                      //
//    2.1) El resultado es un array de objetos [                                                                             //
//            {"type":"6","cnt":"165","parent":"videos"},                                                                    //
//            {"type":"12","cnt":"1","parent":"fans"},                                                                       //
//            {"type":"16","cnt":"3","parent":"photos"}                                                                      //
// ]                                                                                                                         //
// 3) Del resultado del punto 2.1 pasar el parent a [notification_getlatest (con parent)]                                    //
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ACTIVITY API:                                                                                                             //
// $.ajax({url: Routing.generate('es_getactivity_feed'), data: {page: 0}}).then(function(r){console.log(r)})                 //
// $.ajax({url: Routing.generate('es_user_ajaxactivitynumber'), data: {}}).then(function(r){console.log(r)})                 //
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


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
        root.NOTIFICATION = factory(root.jQuery, root.Routing, root.templateHelper, root.ajax, error, root.Meteor, root.notificationChannel);
    }
}(this, function (jQuery, Routing, templateHelper, ajax, error, Meteor, notificationChannel) {
    "use strict";
    var NOTIFICATION = (function() {
        function NOTIFICATION() {
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
        NOTIFICATION.prototype.join = function() {
            var that = this;
            // ADD NOTIFICATION CHANNEL
            if ((typeof Meteor != 'undefined') && (typeof notificationChannel != 'undefined')) {
                console.log('Esta todo bien');
                Meteor.registerEventCallback("process", that.notificationReceived);
                Meteor.joinChannel(that.channel);
                Meteor.connect();
                console.log('Escuchando notifications..');
            }
        };
        NOTIFICATION.prototype.leave = function() {
            var that = this;
            // REMOVE NOTIFICATION CHANNEL
            if ((typeof Meteor != 'undefined') && (typeof notificationChannel != 'undefined')) {
                Meteor.disconnect();
                Meteor.leaveChannel(that.channel);
            }
        };

        NOTIFICATION.prototype.notificationReceived = function(response) {
            var that = this;
            var response = JSON.parse(response);
            console.log('Notification has arrived');
            console.log(response);
            if (response) {
                if (response.t == 'n') {
                    that.total += 1;
                    that.fire({type: "onnotificationreceived", result: response});
                }
            }
        };
        NOTIFICATION.prototype.getTotal = function() {
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
        NOTIFICATION.prototype.getTypeCounts = function() {
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
        NOTIFICATION.prototype.getNotification = function(id) {
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
        NOTIFICATION.prototype.getLatest = function(parentname) {
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
        NOTIFICATION.prototype.getNotifications = function(parentname) {
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
        NOTIFICATION.prototype.delete = function(id) {
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
        NOTIFICATION.prototype.addListener = function(type, listener){
            if (typeof this.listeners[type] === "undefined"){
                this.listeners[type] = [];
            }
            this.listeners[type].push(listener);
        };
        NOTIFICATION.prototype.removeListener = function(type, listener){
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
        NOTIFICATION.prototype.fire = function(event){
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
        NOTIFICATION.prototype.guidGenerator = function() {
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
        NOTIFICATION.prototype.getVersion = function() {
            console.log(this.version);
            return this.version;
        };
        return NOTIFICATION;
    }());
    // Just return a value to define the module export.
    // This example returns an object, but the module
    // can return a function as the exported value.
    return NOTIFICATION;
}));

////////////////////////////////////////////////////////////////////////////////
// FansWorld Activity handler                                                 //
////////////////////////////////////////////////////////////////////////////////
(function (root, factory) {
    "use strict";
    if (typeof exports === 'object') {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like enviroments that support module.exports,
        // like Node.
        module.exports = factory(require('jQuery'), require('Routing'), require('templateHelper'), require('ajax'), require('error'));
    } else if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jQuery', 'Routing', 'templateHelper', 'ajax', 'error'], factory);
    } else {
        // Browser globals (root is window)
        root.ACTIVITY = factory(root.jQuery, root.Routing, root.templateHelper, root.ajax, error);
    }
}(this, function (jQuery, Routing, templateHelper, ajax, error) {
    "use strict";
    var ACTIVITY = (function() {
        function ACTIVITY() {
            ///////////////////
            // Internal init //
            ///////////////////
            var that = this;
            this.jQuery = jQuery;
            this.version = '1.0';
            // Event Listeners
            this.listeners = {};
            this.total = 0;
            this.page = 1;
            this.activity = {};
            this.pushTimer = null;
            // Get total unreaded activityes
            // this.getTotal();
            // Get a limited set of unreaded activityes to populate activity widget
            this.getActivity();
        }
        ACTIVITY.prototype.getTotal = function() {
            var that = this;
            var deferred = new jQuery.Deferred();
            $.ajax({
                url: Routing.generate(appLocale + '_user_ajaxactivitynumber'),
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
        ACTIVITY.prototype.getActivity = function() {
            var that = this;
            var deferred = new jQuery.Deferred();
            console.log(document.domain)
            $.ajax({
                url: Routing.generate(appLocale + '_getactivity_feed'),
                data: {page: that.page},
                type: 'GET'
            })
            .then(function(response) {
                that.activity = response;
                that.fire({type: "ongetactivity", result: that.activity});
                that.page += 1;
                deferred.resolve(that.activity);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                deferred.reject(new Error(jqXHR));
            });
            return deferred.promise();
        };
        ////////////////////////////////////////////////////////////////////////
        //  CUSTOM EVENT HANDLERS                                             //
        ////////////////////////////////////////////////////////////////////////
        ACTIVITY.prototype.addListener = function(type, listener){
            if (typeof this.listeners[type] === "undefined"){
                this.listeners[type] = [];
            }
            this.listeners[type].push(listener);
        };
        ACTIVITY.prototype.removeListener = function(type, listener){
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
        ACTIVITY.prototype.fire = function(event){
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
        ACTIVITY.prototype.guidGenerator = function() {
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
        ACTIVITY.prototype.getVersion = function() {
            console.log(this.version);
            return this.version;
        };
        return ACTIVITY;
    }());
    // Just return a value to define the module export.
    // This example returns an object, but the module
    // can return a function as the exported value.
    return ACTIVITY;
}));

////////////////////////////////////////////////////////////////////////////////
// FansWorld widget plugin 1.0 initial                                        //
////////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
    "use strict";
    // Create the defaults once
    var pluginName = "fwWidget";
    var defaults = {
        title: "Notificaciones",
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
            $(that.element).attr('id', that._name);
            // Listen notifications
            fansworld.notificacion.addListener('onnotificationreceived', function(response){
                var id = response.result.id;
                var parent = response.result.p;

                console.log("notification id: %s, parent: %s", id, parent);
                $.when(fansworld.notificacion.getNotification(id))
                .then(function(notification){
                    $(that.element).find('.widget-app ul').prepend('<li>' + notification + '</li>');
                    that.redrawScrollBar();
                })
            });
            // Get latest unread notifications
            fansworld.notificacion.addListener('ongetnotifications', function(response){
                var i;
                for(i in response.result.notifications) {
                    if (response.result.notifications.hasOwnProperty(i)) {
                        var notification = response.result.notifications[i].view;
                        $(that.element).find('.widget-app ul').prepend('<li>' + notification + '</li>');
                        that.makeScrollPane();
                    }
                }
            });
            window.fansWorldEvents.addListener('widgets-off', function(button, event) {
                if (that.options.isPoped) { 
                    that.popOut(event);
                }
            });            
            window.fansWorldEvents.addListener(that._name + '_toggle', function(button, event) {
                that.toggle(button, event);
            });
            // Bind close button
            $(that.element).find('.close-share').on("click", function(event) {
                that.popOut(event);
            });
            // Bind mouse actions & css transitions
            $(that.element).on('mouseleave', function(event) {
                $(this).animate({opacity: 0.5});
            });            
            $(that.element).on('mouseenter', function(event) {
                $(this).animate({opacity: 1});
            });
        },
        getMoreNews: function() {

        },
        makeScrollPane: function() {
            var that = this;
            $(that.element).css('display', 'block');
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
                    // console.log('Handle jsp-scroll-y', this, 'scrollPositionY=', scrollPositionY, 'isAtTop=', isAtTop, 'isAtBottom=', isAtBottom);
                    if(isAtBottom) {
                        /*
                        $(this).find('.widget-app ul').append('<li><img title="" width="32" src="/uploads/media/default/0001/01/thumb_3_default_small_square_7c6b7e0426a40d52bf972747dac702eea26f5650.jpg">Contenido apendeado</li>');
                        var pane = $(that.element).find('.widget-inner');
                        var api = pane.data('jsp');
                        api.reinitialise();
                        */
                    }
                }
            );
            $(that.element).css('display', 'none');
            return;
        },
        redrawScrollBar: function() {
            var that = this;
            var pane = $(that.element).find('.widget-inner');
            var api = pane.data('jsp');
            api.reinitialise();
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
            if (that.options.isPoped) {
                return;
            }            
            //that.setPosition(event.target);
            event.stopPropagation();
            event.preventDefault();
            $(that.element).show();
            $(that.element).animate({
                opacity: 1
            });
            that.options.isPoped = true;
            that.checkBounds(true);
        },
        popOut: function(event) {
            var that = this;
            if (!that.options.isPoped) {
                return;
            }
            $(that.element).hide();
            $(that.element).animate({
                opacity: 0
            });            
            $(that.options.target).removeClass('active');
            that.options.isPoped = false;
            that.checkBounds(false);
        },
        setTitle: function(title) {
            var that = this;
            $(that.element).find('.widget-title').text(title);
        },
        checkBounds: function(check) {
            var that = this;
            function handleClick(event) {
                if(!that.checkBound(event.pageX, event.pageY, that.element)) {
                    if(that.options.isPoped && !event.target.classList.contains('btn-widget')) {
                        // TODO: buscar un mejor nombre para el boton asociado al evento
                        $(that.options.target).removeClass('active'); 
                        document.body.removeEventListener("click", this, false);
                        that.popOut(event);                            
                    }                            
                }                
            }
            if(check) {
                document.body.addEventListener("click", handleClick, false);
            } else {
                document.body.removeEventListener("click", handleClick, false);
            }
        },
        checkBound: function(x, y, element) {
            var that = this;
            var offset = $(element).offset();
            var height = $(element).height();
            var width = $(element).width();
            if((event.pageY >= offset.top && event.pageY <= offset.top + height) && (event.pageX >= offset.left && event.pageX <= offset.left + width)) {
                return true;
            } else {
                return false;
            }
        },
        toggle: function (button, event) {
            var that = this;
            event.preventDefault();

            if($(button).hasClass('active') && that.options.isPoped) {
                $(that.options.target).removeClass('active');
                $(button).removeClass('active');
                that.popOut(event);                
                return;
            }
            // Set new title
            var title = $(button).attr("data-original-title");
            that.setTitle(title);
            // deselect
            $(that.options.target).removeClass('active');
            // select
            $(button).addClass('active');
            that.options.target = button;

            // Get target window positioning
            var offset = $('nav .widget-bar').offset();
            offset.top = $('nav .widget-bar').height() + 2;
            //offset.top -= parseInt(($(that.element).height() + $(button).height() + 10), 10);
            offset.left -= parseInt(($(that.element).width() / 2) - ($('nav .widget-bar').width() / 2), 10);

            console.log("left: " + offset.left)

            // Set popup position
            /*$(that.element).offset({
                top: offset.top,
                left: offset.left
            });
            */
            $(that.element).css({
                top: offset.top,
                left: offset.left
            });
            $(that.element).css({
                display: 'block'
            });

            var targetOffset = $(button).offset();
            var arrowOffset = $(that.element).find('.arrow-up').offset();

            var displacement = arrowOffset > targetOffset ? $(that.element).find('.arrow-up').position().left + (targetOffset.left - arrowOffset.left) : $(that.element).find('.arrow-up').position().left - (arrowOffset.left - targetOffset.left);

            console.log("targetOffset: " + targetOffset.left + " arrowOffsetLeft: " + arrowOffset.left + " arrowLeft: " + $(that.element).find('.arrow-up').position().left + " move: " + displacement);
            
            $(that.element).find('.arrow-up').css({
                left: displacement + 'px'
            });
            //var b = arrowOffset.left - targetOffset.left;
            $(that.element).find('.arrow-up').css({
                //left: b
            })
            // Toggle visibility
            if (!that.options.isPoped) {
                //$(that.element).find('.widget-title').css('color', '#0f0');
                that.popIn(event);
            }
            else {
                //$(that.element).find('.widget-title').css('color', '#f00');
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

////////////////////////////////////////////////////////////////////////////////
// FansWorld activity widget plugin 1.0 initial                               //
////////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
    "use strict";
    // Create the defaults once
    var pluginName = "fwActivityWidget";
    var defaults = {
        title: "Actividad Reciente",
        isPoped: false,
        isLoadingActivities: false,
        isScrollable: false,
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
            $(that.element).attr('id', that._name);

            // Populate on activity received
            that.options.isLoadingActivities = true;
            fansworld.activity.addListener('ongetactivity', function(response){
                var i;
                console.log('ongetactivity')
                console.log(response)
                for(i in response.result.view) {
                    if (response.result.view.hasOwnProperty(i)) {
                        $(that.element).find('.widget-app ul li .loading').parent().remove();
                        var activity = response.result.view[i];
                        //$(that.element).find('.widget-app ul').append('<li>' + activity + '<br /><span class="notice-text"><i class="icon-barcode" style="margin-top:2px;"></i>' + response.result.activity[i].id + '<i class="icon-time" style="margin-top:2px;"></i> ' + $.timeago(new Date(parseInt(response.result.activity[i].ts, 10) * 1000)) + '</span></li>');
                        $(that.element).find('.widget-app ul').append('<li>' + activity + '<br /><span class="notice-text"><i class="icon-time" style="margin-top:2px;"></i> ' + $.timeago(new Date(parseInt(response.result.activity[i].ts, 10) * 1000)) + '</span></li>');
                        that.options.isLoadingActivities = false;
                        // Make new scrollbars is none
                        if(!that.options.isScrollable) {
                            that.makeScrollPane();
                        } else {
                            // Redraw is scrollbars are present and new data arrived
                            that.redrawScrollBar();
                        }
                    }
                }
            });
            //{"activity":[{"id":"1068","ts":"1373090407","type":1,"typeName":"new_video","media":{"video":{"id":"224","slug":"los-fans-y-el-tenis-segun-djokovic-y-federer","title":"Los fans y el tenis seg\u00fan Djokovic y Federer","image":"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/03\/thumb_2301_default_big_a00654d5344eae2f19f99f5dee0e4b26f265e442.jpg","createdAt":"1373064932","author":{"id":1,"title":"Fansworld.TV ","image":"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/01\/thumb_2_default_small_square_6a39865b3ad9d25f56b288de71b58a8d3d3f6f69.jpg","createdAt":"1373062435","firstname":"Fansworld.TV","lastname":null,"fanCount":0,"splash":null,"sex":null,"username":"fansworld","url":"\/u\/fansworld\/wall","location":null,"canFriend":true},"content":"En la conferencia de presentaci\u00f3n del Masters de Londres, Djokovic relata la historia que m\u00e1s recuerda de un fan suyo y a su vez, habla de la pasi\u00f3n de sus seguidores en Asia y Sudamerica. Por otro lado, Federer rescata lo exitante de viajar por el mundo conociendo culturas y  fan\u00e1ticos del tenis.\n","likeCount":0,"visitCount":0,"commentCount":0,"videocategory":5,"genre_id":5,"genreparent_id":1,"weight":3496,"duration":"03:40","url":"\/tv\/224\/los-fans-y-el-tenis-segun-djokovic-y-federer","modalUrl":"\/modal\/video\/show\/224"}},"target":{"id":1,"title":"Fansworld.TV ","image":"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/01\/thumb_2_default_small_square_6a39865b3ad9d25f56b288de71b58a8d3d3f6f69.jpg","createdAt":"1373062435","firstname":"Fansworld.TV","lastname":null,"fanCount":0,"splash":null,"sex":null,"username":"fansworld","url":"\/u\/fansworld\/wall","location":null,"canFriend":true},"fanOf":[]},{"id":"1067","ts":"1373090119","type":1,"typeName":"new_video","media":{"video":{"id":"223","slug":"un-mano-a-mano-con-federer-el-mejor-tenista-de-la-historia-parte-2","title":"Un mano a mano con Federer, el mejor tenista de la historia Parte 2","image":"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/03\/thumb_2300_default_big_74a3f99a64347159ec9856ca3c23db781eec7a92.jpg","createdAt":"1373064928","author":{"id":1,"title":"Fansworld.TV ","image":"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/01\/thumb_2_default_small_square_6a39865b3ad9d25f56b288de71b58a8d3d3f6f69.jpg","createdAt":"1373062435","firstname":"Fansworld.TV","lastname":null,"fanCount":0,"splash":null,"sex":null,"username":"fansworld","url":"\/u\/fansworld\/wall","location":null,"canFriend":true},"content":"Roger y su motivaci\u00f3n para seguir jugando despu\u00e9s de haber ganado todo. Su mentalidad y su inspiraci\u00f3n; sus ganas de conocer Argentina -previo a la gira por el pa\u00eds- y el fanatismo de los fans sudamericanos por su tenis.\n","likeCount":0,"visitCount":0,"commentCount":0,"videocategory":5,"genre_id":5,"genreparent_id":1,"weight":3496,"duration":"04:59","url":"\/tv\/223\/un-mano-a-mano-con-federer-el-mejor-tenista-de-la-historia-parte-2","modalUrl":"\/modal\/video\/show\/223"}},"target":{"id":1,"title":"Fansworld.TV ","image":"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/01\/thumb_2_default_small_square_6a39865b3ad9d25f56b288de71b58a8d3d3f6f69.jpg","createdAt":"1373062435","firstname":"Fansworld.TV","lastname":null,"fanCount":0,"splash":null,"sex":null,"username":"fansworld","url":"\/u\/fansworld\/wall","location":null,"canFriend":true},"fanOf":[]},{"id":"1066","ts":"1373090116","type":1,"typeName":"new_video","media":{"video":{"id":"222","slug":"un-mano-a-mano-con-federer-el-mejor-tenista-de-la-historia-parte-1","title":"Un mano a mano con Federer, el mejor tenista de la historia Parte 1","image":"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/03\/thumb_2299_default_big_3a28a7d337091e51a9b091461eaa0618c444b68c.jpg","createdAt":"1373064924","author":{"id":1,"title":"Fansworld.TV ","image":"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/01\/thumb_2_default_small_square_6a39865b3ad9d25f56b288de71b58a8d3d3f6f69.jpg","createdAt":"1373062435","firstname":"Fansworld.TV","lastname":null,"fanCount":0,"splash":null,"sex":null,"username":"fansworld","url":"\/u\/fansworld\/wall","location":null,"canFriend":true},"content":"En la previa del Masters de Londres, Federer habla sobre el orgullo de ser se\u00f1alado como el mejor tenista de la historia, aunque no se reconoce como tal. Adem\u00e1s, el suizo cuenta c\u00f3mo es su relaci\u00f3n con los fans, la importancia de ser un ejemplo dentro y fuera de la cancha; y tambi\u00e9n detalla parte del trabajo con su fundaci\u00f3n.\n","likeCount":0,"visitCount":0,"commentCount":0,"videocategory":5,"genre_id":5,"genreparent_id":1,"weight":3496,"duration":"05:17","url":"\/tv\/222\/un-mano-a-mano-con-federer-el-mejor-tenista-de-la-historia-parte-1","modalUrl":"\/modal\/video\/show\/222"}},"target":{"id":1,"title":"Fansworld.TV ","image":"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/01\/thumb_2_default_small_square_6a39865b3ad9d25f56b288de71b58a8d3d3f6f69.jpg","createdAt":"1373062435","firstname":"Fansworld.TV","lastname":null,"fanCount":0,"splash":null,"sex":null,"username":"fansworld","url":"\/u\/fansworld\/wall","location":null,"canFriend":true},"fanOf":[]},{"id":"1065","ts":"1373090112","type":1,"typeName":"new_video","media":{"video":{"id":"221","slug":"fans-molotov","title":"Fans Molotov","image":"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/03\/thumb_2298_default_big_139c607d212e2a17bce27f369359a7f84b452a7b.jpg","createdAt":"1373064923","author":{"id":1,"title":"Fansworld.TV ","image":"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/01\/thumb_2_default_small_square_6a39865b3ad9d25f56b288de71b58a8d3d3f6f69.jpg","createdAt":"1373062435","firstname":"Fansworld.TV","lastname":null,"fanCount":0,"splash":null,"sex":null,"username":"fansworld","url":"\/u\/fansworld\/wall","location":null,"canFriend":true},"content":null,"likeCount":0,"visitCount":0,"commentCount":0,"videocategory":2,"genre_id":10,"genreparent_id":8,"weight":3496,"duration":"02:44","url":"\/tv\/221\/fans-molotov","modalUrl":"\/modal\/video\/show\/221"}},"target":{"id":1,"title":"Fansworld.TV ","image":"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/01\/thumb_2_default_small_square_6a39865b3ad9d25f56b288de71b58a8d3d3f6f69.jpg","createdAt":"1373062435","firstname":"Fansworld.TV","lastname":null,"fanCount":0,"splash":null,"sex":null,"username":"fansworld","url":"\/u\/fansworld\/wall","location":null,"canFriend":true},"fanOf":[]},{"id":"1064","ts":"1373090109","type":1,"typeName":"new_video","media":{"video":{"id":"220","slug":"fans-pearl-jam","title":"Fans Pearl Jam","image":"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/03\/thumb_2297_default_big_abf3e77719b617cf33f090e14e47bec59d7bdf72.jpg","createdAt":"1373064922","author":{"id":1,"title":"Fansworld.TV ","image":"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/01\/thumb_2_default_small_square_6a39865b3ad9d25f56b288de71b58a8d3d3f6f69.jpg","createdAt":"1373062435","firstname":"Fansworld.TV","lastname":null,"fanCount":0,"splash":null,"sex":null,"username":"fansworld","url":"\/u\/fansworld\/wall","location":null,"canFriend":true},"content":"Te mostramos a los famosos m\u00e1s fan\u00e1ticos de Pearl Jam. Los actores Christian Sancho y Gast\u00f3n Soffritti nos cuentan que temas les gustan de la banda y c\u00f3mo empezaron a seguirla. Tambi\u00e9n, Leonardo De Cecco -baterista de Ataque 77- aporta su calificada opini\u00f3n.\n","likeCount":0,"visitCount":0,"commentCount":0,"videocategory":2,"genre_id":10,"genreparent_id":8,"weight":3496,"duration":"05:09","url":"\/tv\/220\/fans-pearl-jam","modalUrl":"\/modal\/video\/show\/220"}},"target":{"id":1,"title":"Fansworld.TV ","image":"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/01\/thumb_2_default_small_square_6a39865b3ad9d25f56b288de71b58a8d3d3f6f69.jpg","createdAt":"1373062435","firstname":"Fansworld.TV","lastname":null,"fanCount":0,"splash":null,"sex":null,"username":"fansworld","url":"\/u\/fansworld\/wall","location":null,"canFriend":true},"fanOf":[]}],"view":["\t<div class=\"avatar\">\n\t\t<img src=\"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/01\/thumb_2_default_small_square_6a39865b3ad9d25f56b288de71b58a8d3d3f6f69.jpg\" \/>\n\t<\/div>\n\t\t\t\t\t\t\t\t\t\t\t<span class=\"notice-text\"><a class=\"notice-link\" href=\"\/u\/fansworld\">fansworld<\/a> Subi\u00f3 <a href=\"\/tv\/224\/los-fans-y-el-tenis-segun-djokovic-y-federer\" data-modal-url=\"\/modal\/video\/show\/224\">un video<\/a><\/span>\n","\t<div class=\"avatar\">\n\t\t<img src=\"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/01\/thumb_2_default_small_square_6a39865b3ad9d25f56b288de71b58a8d3d3f6f69.jpg\" \/>\n\t<\/div>\n\t\t\t\t\t\t\t\t\t\t\t<span class=\"notice-text\"><a class=\"notice-link\" href=\"\/u\/fansworld\">fansworld<\/a> Subi\u00f3 <a href=\"\/tv\/223\/un-mano-a-mano-con-federer-el-mejor-tenista-de-la-historia-parte-2\" data-modal-url=\"\/modal\/video\/show\/223\">un video<\/a><\/span>\n","\t<div class=\"avatar\">\n\t\t<img src=\"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/01\/thumb_2_default_small_square_6a39865b3ad9d25f56b288de71b58a8d3d3f6f69.jpg\" \/>\n\t<\/div>\n\t\t\t\t\t\t\t\t\t\t\t<span class=\"notice-text\"><a class=\"notice-link\" href=\"\/u\/fansworld\">fansworld<\/a> Subi\u00f3 <a href=\"\/tv\/222\/un-mano-a-mano-con-federer-el-mejor-tenista-de-la-historia-parte-1\" data-modal-url=\"\/modal\/video\/show\/222\">un video<\/a><\/span>\n","\t<div class=\"avatar\">\n\t\t<img src=\"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/01\/thumb_2_default_small_square_6a39865b3ad9d25f56b288de71b58a8d3d3f6f69.jpg\" \/>\n\t<\/div>\n\t\t\t\t\t\t\t\t\t\t\t<span class=\"notice-text\"><a class=\"notice-link\" href=\"\/u\/fansworld\">fansworld<\/a> Subi\u00f3 <a href=\"\/tv\/221\/fans-molotov\" data-modal-url=\"\/modal\/video\/show\/221\">un video<\/a><\/span>\n","\t<div class=\"avatar\">\n\t\t<img src=\"http:\/\/fansworld.svn.dodici.com.ar\/uploads\/media\/default\/0001\/01\/thumb_2_default_small_square_6a39865b3ad9d25f56b288de71b58a8d3d3f6f69.jpg\" \/>\n\t<\/div>\n\t\t\t\t\t\t\t\t\t\t\t<span class=\"notice-text\"><a class=\"notice-link\" href=\"\/u\/fansworld\">fansworld<\/a> Subi\u00f3 <a href=\"\/tv\/220\/fans-pearl-jam\" data-modal-url=\"\/modal\/video\/show\/220\">un video<\/a><\/span>\n"],"offset":0}
            window.fansWorldEvents.addListener('widgets-off', function(button, event) {
                if (that.options.isPoped) { 
                    that.popOut(event);
                }
            }); 
            window.fansWorldEvents.addListener(that._name + '_toggle', function(button, event) {
                that.toggle(button, event);
            });

            // Bind close button
            $(that.element).find('.close-share').on("click", function(event) {
                that.popOut(event);
            });
            // Bind mouse actions & css transitions
            $(that.element).on('mouseleave', function(event) {
                $(this).animate({opacity: 0.5});
            });            
            $(that.element).on('mouseenter', function(event) {
                $(this).animate({opacity: 1});
            });            
        },
        makeScrollPane: function() {
            var that = this;
            $(that.element).css('display', 'block');
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
                    // console.log('Handle jsp-scroll-y', this, 'scrollPositionY=', scrollPositionY, 'isAtTop=', isAtTop, 'isAtBottom=', isAtBottom);
                    if(isAtBottom && !that.options.isLoadingActivities) {
                        that.options.isLoadingActivities = true;
                        that.loadMoreActivities();
                    }
                }
            );
            $(that.element).css('display', 'none');
            return;            
        },
        redrawScrollBar: function() {
            var that = this;
            var pane = $(that.element).find('.widget-inner');
            var api = pane.data('jsp');
            api.reinitialise();
        },
        loadMoreActivities: function() {
            var that = this;
            $(that.element).find('.widget-app ul').append('<li><div class="loading"></div></li>');
            that.redrawScrollBar();
            fansworld.activity.getActivity();
        },
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
            if (that.options.isPoped) {
                return;
            }            
            //that.setPosition(event.target);
            event.stopPropagation();
            event.preventDefault();
            $(that.element).show();
            $(that.element).animate({
                opacity: 1
            });
            that.options.isPoped = true;
            that.checkBounds(true);
        },
        popOut: function(event) {
            var that = this;
            if (!that.options.isPoped) {
                return;
            }
            $(that.element).hide();
            $(that.element).animate({
                opacity: 0
            });            
            $(that.options.target).removeClass('active');
            that.options.isPoped = false;
            that.checkBounds(false);
        },
        setTitle: function(title) {
            var that = this;
            $(that.element).find('.widget-title').text(title);
        },
        checkBounds: function(check) {
            var that = this;
            function handleClick(event) {
                if(!that.checkBound(event.pageX, event.pageY, that.element)) {
                    if(that.options.isPoped && !event.target.classList.contains('btn-widget')) {
                        // TODO: buscar un mejor nombre para el boton asociado al evento
                        $(that.options.target).removeClass('active'); 
                        document.body.removeEventListener("click", this, false);
                        that.popOut(event);                            
                    }                            
                }                
            }
            if(check) {
                document.body.addEventListener("click", handleClick, false);
            } else {
                document.body.removeEventListener("click", handleClick, false);
            }
        },
        checkBound: function(x, y, element) {
            var that = this;
            var offset = $(element).offset();
            var height = $(element).height();
            var width = $(element).width();
            if((event.pageY >= offset.top && event.pageY <= offset.top + height) && (event.pageX >= offset.left && event.pageX <= offset.left + width)) {
                return true;
            } else {
                return false;
            }
        },
        toggle: function (button, event) {
            var that = this;
            event.preventDefault();

            if($(button).hasClass('active') && that.options.isPoped) {
                $(that.options.target).removeClass('active');
                $(button).removeClass('active');
                that.popOut(event);                
                return;
            }
            // Set new title
            var title = $(button).attr("data-original-title");
            that.setTitle(title);
            // deselect
            $(that.options.target).removeClass('active');
            // select
            $(button).addClass('active');
            that.options.target = button;

            // Get target window positioning
            var offset = $('nav .widget-bar').offset();
            offset.top = $('nav .widget-bar').height() + 2;
            offset.left -= parseInt(($(that.element).width() / 2) - ($('nav .widget-bar').width() / 2), 10);

            $(that.element).css({
                top: offset.top,
                left: offset.left
            });
            $(that.element).css({
                display: 'block'
            });
            var targetOffset = $(button).offset();
            var arrowOffset = $(that.element).find('.arrow-up').offset();
            var displacement = arrowOffset > targetOffset ? $(that.element).find('.arrow-up').position().left + (targetOffset.left - arrowOffset.left) : $(that.element).find('.arrow-up').position().left - (arrowOffset.left - targetOffset.left); 

            $(that.element).find('.arrow-up').css({
                left: displacement + 'px'
            });
            // Toggle visibility
            if (!that.options.isPoped) {
                //$(that.element).find('.widget-title').css('color', '#0f0');
                that.popIn(event);
            }
            else {
                //$(that.element).find('.widget-title').css('color', '#f00');
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
        title: null,
        name: null
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
                node: that.makeButton(parseInt((Math.random()*0x10), 10), that.options.name),
                count: 0
            });
            console.log(that.options.buttons);
            that.options.footer.find('.widgets').append(that.options.buttons[0].node);

            /*fansworld.notificacion.addListener('ongettotal', function(response){
                that.updateLabel('id', response.result);
            });*/
            // Listen notifications
            fansworld.notificacion.addListener('onnotificationreceived', function(response){
                that.updateLabel('id', fansworld.notificacion.total);
            });
        },
        makeButton: function(id, name) {
            var that = this;
            var button = document.createElement("button");
            button.setAttribute('id', id);
            button.setAttribute('data-toggle', 'dropdown');
            button.setAttribute('title', name);
            //button.setAttribute('rel', 'tooltip');  // Enable tootlit (overlaps widget !)
            button.setAttribute('type', 'button');
            button.setAttribute('data-original-title', that.options.title);
            button.className = "notification";
            button.innerText = name;

            var caret = document.createElement("span");
            caret.className = "caret";
            //button.appendChild(caret);

            var label = that.makeLabel('id', 0);
            button.insertBefore(label, button.firstChild);

            $(button).on("click", function(event) {
                //$('.widget-container').data('fwWidget').toggle(e); // To activate Notifications widget
                $('.widget-container').data('fwActivityWidget').toggle(event);
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
////////////////////////////////////////////////////////////////////////////////
// FansWorld header toolbar                                                   //
////////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
    "use strict";
    // Create the defaults once
    var pluginName = "fwHeaderToolBar";
    var defaults = {
        footer: null,
        buttons: [],
        title: null,
        name: null,
        className: 'btn-widget'
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
            that.options.header = document.querySelector('nav .widget-bar');
            that.options.buttons.forEach(function(element, index, array) {
                element.node = that.makeButton(that.guidGenerator(), element.name, element.title, element.icon, element.plugin);
                that.options.header.insertBefore(element.node, that.options.header.firstChild);
            });
            fansworld.notificacion.addListener('ongettotal', function(response){
                that.updateLabel('id', response.result);
            });
            // Listen notifications
            fansworld.notificacion.addListener('onnotificationreceived', function(response){
                that.updateLabel('id', fansworld.notificacion.total);
            });
        },
        makeButton: function(id, name, title, icon, plugin) {
            var that = this;
            var button = document.createElement("button");
            button.setAttribute('id', id);
            button.setAttribute('data-toggle', 'dropdown');
            button.setAttribute('title', name);
            button.setAttribute('type', 'button');
            button.setAttribute('data-original-title', title);
            button.className = that.options.className;
            button.innerText = name;

            var label = that.makeLabel('id', 0);
            label.style.opacity = 0;
            button.insertBefore(label, button.firstChild);

            var image = document.createElement("i");
            image.classList.add(icon);
            image.classList.add('icon-white');
            button.insertBefore(image, button.firstChild);

            $(button).on("click", function(event) {
                //event.target = button;
                if(!$(this).hasClass('active')) {
                    window.fansWorldEvents.emitEvent('widgets-off', [this, event]);
                }
                /*
                if($(this).hasClass('active')) {
                    window.fansWorldEvents.emitEvent('widgets-off', [this, event]);                    
                } else {
                    window.fansWorldEvents.emitEvent(plugin + '_toggle', [this, event]);
                }
                */
                //event.target.classList.contains('btn-widget')
                window.fansWorldEvents.emitEvent(plugin + '_toggle', [this, event]);
                //$('#' + plugin).data(plugin).toggle(event);                
            });
            return button;
        },
        makeLabel: function(id, count) {
            var span = document.createElement("span");
            span.setAttribute('id', id);
            span.className = "label label-warning label-header";
            span.innerText = count;
            return span;
        },
        updateLabel: function(id, message) {
            var that = this;
            $(that.options.buttons[0].node).find('#id').html(message);
            $(that.options.buttons[0].node).find('#id').effect("highlight", {color: "#a0c882"}, 2000);
        },
        guidGenerator: function() {
            var stack = [];
            var itoh = '0123456789ABCDEF';
            var i = 0;
            // Make array of random hex digits. The UUID only has 32 digits in it, but we
            // allocate an extra items to make room for the '-'s we'll be inserting.
            for (i = 0; i < 36; i += 1) {
                stack[i] = Math.floor(Math.random()*0x10);
            }
            // Conform to RFC-4122, section 4.4
            stack[14] = 4;  // Set 4 high bits of time_high field to version
            stack[19] = (stack[19] && 0x3) || 0x8;  // Specify 2 high bits of clock sequence
            // Convert to hex chars
            for (i = 0; i < 36; i += 1) {
                stack[i] = itoh[stack[i]];
            }
            // Insert '-'s
            stack[8] = stack[13] = stack[18] = stack[23] = '-';

            return stack.join('');
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
    if (window.isLoggedIn) {
        window.fansWorldEvents = window.fansWorldEvents || new EventEmitter();
        // Init Notifications core plugin
        window.fansworld = window.fansworld || {};
        window.fansworld.notificacion = new window.NOTIFICATION();
        // Init Activity core plugin
        window.fansworld = window.fansworld || {};
        window.fansworld.activity = new window.ACTIVITY(); 

        var widgetTemplate = document.querySelector('.widget-container').cloneNode(true);
        var fragment = document.createDocumentFragment();
        fragment.appendChild(widgetTemplate);

        $('header:first').append($(fragment).clone());

        $('.widget-container:eq(0)').fwActivityWidget({
            title: "Actividad"
        });
        $('.widget-container:eq(1)').fwWidget({
            title: "Notificaciones"
        });
        $('header:first').fwHeaderToolBar({
            title: 'fwToolBar',
            id: 'head-toolbar',
            buttons: [
                {plugin: '',name: 'video', title: '', icon: 'icon-film'}, 
                {plugin: 'fwActivityWidget', name: 'activity', title: 'Actividad reciente', icon: 'icon-list'}, 
                {plugin: '', name: 'photos', title: '', icon: 'icon-camera'}, 
                {plugin: 'fwWidget', name: 'Not', title: 'Notificaciones', icon: 'icon-user'}
            ]
        });
        $('#btn-widget-video').on('click', function(event){
            console.log("widget-video")
        });
    }
    return;
});
