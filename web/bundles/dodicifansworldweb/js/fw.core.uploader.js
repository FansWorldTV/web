/*global
    $,
    jQuery,
    File,
    FormData,
    exports,
    module,
    require,
    define
*/
/*jslint nomen: true */                 /* Tolerate dangling _ in identifiers */
/*jslint vars: true */           /* Tolerate many var statements per function */
/*jslint white: true */                       /* tolerate messy whithe spaces */
/*jslint browser: true */                                  /* Target browsers */
/*jslint devel: true */                         /* Assume console, alert, ... */
/*jslint windows: true */               /* Assume window object (for browsers)*/
/*jslint continue: true */                     /* Allow continue inside loops */

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
// 1.7 Custom Events                                                          //
// 1.4 File queue manager                                                     //
// 1.0 Initial version 21-Mar-2013                                            //
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// FansWorld XHR Uploader                                                     //
// Internal modules:                                                          //
// * XHR handler                                                              //
// * Uploader queue handler                                                   //
// * Event handler                                                            //
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// ISOTOPE TODO:
// * Search
// * mis videos y mis fotos
// perfil:
//      videos
//      fotos
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
        function UPLOADER(settings) {
            ////////////////////////////////////////////////////////////////////
            // Constructor                                                    //
            ////////////////////////////////////////////////////////////////////
            var _self = this;
            this.jQuery = jQuery;
            this.version = '1.7';
            this.defaults = {
                // set to true to see the server response
                action: '/app_dev.php/photo/fileupload',
                element: null,
                protocol: 'POST',
                params: {},
                normalHeaders: true,
                customHeaders: {},
                multiple: true,
                maxConnections: 1,
                autoUpload: false,
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
                // Event Listeners
                listeners: {},
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
            // Merge options
            this.options = $.extend({}, this.defaults, settings);
            // Test XHR Lv2 Support
            if (this.isSupported()) {
                this.options.debug("XMLHttpRequest Level 2 is supported");
                this.options.debug("-----------------------------------");
                this.options.debug("settings:");
                this.options.debug(settings);
            } else {
                this.options.debug("XMLHttpRequest Level 2 is not supported");
            }

            ////////////////////////////////////////////////////////////////////
            // Message Queue                                                  //
            ////////////////////////////////////////////////////////////////////
            this.queue = new this.Queue(this);
            this.activeConnections = 0;

            ////////////////////////////////////////////////////////////////////
            // Event Handlers                                                 //
            ////////////////////////////////////////////////////////////////////
            this.addListener('onabort', this.onAbort);
            this.addListener('onerror', this.onError);
            this.addListener('onreadystatechange', this.onReadystatechange);
            this.addListener('onprogress', this.onProgress);
            this.addListener('onload', this.onLoad);
            this.addListener('onupload', this.onUpload);
            this.addListener('oncomplete', this.onComplete);
        }
        UPLOADER.prototype.addFiles = function(files) {
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
            return true;
        };
        UPLOADER.prototype.start = function() {
            var that = this;
            (function loopy() {
                var i;
                that.stop();

                if(!that.queue.isEmpty() && (that.activeConnections <= that.options.maxConnections) && !that.options.paused) {

                    var file = that.queue.dequeue();
                    that.options.recent.push(file);

                    console.log("will process: " + file.atom.file.name);
                    $.when(that.upload(file))
                    .progress(function(event, data) {
                        switch(event) {
                            case 'onprogress':
                                console.log("onprogress [percent: " + data.percent + "]");
                                break;
                            case 'onreadystatechange':
                                console.log("onreadystatechange [readyState: " + data.readyState + "]");
                                break;
                            case 'onabort':
                                console.log("onabort");
                                break;
                            case 'onerror':
                                console.log("onerror");
                                break;
                        }
                    })
                    .then(function(xhr){
                        // paulina risso
                    })
                    .done(function() {
                        // Update recent files queue
                        var last = that.options.recent.pop();
                    })
                    .fail();

                }
                // Repeatedly loop if the delay is a number >= 0
                if ( typeof that.options.delay === 'number' && that.options.delay >= 0 ) {
                    that.options.timeout_id = setTimeout( loopy, that.options.delay );
                }
            }());
        };
        ////////////////////////////////////////////////////////////////////////
        // Stop a running queue (does not cancell any upload)                 //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.stop = function() {
            if(this.options.timeout_id) {
                clearTimeout( this.options.timeout_id );
            }
            this.options.timeout_id = undefined;
            return true;
        };
        ////////////////////////////////////////////////////////////////////////
        //                                                                    //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.pause = function(){
            this.paused = !this.paused;
            return this.paused;
        };
        ////////////////////////////////////////////////////////////////////////
        // Stop all uploads and clear all queues                              //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.stopAll = function(){
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
            var fileIndex = this.options.recent.indexOf(file);
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
                    that.fire({type: "onprogress", source: event, target: object.atom});
                    deferred.notifyWith(this, ["onprogress", {atom: object.atom, percent: percentComplete}]);
                }
            };
            xhr.onreadystatechange = function(event){
                that.fire({type: "onreadystatechange", source: event, target: object.atom});
                deferred.notifyWith(this, ["onreadystatechange", {readyState: xhr.readyState, xhr: xhr}]);
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
                        break;
                }
                // Todo move this condition outside the upload method
                if (xhr.readyState === 4 && xhr.status === 200){
                    that.fire({type: "oncomplete", source: event, target: object.atom});
                    deferred.resolve(xhr);
                }
            };
            xhr.onerror = function(event) {
                that.fire({type: "onerror", source: event, target: object.atom});
                deferred.reject(new Error(xhr));
            };
            xhr.onabort = function(event) {
                that.fire({type: "onabort", source: event, target: object.atom});
            };
            xhr.onload = function(event) {
                that.fire({type: "onload", source: event, target: object.atom});
            };
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
        //  INTERNAL EVENT HANDLERS                                           //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.onProgress = function(event){
            var that = this;
        };
        UPLOADER.prototype.onComplete = function(event){
            var that = this;
            var xhr = event.target.xhr;
            that.activeConnections -= 1;
            console.log('oncomplete');
            return xhr.statusText;
        };
        UPLOADER.prototype.onError = function(event){
            var that = this;
        };
        UPLOADER.prototype.onAbort = function(event){
            var that = this;
        };
        UPLOADER.prototype.onLoad = function(event){
            var that = this;
            var xhr = event.target.xhr;
            that.activeConnections -= 1;
            console.log('onload');
            return xhr.statusText;
        };
        UPLOADER.prototype.onReadystatechange = function(event){
            var that = this;
        };
        ////////////////////////////////////////////////////////////////////////
        // Detect browser fratures                                            //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.isSupported = function(){
            var input = document.createElement('input');
            input.type = 'file';
            input.setAttribute("multiple", "true");
            var supportsMultiple = input.multiple === true;

            return (
                supportsMultiple &&
                    typeof File !== "undefined" &&
                    typeof FormData !== "undefined" &&
                    typeof (new XMLHttpRequest()).upload !== "undefined" );
        };
        ////////////////////////////////////////////////////////////////////////
        // File validation                                                    //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.validateFile = function(file){
            var name, size;

            function extractFilename(path) {
                var x;
                // modern browser
                if (path.substr(0, 12) === "C:\\fakepath\\") {
                    return path.substr(12);
                }
                // Unix-based path
                x = path.lastIndexOf('/');
                if (x >= 0) {
                    return path.substr(x+1);
                }
                // Windows-based path
                x = path.lastIndexOf('\\');
                if (x >= 0) {
                    return path.substr(x+1);
                }
                return path; // just the filename
            }

            if (file.value){
                // it is a file input
                // get input value and remove path to normalize
                //name = file.value.replace(/.*(\/|\\)/, "");
                name = extractFilename(file.value);
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
            var i;
            var ext = (-1 !== fileName.indexOf('.')) ? fileName.replace(/.*[.]/, '').toLowerCase() : '';
            var allowed = this.options.allowedExtensions;

            if (!allowed.length){return true;}

            for (i = 0; i < allowed.length; i += 1){
                if (allowed[i].toLowerCase() === ext){ return true;}
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
                i += 1;
            } while (bytes > 99);

            return Math.max(bytes, 0.1).toFixed(1) + ['kB', 'MB', 'GB', 'TB', 'PB', 'EB'][i];
        };
        ////////////////////////////////////////////////////////////////////////
        // Create and return a "version 4" RFC-4122 UUID string.              //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.guidGenerator = function() {
            var s = [];
            var itoh = '0123456789ABCDEF';
            var i = 0;
            // Make array of random hex digits. The UUID only has 32 digits in it, but we
            // allocate an extra items to make room for the '-'s we'll be inserting.
            for (i = 0; i < 36; i += 1) {
                s[i] = Math.floor(Math.random()*0x10);
            }
            // Conform to RFC-4122, section 4.4
            s[14] = 4;  // Set 4 high bits of time_high field to version
            s[19] = (s[19] && 0x3) || 0x8;  // Specify 2 high bits of clock sequence
            // Convert to hex chars
            for (i = 0; i < 36; i += 1) {
                s[i] = itoh[s[i]];
            }
            // Insert '-'s
            s[8] = s[13] = s[18] = s[23] = '-';

            return s.join('');
        };
        UPLOADER.prototype.createInput = function(){
            var that = this;
            var input = document.createElement("input");

            if (that.options.multiple){
                input.setAttribute("multiple", "multiple");
            }

            if (this.options.acceptFiles) {
                input.setAttribute("accept", this.options.acceptFiles);
            }

            input.setAttribute("type", "file");
            input.setAttribute("name", this.options.name);

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

            this.options.element.appendChild(input);

            $(input).on('change', function(event) {
                that.fire({type: "onchange", source: event, target: input});
            });
            // IE and Opera, unfortunately have 2 tab stops on file input
            // which is unacceptable in our case, disable keyboard access
            if (window.attachEvent){
                // it is IE or Opera
                input.setAttribute('tabIndex', "-1");
            }

            return input;
        };
        ///////////////////////////////////////////////////////////////////////
        // Simple queue handler for multiple file upload                     //
        ///////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.Queue = function(context) {

            // Queue defaults.
            var data = [];
            var self = context;

            this.isEmpty = function() {
                return (data.length === 0);
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

            };
        };
        ////////////////////////////////////////////////////////////////////////
        //  CUSTOM EVENT HANDLERS                                             //
        ////////////////////////////////////////////////////////////////////////
        UPLOADER.prototype.addListener = function(type, listener){
            if (typeof this.options.listeners[type] === "undefined"){
                this.options.listeners[type] = [];
            }
            this.options.listeners[type].push(listener);
        };
        UPLOADER.prototype.removeListener = function(type, listener){
            if (this.options.listeners[type] instanceof Array){
                var listeners = this.options.listeners[type];
                var i, len;
                for (i = 0, len = listeners.length; i < len; i += 1){
                    if (listeners[i] === listener){
                        listeners.splice(i, 1);
                        break;
                    }
                }
            }
        };
        UPLOADER.prototype.fire = function(event){
            var i, len;
            if (typeof event === "string"){
                event = { type: event };
            }
            if (!event.target){
                event.target = this;
            }

            if (!event.type){  //falsy
                throw new Error("Event object missing 'type' property.");
            }

            if (this.options.listeners[event.type] instanceof Array){
                var listeners = this.options.listeners[event.type];
                for (i = 0, len = listeners.length; i < len; i += 1){
                    listeners[i].call(this, event);
                }
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
    window.fansworld.uploader = new window.UPLOADER({autoUpload: true});
    return;
});