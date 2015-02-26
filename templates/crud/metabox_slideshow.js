var appNG = angular.module('metabox_slideshow', [])

.controller('ImagensCtrl', function() {
	this.slideshow = slideshow_db;
	if (this.slideshow.imgs == undefined)
		this.slideshow.imgs = [];

	this.Remove = function(index) {
		if (confirm('Deseja mesmo exlcuir o item?'))
			this.slideshow.imgs.splice(index, 1);
	};
});

function NetSisMetaboxSlideShow() {}

NetSisMetaboxSlideShow.Run = function() {
	jQuery(function ($) {
		$('.nova_imagem').click(function(e) {
			e.preventDefault();

			//Extend the wp.media object
			var custom_uploader = wp.media.frames.file_frame = wp.media({
				title: 'Galeria de Imagens',
				button: {
					text: 'Adicionar ao Slideshow'
				},
				library: {
					type: 'image'
				},
				multiple: true
			});

			//When a file is selected, grab the URL and set it as the text field's value
			custom_uploader.on('select', function() {
				var selection = custom_uploader.state().get('selection');
				selection.map(function(attachment) {
					attachment = attachment.toJSON();
					
					var url = '';
					if ((attachment.sizes !== undefined) && (attachment.sizes.thumbnail !== undefined))
						url = attachment.sizes.thumbnail.url;
					else
						url = attachment.url;

					var scope = angular.element('.image_gallery').scope();
					scope.$apply(function() {
						scope.ctrl.slideshow.imgs[scope.ctrl.slideshow.imgs.length] = {
							id: attachment.id,
							url: url,
							title: attachment.title
						};
					});
				});
			});

			//Open the uploader dialog
			custom_uploader.open();
		});

		$('ul.image_gallery').sortable({
			items: '> li',
			opacity: 0.5
		});

		$('#publish').click(function(e) {
			var slideshow = {
				imgs: []
			};

			slideshow.width = $('input[name="img_width"]').val();
			slideshow.height = $('input[name="img_height"]').val();

			$('ul.image_gallery li').each(function() {
				slideshow.imgs[slideshow.imgs.length] = {
					id: $(this).find('img').data('imageId'),
					link: $(this).find('input[type="text"]').val()
				};
			});

			$('#_slideshow').val(JSON.stringify(slideshow));
		});
	});
}

jQuery(function ($) {
	$(document).ready(function() {
		NetSisMetaboxSlideShow.Run();
	});
});