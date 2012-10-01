function ajaxValidateProfile(email, username, callback) {
    'use strict';

    ajax.genericAction('profile_validate', {
        'username': username,
        'email': email
    }, function (r) {
        callback(r);
    });
}

$(document).ready(function () {
    'use strict';

    var $checkbox = $('input#fos_user_registration_form_accept_toc'),
        $submit = $('form.fos_user_registration_register button#submitRegister'),
        iTypingDelay = 800,
        username = $("#fos_user_registration_form_username, #fos_user_profile_form_user_username"),
        email = $("#fos_user_registration_form_email, #fos_user_profile_form_user_email");

    // toc
    if ($checkbox.is(':checked')) {
        $submit.removeAttr('disabled').addClass('btn-success');
    }

    $checkbox.on('click', function (e) {
        if ($(this).is(':checked')) {
            $submit.removeAttr('disabled').addClass('btn-success');
        } else {
            $submit.attr('disabled', 'true').removeClass('btn-success');
        }
    });

    // username validation
    $('#fos_user_profile_form_user_username')
        .after(
            $('<p>')
                .addClass('fieldinfo help-block')
                .html('http://' + location.host + '/u/' + '<strong class="userurlpreview">' + $(this).val() + '</strong>')
        )
        .keyup(function () {
            $('.userurlpreview').text($(this).val());
        });
    $('.userurlpreview').text($("#fos_user_profile_form_user_username").val());

    $(username).on('keyup', null, function () {
        var htmlElement = $(this),
            iTimeoutID = htmlElement.data("timerID") || null;

        if (iTimeoutID) {
            clearTimeout(iTimeoutID);
            iTimeoutID = null;
        }
        iTimeoutID = setTimeout(function () {
            htmlElement.data("timerID", null);
            htmlElement.removeClass('inputok inputerr').addClass('inputloading');
            ajaxValidateProfile(null, username.val(), function (response) {
                if (response.isValidUsername) {
                    htmlElement.removeClass('inputloading').addClass('inputok');
                } else {
                    htmlElement.removeClass('inputloading').addClass('inputerr');
                }
            });
        }, iTypingDelay);
        htmlElement.data("timerID", iTimeoutID);
    });

    // email
    $(email).on('keyup', null, function () {
        var htmlElement = $(this),
            iTimeoutID = htmlElement.data("timerID") || null;

        if (iTimeoutID) {
            clearTimeout(iTimeoutID);
            iTimeoutID = null;
        }
        iTimeoutID = setTimeout(function () {
            htmlElement.data("timerID", null);
            htmlElement.removeClass('inputok inputerr').addClass('inputloading');
            ajaxValidateProfile(email.val(), null, function (response) {
                if (response.isValidEmail) {
                    htmlElement.removeClass('inputloading').addClass('inputok');
                } else {
                    htmlElement.removeClass('inputloading').addClass('inputerr');
                }
            });
        }, iTypingDelay);
        htmlElement.data("timerID", iTimeoutID);
    });
});
