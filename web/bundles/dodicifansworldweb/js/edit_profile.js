$(function(){
	$('#form_prefs input[type="checkbox"]').checkbox({
        empty: emptyCheckboxImg
    });
	
	filterCityField();
	$('#fos_user_profile_form_user_country').change(function(){
		filterCityField();
		$('#fos_user_profile_form_user_city option').removeAttr('selected');
		$('#fos_user_profile_form_user_city option[value=""]').attr('selected','');
		$('label[for="fos_user_profile_form_user_city"]').closest('li').effect("highlight",{},1000);
	});
});

function filterCityField() {
	$('#fos_user_profile_form_user_city option[value!=""]').hide();
	$('#fos_user_profile_form_user_city option[data-country="'+$('#fos_user_profile_form_user_country').val()+'"]').show();
}