/*global                                                ////////////////////////
    $,                                                  //                    //
    jQuery,                                             //        2013        //
    FormData,                                           //      FANSWORD      //
    exports,                                            //        XHR         //
    module,                                             //      UPLOADER      //
    require,                                            //                    //
    define                                              ////////////////////////
*/
/*jslint nomen: true */                 /* Tolerate dangling _ in identifiers */
/*jslint vars: true */           /* Tolerate many var statements per function */
/*jslint white: true */                       /* tolerate messy whithe spaces */
/*jslint browser: true */                                  /* Target browsers */
/*jslint devel: true */                         /* Assume console, alert, ... */
/*jslint windows: true */               /* Assume window object (for browsers)*/

/*******************************************************************************
 * Class dependencies:                                                         *
 *      jquery > 1.8.3                                                         *
 * External dependencies:                                                      *
 *      FOS Routing                                                            *
 *      templateHelper                                                         *
 *      ExposeTranslation                                                      *
 ******************************************************************************/

////////////////////////////////////////////////////////////////////////////////
// Historia:                                                                  //
// --------                                                                   //
// 1.0 Initial version 21-Mar-2013                                            //
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// FansWorld XHR Uploader                                                     //
////////////////////////////////////////////////////////////////////////////////

(function (root, factory) {
    "use strict";
    if (typeof exports === 'object') {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like enviroments that support module.exports,
        // like Node.
        module.exports = factory(require('jQuery'), require('Routing'), require('templateHelper'), require('ExposeTranslation'));
    } else if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jQuery', 'Routing', 'templateHelper', 'ExposeTranslation'], factory);
    } else {
        // Browser globals (root is window)
        root.UPLOADER = factory(root.jQuery, root.Routing, root.templateHelper, root.ExposeTranslation);
    }
}(this, function (jQuery, Routing, templateHelper, ExposeTranslation) {
    "use strict";
    var UPLOADER = (function() {
        function UPLOADER() {
            ////////////////////////////////////////////////////////////////////
            // Constructor                                                    //
            ////////////////////////////////////////////////////////////////////
            var _self = this;
            this.jQuery = jQuery;
            this.version = '1.0';
            this.options = {
                // set to true to see the server response
                action: '/app_dev.php/photo/fileupload',
                protocol: 'POST',
                params: {},
                normalHeaders: true,
                customHeaders: {},
                multiple: true,
                maxConnections: 1,
                autoUpload: true,
                forceMultipart: false,
                // validation
                allowedExtensions: [],
                acceptFiles: null,      // comma separated string of mime-types for browser to display in browse dialog
                sizeLimit: 0,
                minSizeLimit: 0,
                stopOnFirstInvalidFile: true,
                delay: 500,
                paused: false,
                timeout_id: null,
                recent: [],
                // events
                // return false to cancel submit
                responsePassthrough: false,
                onSubmit: function(id, fileName){},
                onComplete: function(id, fileName, responseJSON){},
                onCancel: function(id, fileName){},
                onUpload: function(id, fileName, xhr){},
                onProgress: function(file, loaded, total){},
                onError: function(id, fileName, reason) {},
                // messages
                messages: {
                    typeError: "{file} has an invalid extension. Valid extension(s): {extensions}.",
                    sizeError: "{file} is too large, maximum file size is {sizeLimit}.",
                    minSizeError: "{file} is too small, minimum file size is {minSizeLimit}.",
                    emptyError: "{file} is empty, please select files again without it.",
                    noFilesError: "No files to upload.",
                    onLeave: "The files are being uploaded, if you leave now the upload will be cancelled."
                },
                showMessage: function(message){
                    alert(message);
                },
                debug: function(message){
                    console.log(message);
                },
                inputName: 'qqfile'
            };
            if (this.isSupported()) {
                this.options.debug("XHR Upload is supported");
            } else {
                this.options.debug("XHR Upload is not supported");
            }

            ////////////////////////////////////////////////////////////////////
            // Message Queue                                                  //
            ////////////////////////////////////////////////////////////////////
            this.queue = new this.Queue(this);
            this.activeConnections = 0;
        }
        UPLOADER.prototype.addFiles = function(files){
            var that = this;
            var i;
            for(i = 0; i < files.length; i += 1) {
                if(files.hasOwnProperty(i)) {
                    var file = files[i];
                    if (!that.validateFile(file)) {
                        continue;
                    } else {
                        this.queue.enqueue({
                            atom: {
                                id: that.guidGenerator(),
                                xhr: null,
                                file: file,
                                progress: document.createElement('progress')
                            }
                        });
                    }
                }
            }
            if (this.options.autoUpload && !that.options.paused) {
                that.start();
            }
            return
        };
        UPLOADER.prototype.start = function(){
            var that = this;
            (function loopy() {
                var i;
                that.stop();

                if(!that.queue.isEmpty() && (that.activeConnections <= that.options.maxConnections) && !that.options.paused) {

                    var file = that.queue.dequeue();
                    that.options.recent.push(file);

                    console.log("will process: " + file.atom.file.name)
                    $.when(that.upload(file))
                    .progress(function(val, data) {
                        //console.log(val + ': ' + data);
                        switch(val) {
                            case 'onprogress':
                                console.log("uploaded: " + data.percent);
                                break;
                            case 'onreadystatechange':
                                break;
                        }
                    })
                    .then(function(xhr){

                    })
                    .done(function() {
                        var last = that.options.recent.pop();
                    })
                    .fail();

                }
                // Repeatedly loop if the delay is a number >= 0
                if ( typeof that.options.delay === 'number' && that.options.delay >= 0 ) {
                    that.options.timeout_id = setTimeout( loopy, that.options.delay );
                }
            })();
        };
        ////////////////////////////////////////////////////////////////////////
        // Stop a running queue (does not cancell any upload)                 //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.stop = function() {
            this.options.timeout_id && clearTimeout( this.options.timeout_id );
            this.options.timeout_id = undefined;
            return true;
        };
        UPLOADER.prototype.pause = function(){
            this.paused = !this.paused;
            return this.paused;
        };
        ////////////////////////////////////////////////////////////////////////
        // Stop all uploads and clear all queues                              //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.stopAll = function(){
            console.log("STOPPING ALL CURRENT TRANSACTIONS")
            var i = 0;
            // Stop timers
            this.stop();
            // Clear the unprocessed files queue
            this.queue.clear();
            // Abort all XHR transactions in the queue
            for(i = 0; i < this.options.recent.length; i += 1) {
                var file = this.options.recent[i];
                console.log("stop: " + file.atom.file.name);
                this.abort(file);
            }
            // Clear the recent files queue
            this.options.recent = [];
        };
        ////////////////////////////////////////////////////////////////////////
        // Abort an upload, remove it from the queue                          //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.abort = function(file) {
            // Abort the upload
            console.log("aborting file: " + file.atom.file.name);
            file.atom.xhr.abort();
            // Remove file from queue
            var fileIndex = this.options.recent.indexOf(file)
            this.options.recent = this.options.recent.slice(fileIndex, fileIndex + 1);
        };
        ////////////////////////////////////////////////////////////////////////
        // Create upload context                                              //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.upload = function(object){
            var that = this;
            var file = object.atom.file;
            var id = object.atom.id;
            var name = file.name;
            var size = file.size;
            var deferred = new jQuery.Deferred();

            var xhr = object.atom.xhr = new XMLHttpRequest();

            xhr.upload.onprogress = function(event){
                if (event.lengthComputable){
                    var percentComplete = parseInt(((event.loaded / event.total) * 100), 10);
                    deferred.notifyWith(this, ["onprogress", {atom: object, percent: percentComplete}]);
                }
            };

            xhr.onreadystatechange = function(){
                deferred.notifyWith(this, ["onreadystatechange", {readyState: xhr.readyState, status: xhr.readyState}]);
                switch(xhr.readyState) {
                    case 0:
                        // request not initialized
                        break;
                    case 1:
                        // server connection established
                        that.activeConnections += 1;
                        break;
                    case 2:
                        // request received
                        break;
                    case 3:
                        // processing request
                        break;
                    case 4:
                        // request finished and response is ready
                        that.activeConnections -= 1;
                        break;
                }
                if (xhr.readyState === 4){
                    console.log("upload complete");
                    that.options.onComplete(id, xhr);
                    deferred.resolve(xhr);
                }
            };

            xhr.onerror = function() {
                console.log("XHR error")
                deferred.reject(new Error(xhr));
            }
            // build query string
            var params = {};
            params[this.options.inputName] = name;
            var queryString = this.options.action + '?' + jQuery.param(params);

            xhr.open(this.options.protocol, queryString, true);

            if (this.options.normalHeaders) {
                //xhr.setRequestHeader("Content-Type", "multipart/form-data");
                xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
                xhr.setRequestHeader("X-File-Name", encodeURIComponent(name));
                xhr.setRequestHeader("Cache-Control", "no-cache");
            }

            if (this.options.forceMultipart) {
                var formData = new FormData();
                formData.append(this.options.inputName, file);
                file = formData;
            } else {
                xhr.setRequestHeader("Content-Type", "application/octet-stream");
                //NOTE: return mime type in xhr works on chrome 16.0.9 firefox 11.0a2
                xhr.setRequestHeader("X-Mime-Type", file.type);
            }

            xhr.send(file);
            return deferred.promise();
        };
        ////////////////////////////////////////////////////////////////////////
        // Handle onComplete                                                  //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.onComplete = function(id, fileName, result){
            if (!this.options.responsePassthrough && !result.success){
                var errorReason = result.error || "Upload failure reason unknown";
                this.options.onError(id, fileName, errorReason);
            }
        };
        ////////////////////////////////////////////////////////////////////////
        // Handle onProgress                                                  //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.onProgress = function(){
        };
        ////////////////////////////////////////////////////////////////////////
        // Detect browser fratures                                            //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.isSupported = function(){
            var input = document.createElement('input');
            input.type = 'file';

            return (
                'multiple' in input &&
                    typeof File != "undefined" &&
                    typeof FormData != "undefined" &&
                    typeof (new XMLHttpRequest()).upload != "undefined" );
        };
        ////////////////////////////////////////////////////////////////////////
        // Validate the file acconding to the settings                        //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.validateFile = function(file){
            var name, size;

            if (file.value){
                // it is a file input
                // get input value and remove path to normalize
                name = file.value.replace(/.*(\/|\\)/, "");
            } else {
                // fix missing properties in Safari 4 and firefox 11.0a2
                name = (file.fileName !== null && file.fileName !== undefined) ? file.fileName : file.name;
                size = (file.fileSize !== null && file.fileSize !== undefined) ? file.fileSize : file.size;
            }

            if (! this.isAllowedExtension(name)){
                this.error('typeError', name);
                return false;

            } else if (size === 0){
                this.error('emptyError', name);
                return false;

            } else if (size && this.options.sizeLimit && size > this.options.sizeLimit){
                this.error('sizeError', name);
                return false;

            } else if (size && size < this.options.minSizeLimit){
                this.error('minSizeError', name);
                return false;
            }

            return true;
        };
        ////////////////////////////////////////////////////////////////////////
        // Validate file type and extension                                   //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.isAllowedExtension = function(fileName){
            var ext = (-1 !== fileName.indexOf('.')) ? fileName.replace(/.*[.]/, '').toLowerCase() : '';
            var allowed = this.options.allowedExtensions;

            if (!allowed.length){return true;}

            for (var i=0; i<allowed.length; i++){
                if (allowed[i].toLowerCase() == ext){ return true;}
            }

            return false;
        };
        ////////////////////////////////////////////////////////////////////////
        // Handle error notifications                                         //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.error = function(code, fileName){
            var message = this.options.messages[code];
            function r(name, replacement){ message = message.replace(name, replacement); }

            var extensions = this.options.allowedExtensions.join(', ');

            r('{file}', this.formatFileName(fileName));
            r('{extensions}', extensions);
            r('{sizeLimit}', this.formatSize(this.options.sizeLimit));
            r('{minSizeLimit}', this.formatSize(this.options.minSizeLimit));

            this.options.onError(null, fileName, message);
            this.options.showMessage(message);
        };
        UPLOADER.prototype.formatFileName = function(name){
            if (name.length > 33){
                name = name.slice(0, 19) + '...' + name.slice(-13);
            }
            return name;
        };
        UPLOADER.prototype.formatSize = function(bytes){
            var i = -1;
            do {
                bytes = bytes / 1024;
                i++;
            } while (bytes > 99);

            return Math.max(bytes, 0.1).toFixed(1) + ['kB', 'MB', 'GB', 'TB', 'PB', 'EB'][i];
        };
        ////////////////////////////////////////////////////////////////////////
        // Generate unique ID's for each file                                 //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.guidGenerator = function() {
            var S4 = function() {
                return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
            };
            return (S4()+S4()+"-"+S4()+"-"+S4()+"-"+S4()+"-"+S4()+S4()+S4());
        };
        ///////////////////////////////////////////////////////////////////////
        // Simple queue handler for multiple file upload                     //
        ///////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.Queue = function(context) {

            // Queue defaults.
            var data = [];
            var self = context;

            this.isEmpty = function() {
                return (data.length == 0);
            };
            this.length = function() {
                return data.length;
            };
            this.enqueue = function(obj) {
                data.push(obj);
            };
            this.dequeue = function() {
                return data.shift();
            };
            this.peek = function() {
                return data[0];
            };
            this.clear = function() {
                data = [];
            };
            this.indexOf = function(data) {

            }

        };
        ////////////////////////////////////////////////////////////////////////
        // Get module version number                                          //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.getVersion = function() {
            return this.version;
        };
        return UPLOADER;
    }());
    // Just return a value to define the module export.
    // This example returns an object, but the module
    // can return a function as the exported value.
    return UPLOADER;
}));

// implicit init that adds module to global scope
// TODO: refactor inside curl
$(document).ready(function () {
    "use strict";
    window.fansworld = window.fansworld || {};
    window.fansworld.uploader = new window.UPLOADER();
    return;
});