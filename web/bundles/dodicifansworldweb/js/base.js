/*
 * dependencies:
 * jquery 1.8.3
 * jquery UI
 * fos-routing
 * facebook
 *
 */

// FansWorld Plugin Boilerplate

;(function($) {
    "use strict";
    $.ajaxianize = function(el, options) {

        // plugin's default options
        var defaults = {
            route: null,
            params: null,
            callback: null,
            errorCallback: null,
            type: 'POST'
        };

        // to avoid confusions, use "plugin" to reference the current instance of the  object
        var plugin = this;
        plugin.settings = {}

        // constructor
        var init = function() {
            defaults.errorCallback = errorHandler;
            plugin.settings = $.extend({}, defaults, options);
            plugin.el = el;
        };

        plugin.genericAction = function(options) {

            if (arguments.length >= 2) {
                // workaround for old call system
                // cast every argument[i] to the literal objet properties
                var settings = {
                    route: arguments[0],
                    params: arguments[1],
                    callback: arguments[2],
                    errorCallback: arguments[3],
                    type: arguments[4],
                }
                options = $.extend({}, plugin.settings, settings);
            }
            else {
                options = $.extend({}, plugin.settings, options);
            }

            $.ajax({
                url: 'http://' + location.host + Routing.generate(appLocale + '_' + options.route),
                type: options.type,
                data: options.params,
                success: function(r){
                    if(typeof(options.callback) !== 'undefined'){
                        options.callback(r);
                    }
                },
                error: function(r){
                    if(isFunction(options.errorCallback)){
                        options.errorCallback(r);
                    }
                }
            });
        };
        // private methods
        var search = function(method, params, callback) {
            if(!ajax.active) {
                ajax.active = true;

                $.ajax({
                    url: 'http://'+ location.host + Routing.generate( appLocale + '_' + method),
                    data: params,
                    success: function(response) {
                        ajax.active = false;
                        if( typeof(callback) !== 'undefined' ) {
                            callback(response);
                        }
                    }
                });
            }
        };
        var errorHandler = function(error) {
            console.log(error);
        };
        var isFunction = function(fname) {
            return (typeof(fname) == typeof(Function));
        };
        // call the "constructor" method
        init();
    }

})(jQuery);

// ***********************************************************
// LEGACY CODE BELOW
// Attach this plugin to window.ajax
// TODO: refactor inside FansWorld own namespace
// ***********************************************************
;(function($) {
    window.ajax = window.ajax || {};
    window.ajax = new $.ajaxianize();
})(jQuery);

function onFbInit() {    

}

/* Global functions */
/* Wrapper functions for Toast messages */
function notice (message, callback) {
    createNotify({
        message: { html: message },
        type: 'info',
        onClosed: callback
    });
}
function warning (message, callback) {
    createNotify({
        message: { html: message },
        type: 'danger',
        onClosed: callback
    });
}
function error (message, callback) {
    createNotify({
        message: { html: message },
        type: 'error',
        onClosed: callback,
        fadeOut: { enabled: false }
    });
}
function success (message, callback) {
    createNotify({
        message: { html: message },
        type: 'success',
        onClosed: callback
    });
}

function createNotify (options) {
    //$('.notifications.top-right').notify(options).show();
    $('.notifications.bottom-right').fwNotify(options);
    var notification = $('.notifications.bottom-right').data('fwNotify');
    notification.show();
}

$(document).ready(function(){
    $("form").each(function(){
        $(this).attr("novalidate", "true");
    });

    $('body').on('click', '[data-facebook-login]', function(e){
        FB.login(function(response) {
            if (response.authResponse && response.status == 'connected') {
                console.log(response);
                window.location.href = Routing.generate('_security_check');
            } else {
                error('No se ha podido conectar con facebook');
            }
        }, {scope: FBperms});
    });
});

$(function(){
   var notifydiv = $('<div>').addClass('notifications bottom-right');
   $('body').append(notifydiv);
});
