(function() {
	var $ = window.jQuery;
	$('#nearst-next-step').click(function() {
		$('#nearst-wp-step-1').hide();
		$('#nearst-wp-step-2').show();
		$('#nearst-upload-key').focus();
	});
})();
