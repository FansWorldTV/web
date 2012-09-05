$(function(){
    /*
    $('#form_prefs input[type="checkbox"], .form-profile input[type="checkbox"]').checkbox({
        empty: emptyCheckboxImg,
        cls: 'jquery-safari-checkbox'
    });
	*/
    filterCityField(function(){
        $('#fos_user_profile_form_user_city').val(selectedCity);
    });
    
    $('#fos_user_profile_form_user_country').change(function(){
        $('label[for="fos_user_profile_form_user_city"]').addClass('loading');
        filterCityField(function(){
            $('label[for="fos_user_profile_form_user_city"]').removeClass('loading');
            $('label[for="fos_user_profile_form_user_city"]').closest('li').effect("highlight",{},1000);
        });
    });
    
    $("dl.privacies button.btn").click(function(){
        var privacies = {};

        $(".privacies button").addClass('disabled');

        $(".privacies dd").each(function(i, element){
            var field = $(element).find('.active').attr('data-field');
            var value = $(element).find('.active').attr('data-value');
            privacies[field] = value;
        });

        var clickedField = $(this).attr('data-field');
        var clickedValue = $(this).attr('data-value');
        privacies[clickedField] = clickedValue;

        ajax.genericAction('profile_ajaxprivacy', {
            'privacy' : privacies,
        }, function(response){
            success(response.error);
            $(".privacies button").removeClass('disabled');
        }, function(e){
            error(e);
        });
    });
});

function filterCityField(callback) {
    if ($('#fos_user_profile_form_user_country').val()) {
        $('#fos_user_profile_form_user_city').html($('<option>').val(''));
        ajax.genericAction('cities_ajax', {
            country: $('#fos_user_profile_form_user_country').val()
        }, 
        function(response){
            if(response.cities){
                $.each(response.cities, function(){
                    var city = this;
                    $('#fos_user_profile_form_user_city').append($('<option>').val(city.id).text(city.title));
                });
	            
                if (typeof callback == 'function') callback();
            }
        },
        function(errortxt) {
            error(errortxt);
        }
        );
    } else {
        $('#fos_user_profile_form_user_city').html($('<option>').val(''));
    }
}

// Link with facebook

$(function(){
    $('#fos_user_profile_form_user_linkfacebook').live('change', function(){
        if ($(this).attr('checked') == 'checked') {
            FB.ui({
                method: 'permissions.request',
                'perms': window.FBperms,
                'display': 'popup',
                'response_type': 'signed_request',
                'fbconnect': 1,
                'next': 'http://' + location.host + Routing.generate( appLocale + '_' + 'facebook_jstoken')
            },
            function(response) {
			    
                }
                );
        }
    });
	
    $('#fos_user_profile_form_user_linktwitter').live('change', function(){
        if ($(this).attr('checked') == 'checked') {
            window.open(Routing.generate(appLocale + '_' + 'twitter_redirect'), 'fw_twit_link', 'menubar=no,status=no,toolbar=no,width=500,height=300');
        }
    });
});