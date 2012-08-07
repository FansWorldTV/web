(function( $ ) {
    $.fn.sort = function(criteria){
        
        var sort = {};
        
        if(typeof(criteria)=='undefined'){
            sort.criteria = 'popular';
        }else{
            sort.criteria = criteria;
        }
        
        sort.page = 1;
        sort.entityId = $("[data-list]").attr('data-entity-id');
        sort.entityType = $("[data-list]").attr('data-entity-type');
        sort.dataList = $("[data-list]").attr('data-list');

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
                for(var i in r.elements){
                    var element = r.elements[i];
                    templateHelper.renderTemplate(sort.entityType + "-list_element", element.viewData, "[data-list-result]");
                }
                if(r.addMore){
                    window.endlessScrollPaused = false;
                }
            }, function(msg){
                error(msg);
            });
        }
        
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
        callback: function(i, p, d) {
            callback(j, p, d);
        }
    });
}