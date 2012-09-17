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
        //e.preventDefault();
        list.category = $(this).parent().attr('data-category-id');
        list.activePage = 1;
        
        $(".list-teams dl").html(' ');
        
        list.getTeams();
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
    
list.getTeams = function(){
    $("div.list-teams").addClass('loading');
        
    ajax.genericAction('team_get',
    {
        'category': list.category,
        'page': list.activePage
    },
    function(r){
        for(i in r.teams){
            var element = r.teams[i];
            
            templateHelper.renderTemplate('team-list_element', element, $(".list-teams dl"), false, function(){
                $("div.list-teams").removeClass('loading');
            });
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