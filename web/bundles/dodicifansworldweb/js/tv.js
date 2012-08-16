var tv = {
	init: 	function(filtersList,channelsList,targetDataList){
		tv.bindRankingFilters(filtersList,channelsList,targetDataList);
		tv.bindChannelsTab(filtersList,channelsList,targetDataList);
	},
	
	
	
	bindRankingFilters:	function(filtersList,channelsList,targetDataList){
		$(filtersList).click(function(e){
			e.stopImmediatePropagation();
			$(filtersList).removeClass('active');
			$(this).addClass('active');
			
		    var activeChannel  = $(channelsList+' ul li.active a').attr('data-target').slice(1);
		    
		    var filter = $(this).attr('filter-type');
	        
	        tv.rankingUpdate(activeChannel,filter,targetDataList,{});
		});
	},
	
	bindChannelsTab:	function(filtersList,channelsList,targetDataList){
		$(channelsList + ' ul li a').click(function(e){
			var activeChannel = $(this).attr('data-target').slice(1);
			var filter = $(filtersList+' li.active').attr('filter-type');
			tv.rankingUpdate(activeChannel,filter,targetDataList,{});
		});
	},
	
	rankingUpdate: function(activeChannel,filter,targetDataList,opts){
		opts = $.merge({
	            'sort': 'popular',
	            'page': 1,
	            'category': activeChannel,
	            'filter': filter
	        },opts);

        $(targetDataList).empty().addClass('loading');
        
	    ajax.genericAction('video_ajaxsearch', opts, function(r){
	        if(typeof r != 'undefined'){
	        	$(targetDataList).removeClass('loading');
                if(typeof r.videos != 'undefined'){
                    templateHelper.renderTemplate("video-list_element", r.videos, targetDataList, false, function(){
                    });
                }
	        }
        }, function(msg){
            error(msg);
        });
	}
	
		
};