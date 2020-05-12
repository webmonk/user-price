<?php
/*
Plugin Name: Woocommerce User Price
Plugin URI: http://codemypain.com
Description: Assign specific price per product to users.
Version: 1.0
Author: Isaac Oyelowo
Author URI: https://isaacoyelowo.dev
Tested up to: Woocommerce 2x
*/

/*
#begin plugin
## CodeMyPain
### Solving real life issues one code at a time.
*/

class ISAAC_price {

	public function __construct() {

		add_action('woocommerce_get_price_html', array($this,'user_price'));
		add_action('init', array($this,'localize') );
		add_action('woocommerce_add_to_cart', array($this,  'add_to_cart_hook'));
		add_action('woocommerce_before_calculate_totals', array($this,  'add_custom_price' ));
		add_action('show_user_profile', array($this,'custom_user_profile_fields'));
		add_action('edit_user_profile', array($this,'custom_user_profile_fields'));
		add_action('admin_enqueue_scripts', array($this, 'sc_admin_js' ) );
		add_action('personal_options_update', array($this,'update_extra_profile_fields') );
		add_action('edit_user_profile_update', array($this,'update_extra_profile_fields') );
	}

	public function localize()  {

		load_plugin_textdomain('user_price', false, dirname(plugin_basename(__FILE__)). "/languages" );
    }

	public function sc_admin_js() {

		wp_register_script( 'usr_settings', plugins_url('/includes/js/admin/settings.js', __FILE__ ) );
		wp_enqueue_script( 'usr_settings' );
	}

	public function user_price($price) {

        global $product;
        $user = wp_get_current_user();
        $isaac_price  = get_user_meta( $user->ID, 'special_prices', true );
        foreach($isaac_price as $user_price) {

        	if($product->id == $user_price['item_number']) {

        		$price = get_woocommerce_currency_symbol().$user_price['price'];
        	}
        	if($user_price['price_suffix'] !='') {

        		$price = $price. ' ' . $user_price['price_suffix'];
        	}
        }
        return $price;
    }

    public function add_to_cart_hook($key) {

        global $woocommerce;
        $user = wp_get_current_user();
        $isaac_price  = get_user_meta( $user->ID, 'special_prices', true );
        foreach($isaac_price as $user_price)
        {
        	foreach ($woocommerce->cart->get_cart() as $cart_item_key => $values) 
            {
                if($values['product_id'] == $user_price['item_number'])
                {
                	$newprice = $user_price['price'];
                    $thousands_sep  = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ), ENT_QUOTES );
                    $decimal_sep = stripslashes( get_option( 'woocommerce_price_decimal_sep' ) );
                    $newprice = str_replace($thousands_sep, '', $newprice);
                    $newprice = str_replace($decimal_sep, '.', $newprice);
                    $_POST['price'] = wc_format_decimal($newprice);
                    if($cart_item_key == $key)
                    {
                        $values['data']->set_price($newprice);
                        $woocommerce->session->__set($key .'_named_price', $newprice);
					}
				}
        	}
        }
    return $key;
    }
	
	public function add_custom_price( $cart_object ) {

        global $woocommerce;
        foreach ( $cart_object->cart_contents as $key => $value ) 
        {
            $named_price = $woocommerce->session->__get($key .'_named_price');
            if($named_price)
            {
                $value['data']->price = $named_price;
            }
		}
    }

    public function custom_user_profile_fields($user) {

    	if(!current_user_can( 'promote_users' ))
    		return false;
    	$user_rates = get_user_meta($user->ID, 'special_prices',true) ;
        ?>
        <table class="form-table">
		<tr valign="top">
			<th scope="row" class="titledesc"><?php _e( 'User Price', 'user_price' ); ?></th>
			<td class="forminp">
				<table class="userprice widefat" cellspacing="0">
	    		<thead>
	    			<tr>
	    				<th class="check-column"><input type="checkbox"></th>
	    				<th><?php _e('Product ID', 'user_price'); ?></th>
						<th><?php _e('Price', 'user_price'); ?></th>
	    				<th><?php _e('Price Suffix', 'user_price'); ?></th>
	    			</tr>
	    		</thead>
	    		<tfoot>
	    			<tr>
	    				<th colspan="3"><a href="#" class="add_option button"><?php _e('+ Add Option', 'user_price'); ?></a></th>
						<th><a href="#" class="remove button"><?php _e('Delete selected rows', 'user_price'); ?></a></th>
	    			</tr>
	    		</tfoot>
	    		<tbody id="price_options">
				<?php $i = -1; if ($user_rates && is_array($user_rates)) foreach( $user_rates as $user_rate ) : $i++; ?>
				<tr class="price_option">
					<td class="check-column"><input type="checkbox" style="margin: 0 0 0 8px;" name="select" /></td>
					<td class="p_id">
						<input type="text" class="text" value="<?php if (isset($user_rate['item_number'])) echo esc_attr( $user_rate['item_number'] ); ?>" name="item_number[<?php echo $i; ?>]" title="<?php _e('Product ID', 'user_price'); ?>" size="16" />
					</td>
					<td class="price">
						<input type="text" class="text" value="<?php if (isset($user_rate['price'])) echo esc_attr( $user_rate['price'] ); ?>" name="price[<?php echo $i; ?>]" title="<?php _e('Price', 'user_price'); ?>" size="16" />
					</td>
					<td class="price_suffix">
						<input type="text" class="text" value="<?php if (isset($user_rate['price_suffix'])) echo esc_attr( $user_rate['price_suffix'] ); ?>" name="price_suffix[<?php echo $i; ?>]" title="<?php _e('Extra Price', 'user_price'); ?>" size="16" />
					</td>
				</tr>
				<?php endforeach; ?>
				</tbody>
				</table>
			</td>
		</tr>
		</table>
    <?php
    }

    public function update_extra_profile_fields($user_id) {

        $user_rate = array();
	    $item_number = (isset($_POST['item_number'])) ? $_POST['item_number'] : array();
	    $price = (isset($_POST['price'])) ? $_POST['price'] : array();
	    $price_suffix = (isset($_POST['price_suffix'])) ? $_POST['price_suffix'] : array();
	    foreach ( $price_suffix as $i=>$val ):
		    $user_rate[] = array(
			    'item_number' => $item_number[$i],
			    'price' => $price[$i],
			    'price_suffix' => $price_suffix[$i]
		    ); 
		endforeach;      
        update_user_meta($user_id, 'special_prices', $user_rate);
    }

}
$isaac_price = new ISAAC_price() ;