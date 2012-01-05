<?php
/*
Plugin Name: Juiz User Custom Meta
Plugin URI:
Description: Allows users to configure some extra meta values to make a rich authors or users page, for example. Add custom fields for all the users of WordPress in one time (in the setting page in *user* submenu). Edit or delete them when you want.
Author: Geoffrey Crofte
Version: 0.5
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

$plugin_url = plugins_url('/juiz-user-custom/');  

define('JUIZ_USER_CUSTOM_SLUG', 'juiz_user_custom_meta');
define('JUIZ_USER_CUSTOM_PATH', $plugin_url);
define('JUIZ_SHOW_DONATION_LINK', true); // put "false" if you don't want to see donation link


// echo plugin_dir_path( __FILE__ );
// return "/var/www/wordpress/wp-content/plugins/juiz-user-custom/"

class juiz_user_meta {
	
	
	function juiz_user_meta() {
		
		global $user_id, $juiz_user_infos;

		$juiz_user_infos = get_option( 'juiz_user_custom_fields', 'a:0:{}' );
		$juiz_user_infos = unserialize ( $juiz_user_infos );
		
		if ( is_admin() ) {
			add_action('show_user_profile', array ( &$this,'juiz_action_show_user_profile' ) );
			add_action('edit_user_profile', array ( &$this,'juiz_action_show_user_profile' ) );
			add_action('personal_options_update', array ( &$this,'juiz_action_process_option_update' ) );
			add_action('edit_user_profile_update', array ( &$this,'juiz_action_process_option_update' ) );
			
			add_action( 'init', array ( &$this,'make_juiz_custom_user_field_multilang') );
			
			wp_enqueue_style( 'juiz_styles', JUIZ_USER_CUSTOM_PATH . 'css/juiz-admin.css', false, false, 'all' );
		}
	}
	
	// languages
	function make_juiz_custom_user_field_multilang() {
		load_plugin_textdomain( 'juiz_cuf', false, 'juiz-user-custom/languages' );
	}

	// user profil
	function juiz_action_show_user_profile() {
		
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

	function juiz_action_process_option_update() {
		
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

require_once('juiz-manage-page.php');

?>