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
                                 wallel.append(this.toString());
                              });
                              if (typeof Meteor != 'undefined') {
                                  Meteor.joinChannel('wall_' + wallid);
                              }
                              wallel.removeClass('loading');
                              wallel.attr('data-wall-loaded', 1);
                              bindWallUpdate(wallel);
                              $("abbr.timeago").timeago();
                             
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
              
              ajax.genericAction('comment_ajaxget', {
                  wall : wallid,
                  limit: commentsLimit,
                  lastid: lastid
                  },
                  function(r){
                      console.log(r);
                      if(r){
                          $.each(r, function(){
                             wallel.append(this.toString());
                          });
                          $("abbr.timeago").timeago();
                          if (typeof Meteor != 'undefined') {
                              Meteor.joinChannel('wall_' + wallid);
                          }
                          wallel.removeClass('loading');
                          wallel.attr('data-wall-loaded', 1);
                      }
                      window.endlessScrollPaused = false;
                      
                      
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
        callback: function(i, p, d) {
            wallel.wallUpdate();
            
        }
    });
}