var home = {};

home.init = function(){
    if($("header").hasClass('home-header')){
        home.toggleSections();
    }
    if($("section").hasClass('home-content')){
        home.toggleTabs();
    }
};

home.toggleSections = function(){
    $(".home-header ul.sections li").on('click', function(){
        var category = $(this).attr('data-category-id');
        
        $(".thumb-container span").addClass('hidden');
        $(".content-container div").addClass('hidden');
        
        $(".content-container div[data-category-id='" + category + "']").removeClass('hidden');
        $(".thumb-container span[data-category-id='" + category + "']").removeClass('hidden');
        
        $(".home-header ul.sections li").removeClass('active');
        $(this).addClass('active');
        
    });
};

home.toggleTabs = function(){
    $("section.home-content .legend:not('.active')").hide();
    $(".home-header ul.tabs li").on('click', function(){
        var typeTab = $(this).attr('data-type-tab');
        $(".home-header ul.tabs li").removeClass('active');
        $(this).addClass('active');
        
        $("section.home-content .legend").hide();
        $('section.home-content .legend[data-type-tab="' + typeTab + '"]').show();
    });
    
};



$(document).ready(function(){
   home.init(); 
});