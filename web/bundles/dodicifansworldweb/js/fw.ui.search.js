var search = {};

search.query = null;
search.running = false;


search.init = function (query) {
    if (search.query !== '') {
        $("input[data-search-input]")
            .val(search.query);
    }

    $("[data-add-more]").on('click', function (e) {
        var $self = $(this);

        if (!search.running) {
            search.it($self);
        }
    });
};

search.it = function ($ele) {
    search.running = true;
    $ele.addClass('rotate');

    var type = $ele.attr('data-add-more'),
        page = $ele.attr('data-page'),
        params = {
            'query': search.query,
            'page': page,
            'type': type
        };

    ajax.genericAction('search_ajaxsearch', params, function (r) {
        if (r) {
            var callback = function () {
            };
            var pointerLoop = 0;

            for (var i in r.search) {
                pointerLoop++;
                var entity = r.search[i];
                var destiny = null;

                console.log('ENTIDAD');
                console.log(entity);

                switch (type) {
                    case 'video':
                        destiny = 'section.search.video .roller';
                        break;
                    case 'idol':
                        destiny = "ul.avatar-list.idols";
                        break;
                    case 'user':
                        destiny = "ul.avatar-list.fans";
                        break;
                    case 'team':
                        destiny = "ul.avatar-list.teams";
                        break;
                    case 'photo':
                        destiny = ".photos-container";
                        break;
                }

                if (pointerLoop == r.search.length) {
                    callback = function () {
                        // fin del append
                        console.log('fin del append');
                        $ele.removeClass('rotate');
                        search.running = false;
                    };
                }

                templateHelper.renderTemplate('search-' + type, entity, destiny, false, callback);
            }
        }
    }, function (r) {
        console.log(r);
    }, 'get');
};
