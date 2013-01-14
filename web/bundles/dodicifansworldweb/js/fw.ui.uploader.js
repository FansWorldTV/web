/*
 * library dependencies:
 *      jquery 1.8.3
 *      fos-routing
 *      fileUploader
 *      colorBox
 * external dependencies:
 *      appLocale
 */

// fansWorld file upload plugin

// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
//;(function ($, window, document, undefined) {
$(document).ready(function ($, window, document, undefined) {

    "use strict";

    // undefined is used here as the undefined global variable in ECMAScript 3 is
    // mutable (ie. it can be changed by someone else). undefined isn't really being
    // passed in so we can ensure the value of it is truly undefined. In ES5, undefined
    // can no longer be modified.

    // window and document are passed through as local variable rather than global
    // as this (slightly) quickens the resolution process and can be more efficiently
    // minified (especially when both are regularly referenced in your plugin).

    // Create the defaults once
    var pluginName = "fwUploader";
    var defaults = {
        propertyName: "fansworld",
        uploadtoken: null,
        mediaExtensions: {
            photo: ['jpg', 'jpeg', 'png', 'gif'],
            video: ['avi', 'mov', 'mpeg'],
            audio: ['wav', 'mp3', 'ogg', 'midi']
        },
        action: {
            photo: Routing.generate(appLocale + '_photo_fileupload'),
            video: 'http://www.kaltura.com/api_v3/index.php'
        },
        mediaType: null,
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
            console.log(that.options.mediaType);
            $(that.element).bind("destroyed", $.proxy(that.teardown, that));
            $(that.element).colorbox({
                innerWidth: 700,
                innerHeight: 475,
                onComplete: function() {
                    if(that.options.mediaType == 'video')
                    {
                        that.getToken('fileName', function(uploadToken){
                            console.log("token: " + uploadToken)
                            that.createFwGenericUploader();
                            that.uploader.setParams({uploadTokenId: that.options.uploadtoken});
                        });
                    } else {
                        that.createFwGenericUploader();
                    }
                    that.resizePopup();
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
            $('#form_album_new_name').parents('.control-group').slideDown('fast', function(){
                that.resizePopup();
            });
        },
        bindFormSubmit: function() {
            var that = this;
            // TODO chupar el form adentro del colorbox
            $("form.upload-photo").submit(function(){
                var data = $('form.upload-photo').serializeArray();
                var href = $('form.upload-photo').attr('action');
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
                forceMultipart: true,
                normalHeaders: false,
                responsePassthrough: true,
                inputName: 'resource:fileData',
                failedUploadTextDisplay: {mode: 'none'},
                allowedExtensions: that.options.mediaExtensions[that.options.mediaType],
                onComplete: function(id, fileName, responseJSON) {
                    if(responseJSON.success) {
                        $.colorbox({
                            href: Routing.generate(appLocale + '_photo_filemeta',
                                {
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
                },
                onUpload: function() {
                    // entry Kaltura
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
                    // TODO
                    // call Kalutra get upload keys
                    console.log("onSubmit")
                    switch(that.options.mediaType)
                    {
                        case 'video':
                            that.getToken(fileName, function(uploadToken){
                                uploader.setParams({
                                    uploadTokenId: uploadToken
                                    service: 'media',
                                    action: 'addContent',
                                    entryId: '',
                                    ks: 'NzQxNDRhZDViNmNlYzRhOGRhNWY3ZDgyYjNlOWQ1OTgzYjk1NzEzNHwxMTY0ODMyOzExNjQ4MzI7MTM1Nzk5NDA0OTsyOzE3ODU7RmFuc3dvcmxkQWRtaW47',
                                    'resource:objectType': 'KalturaUploadedFileResource'
                                });
                            });
                            break;
                    }
                    that.options.onSubmit(id, fileName);
                    return false;
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
                failedUploadTextDisplay: {mode: 'none'},
                onSubmit : function(file, ext){
                    that.uploader.setParams({
                        inputName: 'resource:fileData',
                        service: 'media',
                        action: 'addContent',
                        entryId: $('#entryid').val(),
                        ks: 'MjkyOTNmOGEyZmY1MzJmODA0MDZhMmY2OTdhZGMyODAzNTRmNjhjMnwxMTY0ODMyOzExNjQ4MzI7MTM1ODAxMTY2MDsyOzI1NTI7RmFuc3dvcmxkQWRtaW47',
                        'resource:objectType': 'KalturaUploadedFileResource'
                    });
                },
                onComplete: function(id, fileName, xml){
                    var xmlDoc = $.parseXML(xml);
                    var $xml = $( xmlDoc );
                    //var result = $xml.find( "result" );
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
                failedUploadTextDisplay: {mode: 'none'},
                onSubmit: function(id, fileName){
                    that.uploader.setParams({
                        inputName: 'resource:fileData',
                        service: 'media',
                        action: 'addContent',
                        entryId: $('#entryid').val(),
                        ks: 'MjkyOTNmOGEyZmY1MzJmODA0MDZhMmY2OTdhZGMyODAzNTRmNjhjMnwxMTY0ODMyOzExNjQ4MzI7MTM1ODAxMTY2MDsyOzI1NTI7RmFuc3dvcmxkQWRtaW47',
                        'resource:objectType': 'KalturaUploadedFileResource'
                    });
                    that.options.onSubmit(id, fileName);
                },
                onComplete: function(id, fileName, responseJSON) {
                    that.options.onComplete(id, fileName, responseJSON);
                },
                onUpload: function(id, fileName, xhr) {
                    that.resizePopup();
                    that.options.onUpload(id, fileName, xhr);
                },
                onProgress: function(id, fileName, loaded, total) {
                    that.options.onProgress(id, fileName, loaded, total);
                },
            });
        },
        getToken: function(filename, callback) {
            var that = this;
            $.ajax({
                url: 'http://www.kaltura.com/api_v3/index.php',
                data: {
                    service: 'uploadToken',
                    action: 'add',
                    ks: 'MjkyOTNmOGEyZmY1MzJmODA0MDZhMmY2OTdhZGMyODAzNTRmNjhjMnwxMTY0ODMyOzExNjQ4MzI7MTM1ODAxMTY2MDsyOzI1NTI7RmFuc3dvcmxkQWRtaW47',
                    'uploadToken:fileName': filename,
                    'uploadToken:objectType': 'KalturaUploadToken'
                },
                dataType: 'xml',
                success: function(r) {
                    that.options.uploadtoken = $(r).find('id').text();
                    callback(that.options.uploadtoken);
                }
            });
        },
        resizePopup: function() {
            //window.top.resizeColorbox({innerHeight: $('.popup-content').height() });
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

}); //(jQuery, window, document);