var montageHelper = {
		
		
    init: function(){
			
    },
    
    defaults: {
		minw: 150, 
        margin: 0, 
        liquid: true, 
        minsize: false,
        fillLastRow: true
	},
    
    doMontage: function(container,opts){
    	
		var $imgs		= container.find('img').hide();
		var totalImgs	= $imgs.length;
		var cnt			= 0;
		var options 		= $.extend({},montageHelper.defaults,opts);
		
		$imgs.each(function(i) {
			var $img	= $(this);
			$('<img/>').load(function() {
				++cnt;
				if( cnt === totalImgs ) {
					$imgs.show();
					container.montage(options);
				}
			}).attr('src',$img.attr('src'));
		});	
    },
    
    addToMontage: function(newImages,container,opts)
    {
    	var $newimages = $( newImages );
		$newimages.imagesLoaded( function(){
			container.append( $newimages ).montage( 'add', $newimages );
		});
    	
    },
    
    bindAddMore: function(callback){
    	$(window).endlessScroll({
            fireOnce: true,
            enableScrollTop: false,
            inflowPixels: 100,
            fireDelay: 250,
            intervalFrequency: 2000,
            ceaseFireOnEmpty: false,
            loader: 'cargando',
            callback: function(r) {
                callback(r);
            }
        });
    }
    
    
    
    
};