/*
 * library dependencies:
 *      jquery 1.8.3
 *		isotope
 *		jsrender
 *		jsviews
 * external dependencies:
 *      template helper
 */

// fansWorld isotope wrapper plugin 1.0



$(document).ready(function () {
    "use strict";
    var pluginName = "fwGalerizer";
    var defaults = {
        propertyName: "fansworld",
        selector: null,
        cellWidth = 200,
        feedSource: '',
        defaultMediaType: null,
        // custom callback events
        onError: function(error) {},
        onIsotopeLoad: function(data) {},
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
            var $container = $(that.element);
            that.options.feedSource = $container.attr('data-feed-source');
            $container.addClass(that._name);
        	$container.css('width', '100%');
        	$container.isotope({
            	itemSelector : '.item',
            	resizable: false, // disable normal resizing
            	masonry: {
                	columnWidth: ($container.width() / getMaxSections($container)),
            	}
        	});
            // Attach to window resize event
        	$(window).smartresize(resize);
            // Get feed
            $.when(loadData(that.options.feedSource))
            .then(function(responseJSON){
                if(responseJSON.length > 0) {
                    for(var i in responseJSON) {
                        var element = responseJSON[i];
                    }
                }
            })
        },
        loadData: function(source) {
            var that = this;
            var $container = $(that.element);
            var deferred = new jQuery.Deferred();
            $.ajax({url: 'http://' + location.host + Routing.generate(appLocale + '_' + that.options.feedSource)})
            .then(function (responseJSON) {
                return responseJSON;
            })
            .done(function (data){
                deferred.resolve(data);
            })
            .fail(function (jqXHR, status, error) {
                deferred.reject(new Error(error));
            });
            return deferred.promise();
        },
        normalizeData: function(data) {
            var href = Routing.generate(appLocale + '_' + data.type + '_show', {
                'id': data.id,
                'slug': data.slug
            });

            var authorUrl = Routing.generate(appLocale + '_user_wall', {
                'username': data.author.username
            });
            return {
                    'type': data.type,
                    'date': data.created,
                    'href': href,
                    'image': data.image,
                    'slug': data.slug,
                    'title': data.title,
                    'author': data.author.username,
                    'authorHref': authorUrl,
                    'authorImage': data.author.image
            };
        },
        getMaxSections: function(container) {
		    var $container = $(container)
		    if($container.width() <= 600) {
		        var cells = 1;
		    } else if ($container.width() <= 800) {
		        var cells = 2;
		    } else if ($container.width() <= 1000) {
		        var cells = 3;
		    } else if ($container.width() <= 1200) {
		        var cells = 4;
		    } else if ($container.width() > 1200) {
		       var cells = 5;
		    }
		    return cells;
		},
        resize: function(event) {
			var $container = $(that.element);
        	$container.find('.item').each(function(i, item){
        		var $this = $(this);
				$this.css('width', ((100 / getMaxSections($container)) - 2) + "%") // 2% margen entre los elementos
			});
			$container.isotope({
	        	// update columnWidth to a percentage of container width
				masonry: { 
					columnWidth: $container.width() / getMaxSections($container); 
				}
			});
			$container.isotope('reLayout');
        },
        destroy: function() {
            var that = this;
            $(that.element).unbind("destroyed", that.teardown);
            that.teardown();
        },
        teardown: function() {
            var that = this;
            $.removeData($(that.element)[0], that._name);
            $(that.element).removeClass(that._name);
            that.unbind();
            that.element = null;
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
