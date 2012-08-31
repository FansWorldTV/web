(function($) {

    var List = function($element, options) {
        
        // initialization and settings
        var $list = $element,
        settings = $.extend({
            entity: $list.attr('data-list-entity'),
            filters: $list.attr('data-list-filters'),
            filtersElement: 'li',
            page: 1,
            result: !$list.attr('data-list-result')
                    ? $list 
                    : $($list.attr('data-list-result')),
            
            scrollable: !$list.attr('data-list-scrollable') ? false : true
        }, options || {}),
        $filters = null;
        
        function init() {
            setFilters();
        }
        
        // set filters handling
        function setFilters() {
            $filters = $(settings.filters);
            var $filterItems = $filters.find(settings.filtersElement);
            $filterItems.each(function() {
                var filterType = $(this).attr('data-list-filter-type');
                
                if(settings.scrollable) {
                    setScrollable(function() {
                        fetchList(filterType);
                    });
                }
            
                $(this).click(function() {
                    if(!$list.data('fetchLock')) {
                        $list.data('fetchLock', true);
                        $filterItems.removeClass('active');
                        $(this).addClass('active');

                        fetchList(filterType);
                    }
                });
            });
        }
        
        function setScrollable(callback) {
            $(window).endlessScroll({
                fireOnce: true,
                enableScrollTop: false,
                inflowPixels: 100,
                fireDelay: 250,
                intervalFrequency: 2000,
                ceaseFireOnEmpty: false,
                loader: 'cargando',
                callback: function() {
                    if(typeof callback != "undefined") {
                        callback();
                    }
                }
            });
        }
        
        // fetch items list via ajax, and render the template
        function fetchList(filterType) {
            var methodName = settings.entity;
            var opts = {
                'page': settings.page,
                'entityType': settings.entity
            };
            
            switch(filterType) {
                case 'popular':
                    methodName += "_popular";
                    break;
                case 'viewed':
                    methodName += "_highlighted";
                    break;
                case 'latest':
                    methodName += "_visited";
                    break;
                case 'trend':
                    methodName += "_visited";
                    break;
                case 'highlight':
                    methodName += "_highlighted";
                    break;
                case 'most-visited':
                    methodName += "_visited";
                    break;
                case 'most-visited-today':
                    methodName += "_visited";
                    opts['today'] = true;
                    break;
            }
            
            window.endlessScrollPaused = true;
            
            ajax.genericAction(methodName, opts, function(response) {
                if(!response.elements || !response.elements.length) {
                    settings.result.html("<h2>No se encontraron videos.</h2>");
                    $list.data('fetchLock', false)
                    return false;
                }
                
                settings.result.html("");
                
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
