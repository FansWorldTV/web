/*global ExposeTranslation, $, jQuery, alert, FormData, FileReader, escape, console, error, success, endless, ajax, templateHelper, qq, Routing, appLocale, exports, module, require, define*/
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

// fansWorld file upload plugin 2.0 with youtube link catching
// 1.9 backend listeners
// 1.8 (new frontend with bootstrap)
// 1.7 (new XHR backend)
// 1.6 (auto resize with a timer)

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
            video: ['flv', 'f4v', 'mov', 'mp4', 'qt', 'm4v', 'avi', '3gp', 'asf', 'wmv', 'mpg', 'm1v', 'm2v', 'mkv', 'ogg', 'rm', 'web'],
            audio: ['wav', 'mp3', 'ogg', 'midi'],
            all: ['jpg', 'jpeg', 'png', 'gif', 'flv', 'f4v', 'mov', 'mp4', 'qt', 'm4v', 'avi', '3gp', 'asf', 'wmv', 'mpg', 'm1v', 'm2v', 'mkv', 'ogg', 'rm', 'web']
        },
        allowedExtensions: null,
        action: {
            photo: Routing.generate(appLocale + '_photo_fileupload'),   // link for photos
            video: 'http://www.kaltura.com/api_v3/index.php'            // link for videos
        },
        timer: null,
        mediaType: null,
        isModal: true,  // Plugin will use a modal made with colorbox
        imageType: null, // for profile and splash picture upload
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
            that.options.imageType = $(that.element).attr('data-type');

            $(that.element).bind("destroyed", $.proxy(that.teardown, that));
            if(that.options.isModal === 'false') {
                that.options.uploaderSelector = '#' + $(that.element).attr('data-uploader-selector');
                that.createWithBootstrap();
                return;
            }
            if(that.options.mediaType === 'photo') {
                $(that.element).on('click', function(event) {
                    that.createFwPhotoUploader();
                    return false;
                });
            }
            return;
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
        },
        getImage: function(file) {
            var reader = new FileReader();
            var deferred = new jQuery.Deferred();
            reader.onload = function(event) {
                var img = new Image();
                img.onload = function(event) {
                    deferred.resolve(img);
                };
                img.src = event.target.result;
                img.alt = file.name;
                img.title = escape(file.name);

                var result = event.target.result;
                var fileName = file.name; //Should be 'picture.jpg'
            };
            reader.readAsDataURL(file);
            return deferred.promise();
        },
        placeImage: function(imgObj, container) {
            var imageAspectRatio = imgObj.height / imgObj.width;
            var containerAspectRatio = container.height() / container.width();
            // figure out which dimension hits first and set that to match
            if (imageAspectRatio > containerAspectRatio) {
                imgObj.style.height = container.height() + "px";
            } else {
                imgObj.style.width = container.width() + "px";
                var mul = imgObj.width / container.width();
                var offset = (imgObj.height / mul) / 2;
                imgObj.style.top = '50%';
                imgObj.style.marginTop = '-' + offset + 'px';
            }
            container.append(imgObj);
        },
        createFwPhotoUploader: function() {
            var that = this;
            // Allow all media extensions
            that.options.allowedExtensions = that.options.mediaExtensions.all;

            var boot = null;
            var id = parseInt((Math.random() * 1000), 10);
            var modal = {
                modalId: id,
                modalLabel: 'label',
                modalTitle: 'Compartir',
                modalBody: 'Uploader'
            };
            var uploader = new window.UPLOADER({
                element: $(that.options.uploaderSelector)[0],
                multiple: false,
                autoUpload: true,
                action: that.options.action[that.options.mediaType],
                maxConnections: 1,
                allowedExtensions: that.options.mediaExtensions.all,
                onLoadStart: function(event) {
                    return;
                },
                onProgress: function(event) {
                    return;
                },
                onComplete: function(event) {
                    return;
                }
            });
            function processFiles(files) {
                var i;

                function onProgress(event) {
                    var percentComplete = parseInt(((event.source.loaded / event.source.total) * 100), 10);
                    boot.find('.progress .bar').css('width', percentComplete + '%');
                    $('progress').val(percentComplete);
                }
                function onImageUploadComplete(event) {
                    var xhr = event.target.xhr;
                    var data = JSON.parse(xhr.responseText);
                    var formHtml = null;
                    var href = Routing.generate(appLocale + '_photo_filemeta', {
                        'originalFile': data.originalFile,
                        'tempFile': data.tempFile,
                        'width': data.width,
                        'height': data.height
                    });
                    $.ajax({url: href, type: 'GET'}).then(function(response){
                        formHtml = $(response).clone();
                        formHtml.find('input[type="submit"]').hide();

                        boot.find('.modal-body').html(formHtml);

                        // album actions
                        that.bindAlbumActions();

                        boot.find("#modal-btn-save").removeAttr("disabled");
                        // Set default title
                        boot.find("#form_title").val(data.originalFile);

                        boot.find("#modal-btn-save").one("click", null, null, function(){
                            $(this).addClass('loading-small');
                            boot.find('form').find('input[type="submit"]').click();
                        });
                        boot.find('form').submit(function() {
                            var data = $(this).serializeArray();
                            var action = $(this).attr('action');
                            boot.find('form').find('input[type="submit"]').addClass('loading-small');
                            $.ajax({
                                url: this.getAttribute('action'),
                                data: data,
                                type: 'POST'
                            })
                            .then(function(response){
                                location.reload();
                            });
                            return false;
                        });
                    });
                }
                function getVideoUploadForm(name, description, category, tags, sharewith) {
                    var formHtml = null;
                    var href = Routing.generate(appLocale + '_video_fileupload');
                    $.ajax({url: href, type: 'GET'}).then(function(response){
                        formHtml = $(response).clone();
                        formHtml.find('input[type="submit"]').hide();
                        boot.find('.modal-body').html(formHtml);
                        //boot.find("#modal-btn-save").removeAttr("disabled");
                        // Set default title
                        boot.find("#form_title").val(name);

                        boot.find("#modal-btn-save").one("click", null, null, function(){
                            $(this).addClass('loading-small');
                            boot.find('form').find('input[type="submit"]').click();
                        });

                        boot.find('form').submit(function() {
                            var data = $(this).serializeArray();
                            var action = $(this).attr('action');
                            var method = $(this).attr('method');
                            boot.find('form').find('input[type="submit"]').addClass('loading-small');
                            $.ajax({
                                url: this.getAttribute('action'),
                                data: data,
                                type: method
                            })
                            .then(function(response){
                                location.href = Routing.generate(appLocale + '_things_videos');
                            });
                            return false;
                        });

                    });                    
                }
                function onVideoUploadComplete(event) {
                    var xhr = event.target.xhr;
                    var name = $(xhr.responseText).find('name').text();
                    var entryId = $(xhr.responseText).find('id').text();

                    console.log("Video subido correctamente ID: " + entryId);

                    boot.find('#form_entryid').val(entryId);
                    boot.find("#modal-btn-save").one("click", null, null, function(){
                        $(this).addClass('loading-small');
                        boot.find('form').find('input[type="submit"]').click();
                    });
                    boot.find('form').submit(function() {
                        var data = $(this).serializeArray();
                        var action = $(this).attr('action');
                        var method = $(this).attr('method');
                        boot.find('form').find('input[type="submit"]').addClass('loading-small');
                        $.ajax({
                            url: this.getAttribute('action'),
                            data: data,
                            type: method
                        })
                            .then(function(response){
                                location.reload();
                            });
                        return false;
                    });
                    // Enable saving
                    boot.find("#modal-btn-save").removeAttr("disabled");
                    return;
                }
                // Image files
                uploader.addListener('onprogress', onProgress);
                uploader.addListener('oncomplete', onImageUploadComplete);
                // Video files
                videoUploader.addListener('onprogress', onProgress);
                videoUploader.addListener('oncomplete', onVideoUploadComplete);

                for(i = 0; i < files.length; i += 1) {
                    if(!files.hasOwnProperty(i)) {
                        return false;
                    }
                    var file = files[i];
                    if(!that.isAllowedExtension(file.name)) {
                        //boot.modal('hide');
                        alert("Archivo de extensión inválida");
                        boot.find('#drop_zone').animate({ 'background-color': 'transparent', 'border-color': '#bbb' } );
                        continue;
                    }
                    boot.find('#drop_zone').hide();
                    boot.find('#youtube_share').hide();
                    if (file.type.match('image.*')) {
                        ///////////////////////////////////// IMAGES //
                        uploader.addFile(file);

                        $.when(that.getImage(file))
                        .then(function(image){
                            var container = null;
                            var infobox = null;
                            var uploadBtt = $("<button class='btn upload'>upload</button>");
                            if(files.length > 1) {
                                container = $("<div class='thumbnail' style='width:64px;height:64px;'></div>");
                                infobox = $("<div class='fileinfo' style='height:64px;'></div>")
                                .append("<h5 class='title'>" + image.alt + "</h5>")
                                .append("<div class='progress progress-striped active' style='margin-top:4px;'><div class='bar' style='width: 0%;'></div></div>")
                                .append(uploadBtt);
                            } else {
                                container = $("<div class='thumbnail' style='width: 256px;height:256px;'></div>");
                                infobox = $("<div class='fileinfo' style='width: 200px;''></div>")
                                .append("<h5 class='title'>" + image.alt + "</h5>")
                                .append("<div class='progress progress-striped active' style='margin-top:10px;'><div class='bar' style='width: 0%;'></div></div>")
                                .append("<div class='well'>"+ "file: " + image.alt + "<br /> size: " + file.size +"</div>")
                                .append(uploadBtt);
                            }
                            uploadBtt.one("click", null, null, function(){
                                console.log("upload button clicked");
                                uploader.start();
                            });
                            that.placeImage(image, container);
                            var cosa = $("<li></li>").append(container).append(infobox);
                            boot.find('output ul').append(cosa);
                            uploader.start();
                        });
                    } else if (file.type.match('video.*')) {
                        ///////////////////////////////////// VIDEO //
                        $.when(that.getKs())
                        .then(function(ks) {
                            var dfd = new jQuery.Deferred();
                            $.when(that.getMediaId(file.name, ks), that.getUploadToken(file.name, ks))
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
                            videoUploader.addFile(file, {
                                service: 'media',
                                action: 'addContent',
                                entryId: that.options.entryId,
                                ks: that.options.ks,
                                'resource:objectType': 'KalturaUploadedFileResource'
                            });
                            videoUploader.start();
                            $('progress').show();
                            getVideoUploadForm(file.name);
                            /*
                            $.when(templateHelper.htmlTemplate('general-progress_modal', modal))
                            .then(function(html) {
                                var progress = $(html).find('.modal-body').clone();
                                boot.find('.modal-body').html(progress);
                            });
                            */
                        })
                        .fail(function (error) {
                            return error;
                        });
                    }
                }
            }
            function hookForm(dialog) {
                // Remove spinner
                dialog.find("#modal-btn-save").removeClass('loading-small');
                // Enable native dialog button
                dialog.find("#modal-btn-save").removeAttr("disabled");
                // Hide dialog submit
                dialog.find('input[type="submit"]').hide();
                // Passthrough
                dialog.find("#modal-btn-save").one("click", null, null, function(){
                    $(this).addClass('loading-small');
                    dialog.find('form').find('input[type="submit"]').click();
                });
                dialog.find('form').submit(function(event) {
                    event.preventDefault();
                    var data = $(this).serializeArray();
                    var action = $(this).attr('action');
                    var method = $(this).attr('method');
                    $.ajax({
                        url: this.getAttribute('action'),
                        data: data,
                        type: method
                    })
                    .then(function(response){
                        // Remove spinner
                        dialog.find("#modal-btn-save").removeClass('loading-small');
                        // Process all forms
                        var formHtml = $(response).clone();
                        dialog.find('.modal-body').html(formHtml);
                        console.log(formHtml.find('form').length);
                        if (formHtml.find('form').length) {
                            hookForm(dialog);
                        } else {
                            dialog.find("#modal-btn-save").text('continuar');
                            // No more forms ? ok then we're done
                            dialog.find("#modal-btn-save").one("click", null, null, function(){
                                $(this).addClass('loading-small');
                                location.href = Routing.generate(appLocale + '_things_videos');
                            })
                        }
                    });
                    return false;
                });
            }
            var videoUploader = new window.UPLOADER({
                element: $(that.options.uploaderSelector)[0],
                autoUpload: true,
                multiple: false,
                forceMultipart: true,
                normalHeaders: false,
                responsePassthrough: true,
                action: that.options.action.video,
                maxConnections: 1,
                inputName: 'resource:fileData',
                allowedExtensions: that.options.mediaExtensions.all,
                onLoadStart: function(event) {
                    return;
                },
                onProgress: function(event) {
                    return;
                },
                onComplete: function(event) {
                    return;
                },
                onError: function(error) {
                    return error;
                }
            });
            $.when(templateHelper.htmlTemplate('general-upload_modal', modal)).then(function(html) {
                boot = $(html).clone();

                boot.find('[data-youtubeshare]').on('click', function() {
                    var self = $(this);
                    self.addClass('loading-small');
                    $('[data-dropdownshare]').addClass("dropdown open");
                    var youtube_link = $('[data-youtubelink]').val();

                    if (checkYoutubeUrl(youtube_link)) {
                        shareStatusUpdate('', '#a0c882');
                        $('[data-youtubelink]').val('');
                        $('[data-dropdownshare]').addClass("dropdown");
                        var link = Routing.generate(appLocale + '_video_youtubeupload', {link: youtube_link});
                        $.ajax({url: link, type: 'GET'}).
                        then(function(response) {
                            self.removeClass('loading-small');
                            console.log('VIDEO DE YOUTUBE SUBIDO');

                            var formHtml = $(response).clone();
                            formHtml.find('input[type="submit"]').hide();

                            boot.find('.modal-body').html(formHtml);

                            hookForm(boot);
                            return;

                            boot.find("#modal-btn-save").removeAttr("disabled");

                            boot.find("#modal-btn-save").one("click", null, null, function(){
                                $(this).addClass('loading-small');
                                boot.find('form').find('input[type="submit"]').click();
                            });

                            boot.find('form').submit(function(event) {
                                event.preventDefault();
                                var data = $(this).serializeArray();
                                var action = $(this).attr('action');
                                var method = $(this).attr('method');
                                boot.find('form').find('input[type="submit"]').addClass('loading-small');
                                $.ajax({
                                    url: this.getAttribute('action'),
                                    data: data,
                                    type: method
                                })
                                .then(function(response){
                                    //location.reload();
                                    //console.log(response)
                                    boot.find('.modal-body').html(response);
                                });
                                return false;
                            });
                        })
                    } else {
                        console.log('Invalid Youtube Link');
                        $('[data-youtubelink]').val('');
                        shareStatusUpdate('Link invalido', 'red');
                    }

                    function shareStatusUpdate(text, color) {
                        $('[data-sharestatus-text]').html(text);
                        $('[data-sharestatus-text]').attr('style', 'color:' + color);
                    }

                    function checkYoutubeUrl(url) {
                        var p = /^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((\w|-){11})(?:\S+)?$/;
                        return (url.match(p)) ? true : false;
                    }
                    return false;
                });

                boot.find('input[type="file"]').on('change', function(event) {
                    var i;
                    var files = event.target.files; // FileList object
                    var file = null;
                    processFiles(files);

                    // Loop through the FileList and render image files as thumbnails.
                    for (i = 0; i < files.length; i += 1) {
                        // Only process image files.
                        file = files[i];
                        if (!file.type.match('image.*')) {
                            continue;
                        } else {
                            $.when(that.getImage(file))
                            .then(function(image){
                                console.log(file.name);
                            });
                        }
                    }
                });
                boot.find('#drop_zone')
                .on('dragenter', function(event) {
                    if(event.target === this) {
                        $(this).animate({ 'background-color': '#c0c0c0', 'border-color': '#444' } );
                        console.log('dragenter');
                    }
                }).on('dragover', function(event) {
                    event.stopPropagation();
                    event.preventDefault();
                    if(event.target === this) {
                        event.originalEvent.dataTransfer.dropEffect = 'copy';
                    }
                }).on('dragleave', function(event) {
                    if(event.target === this) {
                        $(this).animate({ 'background-color': 'transparent', 'border-color': '#bbb' } );
                        console.log('dragleave');
                    }
                }).on('drop', function(event) {
                    var i;
                    event.stopPropagation();
                    event.preventDefault();
                    if(event.target === this) {
                        var files = event.originalEvent.dataTransfer.files;
                        processFiles(files);
                        return;
                    }
                });
                boot.find("#modal-btn-close").one("click", null, null, function(){
                    boot.modal('hide');
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                    uploader.stopAll();
                    videoUploader.stopAll();
                });
                boot.modal({
                    backdrop: true
                }).css({
                    width: '700px',
                    'margin-left': '-350px'
                }).on('hide', function() {
                    uploader.stopAll();
                    videoUploader.stopAll();
                    $('.modal-backdrop').remove();
                    $(this).data('modal', null);
                    $(this).remove();
                });
            });
            return false;
        },
        onDrop: function(event) {
            var that = this;
            if(event.target === this) {
                var files = event.originalEvent.dataTransfer.files;
                processFiles(files);
            }
        },
        createInput: function(){
            var that = this;
            var input = document.createElement("input");

            if (that.options.multiple){
                input.setAttribute("multiple", "multiple");
            }

            if (that.options.acceptFiles) {
                input.setAttribute("accept", that.options.acceptFiles);
            }

            input.setAttribute("type", "file");
            input.setAttribute("name", that.options.name);

            $(input).css({
                position: 'absolute',
                // in Opera only 'browse' button
                // is clickable and it is located at
                // the right side of the input
                right: 0,
                top: 0,
                fontFamily: 'Arial',
                // 4 persons reported this, the max values that worked for them were 243, 236, 236, 118
                fontSize: '118px',
                margin: 0,
                padding: 0,
                cursor: 'pointer',
                opacity: 0
            });

            //$(that.options.uploaderSelector)[0].appendChild(input);
            $(that.element).append(input);

            // IE and Opera, unfortunately have 2 tab stops on file input
            // which is unacceptable in our case, disable keyboard access
            if (window.attachEvent){
                // it is IE or Opera
                input.setAttribute('tabIndex', "-1");
            }

            return input;
        },
        createWithBootstrap: function() {
            var that = this;
            var input = that.createInput();
            var boot = null;

            console.log("createWithBootstrap()");

            var uploader = new window.UPLOADER({
                element: $(that.options.uploaderSelector)[0],
                multiple: false,
                autoUpload: true,
                action: that.options.action[that.options.mediaType],
                maxConnections: 1,
                allowedExtensions: that.options.mediaExtensions[that.options.mediaType],
                onLoadStart: function(event) {
                    var id = parseInt((Math.random() * 1000), 10);
                    var modal = {
                        modalId: id,
                        modalLabel: 'label',
                        modalTitle: 'Upload Photo',
                        modalBody: 'Uploader'
                    };

                    $.when(templateHelper.htmlTemplate('general-progress_modal', modal))
                    .then(function(html) {
                        boot = $(html).clone();
                        boot.find("#modal-btn-close").one("click", null, null, function(){
                            boot.modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();
                            uploader.stopAll();
                        });
                        boot.modal({
                            //backdrop: false
                        })
                        .css({
                            width: '700px',
                            'margin-left': '-350px'
                        })
                        .on('hide', function() {
                            console.log("modal hide");
                            $('.modal-backdrop').remove();
                            $(this).data('modal', null);
                            $(this).remove();
                        });
                    });
                },
                onProgress: function(event) {
                    var percentComplete = parseInt(((event.source.loaded / event.source.total) * 100), 10);
                    $('progress').val(percentComplete);
                    console.log("progress: " + percentComplete + "%");
                },
                onComplete: function(event) {
                    var xhr = event.target.xhr;
                    var data = JSON.parse(xhr.responseText);
                    var formHtml = null;
                    var entity = $(that.element).attr('data-entity-type');
                    var route = null;
                    var ajaxData = {
                        'originalFile': data.originalFile,
                        'tempFile':data.tempFile,
                        'width': data.width,
                        'type': that.options.imageType,
                        'height': data.height
                    };
                    switch(entity) {
                        case 'idol':
                            route = '_idol_change_imageSave';
                            ajaxData.idol = $(that.element).attr('data-entity-id');
                            break;
                        case 'team':
                            route = '_team_change_imageSave';
                            ajaxData.team = $(that.element).attr('data-entity-id');
                            break;
                        case 'user':
                            route = '_user_change_imageSave';
                            break;
                    }
                    var href = Routing.generate(appLocale + route, ajaxData);
                    $.ajax({url: href, type: 'GET'}).then(function(response){
                        formHtml = $(response).clone();
                        boot.find('.modal-body').html(formHtml);
                        boot.find("#modal-btn-save").one("click", null, null, function(){
                            $(this).addClass('loading-small');
                            boot.find('form').find('input[type="submit"]').click();
                        });
                        boot.find('form').submit(function() {
                            var data = $(this).serializeArray();
                            var action = $(this).attr('action');
                            console.log("onsubmit");
                            console.log(this.getAttribute('action'));
                            boot.find('form').find('input[type="submit"]').addClass('loading-small');
                            $.ajax({
                                url: this.getAttribute('action'),
                                data: data,
                                type: 'POST'
                            })
                            .then(function(response){
                                location.reload();
                            });
                              return false;
                        });
                    });
                }
            });

            // Attach file browsing event
            $(input).on('change', function(event) {
                uploader.addFiles(event.target.files);
            });
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
        isAllowedExtension: function(fileName){
            var i;
            var that = this;
            var ext = (-1 !== fileName.indexOf('.')) ? fileName.replace(/.*[.]/, '').toLowerCase() : '';
            var allowed = this.options.allowedExtensions;

            if (!allowed.length){return true;}

            for (i = 0; i < allowed.length; i += 1){
                if (allowed[i].toLowerCase() === ext){ return true;}
            }

            return false;
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