var tv = {

    'init': function (filtersList, channelsList, targetDataList) {
        "use strict";

        $('.js-subscribe').click(function () {
            tv.subscribe($(this));
        });

        tv.explore();
    },

    'subscribe': function ($button) {
        "use strict";

        var channel = $button.attr('data-active-channel');

        if (!channel || channel === 'all') {
            return alert('Por favor elija un canal antes de suscribirse.');
        }
        console.log(channel);

    },

    'tags': function (activeChannel, filter, targetDataList, opts) {
        "use strict";

        var filterList = $(targetDataList).closest('.content-container').find('.tag-list-container ul');
        opts = $.merge({
            'videocategory': activeChannel,
            'filtertype': filter
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

        var dropDown = '.breadcrumb .channels-dropdown',
             targetDataList = '.am-container';

        $(dropDown).find('ul.dropdown-menu li a').click(function (e) {

            var activeChannel = {
                slug: $(this).attr('channel-slug'),
                title: $(this).text()
            };
            $(dropDown).find('.dropdown-toggle span').text(activeChannel.title);
            tv.rankingUpdate.videos(activeChannel.slug, null, targetDataList, {});
        });
    }

};
