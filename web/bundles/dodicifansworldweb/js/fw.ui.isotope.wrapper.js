/*global $, jQuery, alert, console, error, success, endless, ajax, templateHelper, Routing, appLocale*/
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
		normalize: true,
		jsonData: null,
		defaultMediaType: null,
		itemSelector: '.item',
		// custom callback events
		onError: function(error) {},
		onIsotopeLoad: function(data) {},
		onEndless: function(data) {},
		onDataReady: function(data) {return data;},
		onBeforeRender: function(data) {},
		onFilter: function(data) {},
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
				itemSelector : that.options.itemSelector,
				resizable: false, // disable normal resizing
				masonry: {
					columnWidth: ($container.width() / that.getMaxSections($container))
				},
				getSortData: {
					date: function ( $elem ) {
						console.log("requesting sort date: %s", $elem.attr('data-element-date'));
						return $elem.attr('data-element-date');
					},
					author: function ( $elem ) {
						console.log("requesting sort author: %s", $elem.find('.author-image').attr('title'));
						return $elem.find('.author-image').attr('title');
					},
					likes: function ( $elem ) {
						console.log("requesting sort likes: %s", $elem.attr('data-likeCount'));
						return $elem.attr('data-likecount');
					}

				},
				sortBy : 'random',
			});

			// Attach to window resize event
			$(window).smartresize(function(){
				var cells = that.getMaxSections($container);
				$container.find('.item').each(function(i, item){
					var $this = $(this);
					$this.css('width', ((100 / cells) - 1) + "%"); // 2% margen entre los elementos
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
				that.options.jsonData = that.options.onDataReady(that.options.jsonData);
				that.loadGallery(that, that.options.jsonData);
				return;
			})
			.done(that.options.onGallery);
		},
		loadGallery: function(plugin, jsonData) {
			var that = plugin;
			var $container = $(that.element);
			var i, element;
			endless.stop();
			function appendElement (htmlTemplate) {
				var $post = $(htmlTemplate);
				$post.attr("data-likeCount", (Math.random() * 100));
				$post.attr("data-visitCount", (Math.random() * 100));
				$container.append($post).isotope('appended', $post).isotope({ sortBy: 'likes' });
				$(htmlTemplate).find('.image').load(function() {
					that.resize();
				});
			}
			if(that.options.jsonData.length > 0) {
				for(i in that.options.jsonData) {
					if (that.options.jsonData.hasOwnProperty(i)) {
						if(that.options.normalize === true) {
							element = that.normalizeData(that.options.jsonData[i]);
						} else {
							element = that.options.jsonData[i];
						}
						$.when(templateHelper.htmlTemplate('general-column_element', element))
						.then(appendElement);
					}
				}
				$container.isotope('reLayout');
				if(that.options.endless) {
					that.makeEndless();
				}
			} else {
				endless.stop();
			}
		},
		loadData: function(source, query) {
			if(typeof(query) === 'undefined'){
				query = {};
			}
			var that = this;
			var $container = $(that.element);
			var deferred = new jQuery.Deferred();
			$.ajax({
				url: 'http://' + location.host + Routing.generate(appLocale + '_' + that.options.feedSource),
				data: query,
				type: 'post'
			})
			.then(function (responseJSON) {
				that.options.jsonData = responseJSON;
				return that.options.jsonData;
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

			var hrefModal = Routing.generate(appLocale + '_modal_media', {
				'id': data.id,
				'type': data.type
			});

			var authorUrl = Routing.generate(appLocale + '_user_wall', {
				'username': data.author.username
			});
			return {
					'type': data.type,
					'date': data.created,
					'href': href,
					'hrefModal': hrefModal,
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
				},
			});
			$container.isotope('reLayout');
		},
		makeEndless: function(event) {
			var that = this;
			var $container = $(that.element);
			endless.init(1, function() {
				endless.stop();
				that.options.onEndless(that);
				$.when(that.loadData(that.options.feedSource, that.options.feedfilter))
				.then(function(jsonData) {
					jsonData = that.options.onDataReady(jsonData);
					that.options.jsonData = that.options.onDataReady(that.options.jsonData);
					that.loadGallery(that, that.options.jsonData);
				});
			});
		},
		sort: function(key) {
			var that = this;
			var $container = $(that.element);
			$container.data('isotope').$filteredAtoms.each( function( i, item ) {
				//console.log( $.data( item, 'isotope-sort-data').perfDate );
				$container.isotope({ sortBy: key }).isotope( 'updateSortData', $(item) ).isotope();
			});
		},
		randomize: function() {
			var that = this;
			var $container = $(that.element);
			$container.isotope('reloadItems').isotope({ sortBy: 'random' }).isotope('option', { sortBy: 'random' });
			//$container.isotope('option', { sortBy: 'random' });
			that.resize();
		},
		removeAll: function() {
			var that = this;
			var $container = $(that.element);
			var $removable = $container.find(that.options.itemSelector);
			$container.isotope( 'remove', $removable );
			endless.stop();
		},
		destroy: function () {
			var that = this;
			$(that.element).unbind("destroyed", that.teardown);
			that.teardown();
		},
		teardown: function () {
			var that = this;
			endless.stop();
			that.removeAll();
			$.removeData($(that.element)[0], that._name);
			$(that.element).removeClass(that._name);
			that.unbind();
			that.element = null;
		},
		bind: function () { },
		unbind: function () { }
	};
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new Plugin(this, options));
			}
		});
	};
});
