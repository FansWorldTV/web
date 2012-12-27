endless = {
    callback : null,
    tolerance : 10
};

endless.init = function(tolerance, callback){
    if(typeof(tolerance) != 'undefined' ){
        endless.tolerance = tolerance;
    }
    if(typeof(callback) != 'undefined'){
        endless.callback = callback;
    }
    
    endless.bindMyScroll();
}

endless.haveScroll = function(){
    return $(document).height() != $(window).height();
};
    
endless.scrollBottom = function(){
    var scrollSize = $(window).scrollTop() + $(window).height();
        
    percentScrollSize = (scrollSize * 100) / $(document).height();
        
    return percentScrollSize >= ( 100 - endless.tolerance );
    
};

endless.bindMyScroll = function(){
    $(window).bind('scroll', function(){
        if(endless.haveScroll() && endless.scrollBottom()){
            endless.callback();
        }
    });
};

endless.stop = function(){
    $(window).unbind('scroll');
};

endless.resume = function(){
    endless.bindMyScroll();
};