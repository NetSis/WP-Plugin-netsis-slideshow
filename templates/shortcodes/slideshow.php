<?php
if ($attr['name'] != '') {
	$ul_class = '';
	if ($attr['ul_class'] != '')
		$ul_class = ' class="'.$attr['ul_class'].'"';
?>
<ul<?php echo $ul_class; ?>>
<?php
	$args = array('post_type' => 'ns_slideshow', 'name' => $attr['name']);

	$loop = new WP_Query($args);
	if ($loop->have_posts()) {
		$loop->the_post();

		$slideshow_str = get_post_meta(get_the_ID(), '_slideshow', true);
		$slideshow = @json_decode($slideshow_str);
		if ($slideshow != null) {
			$upload_dir_info = wp_upload_dir();
			$image_size = 'ns_slideshow_'.$slideshow->width.'x'.$slideshow->height;

			foreach ($slideshow->imgs as $img) {
				$image = wp_get_attachment_metadata($img->id);

				$image_name = substr($image['file'], strrpos($image['file'], '/') + 1);
				$image_ext = substr($image_name, strrpos($image_name, '.'));
				$image_name = substr($image_name, 0, strrpos($image_name, '.'));
				$image_path = substr($image['file'], 0, strrpos($image['file'], '/') + 1);

				$image_ideal = $image_path.$image_name.'-'.$slideshow->width.'x'.$slideshow->height.$image_ext;
				$image_name = $image_path.$image_name.$image_ext;
				if (file_exists($upload_dir_info['basedir'].'/'.$image_ideal))
					$image_url = $upload_dir_info['baseurl'].'/'.$image_ideal;
				else
					$image_url = $upload_dir_info['baseurl'].'/'.$image_name;

				echo '<li>';

				if ($img->link != '') {
					if ((strpos($img->link, 'http://') === false) && (strpos($img->link, 'https://') === false) && (substr($img->link, 0, 7) != 'mailto:'))
						$img->link = 'http://'.$img->link;

					echo '<a href="'.$img->link.'">';
				}

                $img_title = (property_exists($img, 'title')) ? $img->title : '';
				echo '<img src="'.$image_url.'" alt="'.$img_title.'" />';

				if ($img->link != '')
					echo '</a>';

				echo '</li>';
			}
		}
	}
?>
</ul>
<?php } //if ($attr['name'] != '') ?>