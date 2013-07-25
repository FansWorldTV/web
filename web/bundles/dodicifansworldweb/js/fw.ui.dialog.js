
/*global ExposeTrans    lation, $, jQuery, alert, FormData, FileReader, escape, console, error, success, endless, ajax, templateHelper, qq, Routing, appLocale, exports, module, require, define*/
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

// fansWorld Modal Dialogs with bootstrap 1.0

////////////////////////////////////////////////////////////////////////////////
// FansWorld Modal Dialogs with bootstrap                                     //
////////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
    "use strict";
    // Create the defaults once
    var pluginName = "fwModalDialog";
    var defaults = {
        url: null,
        modal: {
            modalId: 123,
            modalLabel: 'label',
            modalTitle: 'Upload Photo',
            modalBody: 'Uploader',
            deleteButton: false,
            backdrop: true,
            width: '700'
        }
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
            $(that.element).bind("destroyed", $.proxy(that.teardown, that));

            console.log('fwModalDialog');
            that.options.url = $(that.element).attr('href');
            that.options.modal.modalTitle = $(that.element).attr('title') || $(that.element).attr('data-original-title');

            $(that.element).on("click", function(event) {
                event.preventDefault();
                if(!that.isExternal(that.options.url)) {
                    that.open(that.options.url);
                } else {
                    that.openExternal(that.options.url);
                }
            });

        },
        open: function(href) {
            var that = this;
            var id = parseInt((Math.random() * 1000), 10);
            var modal = that.options.modal;

            $.get(href)
            .then(function(response){
                var modalBody = $(response).clone();
                // Hide submit
                modalBody.find('input[type="submit"]').hide();
                $.when(templateHelper.htmlTemplate('general-upload_modal', modal))
                .then(function(html) {
                    var dialog = $(html).clone();
                    // Replace body
                    dialog.find('.modal-body').html(modalBody);
                    // Enable save button
                    dialog.find("#modal-btn-save").removeAttr("disabled");
                    // Enable delete
                    if(that.options.modal.deleteButton){
                        dialog.find("#modal-btn-delete").removeClass('hidden');
                        dialog.find("#modal-btn-delete").click(function(){
                            var confirmText = 'Esta seguro de querer eliminar?';
                            if(!confirm(confirmText)) {
                                return false;
                            }

                            var self = $(this);
                            self.addClass('loading-small');

                            var id = $(that.element).attr('data-entity-id');
                            var type = $(that.element).attr('data-entity-type');

                            console.log("ID:"+id);
                            console.log("TYPE:"+type);

                            ajax.genericAction({
                                route: 'delete_ajax',
                                params: {
                                    'id': id,
                                    'type': type
                                },
                                callback: function(response) {
                                    self.removeClass('loading-small');
                                    success(response.message);
                                    window.location.href = response.redirect;
                                },
                                errorCallback: function(responsetext) {
                                    self.removeClass('loading-small');
                                    error(responsetext);
                                }
                            });
                        });
                    }
                    // Bind close button
                    dialog.find("#modal-btn-close").one("click", null, null, function(){
                        dialog.modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                    });
                    // Enable tagifier
                    dialog.find("#form_tagtextac").fwTagify({action: 'tag'});
                    // Bind save button to form submit
                    dialog.find("#modal-btn-save").one("click", null, null, function(){
                        $(this).addClass('loading-small');
                        dialog.find('form').find('input[type="submit"]').click();
                    });
                    // Ajaxianize form
                    dialog.find('form').submit(function(event) {
                        event.preventDefault();
                        var data = $(this).serializeArray();
                        var action = $(this).attr('action');
                        var method = $(this).attr('method');
                        // Send data
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
                            if (formHtml.find('form').length) {
                                //hookForm(dialog);
                                console.log(dialog);
                            } else {
                                dialog.find("#modal-btn-save").text('continuar');
                                // No more forms ? ok then we're done
                                dialog.find("#modal-btn-save").one("click", null, null, function(){
                                    $(this).addClass('loading-small');
                                    location.reload();
                                });
                            }
                        });
                        return false;
                    });
                    // Set styles & bind hide event
                    dialog.modal({
                        backdrop: that.options.modal.backdrop
                    }).css({
                        'z-index': '9999999999',
                        'width': that.options.modal.width,
                        'margin-left': -(that.options.modal.width / 2)
                    }).on('hide', function() {
                        $('.modal-backdrop').remove();
                        $(this).data('modal', null);
                        $(this).remove();
                    });
                });
            });
        },
        openExternal: function(href) {
            var that = this;
            var width = 640;
            var height = 380;
            var left = (screen.width / 2)-(width / 2);
            var top = (screen.height / 2)-(height / 2);

            window.open(href,'Popup','toolbar=no,location=no,directories=no, status=no,menubar=no,scrollbars=no,resizable=no,copyhistory=no, width='+width+', height='+height+', top='+top+', left='+left);
        },
        isExternal: function(url) {
            var match = url.match(/^([^:\/?#]+:)?(?:\/\/([^\/?#]*))?([^?#]+)?(\?[^#]*)?(#.*)?/);
            if (typeof match[1] === "string" && match[1].length > 0 && match[1].toLowerCase() !== location.protocol) {
                return true;
            }
            if (typeof match[2] === "string" && match[2].length > 0 && match[2].replace(new RegExp(":("+{"http:":80,"https:":443}[location.protocol]+")?$"), "") !== location.host) {
                return true;
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
            $.removeData($(that.element)[0], that._name);
            $(that.element).removeClass(that._name);
            that.unbind();
            that.element = null;
        }
    };
    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[pluginName] = function (options) {
        // If the first parameter is an object (options), or was omitted,
        // instantiate a new instance of the plugin.
        if (typeof options === "object" || !options) {
            return this.each(function () {
                // Only allow the plugin to be instantiated once.
                if (!$.data(this, pluginName)) {
                    // Pass options to Plugin constructor, and store Plugin
                    // instance in the elements jQuery data object.
                    $.data(this, pluginName, new Plugin(this, options));
                }
            });
        }
    };
});

/*
$('.internal_buttons').append('<a class="btn edit" href="/app_dev.php/edit/popup/album/5" data-edit="kaka"><i></i></a>');
$("[data-edit='kaka']").fwModalDialog();
$('.internal_buttons').append('<a class="btn edit" href="http://www.google.com" data-edit="kaka"><i></i></a>');
$("[data-edit='kaka']").fwModalDialog();
*/