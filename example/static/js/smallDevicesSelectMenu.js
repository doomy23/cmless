$(document).ready(function() {
	$('#smallDevicesSelectMenu').change(function(){
		if($('#smallDevicesSelectMenu').val()!='')
			window.location = $('#smallDevicesSelectMenu').val();
	});
});