var forum = {
    page: 2,
    postPage: 2,
    
    init: function(){
        forum.addMoreThreads();
        forum.addMorePosts();
        forum.commentThread();
    },
    addMoreThreads: function(){
        $("#addMore.threads").live('click', function(){
            var userId = $("#userId").val() == "" ? false : $("#userId").val();
            var self = $(this);
            self.addClass('loading');
            ajax.searchForumThreads(forum.page, userId, function(response){
                if(response){
                    if(!response.addMore){
                        self.remove();
                    }
                    for(var i in response.threads){
                        var thread = response.threads[i];
                        var template = $("#templates.threads li").clone();
                        var href = Routing.generate(appLocale + '_forum_thread', {
                            'id': thread.id, 
                            'slug': thread.slug
                        });
                        template.find('a').attr('href', href);
                        template.find('a div.messages').prepend(thread.postCount + " ");
                        template.find('a div.post h3').html(thread.title);
                        template.find('a div.post span.contentTruncated').html(thread.content);
                        template.find('a div.post span.user').html(thread.author.name);
                        template.find('a div.post span.date').attr('data-time', thread.createdAt).html(thread.createdAt);
                        
                        $("ul.foro").append(template);
                    }
                    site.parseTimes();
                    forum.page++;
                }
                self.removeClass('loading');
            });
            return false;
        });
    },
    addMorePosts: function(){
        $("#addMore.posts").live('click', function(){
            var threadId = $("#threadId").val();
            var self = $(this);
            self.addClass('loading');
           
            ajax.searchThreadPosts(threadId, forum.postPage, function(response){
                if(response){
                    if(!response.addMore){
                        self.remove();
                    }
                    for(var i in response.posts){
                        var post = response.posts[i];
                        var template = $("#templates div.comment").clone();
                        var href = Routing.generate(appLocale + '_user_land', {
                            'username': post.author.username
                        });
                       
                        template.find('a').attr('href', href);
                        template.find('div.avatar a img').attr('src', post.author.image);
                        template.find('div.user_comment a').html(post.author.name);
                        template.find('div.user_comment span.timeago').attr('data-time',post.createdAt).html(post.createdAt);
                        template.find('div.user_comment').append(post.content);
                       
                        $("div.comments").append(template);
                    }
                   
                    site.parseTimes();
                    forum.postPage++;
                }
            });
           
        });
    },
    commentThread: function(){
        $("div.add_comment a.btn.comment").live('click', function(){
            var self = $(this);
            var text = self.parent().find('textarea').val();
            var threadId = $("#threadId").val();
            
            self.addClass('loading');
            
            if($("div.comments").has('div.comment').length == 0){
                $("div.comments").html('');
            }
            
            ajax.threadCommentAction(threadId, text, function(response){
                if(!response.error){
                    var template = $("#templates div.comment").clone();
                    var href = Routing.generate(appLocale + '_user_land', {
                        'username': response.data.author.username
                    });
                       
                    template.find('a').attr('href', href);
                    template.find('div.avatar a img').attr('src', response.data.author.avatar);
                    template.find('div.user_comment a').html(response.data.author.name);
                    template.find('div.user_comment span.timeago').attr('data-time',response.data.createdAt).html(response.data.createdAt);
                    template.find('div.user_comment').append(response.data.content);
                       
                    $("div.comments").prepend(template);
                    site.parseTimes();
                    self.removeClass('loading');
                    self.parent().find('textarea').val('');
                }
            });
        });
    }
    
};