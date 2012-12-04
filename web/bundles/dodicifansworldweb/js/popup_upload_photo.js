$(function(){
    if ($('#form_album').val() == 'NEW') {
        spawnNewAlbumField($('#form_album'));
    }
    $('#form_album').change(function(e){
        var el = $(this);
        if (el.val() == 'NEW') {
            spawnNewAlbumField(el);
        } else {
            $('#form_album_new_name').parents('.control-group').slideUp('fast',function(){
                $(this).remove();
                resizePopup();
            });
        }
    });
});

function bindAlbumActions() {
    if ($('#form_album').val() == 'NEW') {
        spawnNewAlbumField($('#form_album'));
    }
    $('#form_album').change(function(e){
        var el = $(this);
        if (el.val() == 'NEW') {
            spawnNewAlbumField(el);
        } else {
            $('#form_album_new_name').parents('.control-group').remove();
        }
    });
}

function spawnNewAlbumField(el) {
	var newfield = $(".templateFieldAlbumName .control-group").clone();
	newfield.find('input').attr('id', 'form_album_new_name');
	el.parents('.control-group').after(newfield);
	$('#form_album_new_name').parents('.control-group').slideDown('fast', function(){
		resizePopup();
	});
}

function bindFormSubmit() {
    $("form.upload-photo").submit(function(){
        var data = $('form.upload-photo').serializeArray();
        var href = $('form.upload-photo').attr('action');
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

function bindFormActions() {
    bindFormSubmit();
    bindAlbumActions();
    setTimeout(function(){ resizePopup(); }, 1000);
}

function createUploader() {            
    var uploader = new qq.FileUploader({
        element: $("#file-uploader")[0],
        action: Routing.generate( appLocale + '_photo_fileupload'),
        debug: true,
        multiple: false,
        maxConnections: 1,
        allowedExtensions: ['jpg', 'jpeg', 'png', 'gif'],
        onComplete: function(id, fileName, responseJSON){
            if(responseJSON.success) {
                $.colorbox({
                    href: Routing.generate(appLocale + '_photo_filemeta', 
                        {'originalFile': responseJSON.originalFile, 'tempFile':responseJSON.tempFile}),
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
        onUpload: function() {
            resizePopup();
        },
        onProgress: function(id, fileName, loaded, total) {
        	if (loaded != total){
                $( "#progressbar .bar" ).css('width', Math.round(loaded / total * 100)+'%');
            } else {                                   
                $( "#progressbar .bar" ).css('width','100%');
            }  
            resizePopup();
        }
    });           
}