/*global $, jQuery, alert, console, error, success, endless, ajax, templateHelper, Routing, appLocale, exports, module, require, define*/
/*jslint nomen: true */ /* Tolerate dangling _ in identifiers */
/*jslint vars: true */ /* Tolerate many var statements per function */
/*jslint white: true */
/*jslint browser: true */
/*jslint devel: true */ /* Assume console, alert, ... */
/*jslint windows: true */ /* Assume Windows */
/*jslint maxerr: 100 */ /* Maximum number of errors */

/*
 * Class dependencies:
 *      jquery > 1.8.3
 *      isotope
 *      jsrender
 *      jsviews
 * external dependencies:
 *      templateHelper
 *      base genericAction
 */

// fansWorld landing Class Module 1.0

(function (root, factory) {
    "use strict";
    if (typeof exports === 'object') {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like enviroments that support module.exports,
        // like Node.
        module.exports = factory(require('jQuery'), require('Routing'), require('templateHelper'), require('ajax'));
    } else if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jQuery', 'Routing', 'templateHelper', 'ajax'], factory);
    } else {
        // Browser globals (root is window)
        root.TEAMS = factory(root.jQuery, root.Routing, root.templateHelper, root.ajax, root.error);
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
            var that = this;
            if($("div.list-teams").attr('data-got-more')){
                this.addMore = true;
            }else{
                this.addMore = false;
            }
            this.getTeams();

            $("ul.categories li a").on('click', function(e){
                //e.preventDefault();
                that.category = $(this).parent().attr('data-category-id');
                that.activePage = 1;

                $(".list-teams dl").html(' ');

                that.getTeams();
            });

            $(window).endlessScroll({
                fireOnce: true,
                enableScrollTop: false,
                inflowPixels: 100,
                fireDelay: 250,
                intervalFrequency: 2000,
                ceaseFireOnEmpty: false,
                loader: 'cargando',
                callback: function(i, p, d) {
                    if(that.addMore){
                        that.getTeams();
                    }
                }
            });
        }
        TEAMS.prototype.getTeams = function() {
            $("div.list-teams").addClass('loading');

            ajax.genericAction('team_get', {
                'category': this.category,
                'page': this.activePage
            },
            function(r){
                var i;
                for(i in r.teams){
                    var element = r.teams[i];
                    console.log("loading team: " + element.title);
                    templateHelper.renderTemplate('team-list_element', element, $(".list-teams dl"), false, function(){
                        $("div.list-teams").removeClass('loading');
                    });
                }
                $(".list-teams dl").find(".btn_teamship.add:not('.loading-small')").each(function() {
                    $(this).fwTeamship();
                });
                this.addMore = r.gotMore;
                this.activePage++;
                //teamship.init();
            },
            function(r){
                console.log(r);
                $("div.list-teams").removeClass('loading');
            });
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
    window.fansworld.teams = new TEAMS();
});