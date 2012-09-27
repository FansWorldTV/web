/**
 * Replace video thumbnail with player, on click (wall, etc)
 */

(function( $ ) {
    $.fn.videoplayerinline = function() {
        return this.each(function() 
        {
            var el = $(this);
            var videoid = el.attr('data-video-player-inline');
            if (videoid) {
                el.addClass('loading');
                  
                ajax.genericAction('video_player_url', {
                    id : videoid
                    },
                    function(r){
                        // replace element with iframe
                        var iframe = 
                            $('<iframe>')
                            .attr('src', r)
                            .addClass('video-inline');
                        
                        el.parent().prepend(iframe);
                        el.remove();
                    },
                    function(){
                        el.removeClass('loading');
                        error('Error cargando player');
                    },
                    'get',
                    false
                );
            }
        });
    };
    
    $(document).on('click', '[data-video-player-inline]', function(e){
        e.preventDefault();
        $(this).videoplayerinline(); 
    });
})( jQuery );