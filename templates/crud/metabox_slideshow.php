<?php
$slideshow_str = get_post_meta($post->ID, '_slideshow', true);
$slideshow = @json_decode($slideshow_str);
//se não for uma string json válida, anula o valor retornado
if ($slideshow == null)
	$slideshow_str = '';
?>
<input id="_slideshow" type="hidden" name="_slideshow" value="<?php echo htmlspecialchars($slideshow_str); ?>" />
<script type="text/javascript">
<?php
	if ($slideshow != null)
	{
		$upload_dir_info = wp_upload_dir();

		$imgs = $slideshow->imgs;
		for ($i = 0; $i < count($imgs); $i++) {
			$img = $slideshow->imgs[$i];
			$image = wp_get_attachment_metadata($img->id);

			if ($image)
			{
				if (array_key_exists('sizes', $image) && array_key_exists('thumbnail', $image['sizes']))
					$img->url = $upload_dir_info['baseurl'].'/'.substr($image['file'], 0, strrpos($image['file'], '/') + 1).$image['sizes']['thumbnail']['file'];
				else
					$img->url = $upload_dir_info['baseurl'].'/'.$image['file'];

				$img->title = $image['image_meta']['title'];
			}
			else
				$slideshow->imgs[$i] = null;
		}

		$slideshow->imgs = array_filter($slideshow->imgs);
	}
	else
		$slideshow = new stdClass();
?>
	var slideshow_db = JSON.parse('<?php echo json_encode($slideshow); ?>');
</script>
<div ng-app="metabox_slideshow" ng-controller="ImagensCtrl as ctrl">
	<div class="size">
		Tamanho: <input type="text" name="img_width" value="{{ctrl.slideshow.width}}" /> x <input type="text" name="img_height" value="{{ctrl.slideshow.height}}" /> px
	</div>
	<button class="button button-primary button-small nova_imagem">Nova Imagem</button>
	<div class="clearfix"></div>
	<ul class="image_gallery attachments ui-sortable" ng-model="ctrl.slideshow.imgs">
		<li ng-repeat="img in ctrl.slideshow.imgs">
			<a class="media-modal-close" href="#" ng-click="ctrl.Remove($index)"><span class="media-modal-icon"><span class="screen-reader-text">Excluir</span></span></a>
			<img ng-src="{{img.url}}" alt="{{img.title}}" data-image-id="{{img.id}}" />
			<label for="img_{{img.id}}">Link:</label><br />
			<input type="text" value="{{img.link}}" id="img_{{img.id}}" />
			<div class="clearfix"></div>
		</li>
	</ul>
</div>