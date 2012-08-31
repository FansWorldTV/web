// TODO: login required modal

$(function(){
    $('[data-login-required]').on('click',function(e){
        if (!isLoggedIn) {
            e.preventDefault();
            e.stopImmediatePropagation();
            alert('TODO: login required');
            return false;
        }
    });
});