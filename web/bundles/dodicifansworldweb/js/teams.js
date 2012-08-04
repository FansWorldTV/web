var list = {};
list.category = null;
list.activePage = 1;
    
list.init = function(){
    list.getTeams();
    
    $("#addMore").live('click', function(){
        list.getTeams();
    });
        
    $("ul.categories li").live('click', function(){
        list.category = $(this).attr('categoryId');
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
            $("ul.listMosaic").append("<li> <a href='"+ Routing.generate(appLocale +'_' +'team_wall', {
                'slug': element.slug
            })+"'>" + element.title + " </a></li>");
        }
            
        if(r.gotMore){
            $("#addMore").removeClass('hidden');
        }else{
            $("#addMore").addClass('hidden');
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