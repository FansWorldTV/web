(function( $ ) {
    $.fn.sort = function(criteria){
        var self = this;
        var sort = {};
        var mosaicStarted = false;
        
        if(typeof(criteria)=='undefined'){
            sort.criteria = 'popular';
        }else{
            sort.criteria = criteria;
        }
        
        sort.page = 1;
        sort.entityId = self.attr('data-entity-id');
        sort.entityType = self.attr('data-entity-type');
        sort.dataList = self.attr('data-list');

        function get(){
            var methodName = "";
            var opts = {
                'sort': sort.criteria,
                'page': sort.page,
                'entityId': sort.entityId,
                'entityType': sort.entityType
            };
            
            switch(sort.criteria){
                case 'popular':
                    methodName = sort.dataList + "_popular";
                    break;
                case 'highlight':
                    methodName = sort.dataList + "_highlighted";
                    break;
                case 'most-visited':
                    methodName = sort.dataList + "_visited";
                    break;
                case 'most-visited-today':
                    methodName = sort.dataList + "_visited";
                    opts['today'] = true;
                    break;
            }
            window.endlessScrollPaused = true;
            ajax.genericAction(methodName, opts, function(r){
                var c = 0;
                for(var i in r.elements){
                    var element = r.elements[i];
                    templateHelper.renderTemplate(sort.dataList + "-list_element", element, "[data-list-result]", false, function(){
                        site.startMosaic($("[data-list-result]"), {
                            minw: 150, 
                            margin: 0, 
                            liquid: true, 
                            minsize: false
                        });
                        $("[data-new-element]").imagesLoaded(function(){
                            if(c>0){
                                $('[data-list-result]').montage('add', $("[data-new-element]"));
                            }
                            $("[data-new-element]").removeAttr('data-new-element');
                        });
                    });
                    c++;
                }
                
                if(r.addMore){
                    window.endlessScrollPaused = false;
                }else{
                    window.endlessScrollPaused = true;
                }
            }, function(msg){
                error(msg);
            });
            sort.page++;
        }
        
        sort.page = 1;
        self.find('[data-list-result]').html("");
        site.startMosaic($("[data-list-result]"), {
            minw: 150, 
            margin: 0, 
            liquid: true, 
            minsize: false
        });
        get();
        bindAddMore(function(){
            get();
        });
    };
})( jQuery );

function bindAddMore(callback){
    $(window).endlessScroll({
        fireOnce: true,
        enableScrollTop: false,
        inflowPixels: 100,
        fireDelay: 250,
        intervalFrequency: 2000,
        ceaseFireOnEmpty: false,
        loader: 'cargando',
        callback: function() {
            callback();
        }
    });
}