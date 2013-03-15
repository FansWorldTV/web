/*global $, jQuery, alert, console, error, success, endless, ajax, templateHelper, qq, Routing, appLocale, exports, module, require, define*/
/*jslint nomen: true */ /* Tolerate dangling _ in identifiers */
/*jslint vars: true */ /* Tolerate many var statements per function */
/*jslint white: true */
/*jslint browser: true */
/*jslint devel: true */ /* Assume console, alert, ... */
/*jslint windows: true */ /* Assume Windows */
/*jslint maxerr: 100 */ /* Maximum number of errors */

/*
 * library dependencies:
 *      jquery 1.8.3
 *      fos-routing
 *      fileUploader
 *      colorBox
 * external dependencies:
 *      appLocale
 *      ExposeTranslation
 */

/*jslint browser: true*/
/*global $, jQuery, alert, console, error, success, ajax, templateHelper, Routing, appLocale*/
/*jslint vars: true */ /* Tolerate many var statements per function */
/*jslint maxerr: 100 */ /*  Maximum number of errors */

// fansWorld file upload plugin 1.6 (auto resize with a timer)

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
            video: ['avi', 'mov', 'mpeg', 'mp4', '3gp'],
            audio: ['wav', 'mp3', 'ogg', 'midi']
        },
        action: {
            photo: Routing.generate(appLocale + '_photo_fileupload'),   // link for photos
            video: 'http://www.kaltura.com/api_v3/index.php'            // link for videos
        },
        timer: null,
        mediaType: null,
        isModal: true,  // Plugin will use a modal made with colorbox
        uploaderSelector: '#file-uploader',
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
            that.options.isModal = $(that.element).attr('data-ismodal');
            $(that.element).bind("destroyed", $.proxy(that.teardown, that));
            console.log("data-upload: " + that.options.mediaType + " isModal: " + that.options.isModal)
            if(that.options.isModal === 'false') {
                console.log("sale is modal")
                that.options.uploaderSelector = "#avatar-uploader";
                that.createFwImageUploader();
                return;
            };
            $(that.element).colorbox({
                innerWidth: 700,
                innerHeight: 475,
                onComplete: function() {
                    if(that.options.mediaType === 'video')
                    {
                        that.createFwVideoUploader();
                        that.resizePopup();
                        $.when(that.getKs())
                        .then(function(ks) {
                            var dfd = new jQuery.Deferred();
                            $.when(that.getMediaId('filename', ks), that.getUploadToken('filename', ks))
                            .then(function (mediaId, token){
                                return {kalturaKeys: [that.options.ks, mediaId, token]};
                            })
                            .done(function (kaltura){
                                console.log(JSON.stringify(kaltura));
                                dfd.resolve(kaltura);
                            })
                            .fail(function (error) {
                                that.options.onError(error);
                                dfd.reject(new Error(error));
                            });
                            return dfd.promise();
                        })
                        .done(function (kaltura) {
                            console.log("ks: %s id: %s tk: %s", JSON.stringify(kaltura));
                        })
                        .fail(function (error) {
                            that.options.onError(error);
                        });
                    } else {
                        that.createFwPhotoUploader();
                        that.resizePopup();
                    }
                },
                onClosed: function() {
                    that.uploader._handler.cancelAll();
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
            //$('#cboxLoadedContent').find("form") <--- suck it from above
            $("form.upload-" + that.options.mediaType).submit(function() {
                var data = $(this).serializeArray();
                var href = $(this).attr('action');
                $.colorbox({
                    href: href ,
                    data: data,
                    onComplete: function(){
                        //that.bindFormActions();
                        //that.resizePopup();
                    }
                });
                return false;
            });
        },
        bindFormActions: function() {
            var that = this;
            that.bindFormSubmit();
            that.bindAlbumActions();
            that.options.timer = setInterval(function(){ that.resizePopup(); }, 250);
            console.log("autosize popup")
        },
        createFwPhotoUploader: function() {
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
                            innerHeight: 160,
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
        createFwImageUploader: function() {
            var that = this;
            var list = $('<ul class="qq-upload-list"></ul>');
            var uploader = new qq.FileUploader({
                element: $(that.options.uploaderSelector)[0],
                action: that.options.action[that.options.mediaType],
                debug: true,
                multiple: false,
                maxConnections: 1,
                allowedExtensions: that.options.mediaExtensions[that.options.mediaType],
                disableDefaultDropzone: true,
                template: '<div class="qq-uploaderX">' +
                    '<div class="qq-upload-buttonXX btn btn-success">{uploadButtonText}</div>' +
                    '<ul class="qq-upload-list" style="margin-top: 10px; text-align: center;visibility:hidden;"></ul>' +
                    '</div>',
                fileTemplate: '<li>' +
                    '<div class="qq-progress-bar"></div>' +
                    '<span class="qq-upload-finished"></span>' +
                    '<span class="qq-upload-file"></span>' +
                    '<span class="qq-upload-size"></span>' +
                    '<a class="qq-upload-cancel" href="#">{cancelButtonText}</a>' +
                    '<span class="qq-upload-failed-text">{failUploadtext}</span>' +
                    '</li>',
                classes: {
                    // used to get elements from templates
                    button: 'qq-upload-buttonXX',
                    drop: 'qq-upload-drop-area',
                    dropActive: 'qq-upload-drop-area-active',
                    dropDisabled: 'qq-upload-drop-area-disabled',
                    list: 'qq-upload-list',
                    progressBar: 'qq-progress-bar',
                    file: 'qq-upload-file',
                    spinner: 'qq-upload-spinner',
                    finished: 'qq-upload-finished',
                    size: 'qq-upload-size',
                    cancel: 'qq-upload-cancel',
                    failText: 'qq-upload-failed-text',

                    // added to list item <li> when upload completes
                    // used in css to hide progress spinner
                    success: 'qq-upload-success',
                    fail: 'qq-upload-fail',

                    successIcon: null,
                    failIcon: null
                },
                onComplete: function(id, fileName, responseJSON) {
                    if(responseJSON.success) {
                        $.colorbox({
                            href: Routing.generate(appLocale + '_user_change_imageSave', {
                                'originalFile': responseJSON.originalFile,
                                'tempFile':responseJSON.tempFile,
                                'width': responseJSON.width,
                                'height': responseJSON.height
                            }),
                            iframe: false,
                            innerWidth: 700,
                            innerHeight: 260,
                            onComplete: function() {
                                that.resizePopup();
                                that.bindFormActions();
                            }
                        });
                    }
                    $('.qq-upload-buttonXX').removeClass('loading-small');
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
                    $('.qq-upload-buttonXX').addClass('loading-small');
                    return that.options.onSubmit(id, fileName);
                },
                onError: function(id, fileName, reason) {
                    $('.qq-upload-buttonXX').removeClass('loading-small');
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
                onSubmit: function(id, fileName){
                    that.uploader.setParams({
                        service: 'media',
                        action: 'addContent',
                        entryId: that.options.entryId,
                        ks: that.options.ks,
                        'resource:objectType': 'KalturaUploadedFileResource'
                    });
                    //$('.qq-uploader').hide();
                    var progress = '<div id="progressbar" class="progress progress-success progress-striped" style="margin:20px;height: 40px;"><div class="bar" style="height: 40px;margin: 0px;background-color: #68CE1D;border-radius: 4px;height: 40px"></div></div>';
                    $(".container-up").html('');
                    $(".container-up").html(progress);
                    return that.options.onSubmit(id, fileName);

                },
                onComplete: function(id, fileName, responseJSON) {
                    var entryId = $(responseJSON).find('id').text();
                    $('#form_entryid').val(entryId);
                    console.log("Video subido correctamente ID: " + entryId);
                    $(".container-up").replaceWith('<div class="alert alert-success">' + ExposeTranslation.get('upload_complete') + '</div>');
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
            window.uploader = that.uploader;
            return that.uploader;
        },
        getUploadToken: function (fileName, ks) {
            var that = this;
            var deferred = new jQuery.Deferred();
            $.ajax({
                url: 'http://www.kaltura.com/api_v3/index.php',
                data: {
                    service: 'uploadToken',
                    action: 'add',
                    ks: ks || that.options.ks,
                    'uploadToken:fileName': fileName,
                    'uploadToken:objectType': 'KalturaUploadToken'
                },
                dataType: 'xml' // Kaltura uses XML
            })
            .then(function (responseXML){
                if($(responseXML).find('id').text()) {
                    that.options.uploadtoken = $(responseXML).find('id').text();
                    return that.options.uploadtoken;
                } else {
                    that.options.onError($(responseXML).find('error').text());
                    deferred.reject($(responseXML).find('error').text());
                }
            })
            .done(function (token) {
                deferred.resolve(token);
            })
            .fail(function (error) {
                that.options.onError(error);
                deferred.reject(new Error(error));
            });
            return deferred.promise();
        },
        getKs: function() {
            var that = this;
            var deferred = new jQuery.Deferred();
            $.ajax({url: Routing.generate(appLocale + '_video_kaltura_ks')})
            .then(function (responseJSON) {
                that.options.ks = responseJSON.ks;
                return that.options.ks;
            })
            .done(function (ks){
                deferred.resolve(ks);
            })
            .fail(function (jqXHR, status, error) {
                deferred.reject(new Error(error));
            });
            return deferred.promise();
        },
        getMediaId: function(fileName, ks) {
            var that = this;
            var deferred = new jQuery.Deferred();
            $.ajax({
                url: 'http://www.kaltura.com/api_v3/index.php',
                data: {
                    service: 'media',
                    action: 'add',
                    ks: ks || that.options.ks,
                    'entry:name': fileName,
                    'entry:objectType': 'KalturaMediaEntry',
                    'entry:mediaType': 1
                },
                dataType: 'xml' // Kaltura uses XML
            })
            .then(function(responseXML) {
                if($(responseXML).find('id').text()) {
                    that.options.entryId = $(responseXML).find('id').text();
                    return that.options.entryId;
                } else {
                    that.options.onError($(responseXML).find('error').text());
                    deferred.reject(new Error($(responseXML).find('error').text()));
                }
            })
            .done(function (mediaId){
                deferred.resolve(mediaId);
            })
            .fail(function (jqXHR, status, error) {
                deferred.reject(new Error(error));
            });
            return deferred.promise();
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
            clearInterval(that.options.timer);
            $.removeData($(that.element)[0], that._name);
            $(that.element).removeClass(that._name);
            $(that.element).removeClass('cboxElement').removeData('colorbox');
            that.unbind();
            //$.colorbox.remove();
            that.element = null;
        },
        bind: function() { },
        unbind: function() { }
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