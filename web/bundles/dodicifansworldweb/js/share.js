var share = {};

share.init = function () {
    $("[data-share-button], i.close-share").on('click', function (event) {
        $("[data-share-button]").toggleClass('active');
        /*$("[data-sharebox-gral]").toggle();*/
        $("#share-modal").modal();
    });

    $(".sharer-container [data-share-content]").fwModalDialog();

    share.it();
};

share.it = function () {
    $("#share_it:not('.disabled')").live('click', function () {
        var self = $(this);
        var params = {};

        $(self).addClass('disabled');

        params['fw'] = true;
        params['message'] = $("input.wywtsay").val();
        params['entity-type'] = $("[data-share-button]").attr('data-type');
        params['entity-id'] = $("[data-share-button]").attr('data-id');
        params['share-list'] = {};

        console.log(params);
        console.log("before ajax call");

        ajax.genericAction('share_ajax', params, function (r) {
            if (r) {
                if (r.error) {
                    console.log(r.msg);
                } else {
                    success("Contenido compartido!");
                    $("[data-share-button]").toggleClass('active');
                }
            }
            self.removeClass('disabled');
        }, function (msg) {
            error(msg);
            self.removeClass('disabled');
        });
    });
};

$(document).ready(function () {
    share.init();
});