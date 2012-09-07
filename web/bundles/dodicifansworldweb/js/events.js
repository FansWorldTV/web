var events = {
	actualYear : $.datepicker.formatDate('yy', new Date()),
	actualMonth : $.datepicker.formatDate('mm', new Date()),
	calendarDestination: null,
	eventGridDestination: null,
	selectedDates: {},
	seletedText:   {},
	
	initEventHome: function(calendarDestination,eventGridDestination){
		events.eventGridDestination = eventGridDestination;
		events.initCalendar(calendarDestination);
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
	 	      events.getDayEvents(dateText);
	 	   }
	 	});
	},
	
	getDayEvents: function(dateText, callback){
		events.eventGridDestination.empty().addClass('loading');
		ajax.genericAction('event_getday', 
			{
				date: dateText
			}, 
			function(r){
		        if(typeof r != 'undefined'){
		        	events.loadEvents(r);
		        	
		        	if(typeof callback != 'undefined')
	        		{
		        		callback();
	        		}
		        	events.eventGridDestination.removeClass('loading');
		        }
	        }, function(msg){
	            error(msg);
	            events.eventGridDestination.removeClass('loading');
	        },'get',true);
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
	        		/*
	        		var tempDate = value.fecha.date.split(' ');
	        		var tmpdt = $.datepicker.formatDate('yy-mm-dd', new Date(tempDate[0] ));
	        		*/
	        		events.selectedDates[value.fecha] = true;
	        		//events.seletedText[new Date(tmpdt)] = 'demo text';//value.id;
	        		
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
	
	loadEvents:	function(eventsList){
		//calendarDestination
		
		templateHelper.renderTemplate("event-grid_element", eventsList, events.eventGridDestination, false, function(){
			//console.log('done');
		});
	}
	
	
};