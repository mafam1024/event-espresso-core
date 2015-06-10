<?php if (!defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );/**
 *
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author		Seth Shoultes
 * @ copyright	(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link			http://www.eventespresso.com
 *
 * ------------------------------------------------------------------------
 *
 * EE_Cart class
 * Used to keep track of which tickets the user has specified they want to purchase.
 * This data is used for generating the Transaction and Registrations, and the
 * Line Items on cart are themselves saved for creating a persistent snapshot of
 * what was purchased and for how much.
 *
 *
 * @ version		2.0
 * @subpackage	includes/core/EE_Cart.core.php
 * @ author		Mike Nelson, Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
 class EE_Cart {

	/**
	 * 	instance of the EE_Cart object
	 * 	@access 	private
	 *	@var EE_Cart $_instance
	 */
	private static $_instance = NULL;

	/**
	 * 	instance of the EE_Session object
	 * 	@access    protected
	 *	@var EE_Session $_session
	 */
	protected $_session = NULL;

	/**
	 * The total Line item which comprises all the children line-item subtotals,
	 * which in turn each have their line items.
	 * Typically, the line item structure will look like:
	 * grand total
	 * -tickets-sub-total
	 * --ticket1
	 * --ticket2
	 * --...
	 * -taxes-sub-total
	 * --tax1
	 * --tax2
	 * @var EE_Line_Item
	 */
	private $_grand_total = NULL;



	 /**
	  * @singleton method used to instantiate class object
	  * @access    public
	  * @param EE_Line_Item $grand_total
	  * @param EE_Session $session
	  * @return \EE_Cart
	  */
	 public static function instance( EE_Line_Item $grand_total = null, EE_Session $session = null ) {
		 EE_Registry::instance()->load_helper( 'Line_Item' );
		 // check if class object is instantiated
		 if ( ! empty( $grand_total ) ) {
			 self::$_instance = new self( $grand_total, $session );
		 } elseif ( ! self::$_instance instanceof EE_Cart ) {
			 //try getting the cart out of the session
			 $saved_cart = $session instanceof EE_Session ? $session->cart() : null;
			 self::$_instance = $saved_cart instanceof EE_Cart ? $saved_cart : new self( $grand_total, $session );
			 unset( $saved_cart );
		 }
		 // once everything is all said and done, save the cart to the EE_Session
		 add_action( 'shutdown', array( self::$_instance, 'save_cart' ), 90 );
		 return self::$_instance;
	 }



	 /**
	  *    private constructor to prevent direct creation
	  * @Constructor
	  * @access private
	  * @param EE_Line_Item $grand_total
	  * @param EE_Session $session
	  * @return \EE_Cart
	  */
	 private function __construct( EE_Line_Item $grand_total = null, EE_Session $session = null ) {
		 do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
		 $this->set_session( $session );
		 if ( $grand_total instanceof EE_Line_Item ) {
			 $this->set_grand_total_line_item( $grand_total );
		 }
		 $this->get_grand_total();
	 }



	 /**
	  * Resets the cart completely (whereas empty_cart
	  * @param EE_Line_Item $grand_total
	  * @param EE_Session $session
	  * @return EE_Cart
	  */
	 public static function reset( EE_Line_Item $grand_total = null, EE_Session $session = null ) {
		 remove_action( 'shutdown', array( self::$_instance, 'save_cart' ), 90 );
		 if ( $session instanceof EE_Session ) {
			 $session->reset_cart();
		 }
		 self::$_instance = null;
		 return self::instance( $grand_total, $session );
	 }



	 /**
	  * @param EE_Session $session
	  */
	 public function set_session( EE_Session $session ) {
		 $this->_session = $session;
	 }



	 /**
	  * Sets the cart to match the line item. Especially handy for loading an old cart where you
	  *  know the grand total line item on it
	  * @param EE_Line_Item $line_item
	  */
	 public function set_grand_total_line_item( EE_Line_Item $line_item ) {
		 $this->_grand_total = $line_item;
	 }



	 /**
	  *    get_cart_from_reg_url_link
	  * @access public
	  * @param EE_Transaction $transaction
	  * @param EE_Session $session
	  * @return \EE_Cart
	  */
	public static function get_cart_from_txn( EE_Transaction $transaction, EE_Session $session = null ) {
		$grand_total = $transaction->total_line_item();
		$grand_total->get_items();
		$grand_total->tax_descendants();
		return EE_Cart::instance( $grand_total, $session );
	}



	/**
	 * Creates the total line item, and ensures it has its 'tickets' and 'taxes' sub-items
	 * @return EE_Line_Item
	 */
	private function _create_grand_total(){
		$this->_grand_total = EEH_Line_Item::create_default_total_line_item();
		return $this->_grand_total;
	}



	/**
	 *	Gets all the item line items (ie, all line items for registrations, products, etc. NOT taxes or promotions)
	 *	@access public
	 *	@return \EE_Line_Item[]
	 */
	public function get_tickets() {
		return EEH_Line_Item::get_items_subtotal( $this->_grand_total )->children();
	}



	/**
	 * returns the total quantity of tickets in the cart
	 *
	 * @access public
	 * @return int
	 */
	public function all_ticket_quantity_count() {
		$tickets = $this->get_tickets();
		if ( empty( $tickets )) {
			return 0;
		}
		$count = 0;
		foreach ( $tickets as $ticket ) {
			$count = $count + $ticket->get('LIN_quantity');
		}
		return $count;
	}



	/**
	 *  Gets all tha tax line items
	 * @return \EE_Line_Item[]
	 */
	public function get_taxes(){
		return EEH_Line_Item::get_taxes_subtotal( $this->_grand_total )->children();
	}



	/**
	 * Gets the total line item (which is a parent of all other line items) on this cart
	 * @return EE_Line_Item
	 */
	public function get_grand_total(){
		return $this->_grand_total instanceof EE_Line_Item ? $this->_grand_total : $this->_create_grand_total();
	}



	/**
	 *	@process items for adding to cart
	 *	@access public
	 *	@param EE_Ticket $ticket
	 *	@param int $qty
	 *	@return TRUE on success, FALSE on fail
	 */
	public function add_ticket_to_cart( EE_Ticket $ticket, $qty = 1 ) {
		EEH_Line_Item::add_ticket_purchase( $this->_grand_total, $ticket, $qty );
		return $this->save_cart() ? TRUE : FALSE;
	}



	/**
	 *	get_cart_total_before_tax
	 *	@access public
	 *	@return float
	 */
	public function get_cart_total_before_tax() {
		return $this->get_grand_total()->recalculate_pre_tax_total();
	}



	/**
	 *	gets the total amount of tax paid for items in this cart
	 *	@access public
	 *	@return float
	 */
	public function get_applied_taxes() {
		return EEH_Line_Item::ensure_taxes_applied( $this->_grand_total );
	}



	/**
	 *	Gets the total amount to be paid for the items in the cart, including taxes and other modifiers
	 *	@access public
	 *	@return float
	 */
	public function get_cart_grand_total() {
		EEH_Line_Item::ensure_taxes_applied( $this->_grand_total );
		return $this->get_grand_total()->total();
	}



	/**
	 *	deletes an item from the cart
	 *	@access public
	 *	@param array|bool|string $line_item_codes
	 *	@return int on success, FALSE on fail
	 */
	public function delete_items( $line_item_codes = FALSE ) {

		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
		return EEH_Line_Item::delete_items($this->get_grand_total(), $line_item_codes );

	}



	/**
	 *	@remove ALL items from cart and zero ALL totals
	 *	@access public
	 *	@return void
	 */
	public function empty_cart() {
		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
		$this->_grand_total = $this->_create_grand_total();
		$this->save_cart( TRUE );
	}



	/**
	 *	@save cart to session
	 *	@access public
	 *	@return TRUE on success, FALSE on fail
	 */
	public function save_cart() {
		EEH_Line_Item::ensure_taxes_applied( $this->_grand_total );
		if ( $this->_session instanceof EE_Session ) {
			return $this->_session->set_cart( $this );
		} else {
			return false;
		}
	}





}
/* End of file EE_Cart.core.php */
/* Location: /includes/core/EE_Cart.core.php */
