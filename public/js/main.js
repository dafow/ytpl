$(document).ready(function() {
	$(".refresh-all").click(function() {
		var ytid = this.getAttribute("data-ytid");
		
		$.ajax({
		   url: '/playlists/update/' + ytid,
		   type: 'PUT',
		   success: function(response) {
			 console.log(response);
		   }
		});
	});
});