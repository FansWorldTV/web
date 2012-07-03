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
                  
                  ajax.genericAction('comment_ajaxget', {
                      wall : wallid,
                      limit: commentsLimit
                  },
                  function(r){
                      console.log(r);
                      if(r){
                          $.each(r, function(){
                             wallel.prepend(this.toString());
                          });
                      }
                  }
                  );
                  
                  
                  if (typeof Meteor != 'undefined') {
                      Meteor.joinChannel('wall_' + wallid);
                  }
                  
                  wallel.removeClass('loading');
                  wallel.attr('data-wall-loaded', 1);
              }
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
          }
          );
      }
  };
})( jQuery );