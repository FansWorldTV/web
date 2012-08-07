var templateHelper = {
		
		
    init: function(){
			
    },
		
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
        return $.ajax({
            url:		url,
            data:		params,
            type:		'GET',
            dataType: 	"html",
            success:	function(data)
            {
                $.templates( templateId, data );
            },
            error: function(jqXHR, textStatus, errorThrown){
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
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
		
    renderTemplate:	function(templateId,jsonData,destino,prepend)
    {
        var tplString	=	templateHelper.getTemplate(templateId);	
        if(tplString == true){
            templateHelper.appendRenderedTemplate(templateId,jsonData,destino,prepend);	
        }else{
            tplString.done(function (data) { 
                templateHelper.appendRenderedTemplate(templateId,jsonData,destino,prepend);
            });
        }
    },
		
    appendRenderedTemplate: function(templateId,jsonData,destino,prepend)
    {
        if(prepend == true){
            $( destino ).prepend( $.render[templateId]( jsonData ) );
        }else{
            $( destino ).append( $.render[templateId]( jsonData ) );
        }
				
			
    }
		
		
		
};