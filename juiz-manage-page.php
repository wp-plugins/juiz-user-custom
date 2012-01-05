<?php
/*
	
	Page of setting
	Allow admin to add custom user field by using the menu "Juiz User Custom"

*/


/* Initialize options */

function add_juiz_options_registration() {
	add_option( 'juiz_user_custom_fields', 'a:0:{}' );
}
add_action('admin_init','add_juiz_options_registration');



/* The manage page */

function add_juiz_manage_user_custom_page() {
	add_submenu_page( 'users.php', __('Settings of user custom fields', 'juiz_cuf'), __('Settings of user custom fields', 'juiz_cuf'), 'add_users', JUIZ_USER_CUSTOM_SLUG , 'juiz_manage_user_custom_page' );
}

function juiz_manage_user_custom_page() {
	
	global $user_id, $current_user, $wpdb;
	
	$existing_fields = $juiz_metadata = $need_an_edit = false;
	$edit_form_title = $edit_form_name = $edit_old_key = $the_old_label = $the_old_slug = $the_old_type = '';

	// information about the user
	$can_create_users = (isset($current_user->data->user_level) AND $current_user->data->user_level >=8) ? true : false;
	if ( !$can_create_users )
		$can_create_users = ($current_user->allcaps['create_users']) ? true : false;
	
	// informatives variables
	$juiz_message = '';
	$juiz_error = 0;
	
	function juiz_slugIt($string) {
		$tofind = 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ';
		$replac = 'aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn';
		$string = strtolower( utf8_decode ( $string ) );
		$string = strtr( $string, utf8_decode( $tofind ), $replac );
		$string = preg_replace('/-/','_',$string);
		$string = preg_replace('/(\s+)/','_',$string);
		
		return preg_replace('/[^a-z0-9\_]/i','', utf8_encode ( $string ) );
	}
	
	
	/*
		===============================
		IN CASE OF ADDING SOMETHING
		==============================
	*/
	
	// if the user has rights and send a post information
	if ( $can_create_users && $_GET['page']==JUIZ_USER_CUSTOM_SLUG && isset($_POST['new_field_label']) && trim($_POST['new_field_label'])!='') {
		
		$new_label = trim($_POST['new_field_label']);
		$new_slug = (trim ( $_POST['new_field_slug'] ) != '' )  ? juiz_slugIt(trim($_POST['new_field_slug'])) : juiz_slugIt($new_label);
		
		// when we add a custom user field
		if ( $juiz_metadata = get_option( 'juiz_user_custom_fields' ) ) {
			
			$prev_value = $juiz_metadata; // to be sure when we use update_user_meta line 71
			$juiz_metadata = unserialize ( $juiz_metadata );
			
			// if the custom field already exists
			if ( array_key_exists ( $new_slug, $juiz_metadata ) ) {
				$juiz_message = __('This custom user field already exists.', 'juiz_cuf');
				$juiz_error++;
			}
			// else we add the new custom to the array
			else {
				$juiz_metadata[$new_slug] = array($new_label,'text');
				
				// we keep the juiz_metatada as an array for later ;)
				$juiz_custom_fields = serialize( $juiz_metadata );
				
				// we update the list of custom fields
				$updating_user_meta = update_option( 'juiz_user_custom_fields', $juiz_custom_fields );
				
				if($updating_user_meta)
					// Youpi!
					$juiz_message = __('Your custom user field named', 'juiz_cuf').' <a href="#juiz_'.$new_slug.'">'.$new_label.'</a> '.__('is added', 'juiz_cuf');
				else {
					// should not arrived...
					$juiz_message = __('Adding of field failed', 'juiz_cuf');
					$juiz_error++;
				}
			}
			
		}
		
		// for the first custom added
		else {
			$juiz_custom_fields = array(
				$new_slug => array ( $new_label, 'text' )
			);
			$juiz_custom_fields = serialize($juiz_custom_fields);
			$adding_new_meta = update_option('juiz_user_custom_fields', $juiz_custom_fields);
			
			
			// we test if the add_metadata success
			if($adding_new_meta)
				// Youpi!
				$juiz_message = __('Your first custom user field named', 'juiz_cuf').' <a href="#juiz_'.$new_slug.'">'.$new_label.'</a> '.__('is added', 'juiz_cuf');
			// else we sho an error message
			else {
				$juiz_message = __('Adding of field failed', 'juiz_cuf');
				$juiz_error++;
			}
		}
	}
	// if the user has rights and lets the field blank
	elseif ( $can_create_users && $_GET['page']==JUIZ_USER_CUSTOM_SLUG && isset($_POST['new_field_label']) && trim($_POST['new_field_label'])=='') {
		$juiz_message = __('You need to fill the field', 'juiz_cuf').' <strong><a href="#new_field_label">'.__('Label of field', 'juiz_cuf').'</a></strong>';
		$juiz_error++;
	}
	// if the user hasn't rights
	elseif (!$can_create_users && $_GET['page']==JUIZ_USER_CUSTOM_SLUG && isset($_POST['new_field_label'])) {
		$juiz_message = __("Sorry, you can't do that !", 'juiz_cuf');
		$juiz_error++;
	}
	
	
	/*
		===============================
		IN CASE OF DELETING SOMETHING
		==============================
	*/
	
	if ($can_create_users && isset( $_GET['do'] ) && $_GET['do']=='delete' && isset($_GET['custom']) && $_GET['custom']!='') {
		
		if ( $juiz_metadata = get_option( 'juiz_user_custom_fields' ) ) {

			$before_del = $juiz_metadata; // to be sure when we use update_user_meta line XXX
			$slug_2_del = $_GET['custom'];
			$juiz_metadata = unserialize ( $juiz_metadata );

			// if the custom field exists, we can delete it
			if ( array_key_exists ( $slug_2_del, $juiz_metadata ) ) {

				unset ( $juiz_metadata[$slug_2_del] );
				$juiz_custom_fields = serialize ( $juiz_metadata );
				$updating_user_meta = update_option( 'juiz_user_custom_fields', $juiz_custom_fields );
				
				$delete_users_fields = $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->usermeta WHERE meta_key = %s ", 'juiz_'.$slug_2_del));
				// $wpdb->show_errors();
				// $wpdb->print_error();
				$wpdb->flush(); // freedooooom!
				
				if($updating_user_meta) {
					$juiz_message = __("This custom user field has correctly been removed.", 'juiz_cuf');
					$juiz_message .= ($delete_users_fields == 0) ?  '<br /><i>'.__("Note: no user had informed this field", 'juiz_cuf').'</i>' : '<br /><i><b>'.$delete_users_fields.'</b> '.__('user(s) had informed this field', 'juiz_cuf').'</i>';
				}
				else {
					$juiz_message = __("An error has occured. Deleting isn't possible...", 'juiz_cuf');
					$juiz_error++;
				}
			}
			// else... we can't :p
			else {
				$juiz_message = __("This custom user field doesn't exist...", 'juiz_cuf');
				$juiz_error++;
			}
		}
	}
	elseif (!$can_create_users && isset( $_GET['do'] ) &&  $_GET['do']=='delete') {
		$juiz_message = __("Sorry, you can't do that !", 'juiz_cuf');
		$juiz_error++;
	}
	
	/*
		==============================
		IN CASE OF WE ASK AN EDITION (need another form)
		==============================
	*/
	
	if ($can_create_users && isset( $_GET['do'] ) && $_GET['do']=='edit' && isset($_GET['custom']) && $_GET['custom']!='') {
		
		if ( $juiz_metadata = get_option('juiz_user_custom_fields' ) ) {
			
			$juiz_metadata = unserialize ( $juiz_metadata );
			$slug_2_up = $_GET['custom'];

			// if the custom field exists, we can create the form edition
			if ( array_key_exists ( $slug_2_up, $juiz_metadata ) ) {
				
				$need_an_edit = true;
				$edit_form_title = __('Edit the custom user field named:', 'juiz_cuf').' <i>'.$juiz_metadata[$slug_2_up][0].'</i>';
				$edit_form_name = 'up_';
				$edit_old_key = '<input type="hidden" name="old_key_slug" id="old_key_slug" value="'.$slug_2_up.'" />';
				$the_old_label =  $juiz_metadata[$slug_2_up][0];
				$the_old_slug = $slug_2_up;
				$the_old_type = $juiz_metadata[$slug_2_up][1];
				
			}
			else {
				$juiz_message = __("This custom user field doesn't exist...", 'juiz_cuf');
				$juiz_error++;
			}
			
		}
		
	}
	
	/*
		===============================
		IN CASE OF EDITING SOMETHING
		==============================
	*/
	
	if ($can_create_users && isset( $_GET['do'] ) && $_GET['do']=='editing' && isset($_POST['up_field_label']) && $_POST['up_field_label']!='') {
		
		if ( $juiz_metadata = get_option( 'juiz_user_custom_fields' ) ) {

			$before_up = $juiz_metadata; // to be sure when we use update_user_meta line XXX
			$slug_2_up = $_POST['old_key_slug'];
			
			$up_field_label = $_POST['up_field_label'];
			$up_field_slug = (trim ( $_POST['up_field_slug'] ) != '' )  ? juiz_slugIt(trim($_POST['up_field_slug'])) : juiz_slugIt($up_field_label);

			$juiz_metadata = unserialize ( $juiz_metadata );

			// if the custom field exists, we can update it
			if ( array_key_exists ( $slug_2_up, $juiz_metadata ) && !array_key_exists ( $up_field_slug, $juiz_metadata )) {

				$slug_2_up_content = $juiz_metadata[$slug_2_up]; // save the content
				unset( $juiz_metadata[$slug_2_up] ); // delete the old slug name
				
				$juiz_metadata[$up_field_slug] = $slug_2_up_content; // create new slug name
				$juiz_metadata[$up_field_slug][0] = $up_field_label; // update the label name
				// $juiz_metadata[$up_field_slug][1] = $up_field_type; // update the type... later
				
				$juiz_custom_fields = serialize ( $juiz_metadata );
				$updating_user_meta = update_option( 'juiz_user_custom_fields', $juiz_custom_fields );
				
				$update_users_fields = $wpdb->query($wpdb->prepare("UPDATE $wpdb->usermeta SET meta_key = %s WHERE meta_key = %s ", 'juiz_'.$up_field_slug, 'juiz_'.$slug_2_up));
				// $wpdb->show_errors();
				// $wpdb->print_error();
				$wpdb->flush(); // freedooooom!
				
				if($updating_user_meta) {
					$juiz_message = __("This custom user field correctly been updated.", 'juiz_cuf');
					$juiz_message .= ($update_users_fields == 0) ?  '<br /><i>'.__("Note: no data updated (no user had informed this field)", 'juiz_cuf').'</i>' : '<br /><i><b>'.$update_users_fields.'</b> '.__('data(s) updated', 'juiz_cuf').'</i>';
				}
				else {
					$juiz_message = __("An error has occured. Updating isn't possible...", 'juiz_cuf');
					$juiz_error++;
				}
			}
			elseif ( array_key_exists ( $slug_2_up, $juiz_metadata ) && array_key_exists ( $up_field_slug, $juiz_metadata )) {
				$juiz_message = __("The new name/slug you chose already exists.", 'juiz_cuf');
				$juiz_error++;
			}
			// else... we can't :p
			else {
				$juiz_message = __("This custom user field doesn't exist...", 'juiz_cuf');
				$juiz_error++;
			}
		}
	}
	elseif ($can_create_users && isset( $_GET['do'] ) && $_GET['do']=='editing' && isset($_POST['up_field_label']) && $_POST['up_field_label']=='') {
		$juiz_message = __('You need to fill the field', 'juiz_cuf').' <strong><a href="#up_field_label">'.__('Label of field', 'juiz_cuf').'</a></strong>';
		$juiz_error++;
	}
	elseif (!$can_create_users && isset( $_GET['do'] ) && $_GET['do']=='editing') {
		$juiz_message = __("Sorry, you can't do that !", 'juiz_cuf');
		$juiz_error++;
	}
	
	/*
		===============================
		IN CASE OF NOTHING...
		==============================
	*/
	if(!$juiz_metadata) {
		$juiz_metadata = get_option( 'juiz_user_custom_fields' );
		
		if ( !is_array ( $juiz_metadata ) )
			$juiz_metadata = unserialize ( $juiz_metadata );
	}
	
	if(count($juiz_metadata) != 0) {
		$iii = 0;
		foreach($juiz_metadata as $k => $v) {
			$existing_fields .= '
					<tr id="juiz_'.$k.'" class="alt_'.($iii%2).'">
						<td class="title_col">'.$v[0].'</td>
						<td class="slug_col">'.$k.'<br /></td>
						<td class="technical_help_col">
							<pre><code>&lt;?php echo <a target="_blank" href="http://codex.wordpress.org/Function_Reference/the_author_meta">get_the_author_meta</a>( \'juiz_'.$k.'\', $user_id ); ?&gt;</code></pre>
							<em><code>$user_id</code> : '.__('optional','juiz_cuf').' '.__('in the loop','juiz_cuf').'</em>
						</td>
						<td class="input_type_col"><i>'.$v[1].'</i></td>
						<td class="actions_col">
							<a onclick="return confirm(\''.__('Are you sure?', 'juiz_cuf').'\n'.__('This action will delete all the informations about this custom user field!', 'juiz_cuf').'\')" class="juiz_delete" title="' . __('Delete this custom', 'juiz_cuf') . '" href="?page=' . JUIZ_USER_CUSTOM_SLUG . '&amp;do=delete&amp;custom='.$k.'">' . __('Delete this custom', 'juiz_cuf') . '</a>
							<a class="juiz_edit" title="' . __('Edit this custom', 'juiz_cuf') . '" href="?page=' . JUIZ_USER_CUSTOM_SLUG . '&amp;do=edit&amp;custom='.$k.'">' . __('Edit this custom', 'juiz_cuf') . '</a>
						</td>
					</tr>
			';
			$iii++;
		}
	}
	else {
		$existing_fields .= '<tr><td colspan="5"><em>'.__('No entry for the moment', 'juiz_cuf').'</em></td></tr>';
	}
	?>
	<div id="juiz-custom-user" class="wrap">
		<div id="icon-users" class="icon32">&nbsp;</div>
		<h2><?php _e('Manage Juiz user customs', 'juiz_cuf') ?></h2>
		
		<?php 
			$more_action = '';
			if ( $need_an_edit ) $more_action = "&amp;do=editing";
		?>
		
		<form method="post" action="<?php echo admin_url('users.php?page='.JUIZ_USER_CUSTOM_SLUG.$more_action); ?>" id="juiz_custom_user_form">
		
			<?php 
				// if we have a message
				if($juiz_message != '') {
					// for the styles
					$class = ( $juiz_error > 0 ) ? 'error' : 'updated';
					// for several errors
					$sssss = ( $juiz_error > 1 ) ? 's' : '';
					// for the good title
					$juiz_title = ( $juiz_error > 0 ) ? $juiz_error.' '.__('error', 'juiz_cuf').$sssss : __('Well done!', 'juiz_cuf');
					echo '
						<div id="juiz_message" class="'.$class.'">
							<h3>' . $juiz_title . '</h3>
							<p>' . $juiz_message . '</p>
						</div>
					';
				}
			?>
			
			<h3>
				<?php 
					$label_for_id = 'new_';
					$submit_val = __('Add', 'juiz_cuf');
					$input_val_label = '';
					$input_val_slug = '';
					$input_val_type = '';
					
					if ( $need_an_edit ) {
						echo $edit_form_title;
						echo $edit_old_key;
						$label_for_id = $edit_form_name;
						$submit_val = __('Edit', 'juiz_cuf');
						$input_val_label = $the_old_label;
						$input_val_slug = $the_old_slug;
						$input_val_type = $the_old_type;
					}
					else
						_e('Create new custom user field', 'juiz_cuf');
				?>
			</h3>
			<table class="form-table">
				<thead>
					<tr>
						<th>
							<label for="<?php echo $label_for_id; ?>field_label"><?php _e('Label of field', 'juiz_cuf'); ?><em>(<?php _e('ex: Your profession', 'juiz_cuf'); ?>)</em></label>
						</th>
						<th>
							<label for="<?php echo $label_for_id; ?>field_slug"><?php echo __('Slug of field','juiz_cuf').' <i>('.__('optional','juiz_cuf').')</i>'; ?><em>(<?php _e('ex: your_profession', 'juiz_cuf'); ?>)</em></label>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><input type="text" name="<?php echo $label_for_id; ?>field_label" id="<?php echo $label_for_id; ?>field_label" value="<?php echo $input_val_label; ?>" /></td>
						<td><input type="text" name="<?php echo $label_for_id; ?>field_slug" id="<?php echo $label_for_id; ?>field_slug" value="<?php echo $input_val_slug; ?>" /></td>
					</tr>
					<tr>
						<td colspan="2" class="submit">
							<?php wp_nonce_field( JUIZ_USER_CUSTOM_SLUG, '_wpnonce', true, true ); ?>
							<input class="button-primary" type="submit" value="<?php echo $submit_val .' '. __('this custom field', 'juiz_cuf') ?>" />
						</td>
					</tr>
				</tbody>
			</table>
		<?php 
		if( !$need_an_edit) {
		?>
			<h3><?php _e('Existing custom user fields', 'juiz_cuf'); ?></h3>
			<table class="form-table widefat" id="juiz_cuf_table">
				<thead>
					<tr>
						<th class="title_col"><?php _e('Label of field', 'juiz_cuf'); ?></th>
						<th class="slug_col"><?php _e('Slug of field', 'juiz_cuf'); ?></th>
						<th class="technical_help_col"><?php _e('Show in the front-end with this function', 'juiz_cuf'); ?></th>
						<th class="input_type_col"><?php _e('Type of field', 'juiz_cuf'); ?><em>(<?php _e('Future feature', 'juiz_cuf'); ?>)</em></th>
						<th class="actions_col"><?php _e('Actions', 'juiz_cuf'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
						echo $existing_fields;
					?>
				</tbody>
			</table>
		<?php
		}
		?>
		</form>
		
		<div class="juiz_support">
			<?php
				if ( JUIZ_SHOW_DONATION_LINK ) {
			?>
			<div class="pp"><?php echo __('You love this plugin? You want to offer me something?', 'juiz_cuf') ?> 
				<form class="pp_form" action="https://www.paypal.com/cgi-bin/webscr" method="post">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
					<input type="image" src="<?php echo JUIZ_USER_CUSTOM_PATH; ?>img/buy-me.png" border="0" name="submit" alt="Buy me a beer (or something else)">
					<img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
				</form>
			</div>
			<?php 
				}
			?>
			<p><?php echo __('You found a bug? Please', 'juiz_cuf') .', <a href="mailto:support@creativejuiz.com?subject=Support for the WordPress \'Juiz User Custom\' plugin">'.__('report it', 'juiz_cuf') .'</a> <em>('. __('english, french and - maybe - spannish support', 'juiz_cuf').')</em>'; ?></p>			
		</div>
	</div>
	<?php
}
add_action('admin_menu', 'add_juiz_manage_user_custom_page');


?>