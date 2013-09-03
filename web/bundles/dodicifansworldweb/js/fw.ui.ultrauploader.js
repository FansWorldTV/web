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
                    that.makeUploadForm();
                    return false;
                });
            }
            return;
        },
        makeUploadForm: function() {
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
            that.uploader = new window.UPLOADER({
                element: $(that.options.uploaderSelector)[0],
                multiple: false,
                autoUpload: false,
                action: that.options.action[that.options.mediaType],
                maxConnections: 1,
                allowedExtensions: that.options.mediaExtensions.all,
            });

            that.uploader.addListener('onprogress', that.onProgress);
            that.uploader.addListener('oncomplete', that.onVideoUploadComplete);

            // Create the modal
            $.when(templateHelper.htmlTemplate('general-ultraupload_modal', modal)).then(function(html) {
                boot = $(html).clone();
                that.handleModal(boot);
            });
        },
        onProgress: function(event) {
            var percentComplete = parseInt(((event.source.loaded / event.source.total) * 100), 10);
            console.log(percentComplete);
            //boot.find('.progress .bar').css('width', percentComplete + '%');
            //$('progress').val(percentComplete);
        },
        handleModal: function(modal) {
            var that = this;

            modal.find('[data-youtubelink]').on('change', function(event){
                event.stopPropagation();
                event.preventDefault();
                return false;
            });
            modal.find('[data-youtube-validate]').on('click', function(event){
                event.stopPropagation();
                event.preventDefault();
                var url = modal.find('[data-youtubelink]').val();
                $('.spinner-overlay').removeClass('hide');
                $.ajax({
                    url: Routing.generate(appLocale + '_ajax_getyoutubedata'),
                    data: {
                        url: url
                    }
                }).then(function(response){
                    if(response.metadata) {
                        modal.find('[data-title]').val(response.metadata.title.$t);
                        modal.find('[data-description]').val(response.metadata.content.$t);
                    }
                    modal.find('.spinner-overlay').addClass('hide');
                }).done(function(){
                     modal.find('.spinner-overlay').addClass('hide');
                }).fail(function(error){
                     modal.find('.spinner-overlay').addClass('hide');
                });
                return false;
            });
            modal.find('[data-youtubeshare]').on('click', function() {
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
                        });
                } else {
                    console.log('Invalid Youtube Link');
                    $('[data-youtubelink]').val('');
                    self.removeClass('loading-small');
                    shareStatusUpdate('Link invalido', 'red');
                    setTimeout(function() {
                        shareStatusUpdate('', '');
                    }, 1500);
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
            // Input Field
            modal.find('input[type="file"]').on('change', function(event) {
                var i;
                var files = event.target.files; // FileList object
                that.processFiles(files);
            });
            // Drag & Drop 
            modal.find('#drop_zone')
            .on('dragenter', function(event) {
                if(event.target === this) {
                    $(this).animate({ 'background-color': '#c0c0c0', 'border-color': '#444' } );
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
                event.stopPropagation();
                event.preventDefault();
                if(event.target === this) {
                    var files = event.originalEvent.dataTransfer.files;
                    that.processFiles(files);
                    return;
                }
            });
            modal.find('form').submit(function(event) {
                event.stopPropagation();
                event.preventDefault();

                return false;

                var data = $(this).serializeArray();
                var action = $(this).attr('action');
                var method = $(this).attr('method');
                $.ajax({
                    url: this.getAttribute('action'),
                    data: data,
                    type: method
                })
                .then(function(response){
                    console.log(response);
                });
                return false;
            });
            modal.modal({
                backdrop: true
            }).css({
                width: '600px',
                'margin-left': '-300px'
            }).on('hide', function() {
                that.uploader.stopAll();
                $('.modal-backdrop').remove();
                $(this).data('modal', null);
                $(this).remove();
            })

            return;     
        },
        processFiles: function(files) {
            var i = 0;
            for(i = 0; i < files.length; i += 1) {

                if(!files.hasOwnProperty(i)) {
                    return false;
                }
                var file = files[i];
                if(!that.isAllowedExtension(file.name)) {
                    alert("Archivo de extensión inválida");
                    boot.find('#drop_zone').animate({ 'background-color': 'transparent', 'border-color': '#bbb' } );
                    return false;
                }
                if (file.type.match('image.*')) {
                } else if (file.type.match('video.*')) {
                    $.when(that.getKalturaHanlder(file))
                    .then(function (metadata){
                        that.uploader.addFile(file, metadata);
                        videoUploader.start();
                    })
                }
            }
        },
        getKalturaHanlder: function(file) {
            var deferred = new jQuery.Deferred();

            $.when(that.getKs())
            .then(function(ks) {
                var dfd = new jQuery.Deferred();
                $.when(that.getMediaId(file.name, ks), that.getUploadToken(file.name, ks))
                    .then(function (mediaId, token){
                        return {kalturaKeys: [that.options.ks, mediaId, token]};
                    })
                    .done(function (kaltura){
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
                deferred.resolve({
                    service: 'media',
                    action: 'addContent',
                    entryId: that.options.entryId,
                    ks: that.options.ks,
                    'resource:objectType': 'KalturaUploadedFileResource'
                });
            })
            .fail(function (error) {
                return deferred.reject(new Error(error));
            });

            return deferred.promise();
        },
        checkYoutubeUrl: function(url) {
            var p = /^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((\w|-){11})(?:\S+)?$/;
            return (url.match(p)) ? true : false;
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