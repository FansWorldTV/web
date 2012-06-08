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
        site.listenPendingRequests();
        site.listenNotifications();
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
    
    listenPendingRequests: function(){
        function handleData(response){
            console.log("entro algo");
            console.log(response);
            if(response){
                var actualNumber = $("li.alerts_user a span").html();
                parseInt(actualNumber);
                if(actualNumber == ''){
                    actualNumber = 0;
                }
                
                
                ajax.genericAction('user_ajaxgetfriendship', {
                    'id': response
                }, function(r){
                    console.log(r);
                    if(r){
                        if(!r.target.restricted){
                            notice(r.author.name + " te empezó a seguir.");
                        }else{
                            var template = $("#templatePendingFriends ul li").clone();
                            if(r.author.image){
                                template.find("img.avatar").attr('src', r.author.image);
                            }
                            template.find("span.info a.name").attr('href', r.author.url).html(r.author.name);
                            template.find("a.deny").attr('id', r.author.id);
                            template.find("div.button a.accept").attr('id', r.author.id);
                            template.addClass('hidden');
                      
                            $("li.alerts_user ul li.more").before(template);
                        }
                        actualNumber++;
                        
                        $("li.alerts_user a span").html(actualNumber).removeClass('hidden');
                        $(".alerts_user a:first span").effect("highlight",{},3000);
                        $(".alerts_user a:first").effect("bounce",{
                            times:1
                        },300);
                    }
                });
            }
        }
            
        if ((typeof Meteor != 'undefined') && (typeof notificationChannel != 'undefined')) {
            Meteor.registerEventCallback("process", handleData);
            Meteor.joinChannel(notificationChannel, 0);
            Meteor.mode = 'stream';
	            
            // Start streaming!
            Meteor.connect();
            console.log('Escuchando requests...');
        }
    },
    
    listenNotifications: function(){
        function handleData(response){
            console.log("aca hay algo");
            console.log(response);
            if(response){
                var actualNumber = $("li.notifications_user a span").html();
                var number = 0;
                if(actualNumber == ''){
                    number = 0;
                }else{
                    number = parseInt(actualNumber);
                }
                number++;
                $("li.notifications_user a span").html(number).show().removeClass('hidden');
                $(".notifications_user a:first span").effect("highlight",{},3000);
                $(".notifications_user a:first").effect("bounce",{
                    times:1
                },300);
                
                site.getNotification(response);
            }
        }
            
    //        if ((typeof Meteor != 'undefined') && (typeof notificationChannel != 'undefined')) {
    //            Meteor.registerEventCallback("process", handleData);
    //            Meteor.joinChannel(notificationChannel, 0);
    //            Meteor.mode = 'stream';
    //	            
    //            // Start streaming!
    //            Meteor.connect();
    //            console.log('Escuchando notificaciones...');
    //        }
    },
    
    getNotification: function(id){
        var classes = 'clearfix notification loading';
        if(site.isClosedNotificationess){
            classes += ' hidden';
        }
        $("li.notifications_user ul").prepend("<li id='notification_" + id +"' class='" + classes + "'></li>");
        
        ajax.getNotification(id, function(response){
            if(response){
                var countNotifications = $("li.notifications_user ul li div.info").size();
                if(countNotifications>4){
                    $("li.notifications_user ul li div.info").last().parent().remove();
                }
                $("#notification_" + id).html(response).removeClass('loading');
                
                if($("#notificationsList").size()>0){
                    $("#notificationsList").prepend(response);
                }
                
                if(site.isClosedNotificationess){
                    $("#notification_" + id).addClass('hidden');
                }
            }
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