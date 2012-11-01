// TODO: login required modal

$(function(){
    $('body').on('click', '[data-login-required]', function(e){
        if (!isLoggedIn) {
            e.preventDefault();
            e.stopImmediatePropagation();
            e.stopPropagation();
            alert('TODO: login required');
            return false;
        }
    });
});