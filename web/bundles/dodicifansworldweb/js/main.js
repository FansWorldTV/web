var redirectColorbox = true;

/* Player callToActions */
function jsCallbackReady(widgetId) {
    window.player = document.getElementById(widgetId);
    player.addJsListener("playerPlayEnd", "playerFinalAction");
}
function playerFinalAction(id) {
    var loading = $('[data-ctaloading]');
    var videoContainer = $('#' + id).parents('[data-videocontainer]');
    var videoContainerParent = videoContainer.parents('[data-videocontainer-parent]');
    var videoId = videoContainer.attr('data-videoid');
    loading.show();
    videoContainer.hide();
    ajax.genericAction({
        route: 'video_ajaxPlayerFinalAction',
        params: {
            id: videoId,
            idVideoDom: id
        },
        callback: function(response) {
            if (response) {
                console.log(response);
                videoContainerParent.append(response.view);
                loading.hide();
                $('[data-viewagain=' + id + ']').click(function() {
                    $('[data-finalAction-detail=' + id + ']').remove();
                    videoContainer.show();
                    if ($("[data-viewagain='youtubePlayer']")) {
                        playerYoutube.playVideo();
                    }
                });
            }
        }
    });
}
/* End Player callToActions */

$(document).ready(function() {
    site.init();
    //ajax.init();
});

var site = {
    isClosedNotificationess: true,
    isClosedRequests: true,
    init: function() {
        $("form[data-search-box]").submit(function() {
            $(this).find('i.icon-search').removeClass('icon-search').addClass('loading-small');
        });

        $("#video-search-form").on('submit', function() {
            $(this).addClass('loading');
        });

        $("ul.friendgroupsList").show();

        $("a.btn_picture").colorbox({
            'iframe': true,
            'innerWidth': 350,
            'innerHeight': 200
        });

        $('body').tooltip({
            selector: '[rel="tooltip"]'
        });

        $('.change_image').colorbox({
            iframe: true,
            innerWidth: 700,
            innerHeight: 220
        });

        if (isLoggedIn) {
            /*
            $('.report').colorbox({
                href: this.href,
                innerWidth: 350,
                innerHeight: 250,
                onComplete: function() {
                    //resizePopup();
                }
            });
            */
            $('.report').fwModalDialog({
                modal: {
                    backdrop: false, 
                    width: 290
                }
            });
        }
        else {
            $('.report').on('click', function (e) {
                $(this).off();
                $(this).removeAttr('href');
                e.stopImmediatePropagation();
                //e.preventDefault();
                //e.stopPropagation();
                $('[data-login-btn]').click();

                //return false;
            });
        }

        $(".editbutton").colorbox({
            iframe: true,
            innerWidth: 700,
            innerHeight: 455
        });

        /*
         $(".btn[data-upload='video']").colorbox({
         iframe: false,
         innerWidth: 700,
         innerHeight: 455,
         onComplete: function() {
         createUploader();
         resizePopup();
         }
         });*/
        /*
         $("[data-upload='photo']").colorbox({
         innerWidth: 700,
         innerHeight: 175,
         onComplete: function() {
         createPhotoUploader();
         resizePopup();
         }
         });
         */

        // Invite modal popup
        $("[data-invite-modal]").modalPopup({
            'href': Routing.generate(appLocale + '_modal_invite', {}),
            'width': 1000,
        });


        // fw upload plugin
        // Video
        $("[data-upload='video']").fwUploader({
            onComplete: function() {
                $('#share_it').removeAttr("disabled");
            },
            onError: function(err) {
                $.fn.colorbox.close();
                error("Ha ocurrido un error!");
            }
        });
        // Photos
        $("[data-upload='photo']").fwUploader({});

        $(".btn[data-create-album]").colorbox({
            iframe: true,
            innerWidth: 300,
            innerHeight: 600
        });

        // Edit Photo-info
        $("[data-edit='photo']").fwModalDialog({
            modal: {
                'deleteButton': true
            }
        });

        /*
        $('body').on('click', "[data-edit='video']", function(event){
            event.preventDefault();
            console.log(event)

            $('#modal').fadeOut(function() {
                $('#modal-content').html('');
                $(document).unbind('keydown');
                $("[data-edit='video']").click(event);
            });            
        });
        */
        // Edit Video-info
        $("[data-edit='video']").fwModalDialog({
            modal: {
                'deleteButton': true
            }
        });

        // Edit Album-info
        $("[data-edit='album']").fwModalDialog();

        // Invite friends
        $('[data-invite]').colorbox({
            href: $('[data-invite]').data('invite'),
            innerWidth: 450,
            innerHeight: 450,
            onComplete: function() {
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

        if ($('[data-video-now-watching]').length) {
            $('[data-video-now-watching]').videoAudience({
                ajaxUrl: Routing.generate(appLocale + '_teve_getaudience'),
                keepAliveUrl: Routing.generate(appLocale + '_teve_keepalive')
            });
        }

        $('[data-subscribe-channel]').click(function () {
            TV.prototype.subscribe($(this));
        });

        //$('[data-modal-url]').modalPopup();

        $("body").delegate("a[data-modal-url]", "click", function(e) {
            e.preventDefault();
            $(this).modalPopup({
                'duration': 300
            });
            $(this).trigger('click')
        });

        $("body").delegate("a[data-modal-paramid]", "click", function(e) {
            e.preventDefault();
            urlModal = Routing.generate(appLocale + '_' + $(this).attr('data-modal-route'),
                {'type': $(this).attr('data-modal-type'), 'id': $(this).attr('data-modal-paramid')});
            $(this).modalPopup({'href': urlModal});
        });

        var typeaheadTemplate = '<a href="$url"><div class="image-container"><img width="32px" height="32px" class="image" src="$image"/></div><span class="name">$value</span></a>';
        var typeaheadTemplate2 = '<a href="$url" class="search-history-term"><p>$value</span></p></a>';

        var searchHistoryCount = 0;

        $('.navbar-search input').typeahead({
            minLength: 3
            , remote: Routing.generate(appLocale + '_search_ajaxsearch_autocomplete') + '?q=%QUERY'
            , template: typeaheadTemplate
            , template2: typeaheadTemplate2
            , engine: {
                compile: function(template) {
                  return {
                    render: function(ctx) {
                      if(ctx.type == 'search_history') {
                          searchHistoryCount++;
                          return typeaheadTemplate2.replace(/\$(\w+)/g, function(msg) {
                            return ctx[msg.substring(1)];
                          });
                      }
                      else {
                          return template.replace(/\$(\w+)/g, function(msg) {
                            return ctx[msg.substring(1)];
                          });
                      }
                    }
                  };
                }
            }
        });

        $('.twitter-typeahead .tt-query').keyup(function(e) {
          if ( e.which == 13 ) {
             e.preventDefault();
           }
           $('.tt-dropdown-menu .search-button').text('Buscar "' + $(this).val() + '"');
        });


        site.parseTimes();
        site.denyFriendRequest();
        site.acceptFriendRequest();
        site.getPendingFriends();
        site.getNotifications();
        site.readedNotification();
        site.likeButtons();
        site.shareButtons();
        site.watchLaterButtons();
        site.globalCommentButtons();
        site.globalDeleteButtons();
        site.expander();
        site.showCommentForm();
        site.BindLoginWidget();
        albums.init();

        $('[data-wall]').wall();
        site.bindCarousel();

        // TODO check & fix live update whit removed elements
        //$('[text-height=ellipsis]').ellipsis('',{live: true});
        $('[text-height=ellipsis]').ellipsis();
    },
    parseTimes: function() {
        $('.timeago').each(function() {
            $(this).html($.timeago($(this).attr('data-time')));
        });
    },
    showCommentForm: function() {
        $(".showCommentForm").on('click', function() {
            $(this).parent().find('div.form').toggleClass('hidden');
            return false;
        });
    },
    getNotifications: function() {
        $("li.notifications_user ul li").addClass('hidden');
        $("li.notifications_user a").on('click', function() {
            $("li.notifications_user ul li").toggleClass('hidden');
            if ($("li.notifications_user ul li.hidden").size() > 0) {
                site.isClosedNotificationess = true;
            } else {
                site.isClosedNotificationess = false;
            }
        });
    },
    readedNotification: function() {
        $("li.notifications_user li.notification:not('.readed')").hover(function() {
            var el = $(this).find('div.info');
            var notificationId = el.attr('notificationId');
            ajax.genericAction({
                route: 'user_ajaxdeletenotification',
                params: {
                    id: notificationId
                },
                callback: function(response) {
                    if (response === true) {
                        var cant = $("li.notifications_user a span").html();
                        parseInt(cant);
                        cant--;
                        if (cant > 0) {
                            $(".notifications_user a span").html(cant);
                        } else {
                            $(".notifications_user a span").hide();
                        }

                        el.parent().css('background', '#f4f3b8');
                    } else {
                        console.log(response);
                    }
                }
            });
        });
    },
    getPendingFriends: function() {
        $("li.alerts_user a").click(function() {
            $("li.alerts_user ul li").toggleClass('hidden');
        });
    },
    acceptFriendRequest: function() {
        $("div.button a.accept").click(function() {
            var liElement = $(this).parent().parent();
            var friendshipId = $(this).attr('id');
            ajax.acceptRequestAction(friendshipId, function(response) {
                if (response.error == false) {
                    liElement.remove();
                    var cant = $("li.alerts_user a span").html();
                    parseInt(cant);
                    cant--;
                    $("li.alerts_user a span").html(cant);
                    if (cant <= 0) {
                        $("li.alerts_user a span").parent().addClass('hidden');
                    }
                    success('El usuario ha sido agregado como amigo');
                } else {
                    error('Se ha producido un error');
                }
            });
        });
        $("div.listElement a.accept").click(function() {
            var friendshipId = $(this).attr('id');
            var liElement = $(this).closest('div.listElement');
            ajax.acceptRequestAction(friendshipId, function(response) {
                if (response.error == false) {
                    liElement.remove();
                    var cant = $("li.alerts_user a span").html();
                    parseInt(cant);
                    cant--;
                    $("li.alerts_user a span").html(cant);
                    if (cant <= 0) {
                        $("li.alerts_user a span").parent().addClass('hidden');
                    }
                    success('El usuario ha sido agregado como amigo');
                } else {
                    error('Se ha producido un error');
                }
            });
        });
    },
    denyFriendRequest: function() {
        $("div.listElement a.deny").click(function() {
            var friendshipId = $(this).attr('id');
            var liElement = $(this).closest('div.listElement');
            ajax.denyRequestAction(friendshipId, function(response) {
                if (response.error == false) {
                    liElement.remove();
                    var cant = $("li.alerts_user a span").html();
                    parseInt(cant);
                    cant--;
                    $("li.alerts_user a span").html(cant);
                    if (cant <= 0) {
                        $("li.alerts_user a span").parent().addClass('hidden');
                    }
                    success('Se ha rechazado la amistad');
                } else {
                    error('Se ha producido un error');
                }
            });
            return false;
        });
        $("span.info a.deny").click(function() {
            var friendshipId = $(this).attr('id');
            var liElement = $(this).closest('li.clearfix');
            ajax.denyRequestAction(friendshipId, function(response) {
                if (response.error == false) {
                    liElement.remove();
                    var cant = $("li.alerts_user a span").html();
                    parseInt(cant);
                    cant--;
                    $("li.alerts_user a span").html(cant);
                    if (cant <= 0) {
                        $("li.alerts_user a span").parent().addClass('hidden');
                    }
                    success('Se ha rechazado la amistad');
                } else {
                    error('Se ha producido un error');
                }
            });
            return false;
        });
    },
    likeButtons: function() {
        $('.btn.like:not(.disabled)').on('click', function(e) {
            e.preventDefault();
            if (!window.isLoggedIn) {
                $('[data-login-btn]').click();
                return false;
            }            
            var el = $(this);
            var type = el.attr('data-type');
            var id = el.attr('data-id');
            el.addClass('disabled');
            ajax.genericAction({
                route: 'like_ajaxtoggle',
                params: {
                    'id': id,
                    'type': type
                },
                callback: function(response) {
                    el.removeClass('disabled');
                    el.find('.likecount').text(response.likecount);
                    el.siblings('.likecount:first').text(response.likecount);
                    success(response.message);
                    if (response.liked) {
                        el.find('i').attr('class', '');
                        el.find('i').addClass('icon-star');
                    } else {
                        el.find('i').attr('class', '');
                        el.find('i').addClass('icon-star-empty');
                    }
                },
                errorCallback: function(responsetext) {
                    el.removeClass('disabled');
                    error(responsetext);
                }
            });
        });
    },
    shareButtons: function() {
        $('.sharebutton:not(.loading-small,.disabled)').on('click', function(e) {
            e.preventDefault();
            var el = $(this);

            askText(function(text) {
                var type = el.attr('data-type');
                var id = el.attr('data-id');
                el.addClass('loading-small');
                ajax.genericAction({
                    route: 'share_ajax',
                    params: {
                        'id': id,
                        'type': type,
                        'text': text
                    },
                    callback: function(response) {
                        el.removeClass('loading-small');
                        el.addClass('disabled');
                        success(response.message);
                    },
                    errorCallback: function(responsetext) {
                        el.removeClass('loading-small');
                        error(responsetext);
                    }
                });
            });

        });
    },
    globalCommentButtons: function() {
        $('.comment_button:not(.loading)').on('click', function(e) {
            e.preventDefault();
            var el = $(this),
                    textAreaElement = el.parent().closest('.commentform').find('textarea.comment_message'),
                    type = textAreaElement.attr('data-type'),
                    id = textAreaElement.attr('data-id'),
                    ispin = (textAreaElement.attr('data-pin') == 'true'),
                    content = textAreaElement.val();
            privacy = textAreaElement.parents('.commentform,.shortcommentform').find('.post_privacidad').val() || 1,
                    elDestination = '[data-wall]';


            var prepend = true;
            if (textAreaElement.attr('is-subcomment')) {
                elDestination = textAreaElement.parents('.comments, .comments-and-tags').find('.subcomments-container');
                prepend = false;
            }

            site.postComment(textAreaElement, type, id, ispin, content, privacy, elDestination, prepend);
            return false;
        });

        $('textarea.comment_message:not(.loading)').on('keydown', function(e) {
            if (e.keyCode == 13) {
                var textAreaElement = $(this),
                        type = textAreaElement.attr('data-type'),
                        id = textAreaElement.attr('data-id'),
                        ispin = (textAreaElement.attr('data-pin') == 'true'),
                        content = textAreaElement.val(),
                        privacy = textAreaElement.parents('.commentform,.shortcommentform').find('.post_privacidad').val() || 1,
                        elDestination = '[data-wall]';

                var prepend = true;
                if (textAreaElement.attr('is-subcomment')) {
                    elDestination = textAreaElement.parents('.comments, .comments-and-tags').find('.subcomments-container');
                    prepend = false;
                }
                site.postComment(textAreaElement, type, id, ispin, content, privacy, elDestination, prepend);
                return false;
            }
        });
    },
    watchLaterButtons: function() {
        $('body').on('click', '[data-watch-later]:not(.disabled)', function(e) {
            e.preventDefault();
            var el = $(this);
            var videoid = el.attr('data-video-id');
            var action = 'playlist_ajaxadd';

            if (!window.isLoggedIn) {
                $('[data-login-btn]').click();
                return false;
            }

            if (el.attr('data-later') == 'true') {
                action = 'playlist_ajaxremove';
            }

            el.addClass('disabled');
            ajax.genericAction({
                route: action,
                params: {
                    'video_id': videoid,
                },
                callback: function(response) {
                    el.removeClass('disabled');
                    console.log(response);
                    if (response === "add") {
                        el.find('i').attr('class', '');
                        $('.watchlaterText:first').html('Marcar como visto');
                        el.addClass('added');
                        el.attr('data-later', 'true');
                        success('Added to playlist');
                    } else {
                        el.find('i').attr('class', '');
                        $('.watchlaterText:first').html('Mirar luego');
                        el.removeClass('added');
                        el.attr('data-later', 'false');
                        success('Removed from playlist');
                    }
                },
                errorCallback: function(responsetext) {
                    el.removeClass('disabled');
                    error(responsetext);
                }
            });
        });
    },
    postComment: function(textAreaElement, type, id, ispin, content, privacy, elDestination, prepend) {
        textAreaElement.addClass('loading-small');
        textAreaElement.attr('disabled', 'disabled');
        ajax.genericAction({
            route: 'comment_ajaxpost',
            params: {
                'id': id,
                'type': type,
                'content': content,
                'privacy': privacy,
                'ispin': ispin
            },
            callback: function(response) {
                textAreaElement.val('');
                textAreaElement.removeClass('loadingSmall');

                success(response.message);

                templateHelper.renderTemplate(response.jsonComment.templateId, response.jsonComment, elDestination, prepend);

                $('.timeago').each(function() {
                    $(this).html($.timeago($(this).attr('data-time')));
                });

                if (ispin) {
                    $('.masonbricks').isotope().resize();
                }
                site.expander();
                textAreaElement.removeClass('loading-small');
                textAreaElement.removeAttr('disabled');
            },
            errorCallback: function(response) {
                textAreaElement.removeClass('loading-small');
                textAreaElement.removeAttr('disabled');
                error(response.responseText);
            }
        });
    },
    globalDeleteButtons: function() {
        $('.deletebutton:not(.loading)').on('click', function(e) {
            e.preventDefault();

            var el = $(this);
            if (confirm('¿Seguro desea eliminar?')) {
                var type = el.attr('data-type');
                var id = el.attr('data-id');
                el.addClass('loading');
                ajax.genericAction({
                    route: 'delete_ajax',
                    params: {
                        'id': id,
                        'type': type
                    },
                    callback: function(response) {
                        el.removeClass('loading');
                        success(response.message);
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        }
                    },
                    errorCallback: function(responsetext) {
                        el.removeClass('loading');
                        error(responsetext);
                    }
                });
            }
        });

        $('.deletecomment:not(.loading)').on('click', function(e) {
            e.preventDefault();

            var el = $(this);
            if (confirm('¿Seguro desea eliminar el comentario?')) {
                var type = 'comment';
                var id = el.attr('data-id');
                el.addClass('loading');

                ajax.genericAction({
                    route: 'delete_ajax',
                    params: {
                        'id': id,
                        'type': type
                    },
                    callback: function(response) {
                        success(response.message);
                        el.parent().slideUp('fast', function() {
                            $(this).remove();
                        });
                    },
                    errorCallback: function(responsetext) {
                        el.removeClass('loading');
                        error(responsetext);
                    }
                });

                /*
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
                 */
            }
        });
    },
    expander: function() {
        $("[data-expandable]").each(function(index, element) {
            var slicePoint = 100;
            if ($(this).attr('data-slice-point') !== 'undefined') {
                slicePoint = $(this).attr('data-slice-point');
            }
            $(this).expander({
                slicePoint: slicePoint,
                expandText: 'más',
                userCollapseText: 'ocultar'
            });
        });
    },
    BindLoginWidget: function() {
        //fix triangle for firefox
        if ($.browser.mozilla == true) {
            $('header nav div#login-widget div.arrow-up-border').hide();
        }
        $('header .header-ingresar').click(function() {
            $('header div#login-widget').toggle();
        });
        $('div#login-widget #do-login').click(function() {
            $('form.login').submit();
        });

    },
    bindCarousel: function() {
        $("div.info-detail-carousel div:not('.active')").hide();

        $('.carousel').carousel({
            interval: 5000
        }).bind('slid', function() {
            // Get currently selected item
            var item = $('#myCarousel .carousel-inner .item.active');

            var itemId = $(item).attr('data-video');
            $('div.info-detail-carousel div.active').fadeOut(function() {
                $(this).removeClass('active');
                $('div.info-detail-carousel div[data-video=' + itemId + ']').fadeIn().addClass('active');
            });

            // Deactivate all nav links
            $('#carousel-nav a').removeClass('active');

            // Index is 1-based, use this to activate the nav link based on slide
            var index = item.index() + 1;
            $('#carousel-nav a:nth-child(' + index + ')').addClass('active');
        });

        $('#carousel-nav a').click(function(q) {
            q.preventDefault();
            targetSlide = $(this).attr('data-to') - 1;
            $('#myCarousel').carousel(targetSlide);
        });

    },
    startMosaic: function(container, options) {
        // initialize the plugin
        var $container = container,
                $imgs = $container.find('img').hide(),
                totalImgs = $imgs.length,
                cnt = 0;

        $imgs.each(function(i) {
            var $img = $(this);
            $('<img/>').load(function() {
                ++cnt;
                if (cnt === totalImgs) {
                    $imgs.show();
                    $container.montage(options);
                }
            }).attr('src', $img.attr('src'));
        });
    }
}

function resizeColorbox(options) {
    return;
    if (typeof $.colorbox.resize === "function") {
        $.colorbox.resize(options);
    }
}

function askText(callback) {
    $.colorbox({
        href: Routing.generate(appLocale + '_popup_asktext'),
        onComplete: function() {
            $('#colorbox').find('.submit').click(function() {
                var content = $('#colorbox').find('textarea').val();
                $.colorbox.close();
                callback(content);
            });
        }
    });
}

function removeArrayElement(array, element) {
    var idx = array.indexOf(element); // Find the index
    if (idx != -1)
        array.splice(idx, 1); // Remove it if really found!

    return array;
}

if (!Object.keys) {
    Object.keys = function(obj) {
        var keys = [], k;
        for (k in obj) {
            if (Object.prototype.hasOwnProperty.call(obj, k)) {
                keys.push(k);
            }
        }
        return keys;
    };  
}

