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
        $('input:checkbox.prettycheckbox').checkbox({
            empty: emptyCheckboxImg
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
        
        $(".btn_upload_video").colorbox({
            iframe: false, 
            innerWidth: 700, 
            innerHeight: 455,
            onComplete: function() {
                createUploader();
                resizePopup();
            }
        });
        
        $(".btn_upload_photo").colorbox({
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
        
        $('input[data-default-text]').each(function(){
            $(this).val($(this).attr('data-default-text'))
            .addClass('graytext')
            .focus(
                function(e){
                    if ($(this).val() == $(this).attr('data-default-text')) {
                        $(this).val('');
                        $(this).removeClass('graytext');
                    }
                }
                )
            .blur(
                function(e){
                    if ($(this).val() == '') {
                        $(this).addClass('graytext');
                        $(this).val($(this).attr('data-default-text'));
                    }
                }
                );
        });
    	
        site.parseTimes();
        site.denyFriendRequest();
        site.acceptFriendRequest();
        site.getPendingFriends();
        site.getNotifications();
        site.readedNotification()
        site.likeButtons();
        site.shareButtons();
        site.globalCommentButtons();
        site.globalDeleteButtons();
        site.expander();
        site.showCommentForm();
        searchBox.init();
        site.BindLoginWidget();
        
        $('[data-wall]').wall();
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
        $('.likebutton:not(.loading)').live('click',function(e){
            e.preventDefault();
            var el = $(this);
            var type = el.attr('data-type');
            var id = el.attr('data-id');
            el.addClass('loading');
        	
            ajax.likeToggleAction(type, id,
                function(response){
                    el.removeClass('loading');
                    el.text(response.buttontext);
                    el.siblings('.likecount:first').text(response.likecount);
                    success(response.message);
                },
                function(responsetext){
                    el.removeClass('loading');
                    error(responsetext);
                });
        	
        });
    },
    shareButtons: function(){
        $('.sharebutton:not(.loading,.disabled)').live('click',function(e){
            e.preventDefault();
            var el = $(this);
            
            askText(function(text){
                var type = el.attr('data-type');
                var id = el.attr('data-id');
                el.addClass('loading');
            	
                ajax.shareAction(type, id, text,
                    function(response){
                        el.removeClass('loading');
                        el.addClass('disabled');
                        success(response.message);
                    },
                    function(responsetext){
                        el.removeClass('loading');
                        error(responsetext);
                    });
            });
            
        });
    },
    globalCommentButtons: function(){
        $('.comment_button:not(.loading)').live('click',function(e){
            e.preventDefault();
            var el = $(this);
            var type = el.attr('data-type');
            var id = el.attr('data-id');
            var ispin = (el.attr('data-pin') == 'true');
            var content = el.parents('.commentform').find('.comment_message').val();
            var privacy = el.parents('.commentform').find('.post_privacidad').val();
            el.addClass('loading');
        	
            ajax.globalCommentAction(type, id, content, privacy, ispin,
                function(response){
                    el.closest('.commentform').find('textarea.comment_message').val('');
                    el.removeClass('loading');
                    success(response.message);
                    el.parents('.commentform').after(response.commenthtml);
                    $('.timeago').each(function(){
                        $(this).html($.timeago($(this).attr('data-time')));
                    });
                    if (ispin) {
                        $('.masonbricks').isotope().resize();
                    }
                    site.expander();
                },
                function(responsetext){
                    el.removeClass('loading');
                    error(responsetext);
                });
        	
        });
    	
        $('textarea.comment_message').live('keydown', function (e) {
            if ( e.keyCode == 13 ){
                $(this).closest('.commentform').find('.comment_button').click();
                return false;
            }
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
        $('.actualizacion .comments .status_comment .status_comment_user p, ' +
            '.actualizacion .status p, ' +
            '.pincomments .comment .message')
        .not(':has(.read-more)')
        .expander({
            slicePoint: 100,
            expandText: '[+]',
            userCollapseText: '[-]'
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
        })
         
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