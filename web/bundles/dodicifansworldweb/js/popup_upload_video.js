function bindFormActions(){
    $("form.upload-video").submit(function(){
        var data = $('form.upload-video').serializeArray();
        var href = $('form.upload-video').attr('action');

        $.colorbox({        
            href: href ,
            data: data,
            onComplete: function(){
                bindFormActions();
                resizePopup();
            }
        });
        return false;
    });
}

function createUploader(){            
    bindFormActions();           
}

