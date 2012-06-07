$(function(){
    if ($('#form_album').val() == 'NEW') {
        spawnNewAlbumField($('#form_album'));
    }
    
    $('#form_album').change(function(e){
        var el = $(this);
        if (el.val() == 'NEW') {
            spawnNewAlbumField(el);
        } else {
            $('#form_album_new_name').parents('.field').slideUp('fast',function(){
                $(this).remove();
                resizePopup();
            });
        }
    });
});

function bindAlbumActions(){
    if ($('#form_album').val() == 'NEW') {
        spawnNewAlbumField($('#form_album'));
    }
    
    $('#form_album').change(function(e){
        var el = $(this);
        if (el.val() == 'NEW') {
            spawnNewAlbumField(el);
        } else {
            $('#form_album_new_name').parents('.field').remove();
        }
    });
}

function spawnNewAlbumField(el) {
	var newfield = $(".templateFieldAlbumName .field").clone();
	newfield.find('input').attr('id', 'form_album_new_name');
	el.parents('.field').after(newfield);
	$('#form_album_new_name').parents('.field').slideDown('fast', function(){
		resizePopup();
	});
}

function bindFormSubmit(){
    $("form.upload-photo").submit(function(){
        var data = $('form.upload-photo').serializeArray();
        var href = $('form.upload-photo').attr('action');

        $.colorbox({        
            href: href ,
            data: data
        });
        return false;
    });
}

function bindFormActions(){
    bindFormSubmit()
    bindAlbumActions()
}

function createUploader(){            
    var uploader = new qq.FileUploader({
        element: $("#file-uploader")[0],
        action: Routing.generate( appLocale + '_photo_fileupload'),
        debug: true,
        multiple: false,
        maxConnections: 1,
        allowedExtensions: ['jpg', 'jpeg', 'png', 'gif'],
        onComplete: function(id, fileName, responseJSON){
            if(responseJSON.success){
                $.colorbox({
                    href: Routing.generate( appLocale + '_photo_filemeta') + '/' + responseJSON.mediaId,
                    iframe: false, 
                    innerWidth: 700, 
                    innerHeight: 700,
                    onComplete: function() {
                        resizePopup();
                        bindFormActions();
                    }
                });
            }
        },
        onUpload: function(){
            resizePopup();
        },
        onProgress: function(id, fileName, loaded, total){
            if (loaded != total){
                $( "#progressbar" ).progressbar({
                    value: Math.round(loaded / total * 100)
                });
            } else {                                   
                $( "#progressbar" ).progressbar({
                    value: 100
                });
            }  
            resizePopup();
            
        }
    });           
}