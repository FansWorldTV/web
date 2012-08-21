var templateHelper = {
		
		
    init: function(){
			
    },
		
    isLoading: false,
    
    getPath: function(templateId)
    {
        var tplPart = templateId.split("-");
        params		=	{
            type: tplPart[1]
        };	
        return Routing.generate(appLocale + '_template_' + tplPart[0], params);
    },
		
    preLoadTemplates: function(templates)
    {
        $.each(templates, function(index, value) { 
            templateHelper.loadTemplate(value);
        });
    },
		
    getTemplate: function(templateId,params)
    {
        if (typeof $.render[templateId] != 'undefined'){
            return true;
        }else{
            return templateHelper.getTemplateString(templateId,params);
        }; 
    },
		
    getTemplateString: function(templateId,params){
        var url = templateHelper.getPath(templateId);
        var alreadyLoaded = false;
        	
	        return $.ajaxQueue({
	            url:		url,
	            data:		params,
	            type:		'GET',
	            dataType: 	"html",
	            cache: true,
	            success:	function(data)
	            {
	                $.templates( templateId, data );
	                templateHelper.isLoading = false;
	            },
	            error: function(jqXHR, textStatus, errorThrown){
	                console.log(jqXHR);
	                console.log(textStatus);
	                console.log(errorThrown);
	                templateHelper.isLoading = false;
	            },
	            beforeSend : function(){
	            	if (typeof $.render[templateId] != 'undefined'){
	            		alreadyLoaded = true;
	            		return false;
	                }else alreadyLoaded = false;
	            },
	            abort: function(){
	            	if(alreadyLoaded){
	            		console.log('ya ta carguetti');
	            		//return true;
	            	}
	            }
	        });

    },
    loadTemplate: function(templateId)
    {
        var tplString	=	templateHelper.getTemplate(templateId);	
        if(tplString == true){
            return true;
        }else{
            tplString.done(function (data) { 
                return true;
            });
        }
    },
		
    renderTemplate:	function(templateId,jsonData,destino,prepend, callback)
    {
        var tplString	=	templateHelper.getTemplate(templateId);	
        if(tplString == true){
            templateHelper.appendRenderedTemplate(templateId,jsonData,destino,prepend, callback);	
        }else{
            tplString.done(function (data) { 
                templateHelper.appendRenderedTemplate(templateId,jsonData,destino,prepend, callback);
            });
        }
    },
		
    appendRenderedTemplate: function(templateId,jsonData,destino,prepend, callback)
    {
        if(prepend == true){
            $( destino ).prepend( $.render[templateId]( jsonData ) );
        }else{
            $( destino ).append( $.render[templateId]( jsonData ) );
        }
        if(typeof callback == 'function'){
        	callback();
        }
        
    }
		
		
		
};