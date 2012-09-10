<?php
/*
Plugin Name: bbPress - Mark as Read
Plugin URL: http://pippinsplugins.com/bbpress-mark-as-read
Description: Allows you to mark bbPress topics as read/unread and see all read / unread topics
Version: 0.1
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Contributors: mordauk
*/

class BBP_Mark_As_Read {

	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/

	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	function __construct() {

		load_plugin_textdomain( 'bbp-mar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		add_filter( 'bbp_get_user_subscribe_link', array( $this, 'add_links_to_topics' ), 999, 4 );

		// process marked as read requests
		add_action( 'init', array( $this, 'process_marked_as_read' ) );

		// process marked as unread requests
		add_action( 'init', array( $this, 'process_marked_as_unread' ) );

	} // end constructor


	public function add_links_to_topics( $html, $args, $user_id, $topic_id ) {

		if ( empty( $user_id ) || empty( $topic_id ) ) {
			return $html;
		}

		// No link if you can't edit yourself
		if ( !current_user_can( 'edit_user', (int) $user_id ) ) {
			return $html;
		}

		// Decine which link to show
		$is_read = $this->is_read( $user_id, $topic_id );
		if ( !empty( $is_read ) ) {
			$text       = __('Mark as Unread', 'bbp-mar');
			$query_args = array( 'action' => 'bbp_mark_as_unread', 'topic_id' => $topic_id );
		} else {
			$text       = __('Mark as Read', 'bbp-mar');
			$query_args = array( 'action' => 'bbp_mark_as_read', 'topic_id' => $topic_id );
		}

		// Create the link based where the user is and if the user is
		if ( is_singular( bbp_get_topic_post_type() ) ) {
			$permalink = bbp_get_topic_permalink( $topic_id );
		} elseif ( is_singular( bbp_get_reply_post_type() ) ) {
			$permalink = bbp_get_topic_permalink( $topic_id );
		} elseif ( bbp_is_query_name( 'bbp_single_topic' ) ) {
			$permalink = get_permalink();
		}

		$url = esc_url( wp_nonce_url( add_query_arg( $query_args, $permalink ), 'toggle-read_state_' . $topic_id ) );

		$link = '<span class="mark-as-read-toggle" style="float:right;">';
			$link .= $args['before'];
			$link .= '<a href="' . $url . '">';
				$link .= $text;
			$link .= '</a>';
			$link .= $args['after'];
		$link .= '</span>';
		return $html . $link;
	}

	public function is_read( $user_id, $topic_id ) {

		$read_ids = $this->get_read_ids( $user_id );
		$return = false;
		if( empty( $read_ids ) )
			$return = false;
		if( in_array( $topic_id, $read_ids ) )
			$return = true;

		//delete_user_meta( $user_id, 'bbp_read_ids' );
		return apply_filters( 'bbp_is_read', $return, $user_id, $topic_id );
	}

	public function mark_as_read( $user_id, $topic_id ) {
		$read_ids = $this->get_read_ids( $user_id );
		$read_ids[] = $topic_id;
		return update_user_meta( $user_id, 'bbp_read_ids', $read_ids );
	}

	public function mark_as_unread( $user_id, $topic_id ) {
		$read_ids = $this->get_read_ids( $user_id );
		$found = array_search( $topic_id, $read_ids );
		if( $found !== false )
			unset($read_ids[$found]);
		return update_user_meta( $user_id, 'bbp_read_ids',  $read_ids );
	}

	public function get_read_ids( $user_id ) {
		$read_ids = get_user_meta( $user_id, 'bbp_read_ids', true );
		if( ! $read_ids || !is_array( $read_ids ) )
			$read_ids = array(); // empty array
		return $read_ids;
	}

	public function process_marked_as_read() {
		if( !isset( $_GET['action'] ) || $_GET['action'] != 'bbp_mark_as_read' )
			return;

		$topic_id = absint( $_GET['topic_id'] );

		if( ! wp_verify_nonce( $_GET['_wpnonce'], 'toggle-read_state_' . $topic_id ) )
			return;

		global $user_ID;

		$topic_id = bbp_get_topic_id( $topic_id );

		if ( empty( $user_ID ) || empty( $topic_id ) )
			return false;

		// No link if you can't edit yourself
		if ( !current_user_can( 'edit_user', (int) $user_ID ) )
			return false;
		
		$this->mark_as_read( $user_ID, $topic_id );

	}

	public function process_marked_as_unread() {
		if( !isset( $_GET['action'] ) || $_GET['action'] != 'bbp_mark_as_unread' )
			return;

		$topic_id = absint( $_GET['topic_id'] );

		if( ! wp_verify_nonce( $_GET['_wpnonce'], 'toggle-read_state_' . $topic_id ) )
			return;

		global $user_ID;

		$topic_id = bbp_get_topic_id( $topic_id );

		if ( empty( $user_ID ) || empty( $topic_id ) )
			return false;

		// No link if you can't edit yourself
		if ( !current_user_can( 'edit_user', (int) $user_ID ) )
			return false;
		
		$this->mark_as_unread( $user_ID, $topic_id );


	}

  
} // end class

// instantiate our plugin's class
$GLOBALS['bbp_mark_as_read'] = new BBP_Mark_As_Read();