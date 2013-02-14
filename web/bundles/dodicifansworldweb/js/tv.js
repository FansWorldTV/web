var tv = {

    'init': function (filtersList, channelsList, targetDataList) {
        "use strict";

        // lists
        $('[data-list]').list();

        // modalPopup
        $('#montage-video-list a').modalPopup();

        // subscribe buttons
        $('[data-subscribe-channel]').click(function () {
            tv.subscribe($(this));
        });
        
        // top mosaic
        site.startMosaic($(".content-container .list-mosaic"), {
            minw: 100,
            margin: 3,
            liquid: true,
            minsize: false,
            fillLastRow: false
        });

        // channels explore
        tv.explore();
    },

    'subscribe': function ($button) {
        "use strict";

        var channel = $button.attr('data-subscribe-channel'),
            params = {};

        if (!channel || channel === 'all') {
            return alert('Por favor elija un canal antes de suscribirse.');
        }

        params.channel = channel;
        
        $button.addClass('loading-small');

        ajax.genericAction('teve_channelsubscribe', params, function (response) {
            success(response.message);
            $button.text(response.buttontext);
            
            if (response.state == true) {
                $button.prepend($('<i>').attr('class', 'icon-remove').after(' '));
            } else if (response.state == false) {
                $button.prepend($('<i>').attr('class', 'icon-ok').after(' '));
            }
            
            $button.removeClass('loading-small');
            
        }, function (msg) {
            error(msg);
            
            $button.removeClass('loading-small');
        });

    },

    'tags': function (activeChannel, filter, targetDataList, opts) {
        "use strict";

        var filterList = $(targetDataList).closest('.content-container').find('.tag-list-container ul');
        opts = $.merge({
            'channel': activeChannel,
            'filter': filter
        }, opts);

        $(filterList).empty().addClass('loading');

        ajax.genericAction('tag_ajaxgetusedinvideos', opts, function (r) {
            if (typeof r !== "undefined") {
                $(filterList).removeClass('loading');
                if (typeof r.tags !== "undefined") {
                    templateHelper.renderTemplate("general-tag_list", r.tags, filterList, false, function () {
                    });
                }
            }
        }, function (msg) {
            console.error(msg);
        });
    },

    'explore': function () {
        "use strict";
        /*
        var dropDown = '.breadcrumb .channels-dropdown',
            targetDataList = '.am-container';

        $(dropDown).find('ul.dropdown-menu li a').click(function (e) {
            e.preventDefault();
            var activeChannel = {
                slug: $(this).attr('channel-slug'),
                title: $(this).text()
            };
            $(dropDown).find('.dropdown-toggle span').text(activeChannel.title);
            tv.rankingUpdate.videos(activeChannel.slug, null, targetDataList, {});
        });
        */
    }

};
