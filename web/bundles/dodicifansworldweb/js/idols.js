var idolHome = {};
idolHome.category = null;
idolHome.activePage = 1;
idolHome.addMore = true;

idolHome.init = function(){
    if($("div.list-idols").attr('data-got-more')){
        idolHome.addMore = true;
    }else{
        idolHome.addMore = false;
    }
    idolHome.getIdols();

    $("ul.categories li a").on('click', function(e){
        e.preventDefault();
        idolHome.category = $(this).parent().attr('data-category-id');
        idolHome.activePage = 1;

        $(".list-idols dl").html(' ');

        idolHome.getIdols(function(){
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
            if(idolHome.addMore){
                idolHome.getIdols();
            }
        }
    });

};

idolHome.getIdols = function(callback){
    $("div.list-idols").addClass('loading');

    ajax.genericAction('idol_ajaxlist',
    {
        'tc': idolHome.category,
        'page': idolHome.activePage
    },
    function(r){
        if(r.idols.length>0){
            for(i in r.idols){
                var element = r.idols[i];
                var lastIteration = null;
                if(i == (r.idols.length-1)){
                    lastIteration = function(){
                        $("div.list-idols").removeClass('loading');
                        callback();
                    };
                }

                templateHelper.renderTemplate('idol-list_element', element, $(".list-idols dl"), false, lastIteration);
            }
        }else{
            $("div.list-idols").removeClass('loading');
        }

        idolHome.addMore = r.gotMore;
        idolHome.activePage++;
    },
    function(r){
        console.log(r);
        $("div.list-idols").removeClass('loading');
    });
}

$(function(){
   idolHome.init();
});