(function($){
    $.wordcloud = function(el, tags, options){
        // To avoid scope issues, use 'base' instead of 'this'
        // to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("wordcloud", base);
        
        base.init = function(){
            base.options = $.extend({},$.wordcloud.defaultOptions, options);
            
            base.testWeights();
            
            base.$el.addClass('wordcloud-container');
            for(var i in tags){
                var tag = tags[i];
                var tpl = $("<li>").html($("<a>").html(tag.text).attr('href', tag.link).addClass(tag.type).addClass('w'+base.calculateStyle(tag.weight)));
                base.$el.append(tpl);
            }
        };
        
        base.maxWeight = 0;
        base.minWeight = null;
        
        base.testWeights = function(){
            for(var i in tags){
                var tag = tags[i];
                if( tag.weight > base.maxWeight ){
                    base.maxWeight = tag.weight;
                }
                if( base.minWeight == null || tag.weight < base.minWeight ){
                    base.minWeight = tag.weight;
                }
            }
        };
        
        base.calculateStyle = function(weight) {
            var calculatedPart = base.maxWeight / 8;
            var wStyle = 0;
            var count = 0;
            
            do{
                count+=calculatedPart;
                wStyle++;
            }while(weight > count);
            
            return wStyle;
        };
        
        // Run initializer
        base.init();
    };
    
    $.wordcloud.defaultOptions = {
        'weightTotal': 8
    };
    
    $.fn.wordcloud = function(tags, options){
        return this.each(function(){
            (new $.wordcloud(this, tags, options));
        });
    };

})(jQuery);