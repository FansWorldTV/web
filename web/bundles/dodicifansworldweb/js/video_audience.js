(function($) {
    $.fn.videoAudience = function(options) {
        var defaults = {
            //maxItems: 25, // limits number of current viewers to display
            updateInterval: 120, // seconds
            keepAliveInterval: 120, // seconds
            videoId: null, // video id, this can be set as an data-video-id attribute in the list
            ajaxUrl: null, // url where to fetch the first list
            keepAliveUrl: null, // url where to post the keep alive request
            placeholderAvatar: null
        };
    
        var $list = $(this),
        $listHtml = "",
        keepAliveTimer,
        meteorChannel;
        
        if(!$list.length) {
            return false;
        }
        
        function init() {
            options = $.extend(defaults, options);
            if(!options.videoId) {
                options.videoId = $list.attr('data-video-id');
            }
            
            if(!options.placeholderAvatar) {
                options.placeholderAvatar = $list.attr('data-placeholder');
            }
            
            var mandatoryOpts = ['videoId', 'ajaxUrl', 'keepAliveUrl'];
            $.each(mandatoryOpts, function(key, val) {
                if(!options[val]) {
                    throw 'Expected mandatory ' + val + ' option.'
                }
            });
            
            meteorChannel = 'videoaudience_' + options.videoId;
            
            load(function() {
                subscribe(function() {
                    keepAliveTimer = setInterval(function() {
                        keepAlive();
                    }, options.keepAliveInterval * 100);
                });
            });
            
            return $list;
        }
        
        function load(callback) {
            $.ajax({
                url: options.ajaxUrl,
                data: {
                    'video': options.videoId
                },
                type: 'post',
                dataType: 'json',
                success: function(response) {
                    if(!response || !response.length) {
                        throw 'Something went wrong with the ajax request.';
                    }
                    
                    list(response);
                    if(typeof callback != "undefined") {
                        callback();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    throw 'Something went wrong with the ajax request.';
                }
            });
        }
        
        function list(audience) {
            $list.find('li').remove();
            $.each(audience, function(key, val) {
                if (!val.image && options.placeholderAvatar) {
                    val.image = options.placeholderAvatar;
                }
                if (!val.image) {
                    var extraClass = ' class="noimage"';
                } else {
                    var extraClass = '';
                }
                var html = '' +
                    '<li user_id="' + val.id + '">' +
                    '<a title="' + val.username + '" href="' + val.wall + '"'+ extraClass +'>';
                
                if (val.image) {
                    html += '<img src="' + val.image + '" />';
                }
                
                html += '</a></li>';
                
                $list.append($(html));
            });
        }
        
        function keepAlive() {
            $.post(options.keepAliveUrl, {
                'video': options.videoId
                });
        }
        
        function handleData(response){
            response = JSON.parse(response);
            return;
        }
        
        function subscribe(callback) {
            if (typeof Meteor == 'undefined') {
                throw 'Meteor could not be found.'
            }else{
                Meteor.registerEventCallback("process", handleData);
                Meteor.joinChannel(meteorChannel);
                Meteor.connect();
            }

            if(typeof callback != "undefined") {
                callback();
            }
        }
        
        try {
            return init();
        } catch(exception) {
            console.error('videoAudience failed: ' + exception);
            return false;
        }
    };
})(jQuery);
