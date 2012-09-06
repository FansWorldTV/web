(function ($) {

    "use strict";

    var AjaxDelete = function ($button, options) {

        // initialization and settings
        var settings = $.extend({
            'preloaderImage': '/bundles/dodicifansworldweb/images/ajax-loader-small.gif'
        }, options || {}),
        
        preloader = {
            'show': function () {
                preloader.hide();
                $parentNode.prepend($('<img src="' + settings.preloaderImage + '" class="ajaxdelete-preloader" />'));
            },
            'hide': function () {
                $('.ajaxdelete-preloader').remove();
            }
        },
        
        $parentNode,
        confirmText = 'Esta seguro?',
        entityType = $button.attr('data-entity-type') || null,
        entityId = $button.attr('data-entity-id') || null;

        function init() {
            if(!entityType || !entityId) {
                $.removeData($button, "ajaxdelete");
                return false;
            }
            
            switch(entityType) {
                case 'comment':
                    confirmText = 'Esta seguro de querer eliminar este comentario?';
                    $parentNode = $('[data-comment="' + entityId + '"]')
                    break;
            }
            
            $button.on('click', function() {
                if(!confirm(confirmText)) {
                    return false;
                }
                
                request(function(deleted) {
                    if(!deleted) {
                        return alert('Hubo un problema inesperado, por favor recargue la página y vuelva a intentarlo.');
                    }
                    
                    domRemove();
                });
            });
        }
        
        function request(callback) {
            var params = {
                'id': entityId,
                'type': entityType
            };
            
            preloader.show();
            ajax.genericAction('delete_ajax', params,
                function(response) {
                    preloader.hide();
                    callback(response.deleted);
                });
        }
        
        function domRemove() {
            $parentNode.fadeOut('slow', function() {
                $parentNode.remove();
            });
        }

        
        init();

    };

    // plugin creation
    $.fn.ajaxdelete = function (options) {
        $(this).each(function () {
            var $button = $(this);

            if ($button.data('ajaxdelete')) {
                return;
            }
            $button.data('ajaxdelete', new AjaxDelete($button, options));
        });

    };

}(jQuery));
