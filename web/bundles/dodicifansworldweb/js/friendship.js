var friendship = {
    init: function(){
        
        if ($("ul.friendgroupsList li input:checkbox").length) {
            $("ul.friendgroupsList").hide();
            $("div.addFriend").hover(
                function(){
                    if ($(this).has('.btn_friendship.add')) {
                        friendGroupList = $("ul.friendgroupsList").slideDown('normal');
                    }
                },
                function(){
                    if ($(this).has('.btn_friendship.add')) {
                        friendGroupList = $("ul.friendgroupsList").slideUp('normal');
                    }
                }
                );
        }
            
        friendship.add();
        friendship.cancel();
    },
    
    add: function(){
        $(".btn_friendship.add:not('.loading')").live('click', function(){
            var self = $(this);
            self.addClass('loading');
            
            var targetId = $(this).closest('div.addFriend').attr('targetId');
            var friendgroups = [];
            
            $("ul.friendgroupsList li input:checkbox:checked").each(function(k, el){
                friendgroups[k] = $(el).val();
            });
            
            ajax.addFriendAction(targetId, friendgroups, function(response){
                if(!response.error){
                    if (response.active) {
                        self.removeClass('add').removeClass('btn-success').addClass('remove').attr('friendshipId', response.friendship).text('Dejar de Seguir');
                    } else {
                        self.removeClass('add').removeClass('btn-success').addClass('remove').attr('friendshipId', response.friendship).text('Cancelar Solicitud');
                    }
                    success(response.message);
                }else{
                    error(response.error);
                }
                self.removeClass('loading');
            });
        });
    },
    
    cancel: function(){
        $(".btn_friendship.remove:not('.loading')").live('click', function(){
            var self = $(this);
            var friendshipId = $(this).attr('friendshipId');
            if(confirm('Seguro deseas dejar de seguir a este fan?')){
                self.addClass('loading');
                ajax.cancelFriendAction(friendshipId, function(response){
                    if(!response.error) {
                        window.location.reload();  
                    }else{
                        error(response.error);
                        self.removeClass('loading');
                    }
                });
            }
        });
    }
};