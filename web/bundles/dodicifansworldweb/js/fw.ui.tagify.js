///////////////////////////////////////////////////////////////////////////////
// Plugin generador de tags                                                  //
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
    "use strict";
    var pluginName = "fwTagify";
    var defaults = {
        closable: true,
        input: null,
        selected: null,
        opened: false,
        tags: [],
        tagsWidth: 0,
        allowSpaces: true,
        tagsPosition: 0,
        dataSource: Routing.generate(appLocale + '_tag_ajaxmatch'),
        availableTags:  [
        {
            "id": 3,
            "label": "Lionel Messi",
            "value": "Lionel Messi",
            "result": {
                "id": "87",
                "type": "idol",
                "slug": "lionel-messi",
                "image": "images/equipo_1_avatar.png"
            }
        }, {
            "id": 4,
            "label": "\u00c9ric Abidal",
            "value": "\u00c9ric Abidal",
            "result": {
                "id": "1",
                "type": "idol",
                "slug": "eric-abidal",
                "image": "images/equipo_2_avatar.png"
            }
        }, {
            "id": 5,
            "label": "Alberto Federico Acosta",
            "value": "Alberto Federico Acosta",
            "result": {
                "id": "88",
                "type": "idol",
                "slug": "alberto-federico-acosta",
                "image": "images/equipo_3_avatar.png"
            }
        }, {
            "id": 6,
            "label": "Bryan Adams",
            "value": "Bryan Adams",
            "result": {
                "id": "375",
                "type": "idol",
                "slug": "bryan-adams",
                "image": "images/equipo_4_avatar.png"
            }
        }, {
            "id": 7,
            "label": "Veleria Gastaldi",
            "value": "Valeria Gastaldi",
            "result": {
                "id": "333",
                "type": "idol",
                "slug": "valeria-gastalid",
                "image": "images/equipo_5_avatar.png"
            }
        }, {
            "id": 8,
            "label": "Airbag",
            "value": "Airbag",
            "result": {
                "id": "5",
                "type": "team",
                "slug": "airbag",
                "image": "images/equipo_6_avatar.png"
            }
        }]
    };
    function Plugin(element, options) {
        this.element = element;
        this.options = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;
        this.init();
    }
    Plugin.prototype = {
        init: function () {
            var that = this;
            var self = $(that.element);
            that.options.input = $(that.element).find('.typeahead');
            that.options.tagsWidth = $(that.element).find('.tags-box').width();
            if($(that.element).attr('data-route')) {
                that.options.dataSource = Routing.generate(appLocale + $(that.element).attr('data-route'));
            }
            self.bind("destroyed", $.proxy(that.teardown, that));
            self.addClass(that._name);

            console.log("fwTagify")

            that.options.input.autocomplete({
                //source: that.options.availableTags,
                source: function( request, response ) {
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
                                return item;
                                return {
                                    label: item.label, //+ (item.adminName1 ? ", " + item.adminName1 : "") + ", " + item.countryName,
                                    value: item.value,
                                    result: item.result
                                };
                            }));
                        }
                    });
                },
                minLength: 1,
                focus: function( event, ui ) {
                    that.options.input.val( ui.item.label )
                    that.options.selected = ui.item;
                    return false;
                },
                close: function( event, ui ) {
                    console.log("close");
                    that.options.opened = false;
                },
                create: function( event, ui ) {
                    that.options.selected = null;
                },
                open: function( event, ui ) {
                    //that.options.oldval = that.options.input.val();
                    that.options.opened = true;
                },
                select: function( event, ui ) {
                    that.options.selected = ui.item;
                    that.options.input.val(ui.item.label);
                    console.log("selected: " + ui.item.label + " appending");
                    return true;
              }
            }).data( "ui-autocomplete" )._renderItem = function( ul, item ) {
                var image = item.result.image;
                console.log(item.label)
                return $("<li></li>")
                    .data("item.autocomplete", item)
                    .append("<a><img class='ui-corner-all ui-avatar-icon' src="+ image +">"+ item.label + "</a>")
                    .appendTo(ul);
            }

            that.options.widget = that.options.input.autocomplete( "widget" );

            that.options.widget.on( "menublur", function( event, ui ) {
                console.log("onBlur");
                //that.options.input.val(that.options.oldval);
            });

            that.options.input.keydown(function(event) {
                //event.preventDefault();
                if(that.options.opened){
                    return;
                }
                if (
                    event.which === $.ui.keyCode.COMMA ||
                    event.which === $.ui.keyCode.ENTER ||
                    (
                        event.which == $.ui.keyCode.TAB &&
                        that.options.input.val() !== ''
                    ) ||
                    (
                        event.which == $.ui.keyCode.SPACE &&
                        that.options.allowSpaces !== true &&
                        (
                            $.trim(that.options.input.val()).replace( /^s*/, '' ).charAt(0) != '"' ||
                            (
                                $.trim(that.options.input.val()).charAt(0) == '"' &&
                                $.trim(that.options.input.val()).charAt($.trim(that.options.input.val()).length - 1) == '"' &&
                                $.trim(that.options.input.val()).length - 1 !== 0
                            )
                        )
                    )
                ) {
                    event.stopPropagation();
                    event.preventDefault();
                    console.log("KEYB ADD TAG: " + that.options.input.val());
                    if(that.options.selected) {
                        $(that.element).find('.idols').prepend(that.makeTag(that.options.selected));
                        that.checkBounds();
                    }
                    else {
                        var slug = that.options.input.val();
                        var item = {
                            id: parseInt(Math.random()*99999),
                            label: that.options.input.val(),
                            value: that.options.input.val(),
                            result: {
                                id: parseInt(Math.random()*99999),
                                slug: slug.replace(/ +/g, '-'),
                                type: 'text'
                            }
                        };
                        $(that.element).find('.idols').prepend(that.makeTag(item));
                        that.checkBounds();
                    }
                }
            })

            return this;
        },
        makeTag: function(item) {
            var that = this;
            var deferred = new jQuery.Deferred();
            if(that.options.tags[item.result.id]) {
                that.options.input.val('');
                that.options.selected = null;
                return;
            }
            that.options.input.val('');
            that.options.selected = null;
            that.addEntityItem({label: item.label, result: item.result});
            if(item.result.image) {
                var tag = $('<li class="idol" id="' + item.result.id + '"></li>').append('<div class="thumb"><img src="'+ item.result.image +'" /><div class="info"><a href="/idol/roger-federer"><span class="title">'+item.label+'</span></a><br /><i class="icon-heart"></i> 33</div></div><div class="close"><i class="icon-remove"></i></div>');
            } else {
                var tag = $('<li class="idol" id="' + item.result.id + '"></li>').append('<div class="thumb"><i class="icon-tag custom-tag"></i><div class="info"><a href="#"><span class="title">'+item.label+'</span></a></div></div><div class="close"><i class="icon-remove"></i></div>');
            }
            tag.data('tag', item);

            function tagClose(tag, item) {
                tag.find('.close').on('click', function(event) {
                    that.deleteEntityItem(item);
                    $(that.element).find('#' + item.result.id).hide(250, function () {
                        $(this).remove();
                    })
                });
            }
            that.handleClose(tag, item);
            //deferred.reject($(responseXML).find('error').text());
            //deferred.promise();
            return tag;
        },
        checkBounds: function() {
            var that = this;
            var childrenWidth = 0;
            var tags = $(that.element).find('.idols').children();
            for(var i = 0; i < tags.length; i += 1) {
                if (tags.hasOwnProperty(i)) {
                    childrenWidth += tags[i].clientWidth;
                }
            }
            if(tags.length > 0)
            childrenWidth = $(that.element).find('.idols > .idol:last').position().left + $(that.element).find('.idol:last').width();
            console.log("childrenWidth: %s tagsWidth: %s", childrenWidth, that.options.tagsWidth)
            if(childrenWidth > that.options.tagsWidth) {
                $(that.element).find('.scroll.right').removeClass('hidden');
                that.swipe();
            }
        },
        swipe: function() {
            var that = this;
            $(that.element).find('.scroll.right').on('click', function() {
                var idols = $(that.element).find('.idols');
                var left = idols.css('left');
                idols.css({'-webkit-transform': 'translate3d(-' + 280 + 'px,0,0)'});
                $(that.element).find('.scroll.left').removeClass('hidden');
            });
            $(that.element).find('.scroll.left').on('click', function() {
                var idols = $(that.element).find('.idols');
                var left = idols.css('left');
                idols.css({'-webkit-transform': 'translate3d(-' + 0 + 'px,0,0)'});
            });
        },
        handleClose: function(tag, item) {
            var that = this;
            tag.find('.close').on('click', function(event) {
                that.deleteEntityItem(item);
                $(that.element).find('#' + item.result.id).hide(250, function () {
                    $(this).remove();
                })
            });
            return;
        },
        addEntityItem: function (item) {
            var that = this;
            item.result.type = item.result.type || 'text'; // override to custom tag 'text'
            that.options.tags[item.result.id] = item;
            console.log(that.options.tags);
            that.updateOutput(that.options.tags)
            //var a = that.options[that.options.magic];
            //a[item.result.type].selected.push(item);
            //that.updateInput('#form_' + that.options.action + item.result.type, a[item.result.type].selected);
            //console.log("will add: " + JSON.stringify(item) + " to: #form_" + that.options.action + item.result.type);
        },
        deleteEntityItem: function(item) {
            var that = this;
            //console.log(item)
            item.result.type = item.result.type || 'text'; // override to custom tag 'text'
            delete that.options.tags[item.result.id];
            that.updateOutput(that.options.tags)
        },
        updateOutput: function(list) {
            var that = this;
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
            $('#output').val(str)
            that.options.output = str;
        },
        getTags: function(entry) {
            var that = this;
            var deferred = new jQuery.Deferred();
            /*
            $.ajax({
                url: that.options.dataSource,
                dataType: "JSON",
                type: 'GET',
                data: {
                    'text': request.term
                }
            })
            .then(function (response){

            })
            .done(function (tags) {
                deferred.resolve(tags);
            })
            .fail(function (error) {
                deferred.reject(new Error(error));
            });
            */
            return deferred.promise();
        },
        destroy: function() {
            $(this.element).unbind("destroyed", this.teardown);
            this.options.onClosed();
            this.teardown();
            return true;
        },
        teardown: function() {
            $.removeData($(this.element)[0], this._name);
            $(this.element).removeClass(this._name);
            this.unbind();
            this.element = null;
            return this.element;
        },
        bind: function() { },
        unbind: function() { }
    };
    $.fn[pluginName] = function (options) {
        return this.each(function () {
            if (!$.data(this, pluginName)) {
                $.data(this, pluginName, new Plugin(this, options));
            }
        });
    };
});