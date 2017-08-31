<?php 

class DatamatchGiftCardsAdmin extends DatamatchGiftCards {
	
	public $pluginRoot = '';
	public $options = array();

	public function __construct($pluginRoot) {
		
		$this->pluginRoot	= $pluginRoot;
		$this->settings		= $this->getSettings();
		$this->fields		= $this->getFields();
		
		/* Register textdomain. */
		add_action('plugins_loaded', array( $this, 'registerTextDomain' ) );
		
		/* Register admin menus. */
		add_action( 'admin_menu', array( $this, 'addAdminMenus' ) );
		
		/* Register Settings */
		add_action( 'admin_init', array( $this, 'registerSettings' ) );

		/* Register Hooks */
		if( $this->settings['enable_giftcard_cartpage']) {	
			$this->registerWoocommerceHooks();
		}
		
		/* Register style sheet and scripts for the admin area. */
		//add_action( 'admin_enqueue_scripts', array( $this, 'registerAdminStylesScripts' ) );
		
		/* Register style sheet and scripts for the front. */
		add_action( 'wp_enqueue_scripts', array( $this, 'registerFrontStylesScripts' ) );
		
	}
	
	public function getFields() {
		return array(
			array(
				'id'	=> 'enable_giftcard_cartpage',
				'type'	=> 'checkbox',
				'label'	=> 'Show Gift Card Form at the checkout page',
			),
			array(
				'id'	=> 'enable_giftcard_tax_payment',
				'type'	=> 'checkbox',
				'label'	=> 'Allow customers to pay for tax with their gift card',
			),
			array(
				'id'	=> 'api_key',
				'type'	=> 'textarea',
				'label'	=> 'Datamatch API key',
				'rows'	=> '4',
				'cols'	=> '40'
			)
		);
	}
	
	public function registerTextDomain() {
		load_plugin_textdomain( 'dtm_gift_cards', false, dirname( plugin_basename( $this->pluginRoot ) ) ); 
	}
	
	public function registerFrontStylesScripts() {
		wp_enqueue_style( 'datamatch-front', plugins_url( 'datamatch-gift-cards.css', $this->pluginRoot, array(), '0.1' ) );
	}
	
    public function registerSettings() {
		foreach (self::$pluginSettings as $settingId) {
			register_setting(self::$prefix . 'option_group', self::$prefix . $settingId);
			add_settings_field( $settingId, $settingId, array($this, 'dummy'), 'datamatch-gift-settings', self::$prefix . 'settings' );
		}		
    }
	
	public function registerWoocommerceHooks() {
		add_filter( 'woocommerce_calculated_total', array( $this, 'getWoocommerceDiscount'), 10, 2 );
		add_action( 'woocommerce_cart_actions', array( $this, 'showGiftCardForm') );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'showCheckoutForm'), 10 );
		add_action( 'woocommerce_after_cart_table', array( $this, 'showGiftCardInCart') );
		
		add_action( 'wp_ajax_woocommerce_apply_giftcard', array( $this, 'applyWoocommerceGiftCard') );
		add_action( 'wp_ajax_nopriv_woocommerce_apply_giftcard',  array( $this, 'applyWoocommerceGiftCard') );
		
		add_action ( 'woocommerce_before_cart', array( $this, 'applyWoocommerceGiftCard') );
		add_action ( 'dtm_before_checkout_form', array( $this, 'applyWoocommerceGiftCard') );
		
		add_action( 'woocommerce_review_order_before_order_total', array( $this, 'showGiftCardInOrder') );
		add_action( 'woocommerce_cart_totals_before_order_total',  array( $this, 'showGiftCardInOrder') );
		
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'updateGiftCard') );

	}

	// Adds the Gift Card form to the checkout page so that customers can enter the gift card information
	public function showGiftCardForm() {	
			do_action( 'dtm_before_cart_form' );
			?>
			<div class="giftcard" style="float: left; padding-top: 10px;">
				<label type="text" for="giftcard_code" style="display: none;"><?php _e( 'Giftcard', 'dtm_gift_cards' ); ?>:</label>
				<input type="text" name="giftcard_code" class="dtm-input-text input-text" id="giftcard_code" value="" placeholder="<?php _e( 'Gift Card', 'dtm_gift_cards' ); ?>" />
				<input type="submit" class="button" name="apply_gift_card" value="<?php _e( 'Apply Gift card', 'dtm_gift_cards' ); ?>" />
			</div>
		<?php
			do_action( 'dtm_after_cart_form' );
	
    }
	
	/**
	 * Outputs the Giftcard form for the checkout.
	 */
	public function showCheckoutForm() {

		if( $this->settings['enable_giftcard_cartpage']) {	

			do_action( 'dtm_before_checkout_form' );

			$info_message =  __( 'Have a giftcard?', 'dtm_gift_cards' ) . ' <a href="#showgiftcard" class="showgiftcard">' . __( 'Click here to enter your code', 'dtm_gift_cards' ) . '</a>';
			wc_print_notice( $info_message, 'notice' );
			?>

			<form class="checkout_giftcard" method="post" style="display:none">
				<p class="form-row form-row-first"><input type="text" name="giftcard_code" class="input-text" placeholder="<?php _e( 'Gift card', 'dtm_gift_cards' ); ?>" id="giftcard_code" value="" /></p>
				<p class="form-row form-row-last"><input type="submit" class="button" name="apply_giftcard" value="<?php _e( 'Apply Gift card', 'dtm_gift_cards' ); ?>" /></p>
				<div class="clear"></div>
			</form>
			<script type="text/javascript">
				jQuery(".showgiftcard").click(function(){
					jQuery(".checkout_giftcard").slideToggle(400,function(){jQuery(".checkout_giftcard").find(":input:eq(0)").focus()});
				});
			</script>

			<?php do_action( 'dtm_after_checkout_form' ); ?>

		<?php
		}
	}

	//  Display the current gift card information on the cart	
	public function showGiftCardInCart() {
		$this->checkGiftCardRemoval();
		
		if ( isset(WC()->session->giftcard_post)) {
			$giftcardIds = explode(',',WC()->session->giftcard_post);
			if (count($giftcardIds) == 1) {
				$giftCard = new DatamatchGiftCardsEntity($giftcardIds[0]);
				if( $giftCard->isValid()) {
					echo '<div class="dtm-card">' 
						. __( 'Applied Gift Card:', 'dtm_gift_cards' ) 
						. ' <span class="dtm-card-id">' . $giftCard->id . '</span>, ' . __('value:','dtm_gift_cards') 
						. ' ' . woocommerce_price($giftCard->getValue())
						.'</div>';
				}
			}
			elseif (count($giftcardIds) > 1) {
				$gotoPage = is_cart() ? WC()->cart->get_cart_url() : WC()->cart->get_checkout_url();
				echo '<div class="dtm-card">' . __( 'Applied Gift Cards:', 'dtm_gift_cards' ) . '</div>';
				foreach ($giftcardIds as $giftcardId) {
					$giftCard = new DatamatchGiftCardsEntity($giftcardId);
					if( $giftCard->isValid()) {
						echo '<div class="dtm-card"><span class="dtm-card-id">' . $giftCard->id . '</span>, ' . __('value:','dtm_gift_cards') 
						. ' ' . woocommerce_price($giftCard->getValue())
						. ' <a href="' . add_query_arg( 'remove_giftcards', $giftcardId, $gotoPage ). '">[' . __( 'Remove', 'dtm_gift_cards' ) .']</a>'
						.'</div>';
					}
				}
			}
		}
	}

	/**
	 * Function to add the giftcard data to the cart display on both the card page 
	 * and the checkout page WC()->session->giftcard_balance
	 */
	public function showGiftCardInOrder( ) {
		$this->checkGiftCardRemoval();

		if ( isset( WC()->session->giftcard_post)) {
			$giftcardIds = $this->getCardsFromSession();
			$haveValidCards = false;
			$totalPrice = 0;
			foreach ($giftcardIds as $giftcardId) {
				$giftcard = new DatamatchGiftCardsEntity($giftcardId);
				if ($giftcard->isValid()) {
					$haveValidCards = true;
					$totalPrice += $giftcard->getValue();
				}
			}
			
			if ($haveValidCards) {
				?>
				<tr class="giftcard">
					<th><?php _e( 'Gift Card Payment', 'dtm_gift_cards' ); ?> </th>
					<td style="font-size:0.85em;"><?php echo woocommerce_price( $totalPrice ); ?></td>
				</tr>
				<?php
			}
		}
	}
	
	public function checkGiftCardRemoval( ) {
		if ( isset( $_GET['remove_giftcards'] ) ) {
			if ($this->checkCardInSession($_GET['remove_giftcards'])) {
				$this->removeCardFromSession($_GET['remove_giftcards']);
				WC()->cart->calculate_totals();
			}
		}
	}
	
	public function getCardsFromSession() {
		if (isset(WC()->session->giftcard_post)) {
			$cards = explode(',',WC()->session->giftcard_post);
			$result = array();
			foreach ($cards as $card) {
				if ($card) { $result[] = $card; }
			}
			return $result;
		}
		else { return array(); }
	}
		
	public function addCardToSession($giftcardId) {
		if (isset(WC()->session->giftcard_post)) {
			WC()->session->giftcard_post .= $giftcardId . ',';
		}
		else {
			WC()->session->giftcard_post = $giftcardId . ',';
		}
	}
	
	public function removeCardFromSession($giftcardId) {
		if (isset(WC()->session->giftcard_post)) {
			WC()->session->giftcard_post = str_replace($giftcardId . ',', '', WC()->session->giftcard_post);
		}
		else {
			WC()->session->giftcard_post = '';
		}
	}
	
	public function checkCardInSession($giftcardId) {
		if (isset(WC()->session->giftcard_post)) {
			return (strpos(WC()->session->giftcard_post, $giftcardId . ',') !== false);
		}
		else return false;
	}
	
	public function applyWoocommerceGiftCard() {
		if ( ! empty( $_POST['giftcard_code'] ) ) {
			$giftcardId = sanitize_text_field( $_POST['giftcard_code'] );

			if (!$this->checkCardInSession($giftcardId)) {
				$giftcard = new DatamatchGiftCardsEntity($giftcardId);
				if ($giftcard->isValid()) {
					if( $giftcard->getValue() > 0 ) {
						$this->addCardToSession($giftcardId);
						wc_add_notice(  __( 'Gift card applied successfully.', 'dtm_gift_cards' ), 'success' );
					}
					else {
						wc_add_notice( __( 'Gift Card does not have a balance', 'dtm_gift_cards' ), 'error' );
					}
				}
				else {
					wc_add_notice( __( 'Gift Card does not exist', 'dtm_gift_cards' ), 'error' ); // Giftcard Entered does not exist
				}
			}
			else {		
				wc_add_notice( __( 'Gift Card already in the cart', 'dtm_gift_cards' ), 'error' );  //  You already have a gift card in the cart
			}

			wc_print_notices();
			WC()->cart->calculate_totals();
			
			if ( defined('DOING_AJAX') && DOING_AJAX ) {
				die();
			}
		}
	}
	
	public function updateGiftCard() {
		self::log(time(). 'updateGiftCard');
		self::log(WC()->session->giftcard_post );
		if ( isset( WC()->session->giftcard_post ) ) {
			$cart = WC()->session->cart;
			$giftcardPayment = 0;
			self::log('giftcardPayment = 0');
			foreach( $cart as $product ) {
				if ( isset( $product['product_id'] ) ) {
					$giftcardPayment += $product['line_total'];
					self::log('giftcardPayment += total:' . $product['line_total']);
					if( $this->settings['enable_giftcard_tax_payment']) {	
						$giftcardPayment += $product['line_tax'];
						self::log('giftcardPayment += tax:' . $product['line_tax']);
					}
				}
			}
			
			$giftcardIds = explode(',',WC()->session->giftcard_post);
			if (count($giftcardIds)) {
				foreach ($giftcardIds as $giftcardId) {
					self::log('updateGiftCard :: ' . $giftcardId );
					$giftCard = new DatamatchGiftCardsEntity($giftcardId);			
					if ($giftCard->isValid()) {
						self::log('updateGiftCard :: ' . $giftcardId . ' IS VALID, $giftcardPayment:  ' . $giftcardPayment);
						if ($giftcardPayment > 0) {
							$cardValue = $giftCard->getValue();
							if ($cardValue >= $giftcardPayment) {
								self::log('$giftCard->decreaseValue(' . $giftcardPayment . ')');
								$giftCard->decreaseValue($giftcardPayment);
								$giftcardPayment = 0;
							}
							else {
								$giftcardPayment -= $cardValue;
								$giftCard->updateValue(0);
								self::log('$giftCard->updateValue(' . 0 . '), $giftcardPayment = ' . $giftcardPayment);
							}
						}
					}
				}
			}

			
			
			unset( WC()->session->giftcard_post );
		}
		self::log(time(). 'END OF updateGiftCard');
	}
	
	public function addAdminMenus() {
		add_options_page(
			__('Datamatch Gift Cards Settings','dtm_gift_cards'),		// page title
			__('Datamatch Gift Cards','dtm_gift_cards'),				// menu title
			'manage_options',											// capability
			'datamatch-gift-settings',									// menu_slug
			array($this,'showSettingsPage')								// callable function 
		);
	}
	
	
	public function showSettingsPage() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?>
		<div class="wrap">
		<h1><?php _e('Datamatch Gift Cards Settings','dtm_gift_cards') ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( self::$prefix . 'option_group' );?>
			
			<table class="form-table-wide">
				<?php $this->displayFieldSet($this->fields); ?>
			</table>
			
			<?php
                submit_button(); 
            ?>
		</form>

		</div>
		<?php
	}
	
	public static function getWoocommerceDiscount($total) {
		if ( isset(WC()->session->giftcard_post)) {
			$giftcardIds = explode(',',WC()->session->giftcard_post);
			if (count($giftcardIds)) {
				foreach ($giftcardIds as $giftcardId) {
					$giftCard = new DatamatchGiftCardsEntity($giftcardId);
					if (($giftCard->isValid()) && ($giftCard->getValue() > 0 )) {
						$total -= $giftCard->getValue();
					}
				}
			}
		}
		
		if ($total < 0) { $total = 0; }
		
        return $total;
	}
	
}