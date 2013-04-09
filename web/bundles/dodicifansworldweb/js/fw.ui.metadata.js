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

// FansWorld tagify plugin 2.2 allow only suggested tags
// 2.1 function que retorna array de elementos seleccionados
// 2.0 tags texto con auto id, labels as values
// 1.4 fix FB.ui popup
// 1.5 add 'user' type to tagifier
// 1.6 missing comma
// 1.8 prepopulate
// 1.9 (con auto tag de perfiles (user, idol, team))

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
        magic: null,
        suggestionsOnly: false,
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
        this._version = '2.2';
        this.init();
    }

    Plugin.prototype = {
        init: function () {
            // Place initialization logic here
            // You already have access to the DOM element and
            // the options via the instance, e.g. this.element
            // and this.options
            var that = this;
            var i;
            //that.options.dataSource = Routing.generate(appLocale + '_tag_ajaxmatch');
            that.options.magic = parseInt(Math.random() * 9999);
            that.options[that.options.magic] = {
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
                }
            };
            var route = $(that.element).attr('data-route');
            if(typeof route !== 'undefined') {
                that.options.dataSource = Routing.generate(appLocale + $(that.element).attr('data-route'));
            }
            that.options.action = $(that.element).attr('data-action');

            // Precargo tags autogenerados
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
                    }
                });
            }
            // Si estoy en un profile base agrego el tag automaticamente
            if ($(".profile_head").length > 0) {
                var profile_type = $(".profile_head").attr("data-profile-type");
                var profile_title = $(".profile_head").attr("data-profile-title");
                var profile_id = $(".profile_head").attr("data-profile-id");
                var item = {
                    id: profile_id,
                    label: profile_title,
                    value: profile_title,
                    result: {
                        id: profile_id,
                        type: profile_type.toLowerCase()
                    }
                };
                console.log("creating auto tag for type: %s, title: %s. id: %s", profile_type, profile_title, profile_id)
                that.options.prePopulate.push(item);
            }
            var tagParams = {
                availableTags: that.options.sampleTags,
                suggestionsOnly: that.options.suggestionsOnly,
                allowSpaces: true,
                singleField: true,
                singleFieldNode: $(that.element),
                afterTagAdded: function(evt, ui) {
                    if (!ui.duringInitialization) {
                        that.addEntityItem({label: ui.tagLabel, result: ui.extra});
                    }
                },
                afterTagRemoved: function(evt, ui) {
                    if (!ui.duringInitialization) {
                        that.deleteEntityItem({label: ui.tagLabel, result: ui.tag.data('extra')});
                    }
                }
            };

            if(that.options.dataSource) {
                tagParams.autocomplete = {
                    source: function( request, response ) {
                        console.log("tagit llama autocomplete request.term: " + request.term);
                        $.ajax({
                            url: that.options.dataSource,
                            dataType: "JSON",
                            type: 'GET',
                            data: {
                                'text': request.term
                            },
                            success: function( data ) {
                                response( $.map( data, function( item ) {
                                    if(item.result.type === 'tag') {
                                        item.result.type = 'text';
                                    }
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
                };
            }
            $(that.element).tagit(tagParams);







            for(i = 0; i < that.options.prePopulate.length; i += 1) {
                if (that.options.prePopulate.hasOwnProperty(i)) {
                    var item  = that.options.prePopulate[i];
                    $(that.element).tagit('createTag', item.value, null, null, item.result);
                }
            }
        },
        addEntityItem: function (item) {
            var that = this;
            item.result.type = item.result.type || 'text'; // override to custom tag 'text'
            //that.options[that.options.magic].selected.push(item);
            var a = that.options[that.options.magic];
            a[item.result.type].selected.push(item);
            //that.options[item.result.type].selected.push(item);
            //that.options[this.options.magic].selected.push(item);
            that.updateInput('#form_' + that.options.action + item.result.type, a[item.result.type].selected);
            console.log("will add: " + JSON.stringify(item) + " to: #form_" + that.options.action + item.result.type);
        },
        deleteEntityItem: function(item) {
            var that = this;
            var fields = that.options[that.options.magic];
            var tags = fields[item.result.type].selected;
            var i = null;
            item.result.type = item.result.type || 'text'; // override to custom tag 'text'

            window.ff = fields;
            for (i in tags) {
                if (tags.hasOwnProperty(i)) {
                    var compare = parseInt(tags[i].result.id, 10);
                    if(parseInt(item.result.id, 10) === compare) {
                        delete tags[i];
                        break;
                    }
                }
            }
            that.updateInput('#form_' + that.options.action + item.result.type, fields[item.result.type].selected);
            console.log("will del: " + JSON.stringify(item) + " to: #form_" + that.options.action + item.result.type);
        },
        updateInput: function(inputSelector, list) {
            var i, str = '';
            for (i in list) {
                if (list.hasOwnProperty(i)) {
                    // TODO add hasProperty check
                    if(list[i].result.type === 'text') {
                        str += list[i].label + ',';
                    } else {
                        str += list[i].result.id + ',';
                    }
                }
            }
            $(inputSelector).val(str);
        },
        getAllTags: function() {
            var that = this;
            return that.options[that.options.magic];
        },
        getVersion: function() {
            return this._version;
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
                if (!$.data(this, pluginName)) {
                    // Pass options to Plugin constructor, and store Plugin
                    // instance in the elements jQuery data object.
                    $.data(this, pluginName, new Plugin(this, options));
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
        updateInputValues: function(){
            var that = this;
            $('#form_fb').val(that.options.fb);
            $('#form_tw').val(that.options.tw);
            $('#form_fw').val(that.options.fw);
        },
        facebookMiddleware: function(){
            var that = this;
            if ($(that.element).hasClass('active')) {
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