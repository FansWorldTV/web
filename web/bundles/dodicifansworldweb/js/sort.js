(function( $ ) {
    $.fn.sort = function(criteria){
        var self = this;
        var sort = {};
        
        if(typeof(criteria) =='undefined'){
            sort.criteria	= 'popular';
        }else{
            sort.criteria 	= criteria;
        }
        
        sort.page       = 1;
        sort.entityId 	= self.attr('data-entity-id');
        sort.entityType = self.attr('data-entity-type');
        sort.dataList 	= self.attr('data-list');

        
        
        if(sort.dataList){
            sort.page = 1;
            site.startMosaic($("[data-list-result]"), {
                minw: 150, 
                margin: 0, 
                liquid: true, 
                minsize: false
            });
            self.find('[data-list-result]').html("");
            get(sort);
            /*
            montageHelper.bindAddMore(function(){
                get(sort);
            });
            */
        }
    };
})( jQuery );


function get(sort){
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
    	
    	templateHelper.renderTemplate(sort.dataList + "-list_element", r.elements, "[data-list-result]", false, function(){
    		montageHelper.doMontage($("[data-list-result]"), {
                minw: 150, 
                margin: 0, 
                liquid: true, 
                minsize: false
            });
            
            $("[data-new-element]").imagesLoaded(function(){
                
            	//console.log( $('[data-list-result]').montage);
                $('[data-list-result]').montage('add', $("[data-new-element]"));
                
                $("[data-new-element]").removeAttr('data-new-element');
            });
        });
    	
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
