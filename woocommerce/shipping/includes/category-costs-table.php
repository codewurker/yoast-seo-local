<?php
/**
 * Yoast SEO: Local for WooCommerce plugin file.
 *
 * @package YoastSEO_Local_WooCommerce
 */

$cost_desc = __( 'Enter a cost (excl. tax) or sum, e.g. 10.00 * [qty].', 'yoast-local-seo' ) . '<br/><br/>' . __( 'Use [qty] for the number of items, <br/>[cost] for the total cost of items, and [fee percent="10" min_fee="20" max_fee=""] for percentage based fees.', 'yoast-local-seo' );
?>
<tr valign="top" class="wpseo_local_shipping_costs">
	<th scope="row" class="titledesc"><?php esc_html_e( 'Cost per category', 'yoast-local-seo' ); ?></th>
	<td class="forminp" id="<?php echo $this->id; ?>_locations">
		<table class="shippingrows widefat" cellspacing="0">
			<caption class="screen-reader-text"><?php esc_html_e( 'Cost per category', 'yoast-local-seo' ); ?></caption>
			<thead>
				<tr>
					<th scope="col" class="check-column"></th>
					<th scope="col"><?php esc_html_e( 'Location category', 'yoast-local-seo' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Allow local pickup', 'yoast-local-seo' ); ?> <a class="tips" data-tip="<?php esc_attr_e( 'Whether or not to allow local pickup from locations in this category.', 'yoast-local-seo' ); ?>">[?]</a></th>
					<th scope="col"><?php esc_html_e( 'Costs', 'yoast-local-seo' ); ?> <a class="tips" data-tip="<?php echo esc_attr( $cost_desc ); ?>">[?]</a></th>
				</tr>
			</thead>
			<tbody class="locations">
			<?php
			if ( ! empty( $this->location_categories ) ) {
				foreach ( $this->location_categories as $category ) {

					$yoast_local_label_allow = sprintf(
						/* translators: %s expands to a pickup category name. */
						__( 'Allow pickup category: %s', 'yoast-local-seo' ),
						$category->name
					);
					$yoast_local_label_costs = sprintf(
						/* translators: %s expands to a pickup category name. */
						__( 'Costs for pickup category: %s', 'yoast-local-seo' ),
						$category->name
					);

					printf(
						'<tr class="location">
							<th scope="row" class="check-column"></th>
							<td>%1$s</td>
							<td><label for="%2$s" class="screen-reader-text">%3$s</label><input type="checkbox" %4$s name="%2$s" /></td>
							<td><label for="%5$s" class="screen-reader-text">%6$s</label><input type="text" value="%7$s" name="%5$s" placeholder="%8$s" class="input-text regular-input" /></td>
						</tr>',
						/* First column. */
						esc_html( $category->name ),
						/* Second column. */
						esc_attr( $this->id . '_cat_allowed[' . $category->term_id . ']' ),
						esc_html( $yoast_local_label_allow ),
						checked( true, $category->allowed, false ),
						/* Third column. */
						esc_attr( $this->id . '_cat_cost[' . $category->term_id . ']' ),
						esc_html( $yoast_local_label_costs ),
						esc_attr( $category->price ),
						esc_attr( $cost_desc )
					);
				}
			}
			?>
			</tbody>
			<tfoot>
			<tr>
				<th colspan="4"></th>
			</tr>
			</tfoot>
		</table>
	</td>
</tr>
