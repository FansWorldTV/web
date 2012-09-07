var list = {};
list.category = null;
list.activePage = 1;
    
list.init = function(){
    list.getTeams();
        
    $("ul.categories li :not('div.list-teams.loading')").on('click', function(){
        list.category = $(this).attr('data-category-id');
        list.activePage = 1;
        $(".list-teams dl").html('');
        list.getTeams();
    });
};
    
list.getTeams = function(){
    $("dv.list-teams").addClass('loading');
        
    ajax.genericAction('team_get',
    {
        'category': list.category,
        'page': list.activePage
    },
    function(r){
        console.log(r);
        for(i in r.teams){
            var element = r.teams[i];
            
            templateHelper.renderTemplate('team-list_element', element, $(".list-teams dl"), false, function(){
                $("div.list-teams").removeClass('loading');
            });
        }
            
        list.activePage++;
    },
    function(r){
        console.log(r);
        $("div.list-teams").removeClass('loading');
    });
}