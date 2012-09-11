var events = {
	actualYear : $.datepicker.formatDate('yy', new Date()),
	actualMonth : $.datepicker.formatDate('mm', new Date()),
	calendarDestination: null,
	eventGridDestination: null,
	selectedDates: {},
	filterLoading: null,
	sortByDropdown: null,
	sportDropdown: null,
	teamcategoriesDropdown :null,
	searchOptions: {},
	resetFiltersButton: null,
	resetDateButton: null,
	dateSelected: false,
	
	initEventHome: function(calendarDestination,eventGridDestination){
		events.eventGridDestination = eventGridDestination;
		events.filterLoading = $('.eventfilters-container .loading-container');
		events.sortByDropdown = $('.eventfilters-container .order-by');
		
		events.sportDropdown = $('.eventfilters-container .sports');
		events.teamcategoriesDropdown = $('.eventfilters-container .leagues');
		events.resetFiltersButton = $('.eventfilters-container .resetfilters');
		events.resetDateButton = $('.eventcalendar-container .resetdate');
		
		events.initCalendar(calendarDestination);
		events.bindSportDropdown();
		events.bindTeamcategoriesDropdown();
		events.bindSortbyDropdown();
		events.bindResets();
	},
	
	initCalendar: function(calendarDestination){
		events.calendarDestination = calendarDestination;
		events.getMonthEvents(events.actualYear,events.actualMonth,events.bindCalendar);
	},
	
	bindCalendar: 	function(){
		events.calendarDestination.datepicker({
        	onChangeMonthYear: function(year, month, inst) { 
        		events.getMonthEvents(year,month);
    		},
	 	   beforeShowDay: function(date)
	 	   {
	 		   
    	 	  var tmpDate = $.datepicker.formatDate('dd-mm-yy', date);
	 	      
    	 	  var Highlight = events.selectedDates[tmpDate];
 
	 	      if (Highlight) {
	 	         return [true, "highlighted", 'Haga Click para ver los eventos del dia'];
	 	      }
	 	      else {
	 	         return [false, '', ''];
	 	      }
	 	   },
	 	  onSelect: function(dateText, inst) {
	 		  events.dateSelected = true;
	 	      events.getDayEvents(dateText);
	 	   }
	 	});
	},
	
	getDayEvents: function(dateText, callback){
		events.updateOptions();
		events.searchOptions.dateFrom = dateText;
		events.searchOptions.dateTo   = dateText;
			
		events.loadEvents();
	},
	
	
	getMonthEvents: function(year,month,callback){
		events.calendarDestination.find('.ui-datepicker-title').addClass('loading-small');
		ajax.genericAction('event_getmonth', 
		{
			year: year,
			month: month
		}, 
		function(r){
	        if(typeof r != 'undefined'){
	        	$.each(r,function(index,value){
	        		events.selectedDates[value.fecha] = true;
	        	});
	        	if(typeof callback != 'undefined')
        		{
	        		callback();
        		}
	        	events.calendarDestination.find('ui-datepicker-title ').removeClass('loading-small');
	        }
        }, function(msg){
            error(msg);
            events.calendarDestination.find('ui-datepicker-title ').removeClass('loading-small');
        });
	},
	
	loadEvents:	function(updateOptions){
		if(updateOptions){
			events.updateOptions();
		}
		
		events.eventGridDestination.empty().addClass('loading');
		ajax.genericAction('event_get', 
			events.searchOptions, 
			function(r){
		        if(typeof r != 'undefined'){
		        	templateHelper.renderTemplate("event-grid_element", r, events.eventGridDestination, false, function(){
		        		$('[text-height=ellipsis]').ellipsis();
		        		if(typeof callback != 'undefined')
		        		{
			        		callback();
		        		}
		    		});
		        }
		        events.eventGridDestination.removeClass('loading');
		        
	        }, function(msg){
	            error(msg);
	            events.eventGridDestination.removeClass('loading');
	        },'get',true);
	},
	
	bindSportDropdown: function(){
		
		events.sportDropdown.on('click','ul.dropdown-menu li a',function(e){
			events.sportDropdown.find('.active').removeClass('active');
			$(this).parent().addClass('active');
			events.filterLoading.addClass('loading-small');
			
			var sportId = $(this).attr('sport-id');
			ajax.genericAction('sport_getteamcategories', 
				{
					sportId: sportId
				}, 
				function(r){
			        if(typeof r != 'undefined'){
			        	events.loadTeamcategories(r.categories);
			        	events.loadEvents(true);
			        	if(typeof callback != 'undefined')
		        		{
			        		callback();
		        		}
			        }
			        events.filterLoading.removeClass('loading-small');
		        }, function(msg){
		            error(msg);
		            events.filterLoading.removeClass('loading-small');
		        },'get');		
			
			
		});
	},
	
	loadTeamcategories: function(categories){
		events.teamcategoriesDropdown.find('ul.dropdown-menu').empty();
		$.each(categories,function(i,value){
			events.teamcategoriesDropdown.find('ul.dropdown-menu').append("<li><a category-id='"+value.id+"' tabindex='-1' >"+value.title+"</a></li>");
    	});
	},
	
	bindTeamcategoriesDropdown: function(){
		events.teamcategoriesDropdown.on('click','ul.dropdown-menu li a',function(e){
			events.teamcategoriesDropdown.find('.active').removeClass('active');
			$(this).parent().addClass('active');
			events.loadEvents(true);
		});
	},
	
	bindSortbyDropdown: function(){
		events.sortByDropdown.on('click','ul.dropdown-menu li a',function(e){
			events.sortByDropdown.find('.active').removeClass('active');
			$(this).parent().addClass('active');
			events.loadEvents();
		});
	},
	
	updateOptions: function(){
		if(!events.dateSelected){
			events.calendarDestination.datepicker( "setDate", null);
		}
		var tmpDate = events.calendarDestination.datepicker( "getDate" );
		if(tmpDate != null){
			tmpDate = $.datepicker.formatDate('dd/mm/yy', tmpDate);
		}
		
		events.searchOptions.dateFrom = tmpDate;
		events.searchOptions.dateTo = tmpDate;
		events.searchOptions.sortBy = events.getSortBy();
		events.searchOptions.sport  = events.getSport();
		events.searchOptions.teamcategory  = events.getTeamcategory();
	},
	
	getSortBy: function(){
		return events.sortByDropdown.find('.dropdown-menu li.active a').attr('sort-by');
	},
	
	getSport: function(){
		return events.sportDropdown.find('.dropdown-menu li.active a').attr('sport-id');
	},
	
	getTeamcategory: function(){
		return events.teamcategoriesDropdown.find('.dropdown-menu li.active a').attr('category-id');
	},
	
	resetFilters: function(){
		events.searchOptions.sortBy = null;
		
		events.searchOptions.sport  = null;
		events.sportDropdown.find('.active').removeClass('active');
		
		events.searchOptions.teamcategory  = null;
		events.teamcategoriesDropdown.find('.active').removeClass('active');
		events.teamcategoriesDropdown.find('ul.dropdown-menu').empty();
		
		success('filtros resetados');
	},
	
	resetDate: function(){
		events.calendarDestination.datepicker( "setDate", null );
		events.searchOptions.dateFrom = null;
		events.searchOptions.dateTo = null;
		
		success('fecha resetada');
	},
	
	bindResets: function(){
		events.bindResetFilters();
		events.bindResetDate();
	},
	
	bindResetFilters: function(){
		events.resetFiltersButton.on('click',function(e){
			events.resetFilters();
		});
	},
	
	bindResetDate: function(){
		events.resetDateButton.on('click',function(e){
			events.resetDate();
		});
	}
	
};