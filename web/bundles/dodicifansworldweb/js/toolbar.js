/**
 * Plugin to handle the site's drag&drop (top/left/right/bottom) toolbar
 */
(function ($, document, window) {
    var
    defaults = {
        position: 'left'
    },
    fwtoolbar = 'fwtoolbar',
    positions = ['top','bottom','left','right'],
    settings,
    element,
    $boxes = []
    ;
    
    function makeDraggable(el) {
        $(el)
        .draggable({ 
            revert: "invalid", 
            cancel: "li", 
            cursorAt: {top: 30, left: 30},
            stop: function( event, ui ) {
                $(this).removeAttr('style');
            }
        });
        
        var position = defaults.position;
        
        // get position from user/session
        ajax.genericAction('preference_get', {
                key: 'toolbar_pos'
            },
            function(val){
                if (val) {
                    position = val;
                }
                publicMethod.reposition($(el), position, false);
            },
            function(msg){
                error(msg);
                publicMethod.reposition($(el), position, false);
            },
            'get'
        );
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
    
    publicMethod.reposition = function (bar, position, save) {
        bar.removeClass(positions.join(' ') + ' ui-draggable-dragging')
        .removeAttr('style')
        .addClass(position);
        
        // class for padding to avoid hiding content under the toolbar, etc
        var frame = $('#main-content-frame');
        frame
        .removeClass(positions.join(' '))
        .addClass(position);
        
        // save settings to user/session
        if (typeof save == 'undefined') save = true;
        
        if (save) {
            ajax.genericAction('preference_set', {
                    key: 'toolbar_pos',
                    value: position
                },
                function(){},
                function(msg){
                    error(msg);
                }
            );
        }
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
    
    $(function(){
        $('[data-fwtoolbar]').fwtoolbar();
    });
}( jQuery, document, this ));