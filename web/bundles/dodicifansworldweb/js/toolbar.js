/**
 * Plugin to handle the site's drag&drop (top/left/right/bottom) toolbar
 */
(function ($, document, window) {
    var
    defaults = {
        position: 'top'
    },
    fwtoolbar = 'fwtoolbar',
    positions = ['top','bottom','left','right'],
    settings,
    element,
    $boxes = []
    ;
    
    function makeDraggable(el) {
        $(el)
        .draggable({ revert: "invalid", cancel: "li", cursorAt: {top: 30, left: 30} });
        
        var position = defaults.position;
        
        publicMethod.reposition($(el), position);
    };
    
    // ****************
    // PUBLIC FUNCTIONS
    // ****************
    
    publicMethod = $.fn[fwtoolbar] = $[fwtoolbar] = function (options) {
        var $this = this;
        
        options = options || {};
        
        publicMethod.init();
        
        if (!$this[0]) {
            // if no selector was given, or no element matches the selector, exit
            return $this;
        }
        
        element = $this[0];
        
        $this.each(function () {
            $.data(this, fwtoolbar, $.extend({}, $.data(this, fwtoolbar) || defaults, options));
            makeDraggable(this);
        });

        return $this;
    };
    
    publicMethod.reposition = function (bar, position) {
        bar.removeClass(positions.join(' ') + ' ui-draggable-dragging')
        .removeAttr('style')
        .addClass(position);
        
        // class for padding to avoid hiding content under the toolbar, etc
        var frame = $('#main-content-frame');
        frame
        .removeClass(positions.join(' '))
        .addClass(position);
    };
    
    // Initialize toolbar
    publicMethod.init = function () {
        if (!$boxes.length) {
            
            // If the body is not present yet, wait for DOM ready
            if (!$('body')[0]) {
                $(publicMethod.init);
                return;
            }
            
            // append global drop targets
            $.each(positions, function(){
                var droptarget = $('<div>')
                    .addClass('toolbardrop ' + this)
                    .attr('data-position', this)
                    // make target droppable
                    .droppable({
                        activeClass: "drop-hover",
                        hoverClass: "drop-active",
                        drop: function( event, ui ) {
                            var bar = ui.draggable;
                            var position = $(this).attr('data-position');
                            
                            publicMethod.reposition(bar, position);
                        }
                    });
                
                $boxes.push(droptarget);
                $('body').append(droptarget);
            });
            
        }
    };
    
    publicMethod.settings = defaults;
    
    // Setup FWToolbar
    publicMethod.init();
}( jQuery, document, this ));

$(function(){
    $('[data-fwtoolbar]').fwtoolbar();
});