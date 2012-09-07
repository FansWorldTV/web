var list = {};
list.category = null;
list.activePage = 1;
    
list.init = function(){
    list.getTeams();
        
    $("ul.categories li").live('click', function(){
        list.category = $(this).attr('data-category-id');
        list.activePage = 1;
        $("ul.listMosaic").html('');
        list.getTeams();
    });
};
    
list.getTeams = function(){
    $("div.mask").addClass('loading');
        
    ajax.genericAction('team_get',
    {
        'category': list.category,
        'page': list.activePage
    },
    function(r){
        for(i in r.teams){
            var element = r.teams[i];
            
            template_helper.renderTemplate('team-list_element', element, $(".list-teams dl"), false, function(){
                
            });
            /*
            $("ul.listMosaic").append("<li> <a href='"+ Routing.generate(appLocale +'_' +'team_wall', {
                'slug': element.slug
            })+"'>" + element.title + " </a></li>");*/
        }
            
        list.activePage++;
        $("div.mask").removeClass('loading');
        console.log(r);
    },
    function(r){
        console.log(r);
        $("div.mask").removeClass('loading');
    });
}