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
    wallDestination: null,
    eventId: null,
    localId: null,
    guestId: null,
	
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
        events.loadEvents();
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
        events.loadEvents();
        success('filtros resetados');
    },
	
    resetDate: function(){
        events.calendarDestination.datepicker( "setDate", null );
        events.searchOptions.dateFrom = null;
        events.searchOptions.dateTo = null;
        events.loadEvents();
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
    },

    /* Event wall init */
    initDetail: function(wallDestination){
        events.wallDestination = wallDestination;
        events.eventId = $(".eventwall").attr('data-event-id');
        events.localId = $(".eventwall").attr('data-local-id');
        events.guestId = $(".eventwall").attr('data-guest-id');
        
        events.wallDestination.addClass('loading');
        
        events.listen(events.eventId);
        
        var toLoad	=	['event-wall_comment','event-wall_incident','event-wall_tweet'];
        
        templateHelper.preLoadTemplates(toLoad);
        
        events.loadWallElements(events.eventId);
        
        $("form[data-event-wall]").submit(function(e){
            e.preventDefault();
            var self = $(this);
            var input = self.find('input');
            input.addClass('loading-small');

            params = {
                'event-id': events.eventId,
                'text': input.val()
            };
           
            if(self.attr('data-event-wall') == 'local'){
                params['team-id'] = events.localId;
            }else{
                params['team-id'] = events.guestId;
            }
           
            ajax.genericAction('event_ajaxcomment', params, function(r){
                if(!r.error){
                    input.removeClass('loading-small');
                    input.val("");
                    success('Comentario enviado');
                }else{
                    error(r.msg);
                }
            }, function(e) {
                error(e);
            });
           
            return false;
        });
        
        $(".eventsupporters").hide();
        $(".eventdetail-togglebar .tabs button").click(function(){
            var section = $(this).attr('data-tab');
            var sectionActive = $(this).parent().find('.active').attr('data-tab');
           
            if(section != sectionActive ){
                $(".event"+sectionActive).fadeOut('fast',function(){
                    $(".event"+section).fadeIn("fast");
                });
            }
        });
        
        events.filterChannel();
    },
    
    filterChannel: function(p){
        var dropdownRoot = $("span.dropdown[data-filter-channel]");
        var selected = dropdownRoot.find('.active');

        if(p == 'get'){
            return selected;
        } else if( p == 'filter') {
            eventshipType = selected.attr('data-eventship-type');
            if(eventshipType == 'all'){
                $("[event-wall]").find("[data-eventship-type]").parent().fadeIn();
            }else{
                $("[event-wall]").find("[data-eventship-type][data-eventship-type!='" + eventshipType + "']").parent().fadeOut();
                $("[event-wall]").find("[data-eventship-type][data-eventship-type='" + eventshipType + "']").parent().fadeIn();
            }
        }

        dropdownRoot.find('ul.dropdown-menu li a').click(function(e){
            e.preventDefault();
          
            var clickedSrc = $(this).find('img').attr('src');
          
            dropdownRoot.find('a.dropdown-toggle img').attr('src', clickedSrc);
            dropdownRoot.find('.active').removeClass('active');
            $(this).parent().addClass('active');
          
            selected = $(this);
            eventshipType = $(this).parent().attr('data-eventship-type');
            if(eventshipType == 'all'){
                $("[event-wall]").find("[data-eventship-type]").parent().fadeIn();
            }else{
                $("[event-wall]").find("[data-eventship-type][data-eventship-type!='" + eventshipType + "']").parent().fadeOut();
                $("[event-wall]").find("[data-eventship-type][data-eventship-type='" + eventshipType + "']").parent().fadeIn();
            }
        });
      
    },
	
    listen: function(eventId){
        function handleData(response){
            response = JSON.parse(response);
            if(response){
                if(response.t == 'c'){
                    events.handleComment(response.id);
                }else if(response.t == 'et'){
                    events.handleEventTweet(response.id);
                }else if(response.t == 'ei'){
                    events.handleEventIncident(response.id);
                }
            }
        }
	    
        if ((typeof Meteor != 'undefined') && (typeof notificationChannel != 'undefined')) {
            Meteor.registerEventCallback("process", handleData);
	        
            Meteor.joinChannel('event_'+eventId);
            Meteor.joinChannel('wall_event_'+eventId);
	        
            Meteor.connect();
	                                       	            
            // Start streaming!
            console.log('Escuchando notifications...');
        }else console.log('no hay meteor');
    },
	
    handleComment: function(id) {
        var opts = {
            commentid: id
        };		
        ajax.genericAction('event_getcomment', opts, function (r) {
            if (typeof r !== "undefined") {
                var twitterCommentContainer = events.getTwitterCommentContainer(r.minute,r.teamid);            		
                templateHelper.renderTemplate("event-wall_comment", r, twitterCommentContainer, false, function () {
                    events.filterChannel('filter');
                });
            }
        }, function (msg) {
            console.error(msg);
        },'get');
	    
    },
	
    handleEventIncident: function(id) {
        var opts = {
            incidentid: id
        };		
        ajax.genericAction('event_getincident', opts, function (r) {
            if (typeof r !== "undefined") {
                var incidentContainer = events.getIncidentContainer(r.minute);            		
                templateHelper.renderTemplate("event-wall_incident", r, incidentContainer, false, function () {
                    events.filterChannel('filter');
                });
            }
        }, function (msg) {
            console.error(msg);
        },'get');
    },
	
    handleEventTweet: function(id) {
        var opts = {
            tweetid: id
        };		
        ajax.genericAction('event_gettweet', opts, function (r) {
            if (typeof r !== "undefined") {
                var twitterCommentContainer = events.getTwitterCommentContainer(r.minute,r.teamid);            		
                templateHelper.renderTemplate("event-wall_tweet", r, twitterCommentContainer, false, function () {
                    events.filterChannel('filter');
                });
            }
        }, function (msg) {
            console.error(msg);
        },'get');
    },
	
    getLocalVisitante: function(teamId){
        var localId = events.localId;
        var VisitanteId = events.guestId;
        var localVisitante = 'error';
        if(teamId == localId){
            localVisitante = 'local';
        }else if(teamId == VisitanteId){
            localVisitante = 'visitante';
        }
        return localVisitante;
    },
	
    getMinuteDestination: function(minute){
        var minuteDestination = events.wallDestination.find('.minute-container[minute='+minute+']');
        if(minuteDestination.size() == 0){
            events.wallDestination.find('.minute-container[minute]').filter(function() {
                return  $(this).attr("minute") < minute;
            }).slice(0, 1).before("<div class='minute-container' minute='"+minute+"'>");
    		
            minuteDestination = events.wallDestination.find('.minute-container[minute='+minute+']');
            if(minuteDestination.size() == 0){
                events.wallDestination.append("<div class='minute-container' minute='"+minute+"'>");
                minuteDestination = events.wallDestination.find('.minute-container[minute='+minute+']');
            }
        }
        return minuteDestination;
    },
	
    getIncidentContainer: function(minute){
        var minuteDestination = events.getMinuteDestination(minute);
        var incidentContainer = minuteDestination.find('fullwidth-container');
        if(incidentContainer.size() == 0){
            minuteDestination.append($("<div class='fullwidth-container'>") );
            incidentContainer = minuteDestination.find('.fullwidth-container');
        }
        return incidentContainer;
    },
	
    getTwitterCommentContainer: function(minute,teamId){
        var minuteDestination = events.getMinuteDestination(minute);
        var tweetCommentContainer = null;
        var localVisitante = events.getLocalVisitante(teamId);
        if(localVisitante != 'error' ){
            tweetCommentContainer = minuteDestination.find('.'+localVisitante+'-container');
            if(tweetCommentContainer.size() == 0){
                minuteDestination.append($("<div class='"+localVisitante+"-container'>") );
                tweetCommentContainer = minuteDestination.find('.'+localVisitante+'-container');
            }
        }
        
        return tweetCommentContainer;
    },
	
    loadWallElements: function(eventId,fromMinute,toMinute){
        if(typeof fromMinute == 'undefined'){
            fromMinute = null;
        }
		
        if(typeof toMinute == 'undefined'){
            toMinute = null;
        }
		
        var opts = {
            eventid: eventId,
            fromMinute: fromMinute,
            toMinute: toMinute
        };		
        ajax.genericAction('event_getwallelements', opts, function (r) {
            if (typeof r !== "undefined") {
                events.wallDestination.removeClass('loading');
                $.each(r,function(index,element){
                    if(element.type == 'c'){
                        var twitterCommentContainer = events.getTwitterCommentContainer(element.minute,element.teamid);            		
                        templateHelper.renderTemplate("event-wall_comment", element, twitterCommentContainer, false, function () {
                            });
                    }else if(element.type == 'et'){
                        var twitterCommentContainer = events.getTwitterCommentContainer(element.minute,element.teamid);            		
                        templateHelper.renderTemplate("event-wall_tweet", element, twitterCommentContainer, false, function () {});
                    }else if(element.type == 'ei'){
                        var incidentContainer = events.getIncidentContainer(element.minute);            		
                        templateHelper.renderTemplate("event-wall_incident", element, incidentContainer, false, function () {});
                    }
                } );
            }
        }, function (msg) {
            console.error(msg);
        },'get');
    }
	
	
	
	
	
	
};