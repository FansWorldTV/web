/**
 * 
 */


var ajax = {
    active: false,
  
    init: function(){
        $.ajaxSetup({
            type: 'POST',
            dataType: 'json'
        });
    },
  
    searchAction: function(query, page, callback) {
        if(!ajax.active) {
            ajax.active = true;
            
            $.ajax({
               url: Routing.generate( appLocale + '_user_search'),
               data: { 
                   'query': query,
                   'page': page
               },
               success: function(response){
                   ajax.active = false;
                   if( typeof(callback) !== 'undefined' ){
                       callback(response);
                   }
               }
            });
        }
    }
  
};

function trim (myString) {
    return myString.replace(/^\s+/g,'').replace(/\s+$/g,'')
}

$(document).ready(function(){
    $("form").each(function(){
       $(this).attr("novalidate", "true"); 
    });
}); 