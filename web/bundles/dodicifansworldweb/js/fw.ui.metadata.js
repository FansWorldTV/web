/*global $, jQuery, alert, console, error, success, endless, ajax, templateHelper, Routing, appLocale, exports, module, require, define*/
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
 *      jquery tokeninput 9
 *      fos-routing
 * external dependencies:
 *      appLocale
 */

// FansWorld tagify plugin 1.7 (tagit)
// 1.4 fix FB.ui popup
// 1.5 add 'user' type to tagifier
// 1.6 missing comma

$(document).ready(function () {

    "use strict";

    // Create the defaults once
    var pluginName = "fwTagify";
    var defaults = {
        theme: 'fansworld',
        queryParam: 'text',
        preventDuplicates: true,
        propertyToSearch: 'label',
        team: {
            selected: []
        },
        idol: {
            selected: []
        },
        user: {
            selected: []
        },
        text: {
            selected: []
        },
        sampleTags: ['c++', 'java', 'php', 'coldfusion', 'javascript', 'asp', 'ruby', 'python', 'c', 'scala', 'groovy', 'haskell', 'perl', 'erlang', 'apl', 'cobol', 'go', 'lua'],
        prePopulate: [],
        action: null,
        dataSource: null,
        onEntityAdd: function(entity) {},
        onEntityDelete: function(entity) {}
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
            // Place initialization logic here
            // You already have access to the DOM element and
            // the options via the instance, e.g. this.element
            // and this.options
            var that = this;
            //that.options.dataSource = Routing.generate(appLocale + '_tag_ajaxmatch');
            that.options.dataSource = Routing.generate(appLocale + $(that.element).attr('data-route'));
            that.options.action = $(that.element).attr('data-action');
            var pre = $("#form_prepopulate").val();
            if(typeof(pre) !== 'undefined' && pre.length > 0) {
                pre.split(',').forEach(function(val) {
                    if(val.length > 0) {
                        var tagInfo = val.split(':');
                        var item = {
                            id: tagInfo[0],
                            label: tagInfo[2],
                            value: tagInfo[2],
                            result: {
                                id: tagInfo[0],
                                type: tagInfo[1].toLowerCase()
                            }
                        };
                        that.options.prePopulate.push(item);
                        that.options[item.result.type.toLowerCase()].selected.push(item);
                        //console.log(item.result.type)
                        //that.addEntityItem(that.options.prePopulate[that.options.prePopulate.length -1]);
                    }
                });
                console.log("sale preload");
            }
            console.log(that.options.dataSource);
            /*
            $(that.element).tokenInput(that.options.dataSource, {
                theme: that.options.theme,
                queryParam: that.options.queryParam || "text",
                preventDuplicates: that.options.preventDuplicates || true,
                propertyToSearch: that.options.propertyToSearch || "label",
                prePopulate: that.options.prePopulate,
                onAdd: function(item) {
                    console.log(item)
                    that.addEntityItem(item);
                },
                onDelete: function(item) {
                    that.deleteEntityItem(item);
                }
            });
            */

            $(that.element).tagit({
                availableTags: that.options.sampleTags,
                // This will make Tag-it submit a single form value, as a comma-delimited field.
                autocomplete: {
                    source: function( request, response ) {
                        console.log("tagit llama autocomplete request.term: " + request.term)
                        $.ajax({
                            url: that.options.dataSource,
                            dataType: "JSON",
                            type: 'GET',
                            data: {
                                'text': request.term
                            },
                            success: function( data ) {
                                response( $.map( data, function( item ) {
                                    return {
                                        label: item.label, //+ (item.adminName1 ? ", " + item.adminName1 : "") + ", " + item.countryName,
                                        value: item.value,
                                        extraData: item.result
                                    };
                                }));
                            }
                        });
                    },
                    minLength: 1
                },
                singleField: true,
                singleFieldNode: $(that.element),
                afterTagAdded: function(evt, ui) {
                    if (!ui.duringInitialization) {
                        console.log("afterTagAdded")
                        console.log(ui.extra)
                        that.addEntityItem({result: ui.extra});
                    }
                },
                afterTagRemoved: function(evt, ui) {
                    if (!ui.duringInitialization) {
                        console.log("afterTagRemoved")
                        console.log(ui.tag.data('extra'))
                        that.deleteEntityItem({result: ui.tag.data('extra')});
                    }
                }                
            });

        },
        addEntityItem: function (item) {
            var that = this;
            /*
            if(typeof(item.result.type) === 'undefined') {
                item.result.type = 
            }
            */
            item.result.type = item.result.type || 'text';
            that.options[item.result.type].selected.push(item);
            that.updateInput('#form_' + that.options.action + item.result.type, that.options[item.result.type].selected);
            console.log("will add: " + JSON.stringify(item) + " to: #form_" + that.options.action + item.result.type);
        },
        updateInput: function(inputSelector, list) {
            var i, str = '';
            for (i in list) {
                // TODO add hasProperty check 
                str += list[i].result.id + ',';
            }
            $(inputSelector).val(str);
        },
        deleteEntityItem: function(item) {
            var that = this;
            item.result.type = item.result.type || 'text';
            var pos = that.options[item.result.type].selected.indexOf(item);
            that.options[item.result.type].selected.splice(pos, 1);
            that.updateInput('#form_' + that.options.action + item.result.type, that.options[item.result.type].selected);
            console.log("will del: " + JSON.stringify(item) + " to: #form_" + that.options.action + item.result.type);
        }
    };
    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[pluginName] = function (options) {
        // If the first parameter is an object (options), or was omitted,
        // instantiate a new instance of the plugin.
        if (typeof options === "object" || !options)
        {
            return this.each(function () {
                // Only allow the plugin to be instantiated once.
                if (!$.data(this, "plugin_" + pluginName)) {
                    // Pass options to Plugin constructor, and store Plugin
                    // instance in the elements jQuery data object.
                    $.data(this, "plugin_" + pluginName, new Plugin(this, options));
                }
            });
        }
    };
});

// FansWorld sharify plugin 1.0
$(document).ready(function () {

    "use strict";
    // Create the defaults once
    var pluginName = "fwSharify";
    var defaults = {
        fb: false,
        tw: false,
        fw: true,
        onShare: function() {},
        onUnShare: function() {}
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
            $(that.element).on('click', function() {
                $(that.element).toggleClass('active');
                if ($(this).hasClass('fb')) { that.facebookMiddleware(); }
                if ($(this).hasClass('tw')) { that.twitterMiddleware(); }
                if ($(this).hasClass('fw')) { that.fansworldMiddleware(); }
            });
            that.updateInputValues();
        },
        updateInputValues: function()
        {
            var that = this;
            $('#form_fb').val(that.options.fb);
            $('#form_tw').val(that.options.tw);
            $('#form_fw').val(that.options.fw);
        },
        facebookMiddleware: function(){
            var that = this;
            console.log("facebookMiddleware")
            if ($(that.element).hasClass('active')) {
                console.log("is active")
                that.options.fb = true;
                FB.ui({
                    method: 'permissions.request',
                    'perms': window.FBperms,
                    'display': 'popup',
                    'response_type': 'signed_request',
                    'fbconnect': 1,
                    'next': 'http://' + location.host + Routing.generate(appLocale + '_' + 'facebook_jstoken')
                },
                function(response) {
                    console.log(response);
                });
            } else {
                that.options.fb = false;
            }
        },
        twitterMiddleware: function(){
            var that = this;
            if($(that.element).hasClass('active')) {
                that.options.tw = true;
                window.open(Routing.generate(appLocale + '_' + 'twitter_redirect'), 'fw_twit_link', 'menubar=no,status=no,toolbar=no,width=500,height=300');
            } else {
                that.options.tw = false;
            }
        },
        fansworldMiddleware: function(){
            var that = this;
            if($(that.element).hasClass('active')) {
                that.options.fw = true;
            } else {
                that.options.fw = false;
            }
        }
    };
    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[pluginName] = function (options) {
        // If the first parameter is an object (options), or was omitted,
        // instantiate a new instance of the plugin.
        if (typeof options === "object" || !options)
        {
            return this.each(function () {
                // Only allow the plugin to be instantiated once.
                if (!$.data(this, "plugin_" + pluginName)) {
                    // Pass options to Plugin constructor, and store Plugin
                    // instance in the elements jQuery data object.
                    $.data(this, "plugin_" + pluginName, new Plugin(this, options));
                }
            });
        }
    };
});