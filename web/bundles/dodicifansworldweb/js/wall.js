/**
 * Plugin to handle wall blocks
 */
var wallCommentIds = [],
SUBCOMMENTS_TO_SHOW = 5;
     
(function( $ ) {
    $.fn.wall = function() {
        return this.each(function() {
            var wallel = $(this),
            wallid = wallel.attr('data-wall');
            
            if (wallid) {
                if (!wallel.attr('data-wall-loaded')) {
                    //wallel.addClass('loading');
                    $('.comment-loading').show();
                  
                    ajax.genericAction('comment_ajaxget', {
                        wall : wallid,
                        limit: 10,
                        usejson: true
                    },
                    function(r) {
                        callbackAction(r,wallel);
                    },
                    function() {
                        
                        },
                        'get',
                        false);
                }
            }
        });
    };
  
  
    $.fn.wallUpdate = function() {
        window.endlessScrollPaused = true;
        return this.each(function() {
            var wallel = $(this);
            var wallid = wallel.attr('data-wall');
          
            if (wallid) {
                //wallel.addClass('loading');
                $('.comment-loading').show();
              
                ajax.genericAction('comment_ajaxget', {
                    wall : wallid,
                    limit: 10,
                    lastid: wallel.attr('data-last-id'),
                    usejson: true
                },
                function(r) {
                    console.log(r);
                    if(r && r.length > 0) {
                        console.log('entro');
                        var c = 1;
                        $.each(r, function(index, value) {
                            templateHelper.renderTemplate(value.templateId,value,wallel, false, function(){
                                
                                if(c == r.length){
                                    $("abbr.timeago").timeago();
                                    //wallel.removeClass('loading');
                                    $('.comment-loading').hide();
                                    wallel.attr('data-wall-loaded', 1);
                                    window.endlessScrollPaused = false;
                                }
                                c++;
                            });
                            wallel.attr('data-last-id', value.id);
                        });
                          
                        if (typeof Meteor != 'undefined') {
                            Meteor.joinChannel('wall_' + wallid);
                        }
                    }else{
                        $('.comment-loading').hide();
                        window.endlessScrollPaused = true;
                    }
                },
                function() {
                    
                    },
                    'get',
                    false);
            }
        });
    };
  
  
    $.fn.addWallComment = function(id, parent) {
        var wallel = $(this), container;
      
        if (!(wallel.find('[data-comment="'+id+'"]').length)) {
            if ((typeof parent != "undefined") && parent) {
                container = wallel.find('[data-subcomments="'+parent+'"]');
            } else {
                container = wallel;
            }
          
            ajax.genericAction('comment_ajaxget', {
                'id': id
            },
            function(r) {
                if(r) {
                    if (!(wallel.find('[data-comment="'+id+'"]').length)) {
                        container.prepend(r.toString());
                    }
                }
            },
            function() {
                
                },
                'get');
        }
    };
  
    function callbackAction(r, wallel) {
        if(r) {
            var c = 1;
            var wallid = wallel.attr('data-wall');
            
            if(r.length > 0){
                $.each(r, function(index, value) {
                    templateHelper.renderTemplate(value.templateId, value, wallel, false, function() {
                        wallCommentIds.push(value.id);
                    
                        switch(value.templateId) {
                            case 'comment-comment':
                                $('[ajax-delete]').ajaxdelete();
                                break;
                        }
                      
                        $("abbr.timeago").timeago();
                    
                        if(c == r.length) {
                            //wallel.removeClass('loading');
                            $('.comment-loading').hide();
                            wallel.attr('data-wall-loaded', 1);
          
                            if(wallCommentIds.length) {
                                ajax.genericAction('subcomment_ajaxget', {
                                    'wallCommentIds' : wallCommentIds,
                                    'usejson': true
                                },
                                function(r) {
                                    var count = 1;
                                
                                    $.each(r, function(index, value) {
                                        var $target = $('[data-subcomments="' + index + '"]');
                                    
                                        templateHelper.renderTemplate('comment-subcomment', value, $target, false, function() {
                                            if(count == Object.keys(r).length) {
                                                $('[data-subcomments]').each(function(index, value) {
                                                    $target = $(this);
                                                    var $toHide = $target.find('.subcomment-container:lt(' + ($target.find('.subcomment-container').length - SUBCOMMENTS_TO_SHOW) + ')');
                                            
                                                    if($toHide.length) {
                                                        $toHide.hide();
                                                        $('<a class="js-subcomments-open toggleLink">Ver comentarios anteriores</a>').on('click', function() {
                                                            $(this).parent().find('.subcomment-container:hidden').fadeIn('slow');
                                                            $(this).hide();
                                                        }).prependTo($target);
                                                    }
                                                });
                                            }
                                        });
                                    
                                        count++;
                                    });
                                
                                    bindWallUpdate(wallel);
                                });
                            }
                        }
                    
                        c++;
                    });
                
                    wallel.attr('data-last-id', value.id);
                });
            }else{
                $('.comment-loading').hide();
            }
          
          
            if (typeof Meteor != 'undefined') {
                Meteor.joinChannel('wall_' + wallid);
            }
        }
    }
    
})( jQuery );

function bindWallUpdate(wallel){
    $(window).endlessScroll({
        fireOnce: true,
        enableScrollTop: false,
        inflowPixels: 100,
        fireDelay: 250,
        intervalFrequency: 2000,
        ceaseFireOnEmpty: false,
        loader: 'cargando',
        callback: function(i, p, d) {
            if(!window.endlessScrollPaused){
                wallel.wallUpdate();
            }
        }
    });
}

$(function(){
    var toLoad	= ['comment-new_video','comment-new_photo','comment-likes','comment-new_friend','comment-subcomment','comment-comment'];
    templateHelper.preLoadTemplates(toLoad);
});