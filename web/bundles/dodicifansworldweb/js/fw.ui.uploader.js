/*
 * library dependencies:
 *      jquery 1.8.3
 *      fos-routing
 *      fileUploader
 *      colorBox
 * external dependencies:
 *      appLocale
 */

// fansWorld file upload plugin 1.4

// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
//;(function ($, window, document, undefined) {
$(document).ready(function () {

    "use strict";
    // Create the defaults once
    var pluginName = "fwUploader";
    var defaults = {
        propertyName: "fansworld",
        uploadtoken: null,
        ks: null,
        entryId: null,
        mediaExtensions: {
            photo: ['jpg', 'jpeg', 'png', 'gif'],                       // Allowed extensions by media type
            video: ['avi', 'mov', 'mpeg'],
            audio: ['wav', 'mp3', 'ogg', 'midi']
        },
        action: {
            photo: Routing.generate(appLocale + '_photo_fileupload'),   // link for photos
            video: 'http://www.kaltura.com/api_v3/index.php'            // link for videos
        },
        mediaType: null,
        // custom bindings
        onSubmit: function(id, fileName){},
        onComplete: function(id, fileName, responseJSON){},
        onCancel: function(id, fileName){},
        onUpload: function(id, fileName, xhr){},
        onProgress: function(id, fileName, loaded, total){},
        onError: function(id, fileName, reason) {}
    };

    // The actual plugin constructor
    function Plugin(element, options) {
        this.element = element;
        // jQuery has an extend method which merges the contents of two or
        // more objects, storing the result in the first object. The first object
        // is generally empty as we don't want to alter the default options for
        // future instances of the plugin
        this.options = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;
        this.init();
    }

    Plugin.prototype = {
        init: function () {
            var that = this;
            $(that.element).addClass(that._name);
            that.options.mediaType = $(that.element).attr('data-upload');
            $(that.element).bind("destroyed", $.proxy(that.teardown, that));
            $(that.element).colorbox({
                innerWidth: 700,
                innerHeight: 475,
                onComplete: function() {
                    if(that.options.mediaType === 'video')
                    {
                        that.createFwGenericUploader();
                        that.resizePopup();
                        // Get kaltura KS + entryId + uploadToken
                        that.getToken('fileName', function(uploadToken) {
                            console.log("token: " + uploadToken)
                            that.uploader.setParams({uploadTokenId: that.options.uploadtoken});
                        });
                    } else {
                        that.createFwUploader();
                        that.resizePopup();
                    }
                }
            });
        },
        bindAlbumActions: function() {
            var that = this;
            if ($('#form_album').val() === 'NEW') {
                that.spawnNewAlbumField($('#form_album'));
            }
            $('#form_album').change(function(e){
                var el = $(this);
                if (el.val() === 'NEW') {
                    that.spawnNewAlbumField(el);
                } else {
                    $('#form_album_new_name').parents('.control-group').remove();
                }
            });
        },
        spawnNewAlbumField: function(el) {
            var that = this;
            var newfield = $(".templateFieldAlbumName .control-group").clone();
            newfield.find('input').attr('id', 'form_album_new_name');
            el.parents('.control-group').after(newfield);
            $('#form_album_new_name').parents('.control-group').slideDown('fast', function() {
                that.resizePopup();
            });
        },
        bindFormSubmit: function() {
            var that = this;
            console.log("bindFormSubmit")
            //$('#cboxLoadedContent').find("form") <--- suck it from above
            $("form.upload-" + that.options.mediaType).submit(function() {
                console.log("before submit")
                var data = $(this).serializeArray();
                var href = $(this).attr('action');
                $.colorbox({
                    href: href ,
                    data: data,
                    onComplete: function(){
                        that.bindFormActions();
                        that.resizePopup();
                    }
                });
                return false;
            });
        },
        bindFormActions: function() {
            var that = this;
            that.bindFormSubmit();
            that.bindAlbumActions();
            setTimeout(function(){ that.resizePopup(); }, 2000);
        },
        createFwUploader: function() {
            var that = this;
            var uploader = new qq.FileUploader({
                element: $("#file-uploader")[0],
                action: that.options.action[that.options.mediaType],
                debug: true,
                multiple: false,
                maxConnections: 1,
                allowedExtensions: that.options.mediaExtensions[that.options.mediaType],
                onComplete: function(id, fileName, responseJSON) {
                    if(responseJSON.success) {
                        $.colorbox({
                            href: Routing.generate(appLocale + '_photo_filemeta', {
                                'originalFile': responseJSON.originalFile,
                                'tempFile':responseJSON.tempFile,
                                'width': responseJSON.width,
                                'height': responseJSON.height
                            }),
                            iframe: false,
                            innerWidth: 700,
                            innerHeight: 700,
                            onComplete: function() {
                                that.resizePopup();
                                that.bindFormActions();
                            }
                        });
                    }
                    return that.options.onComplete(id, fileName, responseJSON);
                },
                onUpload: function() {
                    console.log("onUpload");
                    that.resizePopup();
                },
                onProgress: function(id, fileName, loaded, total) {
                    if (loaded !== total){
                        $( "#progressbar .bar" ).css('width', Math.round(loaded / total * 100)+'%');
                    } else {
                        $( "#progressbar .bar" ).css('width','100%');
                    }
                    that.resizePopup();
                },
                onSubmit: function(id, fileName){
                    return that.options.onSubmit(id, fileName);
                },
                onError: function(id, fileName, reason) {
                    return that.options.onError(id, fileName, reason);
                }
            });
        },
        createFwVideoUploader: function() {
            var that = this;
            that.uploader = new qq.FileUploader({
                element: $('#file-uploader')[0],
                action: that.options.action[that.options.mediaType],
                multiple: false,
                forceMultipart: true,
                normalHeaders: false,
                responsePassthrough: true,
                debug: true,
                inputName: 'resource:fileData',
                failedUploadTextDisplay: {mode: 'none'},
                onSubmit: function(file, ext){
                    that.uploader.setParams({
                        service: 'media',
                        action: 'addContent',
                        entryId: that.options.entryId,
                        ks: that.options.ks,
                        'resource:objectType': 'KalturaUploadedFileResource'
                    });
                    return that.options.onSubmit(id, fileName);
                },
                // kaltura returns XML
                onComplete: function(id, fileName, xml){
                    var xmlDoc = $.parseXML(xml);
                    var $xml = $( xmlDoc );
                    //var result = $xml.find( "result" );
                    return that.options.onComplete(id, fileName, xml);
                },
                onUpload: function(id, fileName, xhr) {
                    that.resizePopup();
                    return that.options.onUpload(id, fileName, xhr);
                },
                onProgress: function(id, fileName, loaded, total) {
                    return that.options.onProgress(id, fileName, loaded, total);
                },
                onError: function(id, fileName, reason) {
                    return that.options.onError(id, fileName, reason);
                }
            });
        },
        createFwGenericUploader: function() {
            var that = this;
            that.uploader = new qq.FileUploader({
                element: $('#file-uploader')[0],
                action: that.options.action[that.options.mediaType],
                multiple: false,
                forceMultipart: true,
                normalHeaders: false,
                responsePassthrough: true,
                debug: true,
                inputName: 'resource:fileData',
                failedUploadTextDisplay: {mode: 'none'},
                onSubmit: function(id, fileName){
                    that.uploader.setParams({
                        service: 'media',
                        action: 'addContent',
                        entryId: that.options.entryId,
                        ks: that.options.ks,
                        'resource:objectType': 'KalturaUploadedFileResource'
                    });
                    $('.qq-uploader').hide();
                    that.options.onSubmit(id, fileName);
                },
                onComplete: function(id, fileName, responseJSON) {
                    var entryId = $(responseJSON).find('id').text();
                    $('#form_entryid').val(entryId);
                    console.log("Video subido correctamente ID: " + entryId);
                    return that.options.onComplete(id, fileName, responseJSON);
                },
                onUpload: function(id, fileName, xhr) {
                    that.resizePopup();
                    return that.options.onUpload(id, fileName, xhr);
                },
                onProgress: function(id, fileName, loaded, total) {
                    if (loaded !== total){
                        $( "#progressbar .bar" ).css('width', Math.round(loaded / total * 100)+'%');
                    } else {
                        $( "#progressbar .bar" ).css('width','100%');
                    }
                    that.resizePopup();
                    return that.options.onProgress(id, fileName, loaded, total);
                },
                onError: function(id, fileName, reason) {
                    return that.options.onError(id, fileName, reason);
                }
            });
        },
        getToken: function(filename, callback) {
            var that = this;
            that.getKs(function(ks){
                console.log("getKs: " + that.options.ks)
                that.getMediaId("filename", function(entryId) {
                    console.log("getMediaId: " + that.options.entryId)
                    $.ajax({
                        url: 'http://www.kaltura.com/api_v3/index.php',
                        data: {
                            service: 'uploadToken',
                            action: 'add',
                            ks: that.options.ks,
                            'uploadToken:fileName': filename,
                            'uploadToken:objectType': 'KalturaUploadToken'
                        },
                        dataType: 'xml',
                        success: function(responseJSON) {
                            if($(responseJSON).find('id').text()) {
                                console.log(responseJSON)
                                that.options.uploadtoken = $(responseJSON).find('id').text();
                                callback(that.options.uploadtoken);
                            } else {
                                that.options.onError($(responseJSON).find('error').text());
                            }
                        },
                        error: function (jqXHR, status, error) {
                            return that.options.onError(error);
                        }
                    });
                });
            });
        },
        getKs: function(callback)
        {
            var that = this;
            $.ajax({
                url: Routing.generate(appLocale + '_video_kaltura_ks'),
                dataType: 'json',
                success: function(responseJSON) {
                    that.options.ks = responseJSON.ks;
                    callback(that.options.ks);
                },
                error: function (jqXHR, status, error) {
                    return that.options.onError(error);
                }
            });
        },
        getMediaId: function(fileName, callback) {
            var that = this;
                $.ajax({
                    url: 'http://www.kaltura.com/api_v3/index.php',
                    data: {
                        service: 'media',
                        action: 'add',
                        ks: that.options.ks,
                        'entry:name': fileName,
                        'entry:objectType': 'KalturaMediaEntry',
                        'entry:mediaType': 1
                    },
                    dataType: 'xml',
                    success: function(responseJSON) {
                        if($(responseJSON).find('id').text()) {
                            that.options.entryId = $(responseJSON).find('id').text();
                            callback(that.options.entryId);
                        } else {
                            that.options.onError($(responseJSON).find('error').text());
                        }
                    },
                    error: function (jqXHR, status, error) {
                        return that.options.onError(error);
                    }
                });
        },
        resizePopup: function() {
            window.top.resizeColorbox({innerHeight: $('.popup-content').height() });
        },
        destroy: function() {
            var that = this;
            $(that.element).unbind("destroyed", that.teardown);
            that.teardown();
        },
        teardown: function() {
            var that = this;
            $.removeData($(that.element)[0], that._name);
            $(that.element).removeClass(that._name);
            $(that.element).removeClass('cboxElement').removeData('colorbox');
            that.unbind();
            //$.colorbox.remove();
            that.element = null;
        },
        bind: function() {  },
        unbind: function() {  }
    };

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[pluginName] = function (options) {
        return this.each(function () {
            if (!$.data(this, pluginName)) {
                $.data(this, pluginName, new Plugin(this, options));
            }
        });
    };

});