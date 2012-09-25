(function ($) {

    "use strict";

    var List = function ($element, options) {
        var list = this;
        list.$list = $element;
        list.settings = $.extend({
            // options that may be overriden by html attributes
            'entity': list.$list.attr('data-list-entity'),
            'entityType': list.$list.attr('data-list-entity-type') || null,
            'entityId': list.$list.attr('data-list-entity-id') || null,
            'filters': list.$list.attr('data-list-filters') || null,
            'filtersElement': list.$list.attr('data-list-filters-element') || 'li',
            'channels': typeof list.$list.attr('data-list-channels') === "undefined" ? false : true,
            'fetchMoreButton': typeof list.$list.attr('data-list-fetch-more-button') === "undefined" ? false : true,
            'fetchMoreButtonClass': list.$list.attr('data-list-fetch-more-button-class') || 'data-list-fetch-more-button',
            'result': !list.$list.attr('data-list-result') ? list.$list : $(list.$list.attr('data-list-result')),
            'scrollable': typeof list.$list.attr('data-list-scrollable') === "undefined" ? false : true,
            'montage': typeof list.$list.attr('data-list-montage') === "undefined" ? false : true,
            // other options
            'preloaderImage': '/bundles/dodicifansworldweb/images/ajax-loader-small.gif'
        }, options || {});
        list.filter = $('[data-list-filter-type].active').attr('data-list-filter-type') || null;
        list.target = null;
        list.methodName = null;
        list.fetchedCount = null;
        list.page = 1;
        list.preloader = {
            'show': function () {
                //list.preloader.hide();
                //var $preloader = $('<img src="' + list.settings.preloaderImage + '" class="list-preloader" />');
                //$(list.settings.filters).append($preloader);
                $("[data-list-montage]").find('*').hide();
                $("[data-list-montage]").addClass('loading');
            },
            'hide': function () {
                //$('.list-preloader').remove();
                $("[data-list-montage]").removeClass('loading');
                $("[data-list-montage]").find('*').show();
            }
        };

        list.init = function () {
            if (list.settings.montage) {
                montageHelper.doMontage(list.$list, {});
            }
            
            // set filters handling
            var $filterItems = $(list.settings.filters).find(list.settings.filtersElement);
            $filterItems.each(function () {
                $(this).click(function () {
                    var $btn = $(this);
                    if (!list.$list.data('fetchLock') && !$btn.hasClass('active')) {
                        list.page = 1;
                        list.settings.result.html("");
                        list.preloader.show();
                        if (list.settings.channels) {
                            list.target =  $btn.attr('data-list-target');
                            $('.js-subscribe').attr('data-active-channel', list.target);
                            // showing highlight panels
                            $('.tab-pane').hide();
                            $('.tab-pane#' + list.target).show();

                            // updating bottom list
                            var relatedList = $('#montage-video-list').data('list');
                            relatedList.settings.target = list.target;
                            relatedList.$list.data('fetchLock', true);
                            
                            relatedList.page = 1;
                            relatedList.fetchList();
                        } else {
                            list.filter =  $btn.attr('data-list-filter-type');
                        }
                        console.log(list.$list);
                        list.$list.data('fetchLock', true);
                        $filterItems.removeClass('active');
                        $btn.addClass('active');

                        list.fetchList();

                    } else {
                        return false;
                    }
                });
            });

            if (list.settings.scrollable) {
                list.setScrollable(function () {
                    list.fetchList();
                });
            }

        };

        list.setScrollable = function (callback) {
            $(window).endlessScroll({
                fireOnce: true,
                enableScrollTop: false,
                inflowPixels: 100,
                fireDelay: 250,
                intervalFrequency: 2000,
                ceaseFireOnEmpty: false,
                loader: '<img src="' + list.settings.preloaderImage + '" class="list-preloader" />',
                callback: function () {
                    if (typeof callback !== "undefined") {
                        list.preloader.show();
                        callback();
                    }
                }
            });
        };

        // fetch items list via ajax, and render the template
        list.fetchList = function () {
            list.fetchedCount = 0;
            list.methodName = list.settings.entity + '_' + list.filter;
            
            var opts = {
                'page': list.page,
                'entity': list.settings.entity
            };

            if (list.settings.channels) {
                list.methodName = list.settings.entity + '_ajaxcategory';
            }

            if (list.target !== null) {
                opts.category = list.target;
            }

            if (list.settings.entityType && list.settings.entityId) {
                opts.entityType = list.settings.entityType;
                opts.entityId = list.settings.entityId;
            }

            window.endlessScrollPaused = true;
            ajax.genericAction(list.methodName, opts, function (response) {
                
                if (!response.elements || !response.elements.length) {
                    list.preloader.hide();
                    list.settings.result.html("<h2>No se encontraron videos.</h2>");
                    list.$list.data('fetchLock', false);

                    return false;
                }

                if (list.settings.montage) {
                    list.renderMontage(response);
                } else {
                    list.renderRawList(response);
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

            list.page++;
        };

        list.renderMontage = function (response) {
            for (var i in response.elements) {
                var element = response.elements[i];

                templateHelper.renderTemplate(list.settings.entity + "-list_element", element, list.settings.result, false, function () {
                    site.startMosaic(list.settings.result, {
                        minw: 150,
                        margin: 0,
                        liquid: true,
                        minsize: false
                    });

                    $("[data-new-element]").imagesLoaded(function () {
                        list.fetchedCount++;
                        if (response.elements.length === list.fetchedCount) {
                            list.preloader.hide();
                        }

                        if (i > 0) {
                            list.settings.result.montage('add', $("[data-new-element]"));
                        }
                        $("[data-new-element]").removeAttr('data-new-element');
                    });

                    list.$list.data('fetchLock', false);

                    if (response.addMore && list.settings.fetchMoreButton) {
                        $('.' + list.settings.fetchMoreButtonClass).remove();

                        var $button = $('<a>Ver m&aacute;s</a>');
                        $button.addClass(list.settings.fetchMoreButtonClass);

                        $button.click(function () {
                            list.fetchList();
                        });

                        list.$list.after($button);
                    } else {
                        $('.' + list.settings.fetchMoreButtonClass).remove();
                    }
                });
            }
        }
        
        list.renderRawList = function (response) {
            list.settings.result.html("");
            for(var i in response.elements) {
                list.settings.result.append(response.elements[i].view);
            }
            list.$list.data('fetchLock', false);
            list.preloader.hide();
        }
        
        list.init();

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
