(function($) {

    var List = function($element, options) {
        
        // initialization and settings
        var $list = $element,
        settings = $.extend({
            entity: $list.attr('data-list-entity'),
            entityType: $list.attr('data-list-entity-type') || null,
            entityId: $list.attr('data-list-entity-id') || null,
            filters: $list.attr('data-list-filters') || null,
            filtersElement: 'li',
            preloaderImage: '/bundles/dodicifansworldweb/images/ajax-loader-white.gif',
            fetchMoreButton: typeof $list.attr('data-list-fetch-more-button') == "undefined" ? false : true,
            fetchMoreButtonClass: $list.attr('data-list-fetch-more-button-class') || 'data-list-fetch-more-button',
            page: 1,
            result: !$list.attr('data-list-result')
                    ? $list 
                    : $($list.attr('data-list-result')),
            
            scrollable: typeof $list.attr('data-list-scrollable') == "undefined" ? false : true
        }, options || {});
        
        function init() {
            // set filters handling
            var $filterItems = $(settings.filters).find(settings.filtersElement);
            $filterItems.each(function() {
                if(settings.scrollable) {
                    setScrollable(function() {
                        fetchList();
                    });
                }
            
                $(this).click(function() {
                    var $btn = $(this);
                    if(!$list.data('fetchLock') && !$btn.hasClass('active')) {
                        settings.page = 1;
                        settings.result.html("");
                        preloader.show();
                        settings.filter =  $btn.attr('data-list-filter-type');
                        $list.data('fetchLock', true);
                        $filterItems.removeClass('active');
                        $btn.addClass('active');
                        
                        fetchList();
                        
                    } else {
                        return false;
                    }
                    
                });
            });
        }
        
        var preloader = {
            'show': function() {
                preloader.hide();
                var $preloader = $('<img src="' + settings.preloaderImage + '" class="list-preloader" />');
                $list.after($preloader);
            },
            'hide': function() {
                $('.list-preloader').remove();
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
                callback: function() {
                    if(typeof callback != "undefined") {
                        callback();
                    }
                }
            });
        }
        
        // fetch items list via ajax, and render the template
        function fetchList() {
            var methodName = settings.entity + '_' + settings.filter;
            var opts = {
                'page': settings.page,
                'entity': settings.entity
            };
            
            if(settings.entityType && settings.entityId) {
                opts.entityType = settings.entityType;
                opts.entityId = settings.entityId;
            }
            
            window.endlessScrollPaused = true;
            
            ajax.genericAction(methodName, opts, function(response) {
                if(!response.elements || !response.elements.length) {
                    preloader.hide();
                    settings.result.html("<h2>No se encontraron videos.</h2>");
                    $list.data('fetchLock', false);
                    
                    return false;
                }
                
                for(var i in response.elements) {
                    var element = response.elements[i];
                    templateHelper.renderTemplate(settings.entity + "-list_element", element, settings.result, false, function() {
                        site.startMosaic(settings.result, {
                            minw: 150, 
                            margin: 0, 
                            liquid: true, 
                            minsize: false
                        });
                        
                        $("[data-new-element]").imagesLoaded(function() {
                            
                            if(i>0) {
                                settings.result.montage('add', $("[data-new-element]"));
                            }
                            $("[data-new-element]").removeAttr('data-new-element');
                        });
                        
                        $list.data('fetchLock', false)
                        if(response.addMore) {
                            if(settings.fetchMoreButton) {
                                $('.' + settings.fetchMoreButtonClass).remove();

                                var $button = $('<a>Ver m&aacute;s</a>');
                                if(settings.fetchMoreButtonClass != null) {
                                    $button.addClass(settings.fetchMoreButtonClass);
                                }

                                $button.click(function() {
                                        fetchList();
                                });

                                $list.after($button);
                            }
                        } else {
                            $('.' + settings.fetchMoreButtonClass).remove();
                        }
                    });
                }
                
                if(response.addMore) {
                    window.endlessScrollPaused = false;
                } else {
                    window.endlessScrollPaused = true;
                }
                
                return true;
                
            }, function(msg) {
                console.error(msg);
            });
            
            settings.page++;
        }
        
        init();
        
    };

    // plugin creation
    $.fn.list = function(options) {
        
        $(this).each(function() {
            var $element = $(this);
            
            if ($element.data('list')) return;
            $element.data('list', new List($element, options));
        });
        
    };
    
})(jQuery);
