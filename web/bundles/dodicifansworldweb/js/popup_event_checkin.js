var checkIn = {};

checkIn.eventId = null;

checkIn.init = function(){
    checkIn.eventId = $(".cont.checkin ul.selectFrom").attr("eventId");
    $(".cont.checkin ul.selectFrom li").click(function(){
        var type = $(this).attr("type");
        var teamId = $(".selectTeam").val();
        if(typeof(teamId) == 'undefined'){
            teamId = false;
        }
        checkIn.sendAjax(type, teamId);
    });
};

checkIn.sendAjax = function(type, teamId) {
    ajax.genericAction('event_checkinajax', {
        event: checkIn.eventId, 
        type: type,
        team: teamId
    }, function(r){
        console.log(r);
        if(!r.error){
            $.colorbox.close();
        }
    }, function(r){
        console.error(r);
    });
};