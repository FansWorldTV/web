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
    resizePopup();
}


function createUploader(){            
    var uploader = new qq.FileUploader({
        element: $("#file-uploader")[0],
        action: Routing.generate( appLocale + '_video_ajaxfileupload'),
        debug: true,
        multiple: false,
        maxConnections: 1,
        
        onComplete: function(id, fileName, responseJSON){
            if(responseJSON.success){
                $.colorbox({
                    href: Routing.generate( appLocale + '_video_filemeta') + '/' + responseJSON.videoid + '/'+true,
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
    bindFormActions();  
}