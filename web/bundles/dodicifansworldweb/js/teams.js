var list = {};
list.category = null;
list.activePage = 1;
list.addMore = true;
    
list.init = function(){
    if($("div.list-teams").attr('data-got-more')){
        list.addMore = true;
    }else{
        list.addMore = false;
    }
    
    
    list.getTeams();
        
    $("ul.categories li a").on('click', function(e){
        e.preventDefault();
        list.category = $(this).parent().attr('data-category-id');
        list.activePage = 1;
        
        $(".list-teams dl").html(' ');
        
        list.getTeams(function(){
            var docHeight = window.innerHeight;
            $(document).scrollTop(( docHeight - 20 ));
        });
    });

    $(window).endlessScroll({
        fireOnce: true,
        enableScrollTop: false,
        inflowPixels: 100,
        fireDelay: 250,
        intervalFrequency: 2000,
        ceaseFireOnEmpty: false,
        loader: 'cargando',
        callback: function(i, p, d) {
            if(list.addMore){
                list.getTeams();
            }
        }
    });
    
};
    
list.getTeams = function(callback){
    $("div.list-teams").addClass('loading');
        
    ajax.genericAction('team_get',
    {
        'category': list.category,
        'page': list.activePage
    },
    function(r){
        for(i in r.teams){
            var element = r.teams[i];
            var lastIteration = null;
            console.log("I: "+i+" | Length: "+r.teams.length);
            if(i == (r.teams.length-1)){
                lastIteration = function(){
                    $("div.list-teams").removeClass('loading');
                    callback();
                };
            }
                
            templateHelper.renderTemplate('team-list_element', element, $(".list-teams dl"), false, lastIteration);
        }
        list.addMore = r.gotMore;
        list.activePage++;
        teamship.init();
    },
    function(r){
        console.log(r);
        $("div.list-teams").removeClass('loading');
    });
}

$(function(){
   list.init(); 
});