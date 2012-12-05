var things = {};


things.init = function(){
    $("[data-filter-matchs] button").click(function(){
        $(".eventgrid-container").addClass('loading');
        $(".events-grid").html("");
        
        var type = $(this).attr('data-type');
        things.filter(type);
    });
};

things.filter = function(type, callback){
    var params = {};
    params['type'] = type;
    
    ajax.genericAction('things_ajaxmatchs', params, function(r){
        $(".events-grid").html("");
        for(var i in r.events){
            var event = r.events[i];
            templateHelper.renderTemplate('event-grid_element', event, '.events-grid', function(){
                $("[data-added-element]").hide();
            });
        }
        $(".eventgrid-container").removeClass('loading');
        $("[data-added-element]").show();
    }, function(e){
        error(e);
    });
};

$(document).ready(function(){
    things.init();
});