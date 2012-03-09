$(function(){
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
            ajax.getPhotosAction(null, photopins.pager, true, function(r){
                if(r){

                	// proceed only if we have data
                    if ( !r['images'] || !r['images'].length ) {
                      error('No more results');
                      return;
                    }
                    var items = [], datum;
                    
                    for ( var i=0, len = r['images'].length; i < len; i++ ) {
                      datum = r['images'][i];
                      items.push( datum.htmlpin );
                    }
                    
                    var $items = $( items.join('') );
                    $items.imagesLoaded(function(){
                      $('.photomason').masonry()
                        .append( $items ).masonry( 'appended', $items, true )
                        .masonry().resize();
                      if(!r['gotMore']){
	                      $("a.loadmore.pictures").hide();
	                  }
                      photopins.pager++;
	                  $("a.loadmore.pictures").removeClass('loading');
                    });
                }
            },
            function(error){
            	error(error);
            	$("a.loadmore.pictures").removeClass('loading');
            });
	    }
	};