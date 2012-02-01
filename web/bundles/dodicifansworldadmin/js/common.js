
$(function(){
	$.datepicker.setDefaults($.datepicker.regional['es']);
	$('.datetimepicker').datetimepicker({
		dateFormat: 'dd/mm/yy',
		timeFormat: 'hh:mm'
	});
	
	$('.timepicker').datetimepicker({
		timeFormat: 'hhmm',
		timeOnly: true,
		stepMinute: 5
	});
	
	tinyMCE.init({
		mode: "specific_textareas",
		language: "en",
		editor_selector : "tinymce",
		theme : "advanced",
		plugins : "imagemanager,autolink,lists,table,advlink,preview,contextmenu,paste,directionality,noneditable",
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,tablecontrols",
		theme_advanced_buttons2 : "undo,redo,|,link,unlink,insertimage,|,cleanup,|,preview",
		theme_advanced_buttons3 : "",
		relative_urls : false,
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_statusbar_location : "bottom",
        theme_advanced_resizing : true,

        // Skin options
        skin : "o2k7",
        skin_variant : "silver",

	});
});