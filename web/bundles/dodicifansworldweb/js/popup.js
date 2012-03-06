function resizePopup() {
	window.top.resizeColorbox({innerHeight: $('.popup-content').height() });
}

$(function(){
	resizePopup();
});