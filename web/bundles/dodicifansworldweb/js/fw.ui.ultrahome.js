// NUEVO HOME
 
 
// WARNING GLOBAL VARIABLE
// EventEmitter is taken from packery but can be download from https://github.com/Wolfy87/EventEmitter
$(document).ready(function () {
    "use strict";
    window.fansWorldEvents = window.fansWorldEvents || new EventEmitter();
});
 
///////////////////////////////////////////////////////////////////////////////
// Attach plugin to all matching element                                     //
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
    "use strict";
 
    var heroMenu = '.filter-home';
    var highlighteds = 'section.highlighteds';
    var followed = 'section.followed > .videos-container';
    var followedTags = 'section.followed-tags > ul';
    var popular = 'section.popular > .videos-container';
    var popularTags = 'section.popular-tags > ul';
    

    function fillMain(element, videos) {
        $.when(fillGrid(element, videos))
        .then(function(videos) {
            $(element).find('.span2').empty()
            .append(videos[0])
            .append(videos[2]);
            $(element).find('.span4').empty()
            .append(videos[4]);
            $(element).find('.span6').empty()
            .append(videos[6])
            .append(videos[8])
            .append(videos[10])
            .append(videos[12])
            .append(videos[14])
            .append(videos[16]);
        });
    }
    function fillFollowed(element, videos) {
        var deferred = new jQuery.Deferred();
        var follow = [];
        for (var key in videos.follow) {
            if($.isNumeric(key)) {
                follow.push(videos.follow[key])
            };
        }
        fillTags(followedTags, videos.follow.tags);
        $.when(fillGrid(element, follow))
        .then(function(html){
            $(element).append(html);
            console.log("addMore: " + videos.follow.addMore)
            if(videos.follow.addMore) {
                $(element).parent().find('.add-more').show();
            } else {
                $(element).parent().find('.add-more').hide();
            }
            deferred.resolve(html);
        })
        .fail(function(error){
            deferred.reject(new Error(error));
        });
        return deferred.promise();
    }
    function fillPopular(element, videos) {
        var deferred = new jQuery.Deferred();
        var popular = [];
        for (var key in videos.popular) {
            if($.isNumeric(key)) {
                popular.push(videos.popular[key])
            };
        }
        fillTags(popularTags, videos.popular.tags);
        $.when(fillGrid(element, popular))
        .then(function(html){
            $(element).append(html);
            console.log("addMore: " + videos.popular.addMore)
            if(videos.popular.addMore) {
                $(element).parent().find('.add-more').show();
            } else {
                $(element).parent().find('.add-more').hide();
            }            
            deferred.resolve(html);
        })
        .fail(function(error){
            deferred.reject(new Error(error));
        });
        return deferred.promise();
    }
    function fillGrid(grid, videos) {
        var deferred = new jQuery.Deferred();
        $.when(templateHelper.htmlTemplate('general-videos_std_block', {videos: videos }))
        .then(function(response){
            var thumbs = $(response).clone();
            return thumbs;
        })
        .done(function(thumbs){
            deferred.resolve(thumbs);
        }).fail(function(error){
            deferred.reject(new Error(error));
        });
        return deferred.promise();
    }
    function fillTags(element, tags) {
        for (var tag in tags) {
            var tagElement = document.createElement('li');
            tagElement.innerText = tags[tag].title;
            tagElement.setAttribute('id', tags[tag].id);
            tagElement.setAttribute('data-list-filter-type', tags[tag].type);
            tagElement.setAttribute('data-id', tags[tag].id);
            $(element).append(tagElement);
        }
    }
    function emptyAll() {
        console.log("empty")
        $(followed).empty();$(followedTags).empty();
        $(popular).empty();$(popularTags).empty();
    }
    function getVideos(type, id){
        console.log("getVideos")
        var deferred = new jQuery.Deferred();
        var request = {};
        request[type] = id;
 
        $.ajax({
            url: Routing.generate(appLocale + '_ajax_newhomefilter'),
            data: request
        })
        .done(function(response){
            deferred.resolve(response);
        }).fail(function(error){
            deferred.reject(new Error(error));
        });
        return deferred.promise();
    }
    function reloadAllVideos(type, id) {
        emptyAll();
        $.when(getVideos(type, id))
        .then(function(data){
            fillMain(highlighteds, data.highlighted),
            fillFollowed(followed, data),
            fillPopular(popular, data)
        }).fail(function(error){
 
        });
    }
    window.fansWorldEvents.addListener('onMenuChange', reloadAllVideos);
 
    $('body').on("click", heroMenu  + ":not('.editing') > li:not('[data-override]')", function(event) {
        if ($(this).hasClass('active')) {
            return;
        }
        $(this).parent().find('.active').removeClass('active');
        $(this).addClass('active');
 
        var type = $(this).attr('data-entity-type');
        var id = parseInt($(this).attr('data-entity-id'), 10);
        var vc = $(this).attr('data-video-category');
 
        console.log("event ")
        ///////////////////////////////////////////////////////////////////////
        // Decoupled EventTrigger //
        ///////////////////////////////////////////////////////////////////////
        window.fansWorldEvents.emitEvent('onMenuChange', [type, id]);
    });


    $('body').on("click", '.add-more', function(event) {
        var section = $(this).attr('data-addmore');
        $(this).addClass('rotate');
        console.log(section)
    });

});
 
//window.fansWorldEvents.emitEvent('onMenuChange', ['genre', 1])