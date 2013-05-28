// TODO: login required modal

$(function(){
    $('[data-login-required]').off();

    $('body').on('click', '[data-login-required]', function(e){
        if (!isLoggedIn) {
            e.preventDefault();
            e.stopImmediatePropagation();
            e.stopPropagation();
            //alert('TODO: login required');
            //$('[data-register-btn]').click();
            $('[data-login-btn]').click();
            return false;
        }
    });
});