var templateHelper = {

    'queue': null,

    'items': {},

    'init': function () {
        "use strict";

        templateHelper.queue = $.jqmq({
            delay: -1,
            batch: 1,
            callback: function (templateId) {
                var items = templateHelper.items[templateId];

                $.ajax({
                    url: templateHelper.getPath(templateId),
                    type: 'get',
                    dataType: "html",
                    cache: true,
                    success: function (data) {
                        if (!data) {
                            templateHelper.queue.next(true);
                        }

                        $.templates(templateId, data);
                        $.each(items, function(i, item) {
                            if (typeof item.callback === "function") {
                                item.callback();
                            }
                        });

                        templateHelper.queue.next();
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.error(jqXHR, textStatus, errorThrown);
                    }
                });

            },
            complete: function () {
            }
        });
    },

    'getPath': function (templateId) {
        "use strict";

        var tplPart = templateId.split("-");
        return Routing.generate(appLocale + '_template_' + tplPart[0], {
            type: tplPart[1]
        });
    },

    'preLoadTemplates': function (templates,callback) {
        "use strict";

        if (!templateHelper.queue) {
            templateHelper.init();
        }

        $.each(templates, function (index, value) {
            templateHelper.getTemplate(value,callback);
        });
    },

    'getTemplate': function (templateId, callback) {
        "use strict";

        if (typeof $.render[templateId] !== "undefined") {
            if (typeof callback === "function") {
                return callback();
            }

            return true;
        }
        var item = {
            'templateId': templateId,
            'callback': callback
        };

        if (typeof templateHelper.items[templateId] === "undefined") {
            templateHelper.items[templateId] = [item];
            templateHelper.queue.add(templateId);
        } else {
            templateHelper.items[templateId].push(item);
        }
    },

    'renderTemplate': function (templateId, jsonData, destino, prepend, callback) {
        "use strict";

        try {
            if (!templateHelper.queue) {
                templateHelper.init();
            }

            templateHelper.getTemplate(templateId, function () {
                templateHelper.appendRenderedTemplate(templateId, jsonData, destino, prepend, callback);
            });
        } catch (exception) {
            console.error(exception);
        }
    },
    'htmlTemplate': function (templateId, jsonData) {
        "use strict";
        var deferred = new jQuery.Deferred();
        try {
            if (!templateHelper.queue) {
                templateHelper.init();
            }

            templateHelper.getTemplate(templateId, function () {
                deferred.resolve($.render[templateId](jsonData));
            });
        } catch (exception) {
            console.error(exception);
            deferred.reject(new Error(error));
        }
        return deferred.promise();
    },
    'appendRenderedTemplate': function (templateId, jsonData, destino, prepend, callback) {
        "use strict";

        if (prepend === true) {
            $(destino).prepend($.render[templateId](jsonData));
        } else {
            $(destino).append($.render[templateId](jsonData));
        }

        if (typeof callback === 'function') {
            callback();
        }
    }
};
