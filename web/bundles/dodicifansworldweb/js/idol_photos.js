$(function(){
    $('.photomason').isotope({
        layoutMode: 'masonry',
        masonry: {
            itemSelector: '.brick',
            columnWidth: 260
        }
    });
    photopins.init();
});

var photopins = {
    pager : 1,
	    
    init: function(){
        $("a.loadmore.pictures:not(.loading)").click(function(e){
            e.preventDefault();
            photopins.get();
        });
        photopins.get();
    },
	    
    get : function(){
        $("a.loadmore.pictures").addClass('loading');
        ajax.getPhotosAction($('#user_id').val(), photopins.pager, true, function(r){
            if(r){

                // proceed only if we have data
                if ( !r['images'] || !r['images'].length ) {
                    error('No more results');
                    $("a.loadmore.pictures.loading").remove();
                    $(".user_profile_body .cont").append("<h3 class='border0'>No hay resultados.</h3>");
                    return;
                }
                var items = [], datum;
                    
                for ( var i=0, len = r['images'].length; i < len; i++ ) {
                    datum = r['images'][i];
                    items.push( datum.htmlpin );
                }
                    
                var $items = $( items.join('') );
                $items.find('.pincomments .comment .message').expander({
                    slicePoint: 100,
                    expandText: '[+]',
                    userCollapse: false,
                    afterExpand: function() {
                        $('.photomason').isotope().resize();
                    }
                });
                $items.imagesLoaded(function(){
                    $('.photomason').isotope( 'insert', $items, function(){
                        if(!r['gotMore']){
                            $("a.loadmore.pictures").hide();
                        }
                        photopins.pager++;
                        $("a.loadmore.pictures").removeClass('loading');
                    });
                });
            }
        },
        function(error){
            error(error);
            $("a.loadmore.pictures").removeClass('loading');
        });
    }
};