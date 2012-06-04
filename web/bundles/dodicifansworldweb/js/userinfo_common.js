$(function() {
	$('#fos_user_registration_form_username, #fos_user_profile_form_user_username')
	.after(
		$('<div>')
		.addClass('fieldinfo')
		.html('http://'+location.host+'/u/'+'<strong class="userurlpreview">'+$(this).val()+'</strong>')
	)
	.keyup(function(){
		$('.userurlpreview').text($(this).val());
	})
	;
        $('.userurlpreview').text($("#fos_user_profile_form_user_username").val());
});


//Validate username/email
$(document).ready(function(){
	var iTypingDelay = 800;
    var username = $("#fos_user_registration_form_username, #fos_user_profile_form_user_username");
    var email = $("#fos_user_registration_form_email, #fos_user_profile_form_user_email");


    $(username).on('keyup', null, function(){
    	var htmlElement = $(this);
        var iTimeoutID = htmlElement.data("timerID") || null; 
        if (iTimeoutID) {
            clearTimeout(iTimeoutID);
            iTimeoutID = null;
        }        
        iTimeoutID = setTimeout(function() {
        	htmlElement.data("timerID", null);
            htmlElement.removeClass('inputok inputerr').addClass('inputloading');
            ajaxValidateProfile(null, username.val(), function(response){
                if(response.isValidUsername){
                	htmlElement.removeClass('inputloading').addClass('inputok');
                }else{
                	htmlElement.removeClass('inputloading').addClass('inputerr');
                }
            });
        }, iTypingDelay);
        htmlElement.data("timerID", iTimeoutID);
    });  
    
    $(email).on('keyup', null, function(){
    	var htmlElement = $(this);
        var iTimeoutID = htmlElement.data("timerID") || null;
        if (iTimeoutID) {
            clearTimeout(iTimeoutID);
            iTimeoutID = null;
        }       
        iTimeoutID = setTimeout(function() {
        	htmlElement.data("timerID", null);
            htmlElement.removeClass('inputok inputerr').addClass('inputloading');
            ajaxValidateProfile( email.val(), null, function(response){
                if(response.isValidEmail){
                	htmlElement.removeClass('inputloading').addClass('inputok');
                }else{
                	htmlElement.removeClass('inputloading').addClass('inputerr');
                }
            });
        }, iTypingDelay);
        htmlElement.data("timerID", iTimeoutID);
    });
    
    
});
function ajaxValidateProfile(email, username, callback){
    ajax.genericAction('profile_validate', {
        'username': username,
        'email': email
    }, function(r){
        callback(r);
    });
}