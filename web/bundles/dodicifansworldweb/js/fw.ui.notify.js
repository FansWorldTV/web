///////////////////////////////////////////////////////////////////////////////
// Plugin generador de tags                                                  //
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
    "use strict";
    var pluginName = "fwNotify";
    var note = $('<div class="alert"></div>');
    var defaults = {
        type: 'success',
        closable: true,
        transition: 'fade',
        fadeOut: {
            enabled: true,
            delay: 3000
        },
        message: {
            html: false,
            text: 'This is a message.'
        },
        onClose: function () {},
        onClosed: function () {}
    };
    function Plugin(element, options) {
        this.element = element;
        this.options = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;
        this.note = note;
        this.init();
    }
    Plugin.prototype = {
        init: function () {
            var that = this;
            var self = $(that.element);
            self.bind("destroyed", $.proxy(that.teardown, that));
            self.addClass(that._name);

            // Setup from options
            if(this.options.transition)
                if(this.options.transition == 'fade')
                    this.note.addClass('in').addClass(this.options.transition)
                else this.note.addClass(this.options.transition)
            else this.note.addClass('fade').addClass('in')

            if(this.options.type)
                this.note.addClass('alert-' + this.options.type)
            else this.note.addClass('alert-success')

            if(typeof this.options.message === 'object')
                if(this.options.message.html)
                this.note.html(this.options.message.html)
                else if(this.options.message.text)
                    this.note.text(this.options.message.text)
            else
                this.note.html(this.options.message)

            if(this.options.closable)
                this.note.prepend($('<a class="close pull-right" data-dismiss="alert" href="#">&times;</a>'))


            this.note = this.create(this.options.type, this.options.message.html, "hace 2 dias");
            return this;
        },
        create: function(title, message, time) {
            return $('<div class="popup white swing"><span class="title">'+title+'</span><p>' + message + '<br /> '+ time +'</p><a href="#" class="close">Close</a></div>');
        },
        show: function () {
            var self = this;
            console.log("show")
            if(this.options.fadeOut.enabled)
            setTimeout(function(){
                self.hide();
            }, this.options.fadeOut.delay || 3000);

            console.log(this.note)
            $(this.element).append(this.note);

            this.note.addClass('animated')
            this.note.on('click', '.close', function(){
                console.log("closed");
                self.hide();
                self.options.onClosed();
            });
        },
        hide: function () {
            var self = this;
            if(this.options.fadeOut.enabled) {
                console.log("fadeee")
                this.note.removeClass('swing').addClass('bounceOutUp animated');
                setTimeout(function(){
                    self.note.remove();
                }, 1000);
                self.destroy();
            }
            else {
                self.options.onClose();
                this.note.removeClass('swing').addClass('bounceOutDown animated');
                setTimeout(function(){
                    self.note.remove();
                }, 800);
                self.destroy();
            }
        },
        destroy: function() {
            $(this.element).unbind("destroyed", this.teardown);
            this.options.onClosed();
            this.teardown();
            return true;
        },
        teardown: function() {
            $.removeData($(this.element)[0], this._name);
            $(this.element).removeClass(this._name);
            this.unbind();
            this.element = null;
            return this.element;
        },
        bind: function() { },
        unbind: function() { }
    };
    $.fn[pluginName] = function (options) {
        return this.each(function () {
            if (!$.data(this, pluginName)) {
                $.data(this, pluginName, new Plugin(this, options));
            }
        });
    };
});