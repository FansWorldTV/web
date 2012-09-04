(function ($) {
    "use strict";
    var List = function ($element, options) {
        
        // initialization and settings
        var $list = $element,
            settings = $.extend({
                // options that may be overriden by html attributes
                'entity': $list.attr('data-list-entity'),
                'entityType': $list.attr('data-list-entity-type') || null,
                'entityId': $list.attr('data-list-entity-id') || null,
                'filters': $list.attr('data-list-filters') || null,
                'filtersElement': $list.attr('data-list-filters-element') || 'li',
                'channels': typeof $list.attr('data-list-channels') === "undefined" ? false : true,
                'fetchMoreButton': typeof $list.attr('data-list-fetch-more-button') === "undefined" ? false : true,
                'fetchMoreButtonClass': $list.attr('data-list-fetch-more-button-class') || 'data-list-fetch-more-button',
                'result': !$list.attr('data-list-result') ? $list : $($list.attr('data-list-result')),
                'scrollable': typeof $list.attr('data-list-scrollable') === "undefined" ? false : true,
                 // other options
                'preloaderImage': '/bundles/dodicifansworldweb/images/ajax-loader-small.gif'
            }, options || {}),
            filter = null,
            target = null,
            methodName = null,
            fetchedCount = 0,
            page = 1,
            preloader = {
                'show': function () {
                    preloader.hide();
                    var $preloader = $('<img src="' + settings.preloaderImage + '" class="list-preloader" />');
                    $(settings.filters).append($preloader);
                },
                'hide': function () {
                    $('.list-preloader').remove();
                }
            };

        function init() {
            // set filters handling
            var $filterItems = $(settings.filters).find(settings.filtersElement);
            $filterItems.each(function () {

                $(this).click(function () {
                    var $btn = $(this);
                    if (!$list.data('fetchLock') && !$btn.hasClass('active')) {
                        page = 1;
                        settings.result.html("");
                        preloader.show();
                        if (settings.channels) {
                            target =  $btn.attr('data-list-target');
                        } else {
                            filter =  $btn.attr('data-list-filter-type');
                        }
                        $list.data('fetchLock', true);
                        $filterItems.removeClass('active');
                        $btn.addClass('active');

                        fetchList();

                    } else {
                        return false;
                    }

                });
            });

            if (settings.scrollable) {
                setScrollable(function () {
                    fetchList();
                });
            }

        }

        function setScrollable(callback) {
            $(window).endlessScroll({
                fireOnce: true,
                enableScrollTop: false,
                inflowPixels: 100,
                fireDelay: 250,
                intervalFrequency: 2000,
                ceaseFireOnEmpty: false,
                loader: '<img src="' + settings.preloaderImage + '" class="list-preloader" />',
                callback: function () {
                    if (typeof callback !== "undefined") {
                        preloader.show();
                        callback();
                    }
                }
            });
        }

        // fetch items list via ajax, and render the template
        function fetchList() {
            fetchedCount = 0;
            methodName = settings.entity + '_' + filter;

            var opts = {
                'page': page,
                'entity': settings.entity
            };

            if (settings.channels) {
                methodName = settings.entity + '_highlighted';
                methodName = 'video_ajaxcategory';
                opts.category = target;
            }

            if (settings.entityType && settings.entityId) {
                opts.entityType = settings.entityType;
                opts.entityId = settings.entityId;
            }

            window.endlessScrollPaused = true;

            ajax.genericAction(methodName, opts, function (response) {
                var i,
                element = response.elements[i];
                if (!response.elements || !response.elements.length) {
                    preloader.hide();
                    settings.result.html("<h2>No se encontraron videos.</h2>");
                    $list.data('fetchLock', false);

                    return false;
                }

                for (i in response.elements) {
                    templateHelper.renderTemplate(settings.entity + "-list_element", element, settings.result, false, function () {
                        site.startMosaic(settings.result, {
                            minw: 150,
                            margin: 0,
                            liquid: true,
                            minsize: false
                        });

                        $("[data-new-element]").imagesLoaded(function () {
                            fetchedCount++;
                            if (response.elements.length === fetchedCount) {
                                preloader.hide();
                            }

                            if (i > 0) {
                                settings.result.montage('add', $("[data-new-element]"));
                            }
                            $("[data-new-element]").removeAttr('data-new-element');
                        });

                        $list.data('fetchLock', false);

                        if (response.addMore) {
                            if (settings.fetchMoreButton) {
                                $('.' + settings.fetchMoreButtonClass).remove();

                                var $button = $('<a>Ver m&aacute;s</a>');
                                if (settings.fetchMoreButtonClass !== null) {
                                    $button.addClass(settings.fetchMoreButtonClass);
                                }

                                $button.click(function () {
                                        fetchList();
                                });

                                $list.after($button);
                            }
                        } else {
                            $('.' + settings.fetchMoreButtonClass).remove();
                        }
                    });
                }

                if (response.addMore) {
                    window.endlessScrollPaused = false;
                } else {
                    window.endlessScrollPaused = true;
                }

                return true;

            }, function (msg) {
                console.error(msg);
            });

            page++;
        }

        init();

    };

    // plugin creation
    $.fn.list = function (options) {

        $(this).each(function () {
            var $element = $(this);

            if ($element.data('list')) {
                return;
            }
            $element.data('list', new List($element, options));
        });

    };

}(jQuery));
