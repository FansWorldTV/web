var redirectColorbox = true;

$(document).ready(function(){
    site.init();
    ajax.init();
});

var site = {
    isClosedNotificationess: true,
    isClosedRequests: true,
    
    init: function(){
        $("#video-search-form").live('submit', function(){
            $(this).addClass('loading');
        });
        
        $("ul.friendgroupsList").show();
        
        $("a.btn_picture").colorbox({
            'iframe': true,
            'innerWidth': 350,
            'innerHeight': 200
        });
        
        $('[title]').tooltip();
        
        $('.change_image').colorbox({
            iframe: true, 
            innerWidth: 700, 
            innerHeight: 220
        });
        
        $('.report').colorbox({
            'iframe': true,
            'innerWidth': 350,
            'innerHeight': 200
        });
        
        $(".editbutton").colorbox({
            iframe: true, 
            innerWidth: 700, 
            innerHeight: 455
        });
        
        
        $(".btn[data-upload='video']").colorbox({
            iframe: false, 
            innerWidth: 700, 
            innerHeight: 455,
            onComplete: function() {
                createUploader();
                resizePopup();
            }
        });
        
        $(".btn[data-upload='photo']").colorbox({
            iframe: false, 
            innerWidth: 700, 
            innerHeight: 175,
            onComplete: function() {
                createUploader();
                resizePopup();
            }
        });
        
        $.datepicker.setDefaults($.datepicker.regional[appLocale]);
        $('.datepicker').datepicker({
            dateFormat: 'dd/mm/yy',
            changeMonth: true,
            changeYear: true,
            yearRange: '-80'
        });
        
        $('.datetimepicker').datetimepicker({
            dateFormat: 'dd/mm/yy',
            timeFormat: 'hh:mm'
        });
    	
        $('.timepicker').datetimepicker({
            timeFormat: 'hhmm',
            timeOnly: true,
            stepMinute: 5
        });
        
        if($('[data-video-now-watching]').length) {
            $('[data-video-now-watching]').videoAudience({
                ajaxUrl: Routing.generate(appLocale + '_teve_getaudience'),
                keepAliveUrl: Routing.generate(appLocale + '_teve_keepalive')
            });
        }
        
        site.parseTimes();
        site.denyFriendRequest();
        site.acceptFriendRequest();
        site.getPendingFriends();
        site.getNotifications();
        site.readedNotification();
        site.likeButtons();
        site.shareButtons();
        site.globalCommentButtons();
        site.globalDeleteButtons();
        site.expander();
        site.showCommentForm();
        searchBox.init();
        site.BindLoginWidget();
        albums.init();
        teamship.init();
        
        $('[data-wall]').wall();
        site.bindCarousel();
        
        $('[data-list]').list();

        // TODO check & fix live update whit removed elements
        //$('[text-height=ellipsis]').ellipsis('',{live: true});
        $('[text-height=ellipsis]').ellipsis();
    },
    
    parseTimes: function(){
        $('.timeago').each(function(){
            $(this).html($.timeago($(this).attr('data-time')));
        });
    },
    
    showCommentForm: function(){
        $(".showCommentForm").live('click',function(){
            $(this).parent().find('div.form').toggleClass('hidden');
            return false;
        });
    },
    
    getNotifications: function(){
        $("li.notifications_user ul li").addClass('hidden');
        $("li.notifications_user a").live('click', function(){
            $("li.notifications_user ul li").toggleClass('hidden');
            if($("li.notifications_user ul li.hidden").size() > 0){
                site.isClosedNotificationess = true;
            }else{
                site.isClosedNotificationess = false;
            }
        });
    },
    
    readedNotification: function(){
        $("li.notifications_user li.notification:not('.readed')").hover(function(){
            var el = $(this).find('div.info');
            var notificationId = el.attr('notificationId');
            ajax.deleteNotification(notificationId, function(response){
                if(response === true){
                    var cant = $("li.notifications_user a span").html();
                    parseInt(cant);
                    cant--;
                    if(cant>0){
                        $(".notifications_user a span").html(cant);
                    }else{
                        $(".notifications_user a span").hide();
                    }
                    
                    el.parent().css('background', '#f4f3b8');
                }else{
                    console.log(response);
                }
            });
        });
    },
    
    getPendingFriends: function(){
        $("li.alerts_user a").click(function(){
            $("li.alerts_user ul li").toggleClass('hidden');
        });
    },
    
    acceptFriendRequest: function(){
        $("div.button a.accept").click(function(){
            var liElement = $(this).parent().parent();
            var friendshipId = $(this).attr('id');
            ajax.acceptRequestAction(friendshipId, function(response){
                if(response.error == false){
                    liElement.remove();
                    var cant = $("li.alerts_user a span").html();
                    parseInt(cant);
                    cant--;
                    $("li.alerts_user a span").html(cant);
                    if(cant<=0){
                        $("li.alerts_user a span").parent().addClass('hidden');
                    }
                    success('El usuario ha sido agregado como amigo');
                }else{
                    error('Se ha producido un error');
                }
            });
        });
        $("div.listElement a.accept").click(function(){
            var friendshipId = $(this).attr('id');
            var liElement = $(this).closest('div.listElement');
            ajax.acceptRequestAction(friendshipId, function(response){
                if(response.error == false){
                    liElement.remove();
                    var cant = $("li.alerts_user a span").html();
                    parseInt(cant);
                    cant--;
                    $("li.alerts_user a span").html(cant);
                    if(cant<=0){
                        $("li.alerts_user a span").parent().addClass('hidden');
                    }
                    success('El usuario ha sido agregado como amigo');
                }else{
                    error('Se ha producido un error');
                }
            });
        });
    },
    denyFriendRequest: function(){
        $("div.listElement a.deny").click(function(){
            var friendshipId = $(this).attr('id');
            var liElement = $(this).closest('div.listElement');
            ajax.denyRequestAction(friendshipId, function(response){
                if(response.error == false){
                    liElement.remove();
                    var cant = $("li.alerts_user a span").html();
                    parseInt(cant);
                    cant--;
                    $("li.alerts_user a span").html(cant);
                    if(cant<=0){
                        $("li.alerts_user a span").parent().addClass('hidden');
                    }
                    success('Se ha rechazado la amistad');
                }else{
                    error('Se ha producido un error');
                }
            });
            return false;
        });
        $("span.info a.deny").click(function(){
            var friendshipId = $(this).attr('id');
            var liElement = $(this).closest('li.clearfix');
            ajax.denyRequestAction(friendshipId, function(response){
                if(response.error == false){
                    liElement.remove();
                    var cant = $("li.alerts_user a span").html();
                    parseInt(cant);
                    cant--;
                    $("li.alerts_user a span").html(cant);
                    if(cant<=0){
                        $("li.alerts_user a span").parent().addClass('hidden');
                    }
                    success('Se ha rechazado la amistad');
                }else{
                    error('Se ha producido un error');
                }
            });
            return false;
        }); 
    },
    likeButtons: function(){
        $('.likebutton:not(.loading-small)').live('click',function(e){
            e.preventDefault();
            var el = $(this);
            var type = el.attr('data-type');
            var id = el.attr('data-id');
            el.addClass('loading-small');
        	
            ajax.likeToggleAction(type, id,
                function(response){
                    el.removeClass('loading-small');
                    el.find('.likecount').text(response.likecount);
                    //el.siblings('.likecount:first').text(response.likecount);
                    success(response.message);
                    if(response.liked){
                        el.find('i').attr('class', '');
                        el.find('i').addClass('icon-star');
                    }else{
                        el.find('i').attr('class', '');
                        el.find('i').addClass('icon-star-empty');
                    }
                },
                function(responsetext){
                    el.removeClass('loading-small');
                    error(responsetext);
                });
        	
        });
    },
    shareButtons: function(){
        $('.sharebutton:not(.loading-small,.disabled)').live('click',function(e){
            e.preventDefault();
            var el = $(this);
            
            askText(function(text){
                var type = el.attr('data-type');
                var id = el.attr('data-id');
                el.addClass('loading-small');
            	
                ajax.shareAction(type, id, text,
                    function(response){
                        el.removeClass('loading-small');
                        el.addClass('disabled');
                        success(response.message);
                    },
                    function(responsetext){
                        el.removeClass('loading-small');
                        error(responsetext);
                    });
            });
            
        });
    },
    globalCommentButtons: function(){
        $('.comment_button:not(.loading)').live('click', function(e) {
            e.preventDefault();
            var el = $(this),
                 type = el.attr('data-type'),
                 id = el.attr('data-id'),
                 ispin = (el.attr('data-pin') == 'true'),
                 content = el.parents('.commentform').find('.comment_message').val();
                 privacy = textAreaElement.parents('.commentform,.shortcommentform').find('.post_privacidad').val() || 1,
                 textAreaElement = el.closest('.commentform').find('textarea.comment_message'),
                 elDestination = '[data-wall]';
                 
            if(!textAreaElement.attr('is-subcomment')) {
                elDestination = textAreaElement.parents('.comments,.comments-and-tags').find('.comments-container');
            }
                
            site.postComment(textAreaElement,type,id,ispin,content,privacy,elDestination);
            return false;
        });
    	
        $('textarea.comment_message:not(.loading)').live('keydown', function (e) {
            if (e.keyCode == 13) {
                var textAreaElement = $(this),
                     type = textAreaElement.attr('data-type'),
                     id = textAreaElement.attr('data-id'),
                     ispin = (textAreaElement.attr('data-pin') == 'true'),
                     content = textAreaElement.val(),
                     privacy = textAreaElement.parents('.commentform,.shortcommentform').find('.post_privacidad').val() || 1,
                     elDestination = textAreaElement.closest('.comments,.comments-and-tags').find('.subcomment-container');
                     
                if(!textAreaElement.attr('is-subcomment')) {
                    elDestination = textAreaElement.parents('.comments,.comments-and-tags').find('.comments-container');
                }
                
                site.postComment(textAreaElement, type, id, ispin, content, privacy, elDestination);
                return false;
            }
        });
    },
    
    postComment: function (textAreaElement,type,id,ispin,content,privacy,elDestination) {
        textAreaElement.addClass('loadingSmall');
        textAreaElement.attr('disabled','disabled');
    	
        ajax.globalCommentAction(type, id, content, privacy, ispin,
            function (response) {
                textAreaElement.val('');
                textAreaElement.removeClass('loadingSmall');
                success(response.message);
                
                templateHelper.renderTemplate(response.jsonComment.templateId,response.jsonComment,elDestination, false);
                
                $('.timeago').each(function () {
                    $(this).html($.timeago($(this).attr('data-time')));
                });
                
                if (ispin) {
                    $('.masonbricks').isotope().resize();
                }
                site.expander();
                textAreaElement.removeAttr('disabled');
            },
            function (responsetext) {
                textAreaElement.removeClass('loadingSmall');
                textAreaElement.removeAttr('disabled');
                error(responsetext);
            });
    },
    
    globalDeleteButtons: function(){
        $('.deletebutton:not(.loading)').live('click',function(e){
            e.preventDefault();
            
            var el = $(this);
            if (confirm('¿Seguro desea eliminar?')) {
                var type = el.attr('data-type');
                var id = el.attr('data-id');
                el.addClass('loading');
	        	
                ajax.globalDeleteAction(type, id,
                    function(response){
                        el.removeClass('loading');
                        success(response.message);
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        }
                    },
                    function(responsetext){
                        el.removeClass('loading');
                        error(responsetext);
                    });
            }
        });
        
        $('.deletecomment:not(.loading)').live('click',function(e){
            e.preventDefault();
            
            var el = $(this);
            if (confirm('¿Seguro desea eliminar el comentario?')) {
                var type = 'comment';
                var id = el.attr('data-id');
                el.addClass('loading');
	        	
                ajax.globalDeleteAction(type, id,
                    function(response){
                        success(response.message);
                        el.parent().slideUp('fast',function(){
                            $(this).remove();
                        });
                    },
                    function(responsetext){
                        el.removeClass('loading');
                        error(responsetext);
                    });
            }
        });
    },
    
    expander: function() {
        $("[data-expandable]").each(function(index, element){
            var slicePoint = 100;
            if($(this).attr('data-slice-point') !== 'undefined'){
                slicePoint = $(this).attr('data-slice-point');
            }
            $(this).expander({
                slicePoint: slicePoint,
                expandText: 'más',
                userCollapseText: 'ocultar'
            });
        });
    },
    
    BindLoginWidget: function(){
        //fix triangle for firefox
        if($.browser.mozilla == true){
            $('header nav div#login-widget div.arrow-up-border').hide();
        }
        $('header .header-ingresar').click(function(){
            $('header div#login-widget').toggle(); 
        });
        $('div#login-widget #do-login').click(function(){
            $('form.login').submit();
        });
         
    },
    
    bindCarousel: function(){
        $("div.info-detail-carousel div:not('.active')").hide();
        
        $('.carousel').carousel({
            interval: 5000
        }).bind('slid', function() {
            // Get currently selected item
            var item = $('#myCarousel .carousel-inner .item.active');
            
            var itemId = $(item).attr('data-video');
            $('div.info-detail-carousel div.active').fadeOut(function(){
                $(this).removeClass('active');
                $('div.info-detail-carousel div[data-video='+itemId+']').fadeIn().addClass('active');
            });

            // Deactivate all nav links
            $('#carousel-nav a').removeClass('active');

            // Index is 1-based, use this to activate the nav link based on slide
            var index = item.index() + 1;
            $('#carousel-nav a:nth-child(' + index + ')').addClass('active');
        });
        
        $('#carousel-nav a').click(function(q){
            q.preventDefault();
            targetSlide = $(this).attr('data-to')-1;
            $('#myCarousel').carousel(targetSlide);
        });
            
    },
    
    startMosaic: function(container, options){
        // initialize the plugin
        var $container 	= container,
        $imgs		= $container.find('img').hide(),
        totalImgs   = $imgs.length,
        cnt			= 0;

        $imgs.each(function(i) {
            var $img	= $(this);
            $('<img/>').load(function() {
                ++cnt;
                if( cnt === totalImgs ) {
                    $imgs.show();
                    $container.montage(options);
                }
            }).attr('src',$img.attr('src'));
        });
    }
}

function resizeColorbox(options) {
    $.colorbox.resize(options);
}

function askText(callback) {
    $.colorbox({
        href: Routing.generate(appLocale + '_popup_asktext'),
        onComplete: function(){ 
            $('#colorbox').find('.submit').click(function(){
                var content = $('#colorbox').find('textarea').val();
                $.colorbox.close();
                callback(content);
            }); 
        }
    });
}

function removeArrayElement(array, element){
    var idx = array.indexOf(element); // Find the index
    console.log(idx);
    if(idx!=-1) array.splice(idx, 1); // Remove it if really found!

    return array;
}