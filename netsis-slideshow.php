<?php
/*
Plugin Name: NetSis - Slideshow
Plugin URI: 
Description: Slideshow
Version: 0.1.0
Author: NetSis - Sistemas Web
Author URI: http://www.netsis.com.br
License: Copyright
*/

$exception = null;

include_once(sprintf("%s/../netsis/classes/NetSisUserUtil.php", dirname(__FILE__)));

if(!class_exists('NetSisSlideShow'))
{
	class NetSisSlideShow
	{
		/**
		 * Construct the plugin object
		 */
		public static function Run()
		{
			add_action('init', 'NetSisSlideShow::init');
			add_action('admin_enqueue_scripts', 'NetSisSlideShow::admin_enqueue_scripts');

			add_action('add_meta_boxes', 'NetSisSlideShow::custom_post_type_interface');
			add_action('save_post', 'NetSisSlideShow::custom_post_type_save_meta', 1, 2);
        }

        /**
         * Activate the plugin
         */
        public static function activate()
        {
            // Do nothing
        }

        /**
         * Deactivate the plugin
         */     
        public static function deactivate()
        {
            // Do nothing
        }

        public static function init()
        {
        	// Slideshow
        	// ------------
			register_post_type('ns_slideshow',
				array(
					'labels' => array(
						'name' => 'Slideshow',
						'singular_name' => 'Slideshow',
						'add_new' => 'Novo',
						'add_new_item' => 'Novo Slideshow',
						'edit_item' => 'Editar Slideshow',
						'new_item' => 'Novo Slideshow',
						'view_item' => 'Ver Slideshow',
						'search_itens' => 'Procurar Slideshow'
					),
					'public' => true,
					'has_archive' => true,
					'rewrite' => array('slug' => 'ns_slideshows'),
					'hierarchical' => false,
					'supports' => array('title')
				)
			);

			//shortcodes
			add_shortcode('netsis_slideshow', 'NetSisSlideShow::shortcode_slideshow');

			//order by title
			add_filter('posts_orderby', 'NetSisSlideShow::posts_orderby_custom', 10, 2);
		}

		public static function admin_enqueue_scripts()
		{
			global $post;

			if ($post->post_type == 'ns_slideshow')
			{
				wp_enqueue_media();
				wp_enqueue_script('angularjs', 'https://ajax.googleapis.com/ajax/libs/angularjs/1.3.0-rc.2/angular.min.js');
			}
		}

        public static function posts_orderby_custom($orderby, &$query){
			global $wpdb;

			//figure out whether you want to change the order
			switch(get_query_var('post_type')) {
				case 'ns_slideshow':
					return "$wpdb->posts.post_title ASC";

				default:
					return $orderby;
			}
		}

        public static function custom_post_type_interface() {
			//slideshow
			add_meta_box('metabox_slideshow', 'Imagens', 'NetSisSlideShow::metabox_slideshow', 'ns_slideshow', 'normal', 'default');
        }

		public static function metabox_slideshow($post) {
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('metabox_slideshow', plugins_url('/templates/crud/metabox_slideshow.js', __FILE__), array('jquery'));
			wp_enqueue_style('metabox_slideshow', plugins_url('/templates/crud/metabox_slideshow.css', __FILE__));

			include_once(sprintf("%s/templates/crud/metabox_slideshow.php", dirname(__FILE__)));
		}

		public static function custom_post_type_save_meta($post_id, $post) {
			// Don't store custom data twice
			if($post->post_type == 'revision')
				return;

			// verify this came from the our screen and with proper authorization,
			// because save_post can be triggered at other times
			//if (!wp_verify_nonce($_POST['eventmeta_noncename'], 'netsis-site')) //ToDo: tratar nonce
			//	return $post->ID;

			// Is the user allowed to edit the post or page?
			if (!current_user_can('edit_post', $post->ID))
				return $post->ID;

			$events_meta = array();

			// OK, we're authenticated: we need to find and save the data
			// We'll put it into an array to make it easier to loop though.
        	switch($post->post_type) {
        		case 'ns_slideshow':
        			$events_meta['_slideshow'] = ($_POST['_slideshow'] != '') ? stripslashes($_POST['_slideshow']) : null;

					if ($events_meta['_slideshow'] != '') {
						$slideshow = @json_decode($events_meta['_slideshow']);
						if ($slideshow != null)
							NetSisSlideShow::GenerateImages($slideshow);
					}
        			break;

        		default:
        			return;
        	}

        	// Add values of $events_meta as custom fields
			foreach ($events_meta as $key => $value) { // Cycle through the $events_meta array!
				$value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
				if(get_post_meta($post->ID, $key, FALSE)) // If the custom field already has a value
					update_post_meta($post->ID, $key, $value);
				else // If the custom field doesn't have a value
					add_post_meta($post->ID, $key, $value);

				if(!$value)
					delete_post_meta($post->ID, $key); // Delete if blank
			}
        }

		public static function shortcode_slideshow($attr)
		{
			ob_start();
			include(sprintf("%s/templates/shortcodes/slideshow.php", dirname(__FILE__)));
			return ob_get_clean();
		}

		public static function GenerateImages($slideshow)
		{
			if ((count($slideshow->imgs) > 0) && ($slideshow->width > 0) && ($slideshow->height > 0)) {
				$tamanho = 'ns_slideshow_'.$slideshow->width.'x'.$slideshow->height;
				$upload_dir_info = wp_upload_dir();
				foreach ($slideshow->imgs as $img) {
					$image = wp_get_attachment_metadata($img->id);
					$image_path = $upload_dir_info['basedir'].'/'.substr($image['file'], 0, strrpos($image['file'], '/') + 1);

					$tamanho_existe = false;
					foreach ($image['sizes'] as $size => $size_info) {
						if ($size == $tamanho)
						{
							if (file_exists($image_path.$size_info['file']))
								$tamanho_existe = true;

							break;
						}
					}

					if (!$tamanho_existe) {
						add_image_size($tamanho, $slideshow->width, $slideshow->height);
						wp_generate_attachment_metadata($img->id, $upload_dir_info['basedir'].'/'.$image['file']);
					}
				}
			}
		}
    }

	// Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('NetSisSlideShow', 'activate'));
    register_deactivation_hook(__FILE__, array('NetSisSlideShow', 'deactivate'));

    // run
    NetSisSlideShow::Run();
}
?>