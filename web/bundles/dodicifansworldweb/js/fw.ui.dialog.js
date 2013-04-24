
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
            modalBody: 'Uploader'
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
            that.options.modal.modalTitle = $(that.element).attr('title');

            $(that.element).on("click", function(event) {
                event.preventDefault();
                that.open(that.options.url);
            });

        },
        open: function(href) {

            var id = parseInt((Math.random() * 1000), 10);
            var modal = {
                modalId: id,
                modalLabel: 'label',
                modalTitle: 'Upload Photo',
                modalBody: 'Uploader'
            };

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
                    // Bind close button
                    dialog.find("#modal-btn-close").one("click", null, null, function(){
                        dialog.modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                    });
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
                        backdrop: true
                    }).css({
                        width: '700px',
                        'margin-left': '-350px'
                    }).on('hide', function() {
                        $('.modal-backdrop').remove();
                        $(this).data('modal', null);
                        $(this).remove();
                    });
                });
            });
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
*/