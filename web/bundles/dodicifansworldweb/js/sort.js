(function( $ ) {
    $.fn.sort = function(criteria){
        if(typeof(criteria)=='undefined'){
            this.criteria = 'popular';
        }else{
            this.criteria = criteria;
        }
        this.page = 1;
        
    };
})( jQuery );