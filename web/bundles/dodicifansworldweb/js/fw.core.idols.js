/*global
    $,
    jQuery,
    error,
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

/*******************************************************************************
 * Class dependencies:
 *      jquery > 1.8.3
 *      jsrender
 *      jsviews
 * External dependencies:
 *      FOS Routing
 *      templateHelper
 *      ajax
 *      error
 *      endless
 ******************************************************************************/

/*******************************************************************************
    FansWorld idols Class Module 1.3 minor fixes
    // 1.2 (adds hasOwnProperty check)
    // 1.1 Initial

*******************************************************************************/
(function (root, factory) {
    "use strict";
    if (typeof exports === 'object') {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like enviroments that support module.exports,
        // like Node.
        module.exports = factory(require('jQuery'), require('Routing'), require('templateHelper'), require('ajax'), require('error'), require('endless'));
    } else if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jQuery', 'Routing', 'templateHelper', 'ajax', 'error', 'endless'], factory);
    } else {
        // Browser globals (root is window)
        root.IDOLS = factory(root.jQuery, root.Routing, root.templateHelper, root.ajax, root.error, root.endless);
    }
}(this, function (jQuery, Routing, templateHelper, ajax, error) {
    "use strict";
    var IDOLS = (function() {
        function IDOLS() {
            ///////////////////
            // Internal init //
            ///////////////////
            this.jQuery = jQuery;
            this.version = '1.2';
            this.category = 0;
            this.activePage = 1;
            var that = this;
            if($("div.list-idols").attr('data-got-more')){
                this.addMore = true;
            }else{
                this.addMore = false;
            }
            this.getIdols();
            $("ul.categories li a").on('click', function(e){
                e.preventDefault();
                that.category = parseInt($(this).parent().attr('data-category-id'),0);
                that.activePage = 1;

                $(".list-idols dl").html(' ');

                that.getIdols(function(){
                    var docHeight = window.innerHeight;
                    $(document).scrollTop(( docHeight - 20 ));
                });
            });
        }
        IDOLS.prototype.getIdols = function(callback) {
            var that = this;
            var deferred = new jQuery.Deferred();

            endless.stop();
            $("div.list-idols").addClass('loading');

            ajax.genericAction('idol_ajaxlist', {
                'tc': that.category,
                'page': that.activePage
            },
            function(r){
                var i;
                var tplHelperCallback = function() {
                    // Attach bindings to 'idolship' button
                    $(".list-idols dl").find(".btn_idolship.add:not('.loading-small')").each(function() {
                        $(this).fwIdolship({
                            onAddIdol: function(plugin, data) {                                
                                var self = $(plugin.element);
                                self.addClass('disabled');
                                self.removeClass('add');
                                self.text("YA ERES FAN");
                                var number = Number(self.prev().find('span:first').text()) + 1;
                                self.prev().find('span:first').text(number);
                            }
                        });
                    });
                    // Last item clears the spinner and makes callback
                    if(i === (r.idols.length-1)){
                        $("div.list-idols").removeClass('loading');
                        if(typeof(callback) !== 'undefined'){
                            callback();
                        }
                    }
                };
                if(r.idols.length > 0) {
                    for(i in r.idols) {
                        if (r.idols.hasOwnProperty(i)) {
                            var element = r.idols[i];
                            templateHelper.renderTemplate('idol-list_element', element, $(".list-idols dl"), false, tplHelperCallback);
                        }
                    }
                } else {
                    $("div.list-idols").removeClass('loading');
                }

                that.addMore = r.gotMore;
                that.activePage += 1;
                endless.init(1, function() {
                    that.getIdols();
                });
            },
            // On error rejet promise clear spinner
            function(r){
                $("div.list-idols").removeClass('loading');
                endless.stop();
                deferred.reject(new Error(error));
            });

            return deferred.promise();
        };
        IDOLS.prototype.getVersion = function() {
            console.log(this.version);
            return this.version;
        };
        return IDOLS;
    }());
    // Just return a value to define the module export.
    // This example returns an object, but the module
    // can return a function as the exported value.
    return IDOLS;
}));


$(document).ready(function () {
    "use strict";
    window.fansworld = window.fansworld || {};
    window.fansworld.idols = new window.IDOLS();
});