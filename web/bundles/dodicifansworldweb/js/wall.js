/**
 * Plugin to handle wall blocks
 */
(function( $ ) {
  $.fn.wall = function() {
      var commentsLimit = 10;
      
      return this.each(function() 
      {
          var wallel = $(this);
          var wallid = wallel.attr('data-wall');
          
          if (wallid) {
              if (!wallel.attr('data-wall-loaded')) {
                  wallel.addClass('loading');
                  $('.comment-loading').show();
                  ajax.genericAction('comment_ajaxget', {
                          wall : wallid,
                          limit: commentsLimit,
                          usejson: true
                      },
                      function(r){
                          console.log(r);
                          if(r){
                              $.each(r, function(index, value){
                            	  templateHelper.renderTemplate(value.templateId,value,wallel);
                              });
                              
                              wallel.removeClass('loading');
                              $('.comment-loading').hide();
                              wallel.attr('data-wall-loaded', 1);
                              bindWallUpdate(wallel);
                              $("abbr.timeago").timeago();
                              
                              if (typeof Meteor != 'undefined') {
                                  Meteor.joinChannel('wall_' + wallid);
                              }
                              
                             
                          }
                      },
                      function(){},
                      'get'
                  );
              }
          }
      });
  };
  
  
  $.fn.wallUpdate = function() {
      var commentsLimit = 10;
      window.endlessScrollPaused = true;
      return this.each(function() 
      {
          var wallel = $(this);
          var wallid = wallel.attr('data-wall');
          
          if (wallid) {
             var lastid = wallel.find('[data-comment]').last().attr('data-comment');
             wallel.addClass('loading');
             $('.comment-loading').show();
              
              ajax.genericAction('comment_ajaxget', {
                  wall : wallid,
                  limit: commentsLimit,
                  lastid: lastid,
                  usejson: true
                  },
                  function(r){
                      console.log(r);
                      if(r){
                          $.each(r, function(index, value){
                        	  templateHelper.renderTemplate(value.templateId,value,wallel);
                          });
                          $("abbr.timeago").timeago();
                          wallel.removeClass('loading');
                          $('.comment-loading').hide();
                          wallel.attr('data-wall-loaded', 1);
                          window.endlessScrollPaused = false;
                          
                          if (typeof Meteor != 'undefined') {
                              Meteor.joinChannel('wall_' + wallid);
                          }
                          
                      }
                     
                      
                      
                  },
                  function(){},
                  'get'
              );
          }
      });
  };
  
  
  $.fn.addWallComment = function(id, parent) {
      var wallel = $(this);
      
      if (!(wallel.find('[data-comment="'+id+'"]').length)) {
          if ((typeof parent != "undefined") && parent) {
              var container = wallel.find('[data-subcomments="'+parent+'"]');
          } else {
              var container = wallel;
          }
          
          ajax.genericAction('comment_ajaxget', {
              'id': id
          },
          function(r){
              console.log(r);
              if(r){
                  if (!(wallel.find('[data-comment="'+id+'"]').length)) {
                      container.prepend(r.toString());
                  }
              }
          },
          function(){},
          'get'
          );
      }
  };
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
            wallel.wallUpdate();
            
        }
    });
}

$(function(){
	var toLoad	=	['comment-new_video','comment-new_photo','comment-likes','comment-new_friend','comment-subcomment','comment-comment'];
	templateHelper.preLoadTemplates(toLoad);
});