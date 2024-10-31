<?php
/*
   Plugin Name: WooCommerce - Automatic Catalog Maker
   Plugin URI: http://www.nowinstore.com
   Description: Create Online and Printable (PDF) Catalogs, Line Sheets, Lookbooks, Barcodes, PDF Viewer, & more,...
   Version: 2.1.3
   Author: Now In Store Inc.
   Author URI: https://www.nowinstore.com
   License: GPL2
*/
class NowInStore_CatalogBuilder
{
    public function __construct()
    {
      	register_activation_hook( __FILE__, array($this,'plugin_activate') );
      	register_deactivation_hook( __FILE__, array($this,'plugin_deactivate')  );
      	add_filter( 'plugin_action_links_'. plugin_basename( __FILE__ ), array($this,'plugin_action_links') );
		add_action('admin_menu', array($this,'plugin_admin_menu') );
    }

	public function plugin_admin_menu(){
		error_log( "add menu" );
		add_menu_page( 'Automatic Catalog Maker', 'Now In Store', 'manage_options', 'nowinstore-interface', array($this,'display_interface')
			, 'https://s3.amazonaws.com/nowinstore.com/ic-wordpress.png');
		error_log( "add menu page" );
	}

	public function display_interface(){
		$consumerKey = get_option('consumer_key');
		$consumerSecret = get_option('consumer_secret');
		$businessName = get_option('blogname');
		$email = get_option('admin_email');
		$baseUrl = urlencode (home_url());
		if (is_null($baseUrl) || empty($baseUrl)) {
			$baseUrl = urlencode (get_site_url());
		}
		$isoCurrencyCode = get_woocommerce_currency();
		echo "<a style='font-weight: bold' href='https://www.nowinstore.com/auth/woocommerce/callback?baseUrl=$baseUrl&k=$consumerKey;$consumerSecret&businessName=$businessName&email=$email&isoCurrencyCode=$isoCurrencyCode' target='_blank'>Open in new window</a>";
		echo "<iframe style='width: 100%; height: 800px' src='https://www.nowinstore.com/auth/woocommerce/callback?baseUrl=$baseUrl&k=$consumerKey;$consumerSecret&businessName=$businessName&email=$email&isoCurrencyCode=$isoCurrencyCode'></iframe>";
	}

	// For open link
	public function plugin_action_links( $actions )
    {
		$consumerKey = get_option('consumer_key');
		$consumerSecret = get_option('consumer_secret');
		$businessName = get_option('blogname');
		$email = get_option('admin_email');
		$baseUrl = urlencode (home_url());
        if (is_null($baseUrl) || empty($baseUrl)) {
            $baseUrl = urlencode (get_site_url());
        }
        $isoCurrencyCode = get_woocommerce_currency();
		$actions[] = "<a style='font-weight: bold' href='https://www.nowinstore.com/auth/woocommerce/callback?baseUrl=$baseUrl&k=$consumerKey;$consumerSecret&businessName=$businessName&email=$email&isoCurrencyCode=$isoCurrencyCode' target='_blank'>Open in new window</a>";

    	return $actions;
    }

	// For work on activated
	function plugin_activate() {
	    global $wpdb;
		//print_r(get_super_admins()); die;

		$current_user = wp_get_current_user();
		$uid=$current_user->ID;

		//echo wp super-admin list;
		try
		{
			$table=$wpdb->prefix . 'options';
			$query = "SELECT `option_id` FROM $table where `option_name`='woocommerce_api_enabled' and `option_value`='no'";
			$result = $wpdb->get_results($query, ARRAY_A);
			$id=0;
			foreach($result as $rs)
			{
				$id=$rs['option_id'];
			}

			$update_data = array('option_value' => 'yes');

			$wpdb->update(
				$wpdb->prefix . 'options',
				$update_data,
				array( 'option_id' => $id ),
				array('%s'),
				array('%d')
			);

			$consumer_key    = 'ck_' . wc_rand_hash();
			$consumer_secret = 'cs_' . wc_rand_hash();

			$data = array(
				'user_id'         => $uid,
				'description'     => 'Now In Store',
				'permissions'     => 'read',
				'consumer_key'    => wc_api_hash( $consumer_key ),
				'consumer_secret' => $consumer_secret,
				'truncated_key'   => substr( $consumer_key, -7 )
			);

			$wpdb->insert(
				$wpdb->prefix . 'woocommerce_api_keys',
				$data,
				array(
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s'
				)
			);

			add_option('consumer_key', $consumer_key);
			add_option('consumer_secret', $consumer_secret);
//			wp_send_json_success( array( 'message' => __( 'API Key generated successfully!', 'nowinstore' )) );
		}
		catch(Exception $e)
		{
			wp_send_json_error( array( 'message' => $e->getMessage(), 'error' => $wpdb->last_error ) );
		}
	}

	// For work on deactivated
	function plugin_deactivate() {
		delete_option('consumer_key');
		delete_option('consumer_secret');
	}
}
new NowInStore_CatalogBuilder();

?>
