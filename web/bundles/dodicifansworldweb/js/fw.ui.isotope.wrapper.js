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
		endless: true,
		cellWidth: 200,
		feedSource: '',
		feedfilter: {},
		defaultMediaType: null,
		// custom callback events
		onError: function(error) {},
		onIsotopeLoad: function(data) {},
		onEndless: function(data) {},
		onGallery: function(data) {}
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
			$container.isotope({
				itemSelector : '.item',
				resizable: false, // disable normal resizing
				masonry: {
					columnWidth: ($container.width() / that.getMaxSections($container))
				}
			});
			// Attach to window resize event
			$(window).smartresize(function(){
				var cells = that.getMaxSections($container);
				$container.find('.item').each(function(i, item){
				var $this = $(this);
				$this.css('width', ((100 / cells) - 1) + "%") // 2% margen entre los elementos
				});
				$container.isotope({
					// update columnWidth to a percentage of container width
					masonry: { columnWidth: $container.width() / cells }
				});
				$container.isotope('reLayout');
		    });
			// Get feed
			$.when(that.loadData(that.options.feedSource, that.options.feedfilter))
			.then(function(jsonData) {
				that.loadGallery(that, jsonData);
				return;
			})
			.done(that.options.onGallery);
		},
		loadGallery: function(plugin, jsonData) {
			var that = plugin;
			var $container = $(that.element);
			endless.stop();
			if(jsonData.length > 0) {
				for(var i in jsonData) {
					var element = that.normalizeData(jsonData[i]);
					$.when(templateHelper.htmlTemplate('general-column_element', element))
					.then(function(htmlTemplate) {
						var $post = $(htmlTemplate)
						$container.append($post).isotope('appended', $post);
						$(htmlTemplate).find('.image').load(function() {
							that.resize()
						});

					})
				}
				$container.isotope('reLayout');
				if(that.options.endless) {
					console.log("make endless")
					that.makeEndless();

				}
			} else {
				endless.stop();
			}
		},
		loadData: function(source, query) {
			if(typeof(query) == 'undefined'){
				query = {};
			}
			var that = this;
			var $container = $(that.element);
			var deferred = new jQuery.Deferred();
			$.ajax({
				url: 'http://' + location.host + Routing.generate(appLocale + '_' + that.options.feedSource),
				data: query
			})
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
			var that = this;
			var $container = $(that.element);
			var cells = 0;

			if($container.width() <= 600) {
				cells = 1;
			} else if ($container.width() <= 800) {
				cells = 2;
			} else if ($container.width() <= 1000) {
				cells = 3;
			} else if ($container.width() <= 1200) {
				cells = 5;
			} else if ($container.width() > 1200) {
				cells = 6;
			}
			return cells;
		},
		resize: function(event) {
			var that = this;
			var $container = $(that.element);
			$container.find('.item').each(function(i, item){
				var $this = $(this);
				$this.css('width', '16%'); // 2% margen entre los elementos
			});
			$container.isotope({
				// update columnWidth to a percentage of container width
				masonry: {
					columnWidth: $container.width() / that.getMaxSections($container)
				}
			});
			$container.isotope('reLayout');
		},
		makeEndless: function(event) {
			var that = this;
			var $container = $(that.element);
			endless.init(1, function() {
				endless.stop();
				console.log("doing endless")
				that.options.onEndless(that);
				$.when(that.loadData(that.options.feedSource, that.options.feedfilter))
				.then(function(jsonData) {
					that.loadGallery(that, jsonData)
				});
			});
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
