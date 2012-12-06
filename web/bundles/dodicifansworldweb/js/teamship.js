var teamship = {
    init: function(){
        teamship.add();
        teamship.remove();
    },
    
    add: function(){
        $(".btn_teamship.add:not('.loading-small')").on('click', function(e){
            var self = $(this);
            self.addClass('loading-small');
            
            var teamId = self.attr('data-team-id');
            
            ajax.genericAction('teamship_ajaxtoggle', {
                'team': teamId
            }, function(r){
                if(r){
                    if(r.isFan){
                        //self.addClass('disabled');
                        self.removeClass('add');
                        success(r.message);
                        self.html(r.buttontext);
                    }
                }
                
                self.removeClass('loading-small');
            }, function(e){
                error(e);
                self.removeClass('loading-small');
            });
        });
    },
    
    remove: function(){
        $(".btn_teamship.remove:not('.loading-small')").on('click', function(e){
            e.preventDefault();
            var self = $(this);
            self.addClass('loading-small');
            
            var teamId = self.attr('data-team-id');
            
            ajax.genericAction('teamship_ajaxtoggle', {
                'team': teamId
            }, function(r){
                if(r){
                    self.parents('.entityBlock').remove();
                }
                
                self.removeClass('loading-small');
            }, function(e){
                error(e);
                self.removeClass('loading-small');
            });
        });
    }
};

$(function(){
    teamship.init();
});