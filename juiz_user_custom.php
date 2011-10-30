<?php
/*
Plugin Name: Juiz User Custom
Plugin URI:
Description: Allows users to configure some extra meta values to make a rich authors or users page, for example. Add custom fields for all the users of WordPress in one time (in <a href="http://localhost/wordpress/wp-admin/users.php?page=juiz_user_custom">the setting page</a>). Edit or delete them when you want. <a href="http://localhost/wordpress/wp-admin/users.php?page=juiz_user_custom">Setting page</a>
Author: Geoffrey Crofte
Version: 0.1
Author URI: http://crofte.fr
License: GPLv2 or later 
*/

/*

Copyright 2011  Geoffrey Crofte  (email : support@creativejuiz.com)

    
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/


/*
Use at the frontend as get_the_author_meta('slug_of_meta') or the_author_meta('slug_of_meta')
*/

define('JUIZ_USER_CUSTOM_SLUG', 'juiz_user_custom');

$plugin_url = plugins_url('/juiz-user-custom/');  
define('JUIZ_USER_CUSTOM_PATH', $plugin_url);


// echo plugin_dir_path( __FILE__ );
// return "/var/www/wordpress/wp-content/plugins/juiz_user_custom/"

function make_juiz_custom_user_field_multi() {
	load_plugin_textdomain( 'juiz_cuf', false, JUIZ_USER_CUSTOM_SLUG . '/languages' );
}
add_action( 'init', 'make_juiz_custom_user_field_multi' );

class juiz_user_meta {
	
	
	function juiz_user_meta() {
		
		global $user_id, $juiz_user_infos;
		
		// $juiz_user_infos = get_user_meta( 1, 'juiz_user_custom_fields', true );
		$juiz_user_infos = get_option( 'juiz_user_custom_fields', 'a:0:{}' );
		$juiz_user_infos = unserialize ( $juiz_user_infos );
		
		if ( is_admin() ) {
			add_action('show_user_profile', array ( &$this,'action_show_user_profile' ) );
			add_action('edit_user_profile', array ( &$this,'action_show_user_profile' ) );
			add_action('personal_options_update', array ( &$this,'action_process_option_update' ) );
			add_action('edit_user_profile_update', array ( &$this,'action_process_option_update' ) );
			
			wp_enqueue_style( 'juiz_styles', JUIZ_USER_CUSTOM_PATH . 'css/juiz-admin.css', false, false, 'all' );
		}
	}

	function action_show_user_profile() {
		
		global $user_id, $juiz_user_infos;
		
	?>
	
		<h3><?php _e('Other info ?', 'juiz_cuf') ?></h3>
	
	<?php 
		if( count ( $juiz_user_infos ) != 0 ) {
	?>
		<table class="form-table">
			<?php
			foreach($juiz_user_infos as $k => $v) {
			?>
			<tr>
				<th>
					<label for="juiz_<?php echo $k; ?>"><?php echo $v[0]; ?></label>
				</th>
				<td>
					<?php
					if($v[1]=='text') {
					?>
						<input type="text" name="juiz_<?php echo $k; ?>" id="juiz_<?php echo $k; ?>" value="<?php echo esc_attr(get_the_author_meta('juiz_'.$k, $user_id) ); ?>" />
					<?php 
					}
					?>
				</td>
			</tr>
			<?php
			}
			?>
		</table>
		<?php
		}
		else {
		?>
		
		<p><?php _e('You need to', 'juiz_cuf') ?> <a href="<?php bloginfo('url') ?>/wp-admin/users.php?page=<?php echo JUIZ_USER_CUSTOM_SLUG; ?>"><?php _e('add a new user field', 'juiz_cuf') ?></a></p>
		
	<?php
		}
	}

	function action_process_option_update() {
		
		global $user_id, $juiz_user_infos;

		foreach($juiz_user_infos as $k => $v) {
		
			$k = 'juiz_'.$k;
			
			if ( function_exists( update_usermeta ) )
				update_usermeta ( $user_id, $k, ( isset ( $_POST[$k] ) ? $_POST[$k] : '' ) );
			else
				update_user_meta ( $user_id, $k, ( isset ( $_POST[$k] ) ? $_POST[$k] : '' ) );
		}
	}

}

/* Initialise plugin */
add_action('plugins_loaded', create_function('','global $juiz_user_meta_instance; $juiz_user_meta_instance = new juiz_user_meta();'));



/* Manage page */

require_once('juiz_manage_page.php');

?>