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
    location: {
      x: 0,
      y: 0
    },
    firstVisible: null,
    dataSource: Routing.generate(appLocale + '_tag_ajaxmatch'),
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
      that.options.tagsWidth = $(that.element).width();
      if($(that.element).attr('data-route')) {
        that.options.dataSource = Routing.generate(appLocale + $(that.element).attr('data-route'));
      }
      self.bind("destroyed", $.proxy(that.teardown, that));
      self.addClass(that._name);
      
      console.log("fwTagify")
      
      // Activate swipe listeners
      that.swipe();
      
      that.options.input.autocomplete({
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
                $(that.element).removeClass('menu-active');
                that.options.opened = false;
              },
              create: function( event, ui ) {
                that.options.selected = null;
              },
              open: function( event, ui ) {
                $(that.element).addClass('menu-active');
                that.options.opened = true;
              },
              select: function( event, ui ) {
                that.options.selected = ui.item;
                that.makeTag(that.options.selected);
                that.options.input.val("");
                console.log("selected: " + ui.item.label);
                return false;
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
              event.which === $.ui.keyCode.COMMA || event.which === $.ui.keyCode.ENTER ||
              ( event.which == $.ui.keyCode.TAB && that.options.input.val() !== '' )
            )
            {
              event.stopPropagation();
              event.preventDefault();
              console.log("KEYB ADD TAG: " + that.options.input.val());
              if(that.options.selected) {
                //$(that.element).find('.idols').prepend(that.makeTag(that.options.selected));
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
                that.makeTag(item);
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
          var tag = $('<li class="idol" id="' + item.result.id + '"></li>').append('<div class="thumb"><img src="'+ item.result.image +'" /><div class="info"><a href="/idol/roger-federer"><span class="title">'+item.label+'</span></a><br /><i class="icon-heart"></i> 33</div></div><div class="close"><i class="icon-remove icon-white"></i></div>');
        } else {
          var tag = $('<li class="idol" id="' + item.result.id + '"></li>').append('<div class="thumb"><i class="icon-tag custom-tag"></i><div class="info"><a href="#"><span class="title">'+item.label+'</span></a></div></div><div class="close"><i class="icon-remove icon-white"></i></div>');
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
        $(that.element).find('.idols').prepend(tag);
        that.options.firstVisible = $(that.element).find('.idols').children().first()[0];
        setTimeout(function(){
          that.checkBounds();
          $(that.element).find('.idols').css('left', '0px');
        }, 0);
        return tag;
      },
      checkBounds: function() {
        var that = this;
        var childrenWidth = 0;
        $(that.element).find('.idols').children().each(function() {
          console.log($(this)[0].clientWidth)
          childrenWidth += $(this)[0].clientWidth;
        });
        console.log("childrenWidth: %s tagsWidth: %s", childrenWidth, that.options.tagsWidth);
        
        if(childrenWidth > that.options.tagsWidth) {
          $(that.element).find('.scroll.right').removeClass('hidden');
        }
      },
      swipe: function() {
        var that = this;
        $(that.element).find('.scroll.right').on('click', function() {
          if(that.options.firstVisible.nextSibling) {
            var idols = $(that.element).find('.idols');
            var left = that.options.firstVisible.nextSibling.offsetLeft;
            //idols.css({'-webkit-transform': 'translate3d(-' + left + 'px,0,0)'});
            idols.css('left', -left);
            $(that.element).find('.scroll.left').removeClass('hidden');
            that.options.firstVisible = that.options.firstVisible.nextSibling;
          } else {
            $(this).addClass('hidden');
          }
        });
        $(that.element).find('.scroll.left').on('click', function() {
          if(that.options.firstVisible.previousSibling) {
            var idols = $(that.element).find('.idols');
            //var left = idols.css('left');
            var left = that.options.firstVisible.previousSibling.offsetLeft;
            //idols.css({'-webkit-transform': 'translate3d(-' + left + 'px,0,0)'});
            idols.css('left', -left);
            that.options.firstVisible = that.options.firstVisible.previousSibling;
            if($(that.options.firstVisible.previousSibling).index() == 0) {
              $(that.element).find('.scroll.right').removeClass('hidden');
            }
          } else {
            $(this).addClass('hidden');
          }
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
        item.result.type = item.result.type || 'tag'; // override to custom tag 'text'
        that.options.tags[item.result.type] = that.options.tags[item.result.type] || [];
        that.options.tags[item.result.type][item.result.id] = item;
        //that.updateOutput(that.options.tags)
      },
      deleteEntityItem: function(item) {
        var that = this;
        item.result.type = item.result.type || 'tag'; // override to custom tag 'text'
        delete that.options.tags[item.result.type][item.result.id];
        delete that.options.tags[item.result.type];
        //that.updateOutput(that.options.tags)
      },
      updateOutput: function(list) {
        var that = this;
        var i, str = '';
        for (i in list) {
          if (list.hasOwnProperty(i)) {
            // TODO add hasProperty check
            if(list[i].result.type === 'tag') {
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