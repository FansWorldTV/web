$(document).ready(function(){
    checkIn.init();
});

var checkIn = {};

checkIn.eventId = null;

checkIn.init = function(){
    checkIn.eventId = $(".cont.checkin ul.selectFrom").attr("eventId");
    $(".cont.checkin ul.selectFrom li").click(function(){
        var type = $(this).attr("type");
        checkIn.sendAjax(type);
    });
};

checkIn.sendAjax = function(type) {
    ajax.genericAction('event_checkinajax', {
        event: checkIn.eventId, 
        type: type
    }, function(r){
        console.log(r);
        if(!r.error){
            window.top.$.colorbox.close();
        }
    }, function(r){
        console.error(r);
    });
};