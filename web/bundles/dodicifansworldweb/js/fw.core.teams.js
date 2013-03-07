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
/*jslint browser: true */
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
    FansWorld teams Class Module 1.0

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
        root.TEAMS = factory(root.jQuery, root.Routing, root.templateHelper, root.ajax, root.error, root.endless);
    }
}(this, function (jQuery, Routing, templateHelper, ajax, error) {
    "use strict";
    var TEAMS = (function() {
        function TEAMS() {
            ///////////////////
            // Internal init //
            ///////////////////
            this.jQuery = jQuery;
            this.version = '1.0';
            this.category = null;
            this.activePage = 1;

            var that = this;
            if($("div.list-teams").attr('data-got-more')){
                this.addMore = true;
            }else{
                this.addMore = false;
            }
            this.getTeams();

            /*$(".list-teams dl").find(".btn_teamship.add:not('.loading-small')").each(function() {
                $(this).fwTeamship();
            });*/

            $("ul.categories li a").on('click', function(e){
                //e.preventDefault();
                that.category = $(this).parent().attr('data-category-id');
                that.activePage = 1;

                $(".list-teams dl").html(' ');

                that.getTeams(function(){
                    var docHeight = window.innerHeight;
                    $(document).scrollTop(( docHeight - 20 ));
                });
            });
        }
        TEAMS.prototype.getTeams = function(callback) {
            var that = this;
            endless.stop();
            $("div.list-teams").addClass('loading');
            var deferred = new jQuery.Deferred();
            console.log("category: %s, page: %s", that.category, that.activePage);
            ajax.genericAction('team_get', {
                'category': that.category,
                'page': that.activePage
            },
            function(r){
                var i;
                var tplHelperCallback = function(){
                    $(".list-teams dl").find(".btn_teamship.add:not('.loading-small')").each(function() {
                        $(this).fwTeamship();
                    });

                    if(i === (r.teams.length-1)){
                        $("div.list-teams").removeClass('loading');
                        if(typeof(callback) !== 'undefined'){
                            callback();
                        }
                    }
                };
                for(i in r.teams){
                    if (r.teams.hasOwnProperty(i)) {
                        var element = r.teams[i];
                        templateHelper.renderTemplate('team-list_element', element, $(".list-teams dl"), false, tplHelperCallback);
                    }
                }
                that.addMore = r.gotMore;
                that.activePage += 1;
                endless.init(1, function() {
                    that.getTeams();
                });
            },
            function(error){
                $("div.list-teams").removeClass('loading');
                endless.stop();
                deferred.reject(new Error(error));
            });
            return deferred.promise();
        };
        TEAMS.prototype.version = function() {
            console.log(this.version);
            return this.version;
        };
        return TEAMS;
    }());
    // Just return a value to define the module export.
    // This example returns an object, but the module
    // can return a function as the exported value.
    return TEAMS;
}));


$(document).ready(function () {
    "use strict";
    window.fansworld = window.fansworld || {};
    window.fansworld.teams = new window.TEAMS();
});