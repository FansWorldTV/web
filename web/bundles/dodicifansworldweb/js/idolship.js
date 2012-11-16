var idolship = {
    init: function(){
        idolship.add();
    },
    
    add: function(){
        $(".btn_idolship.add:not('.loading-small')").on('click', function(e){
            var self = $(this);
            self.addClass('loading-small');
            
            var idolId = self.attr('data-idol-id');
            
            ajax.genericAction('idolship_ajaxtoggle', {
                'idol-id': idolId
            }, function(r){
                if(r){
                    if(r.isFan){
//                        self.addClass('disabled');
//                        self.removeClass('add');
//                        success(r.message);
//                        self.html(r.buttontext);
                        var parent = self.parent();
                        self.remove();
                        parent.html('<span class="label">YA ERES FAN</span>');
                    }
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
    idolship.init();
});