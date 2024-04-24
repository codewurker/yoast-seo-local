<?php
/**
 * Yoast SEO: Local for WooCommerce plugin file.
 *
 * @package YoastSEO_Local_WooCommerce
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class: Yoast_WCSEO_Local_Transport_List.
 *
 * @deprecated 14.9
 * @codeCoverageIgnore
 */
class Yoast_WCSEO_Local_Transport_List extends WP_List_Table {

	/**
	 * Gets column table data.
	 *
	 * @deprecated 14.9
	 * @codeCoverageIgnore
	 *
	 * @return array
	 */
	public function get_columns() {
		_deprecated_function( __METHOD__, 'Yoast Local SEO 14.9' );
		return [
			'order'       => _x( 'Order', 'noun', 'yoast-local-seo' ),
			'status'      => __( 'Status', 'yoast-local-seo' ),
			'products'    => __( 'Products', 'yoast-local-seo' ),
			'destination' => __( 'Destination', 'yoast-local-seo' ),
		];
	}

	/**
	 * Emptied to make sure there is no chance of updating order status.
	 *
	 * @deprecated 14.9
	 * @codeCoverageIgnore
	 *
	 * @return void
	 */
	public function process_bulk_action() {
		_deprecated_function( __METHOD__, 'Yoast Local SEO 14.9' );
		switch ( $this->current_action() ) {
			case 'wc-completed':
			case 'wc-processing':
			case 'wc-transporting':
			case 'wc-ready-for-pickup':
				$do_post_update = true;
				break;
			default:
				$do_post_update = false;
				break;
		}

		if ( $do_post_update === false || ! isset( $_REQUEST['post'] ) ) {
			return;
		}

		$order = new WC_Order( (int) $_REQUEST['post'] );
		$order->update_status( $this->current_action() );
	}

	/**
	 * Prepares data for a table.
	 *
	 * @deprecated 14.9
	 * @codeCoverageIgnore
	 *
	 * @return void
	 */
	public function prepare_items() {
		_deprecated_function( __METHOD__, 'Yoast Local SEO 14.9' );

		$this->process_bulk_action();

		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = [
			'order'  => [ 'ID', false ],
			'status' => [ 'post_status', false ],
		];
		$this->_column_headers = [ $columns, $hidden, $sortable ];
	}

	/**
	 * Sets the column order for a table.
	 *
	 * @deprecated 14.9
	 * @codeCoverageIgnore
	 *
	 * @param array $item Line for the table.
	 *
	 * @return string
	 */
	public function column_order( $item ) {
		_deprecated_function( __METHOD__, 'Yoast Local SEO 14.9' );
		$actions = [
			'edit'             => sprintf( '<a href="' . admin_url( 'post.php' ) . '?action=%s&post=%s">' . __( 'Edit', 'yoast-local-seo' ) . '</a>', 'edit', $item->ID ),
			'processing'       => sprintf( '<a href="?page=%s&action=%s&post=%s">' . __( 'Processing', 'yoast-local-seo' ) . '</a>', $_REQUEST['page'], 'wc-processing', $item->ID ),
			'transporting'     => sprintf( '<a href="?page=%s&action=%s&post=%s">' . __( 'Transporting', 'yoast-local-seo' ) . '</a>', $_REQUEST['page'], 'wc-transporting', $item->ID ),
			'ready-for-pickup' => sprintf( '<a href="?page=%s&action=%s&post=%s">' . __( 'Ready for pickup', 'yoast-local-seo' ) . '</a>', $_REQUEST['page'], 'wc-ready-for-pickup', $item->ID ),
			'completed'        => sprintf( '<a href="?page=%s&action=%s&post=%s">' . __( 'Completed', 'yoast-local-seo' ) . '</a>', $_REQUEST['page'], 'wc-completed', $item->ID ),
		];

		// Switch to just a string instead of a link if the item already has that status.
		switch ( $item->post_status ) {
			case 'wc-processing':
				$actions['processing'] = __( 'Processing', 'yoast-local-seo' );
				break;
			case 'wc-transporting':
				$actions['transporting'] = __( 'Transporting', 'yoast-local-seo' );
				break;
			case 'wc-ready-for-pickup':
				$actions['ready-for-pickup'] = __( 'Ready for pickup', 'yoast-local-seo' );
				break;
		}

		$the_order = wc_get_order( $item->ID );

		if ( $the_order->user_id ) {
			$user_info = get_userdata( $the_order->user_id );
		}

		if ( ! empty( $user_info ) ) {

			$username = '<a href="user-edit.php?user_id=' . absint( $user_info->ID ) . '">';

			if ( $user_info->first_name || $user_info->last_name ) {
				/* translators: 1: User first name; 2: User last name. */
				$username .= esc_html( sprintf( _x( '%1$s %2$s', 'full name', 'yoast-local-seo' ), ucfirst( $user_info->first_name ), ucfirst( $user_info->last_name ) ) );
			}
			else {
				$username .= esc_html( ucfirst( $user_info->display_name ) );
			}

			$username .= '</a>';
		}
		elseif ( $the_order->billing_first_name || $the_order->billing_last_name ) {
			/* translators: 1: User first name; 2: User last name. */
			$username = trim( sprintf( _x( '%1$s %2$s', 'full name', 'yoast-local-seo' ), $the_order->billing_first_name, $the_order->billing_last_name ) );
		}
		elseif ( $the_order->billing_company ) {
			$username = trim( $the_order->billing_company );
		}
		else {
			$username = __( 'Guest', 'yoast-local-seo' );
		}

		$output = '';
		/* translators: 1: the id number of the order, like: '#34'; 2: the username/customer that has submitted the order, like: 'Joost de Valk' */
		$output .= sprintf( _x( '%1$s by %2$s', 'Order number by X', 'yoast-local-seo' ), '<a href="' . admin_url( 'post.php?post=' . absint( $item->ID ) . '&action=edit' ) . '" class="row-title"><strong>#' . esc_attr( $the_order->get_order_number() ) . '</strong></a>', $username );

		if ( $the_order->billing_email ) {
			$output .= '<small class="meta email"><a href="' . esc_url( 'mailto:' . $the_order->billing_email ) . '">' . esc_html( $the_order->billing_email ) . '</a></small>';
		}

		$output .= $this->row_actions( $actions );

		$output .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details', 'yoast-local-seo' ) . '</span></button>';

		return $output;
	}

	/**
	 * Sets the default for a column.
	 *
	 * @deprecated 14.9
	 * @codeCoverageIgnore
	 *
	 * @param array  $item        Item for the table.
	 * @param string $column_name The column name.
	 *
	 * @return string|void|null
	 */
	public function column_default( $item, $column_name ) {
		_deprecated_function( __METHOD__, 'Yoast Local SEO 14.9' );
		switch ( $column_name ) {
			case 'status':
				return $this->get_status_output( $item );
			case 'products':
				return $this->get_products_output( $item );
			case 'destination':
				return $this->get_destination_output( $item );
			default:
				return '';
		}
	}

	/**
	 * Renders a row.
	 *
	 * @deprecated 14.9
	 * @codeCoverageIgnore
	 *
	 * @param array $item A row.
	 *
	 * @return void
	 */
	public function single_row( $item ) {
		_deprecated_function( __METHOD__, 'Yoast Local SEO 14.9' );
		$active_class = isset( $item->active ) ? 'active' : '';

		echo '<tr class="' . $active_class . '">';
		echo $this->single_row_columns( $item );
		echo "</tr>\n";
	}

	/**
	 * Sort function.
	 *
	 * @deprecated 14.9
	 * @codeCoverageIgnore
	 *
	 * @param array $a Sort option 1.
	 * @param array $b Sort option 2.
	 *
	 * @return int
	 */
	public function usort_reorder( $a, $b ) {
		_deprecated_function( __METHOD__, 'Yoast Local SEO 14.9' );
		// If no sort, default to shop_order.
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'ID';
		// If no order, default to asc.
		$order = ( ! empty( $_GET['order'] ) ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'asc';

		// Determine sort order.
		$result = strcmp( $a->{$orderby}, $b->{$orderby} );

		// Send final sort direction to usort.
		return ( ( $order === 'asc' ) ? $result : -$result );
	}

	/**
	 * Gets status string.
	 *
	 * @deprecated 14.9
	 * @codeCoverageIgnore
	 *
	 * @param array $item The line item.
	 *
	 * @return string|null
	 */
	public function get_status_output( $item ) {
		_deprecated_function( __METHOD__, 'Yoast Local SEO 14.9' );

		switch ( $item->post_status ) {
			case 'wc-processing':
				return __( 'Processing', 'yoast-local-seo' );
			case 'wc-transporting':
				return __( 'Transporting', 'yoast-local-seo' );
			case 'wc-ready-for-pickup':
				return __( 'Ready for pickup', 'yoast-local-seo' );
			default:
				return $item->post_status;
		}
	}

	/**
	 * Gets the output.
	 *
	 * @deprecated 14.9
	 * @codeCoverageIgnore
	 *
	 * @param array $item The line to generate output for.
	 *
	 * @return string
	 */
	public function get_products_output( $item ) {
		$order    = wc_get_order( $item );
		$products = $order->get_items();

		$list_items = [];
		foreach ( $products as $product ) {
			$id           = array_pop( $product['item_meta']['_product_id'] );
			$url          = admin_url( sprintf( 'post.php?post=%d&action=edit', $id ) );
			$list_items[] = array_pop( $product['item_meta']['_qty'] ) . 'x <a href="' . $url . '">' . $product['name'] . '</a><br />';
		}

		return implode( $list_items );
	}

	/**
	 * Gets destination output.
	 *
	 * @deprecated 14.9
	 * @codeCoverageIgnore
	 *
	 * @param array $item The line to generate output for.
	 *
	 * @return mixed
	 */
	public function get_destination_output( $item ) {
		$order = wc_get_order( $item );

		return $order->get_shipping_method();
	}
}
