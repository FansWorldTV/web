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
        $(".btn_friendship.add:not('.loading-small')").live('click', function(e){
            e.stopImmediatePropagation();
            var self = $(this);
            self.addClass('loading-small');

            var targetId = self.attr('data-user-id');
            var friendGroups = [];

            $("ul.friendgroupsList li input:checkbox:checked").each(function(k, el){
                friendgroups[k] = $(el).val();
            });

            /*
            ajax.addFriendAction(targetId, friendgroups, function(response){
                if(!response.error){
                    if (response.active) {
                        //self.removeClass('add').attr('friendshipId', response.friendship).html(response.buttontext);
                        self.after('<span class="label">'+ response.buttontext +'</span>');
                        self.remove();
                    } else {
                        self.removeClass('add').removeClass('btn-success').addClass('remove').attr('friendshipId', response.friendship).html(response.buttontext);
                    }
                    success(response.message);
                }else{
                    error(response.error);
                }
                self.removeClass('loading-small');
            });
            */

        ajax.genericAction('friendship_ajaxaddfriend', {
                'target': targetId,
                'friendgroups': friendGroups
            }, function(response) {
                if(!response.error) {
                    if (response.active) {
                        self.after('<span class="label">'+ response.buttontext +'</span>');
                        self.remove();
                    } else {
                        self.removeClass('add').removeClass('btn-success').addClass('remove').attr('friendshipId', response.friendship).html(response.buttontext);
                    }
                    success(response.message);
                }else{
                    error(response.error);
                }
                self.removeClass('loading-small');
            });

        });
    },

    cancel: function(){
        $(".btn_friendship.remove:not('.loading-small'), [data-remove-friendship]:not('.loading-small')").on('click', function(e){
            e.preventDefault();
            e.stopImmediatePropagation();
            var self = $(this);
            var friendshipId = self.attr('data-friendship-id');
            var userId = self.attr('data-user-id');
            var dontRefresh = self.attr('data-dont-refresh');

            if(typeof(dontRefresh) == 'undefined'){
                dontRefresh = false;
            }else{
                var parentElement = self.attr('data-remove-element');
            }

            console.log("friendshipId: " + friendshipId + " userId: " + userId);
            if(confirm('Seguro deseas dejar de seguir a este usuario?')){
                self.addClass('loading-small');
                $.ajax({url: Routing.generate(appLocale + '_friendship_ajaxcancelfriend'),
                    data: {
                        user: null,
                        friendship: null
                    }
                }).then(function(response){
                    if(!response.error) {
                        if(!dontRefresh){
                            window.location.reload();
                        }else{
                            self.parents(parentElement).remove();
                        }
                    }else{
                        error(response.error);
                        self.removeClass('loading-small');
                    }
                }).fail(function(error){
                    error(error.responseText);
                    self.removeClass('loading-small');
                });
            }
        });
    }
};


$(function(){
    friendship.init();
});