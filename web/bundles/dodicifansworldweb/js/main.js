$(document).ready(function(){
    ajax.init();
    
    var page = 0;
    $("#addMore").click(function(){
        var query = this.parent().find('input#query').val();
        
        ajax.searchAction( query, page, function(response){
            if(response){
                var elementTmp = $("div.templates div.listMosaic div.element").clone();
                
                for(var i in response){
                    elementTmp.find('.name').html(response[i].name);
                    elementTmp.find('.avatar').attr('src', response[i].image);
                    elementTmp.find('.commonFriends').html(response[i].commonFriends)
                }
                
                if(response.gotMore){
                    $("#addMore").removeClass('hidden');
                    page++;
                }else{
                    $("#addMore").addClass('hidden');
                }
            }
        });
    });
    
    $("#formSearch").submit(function(event){
        var query = $('#query').val();
        
        
        ajax.searchAction(query, 0, function(r){
            console.log(r);
        });
        
        event.preventDefault();        
        return false;
    });
});