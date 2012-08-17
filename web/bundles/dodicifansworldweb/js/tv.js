var tv = {
	init: 	function(filtersList,channelsList,targetDataList){
		tv.bindRankingFilters(filtersList,channelsList,targetDataList);
		tv.bindChannelsTab(filtersList,channelsList,targetDataList);
	},
	
	bindChannelsExplorer: function(dropDown,targetDataList){

		$(dropDown).find('ul.dropdown-menu li a').click(function(e){
			
			var activeChannel = {
					slug: $(this).attr('channel-slug'),
					title: $(this).text()
			};
			$(dropDown).find('.dropdown-toggle span').text(activeChannel.title);
			tv.rankingUpdate.widget(activeChannel.slug,null,targetDataList,{});
		});
	},
	
	
	
	bindRankingFilters:	function(filtersList,channelsList,targetDataList){
		$(filtersList).click(function(e){
			e.stopImmediatePropagation();
			$(filtersList).removeClass('active');
			$(this).addClass('active');
			
		    var activeChannel  = $(channelsList+' ul li.active a').attr('data-target').slice(1);
		    
		    var filter = $(this).attr('filter-type');
	        
	        tv.rankingUpdate.widget(activeChannel,filter,targetDataList,{});
		});
	},
	
	bindChannelsTab:	function(filtersList,channelsList,targetDataList){
		$(channelsList + ' ul li a').click(function(e){
			var activeChannel = $(this).attr('data-target').slice(1);
			var filter = $(filtersList+' li.active').attr('filter-type');
			tv.rankingUpdate.widget(activeChannel,filter,targetDataList,{});
		});
	},
	
	rankingUpdate: {
		widget: function(activeChannel,filter,targetDataList,opts){
			tv.rankingUpdate.videos(activeChannel,filter,targetDataList,opts);
			tv.rankingUpdate.tags(activeChannel,filter,targetDataList,opts);
		},
		
		videos: function(activeChannel,filter,targetDataList,opts){
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
	        
		},
		
		tags: function(activeChannel,filter,targetDataList,opts){
			var filterList = $(targetDataList).closest('.content-container').find('.tag-list-container ul');
			console.log(filterList);
			opts = $.merge({
	            'videocategory': activeChannel,
	            'filtertype': filter
	        },opts);
	
	        $(filterList).empty().addClass('loading');
	        
		    ajax.genericAction('tag_ajaxgetusedinvideos', opts, function(r){
		        if(typeof r != 'undefined'){
		        	$(filterList).removeClass('loading');
	                if(typeof r.tags != 'undefined'){
	                    templateHelper.renderTemplate("general-tag_list", r.tags, filterList, false, function(){
	                    });
	                }
		        }
	        }, function(msg){
	            error(msg);
	        });
		}
	}
		
		
		
		
	
		
};