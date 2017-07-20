$(document).ready(function() {
	//Show title history form on btn click
	$('.videos .video .title-history-show').click(function(e) {
		e.preventDefault();
		$(e.currentTarget).siblings('.title-history-form').slideDown();
	});
	
	$('.videos .video .title-history-form .btn-warning').click(function(e) {
		e.preventDefault();
		$(e.currentTarget).parent().slideUp();
	});

	//On title history item click, mark it as active
	$('.videos .video .title-history-form .list-group-item').click(function(e) {
		e.preventDefault();
		
		$(this).parent().find('.list-group-item').each(function(idx, el) {
			el.setAttribute('class', el.getAttribute('class').replace('active', ''));
		});
		
		e.currentTarget.setAttribute('class', e.currentTarget.getAttribute('class') + ' active');
	});
	
	//AJAX request to update video current title
	$('.videos .title-history-form .btn-success').click(function(e) {
		e.preventDefault();
		
		let $formContainer = $(e.currentTarget).parent();
		let $videoContainer = $formContainer.parent();
		
		let $activeTextContainer = $formContainer.find('.list-group-item.active');
		let newTitle, title, oldTitle;
		if ($activeTextContainer.length > 0) {
			newTitle = $formContainer.find('.list-group-item.active')[0].textContent;
			title = $videoContainer.find('.title')[0];
			oldTitle = title.textContent;
		}
		let owTitle = $formContainer.find('input[type=checkbox]')[0].checked;
		let videoId = $formContainer.parent().find('.video-id').val();
		let data = { title: newTitle, owTitle: owTitle, videoId: videoId };
		
		$.post(window.F3.baseUrl + '/videos/'+videoId+'/update', data, function(res) {
			if (res) {
				try {
					let resjson = JSON.parse(res);
					if (resjson.title !== undefined && $activeTextContainer.length > 0) {
						title.textContent = data.title;
						$formContainer.find('.list-group-item').each(function(idx, el) {
							if (el.textContent == newTitle) {
								el.textContent = oldTitle;
								el.setAttribute('class', el.getAttribute('class').replace('active', ''));
							}
						});
						
						$formContainer.slideUp();
					}
				}
				catch(e) {
					console.log(e);
				}
			}
		});
	});
});