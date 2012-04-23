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
});