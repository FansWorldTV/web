$(document).ready(function(){
    importer.init();
});


var importer = {};

importer.init = function(){
    $("form#importMails").submit(function(){
        var mail = $(this).find('span.mail input').val();
        var password =$(this).find('span.password input').val();
        var provider =$(this).find('.provider').val();
        
        importer.getContacts(mail, password, provider, function(response){
            $("ul.listImported li").remove();
            if(!response.error){
                for(var i in response.contacts){
                    var contact = response.contacts[i];
                    $("ul.listImported").append("<li><input type='checkbox' id='" +i + "' name='contact[]' value='" + i + "'/><label>" + contact + "</label></li>");
                }
                $("ul.listImported").prepend('<li><input type="button" value="invitar" /></li>');
            }
        });
        return false;
    });
    
    $("ul.listImported input[type='button']").live('click', function(){
        importer.invite();
    });
};

importer.getContacts = function(mail, pass, provider, callback){
    ajax.genericAction('invite_ajaximport', {
        mail: mail, 
        password: pass, 
        provider: provider
    }, function(response){
        callback(response);
    }, function(e){
        error(e);
    })
};

importer.invite = function(){
    var users2bInvited = new Array();
    $.each($("ul.listImported li input:checked"), function(index, element){
        users2bInvited[index] = $(element).val();
    });
    
    ajax.genericAction('invite_generateInvitation', {
        users: users2bInvited
    }, function(r){
        console.log(r)
        if(r){
            for(var i in r.invites){
                var invite = r.invites[i];
                if(invite.sent){
                    $("#" + i).parent().addClass('bold');
                }else{
                    console.error('No se envio: ' + i);
                }
            }
        }
    }, function(r){
        console.error(r);
    });
};