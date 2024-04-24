<?php
/**
 * Yoast SEO: Local for WooCommerce plugin file.
 *
 * @package YoastSEO_Local_WooCommerce
 */

$cost_desc = __( 'Enter a cost (excl. tax) or sum, e.g. 10.00 * [qty].', 'yoast-local-seo' ) . '<br/><br/>' . __( 'Use [qty] for the number of items, <br/>[cost] for the total cost of items, and [fee percent="10" min_fee="20" max_fee=""] for percentage based fees.', 'yoast-local-seo' );
?>
<tr valign="top" class="wpseo_local_shipping_costs">
	<th scope="row" class="titledesc">
		<?php esc_html_e( 'Cost per location', 'yoast-local-seo' ); ?>
		<?php
		if ( is_array( $this->location_categories ) && count( $this->location_categories ) > 0 ) {
			echo '<p>' . esc_html__( 'These settings will override any category specific settings made above.', 'yoast-local-seo' ) . '</p>';
		}
		?>
	</th>
	<td class="forminp" id="<?php echo $this->id; ?>_locations">
		<table class="shippingrows widefat" cellspacing="0">
			<caption class="screen-reader-text"><?php esc_html_e( 'Cost per location', 'yoast-local-seo' ); ?></caption>
			<thead>
			<tr>
				<th scope="col" class="check-column"></th>
				<th scope="col"><?php esc_html_e( 'Location', 'yoast-local-seo' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Allow local pickup', 'yoast-local-seo' ); ?> <a class="tips" data-tip="<?php esc_attr_e( 'Whether or not to allow local pickup from this location.', 'yoast-local-seo' ); ?>">[?]</a></th>
				<th scope="col"><?php esc_html_e( 'Costs', 'yoast-local-seo' ); ?> <a class="tips" data-tip="<?php echo esc_attr( $cost_desc ); ?>">[?]</a></th>
				<th scope="col"></th>
			</tr>
			</thead>
			<tbody id="shipping_locations" class="locations">
			<?php
			if ( ! empty( $this->saved_locations ) ) {
				foreach ( $this->saved_locations as $location ) {
					$defaults = $this->resolve_defaults( $location );

					$yoast_local_label_allow = sprintf(
						/* translators: Hidden accessibility text; %s expands to a pickup location title. */
						__( 'Allow pickup location: %s', 'yoast-local-seo' ),
						$location->post_title
					);
					$yoast_local_label_costs = sprintf(
						/* translators: Hidden accessibility text; %s expands to a pickup location title. */
						__( 'Costs for pickup location: %s', 'yoast-local-seo' ),
						$location->post_title
					);

					printf(
						'<tr class="location" data-id="%1$s" data-title="%2$s" data-defaults=\'%3$s\' >
							<th scope="row" class="check-column"></th>
							<td>%4$s</td>
							<td><label for="%5$s" class="screen-reader-text">%6$s</label><input type="checkbox" %7$s name="%5$s" /> <small>%8$s</small></td>
							<td><label for="%9$s" class="screen-reader-text">%10$s</label><input type="text" value="%11$s" name="%9$s" placeholder="%12$s" class="input-text regular-input" /> <small>%13$s</small></td>
							<td><input class="location_rule_remove" type="button" class="button" value="%14$s"></td>
						</tr>',
						/* Row attributes - placeholder 1 to 3. */
						(int) $location->ID,
						esc_attr( $location->post_title ),
						// phpcs:ignore WordPress.Security.EscapeOutput -- WPCS bug: methods can't be globally ignored yet.
						WPSEO_Utils::format_json_encode( array_map( 'esc_attr', $defaults ) ),
						/* First column - placeholder 4. */
						esc_html( $location->post_title ),
						/* Second column - placeholder 5 to 8. */
						esc_attr( $this->id . '_location_allowed[' . $location->ID . ']' ),
						esc_html( $yoast_local_label_allow ),
						checked( true, $location->allowed, false ),
						esc_html( $defaults['status'] ),
						/* Third column - placeholder 9 to 13. */
						esc_attr( $this->id . '_location_cost[' . $location->ID . ']' ),
						esc_html( $yoast_local_label_costs ),
						esc_attr( $location->price ),
						esc_attr( $cost_desc ),
						esc_html( $defaults['price'] ),
						/* Fourth column - placeholder 14. */
						esc_attr__( 'Remove', 'yoast-local-seo' )
					);
				}
			}
			?>
			</tbody>
			<tfoot>
			<tr>
				<td colspan="5">
					<?php esc_html_e( 'New location specific settings for:', 'yoast-local-seo' ); ?>
					<select id="location_setting_select">
						<option value="0"><?php esc_html_e( 'Select a location to add', 'yoast-local-seo' ); ?></option>
						<?php
						if ( ! empty( $this->available_locations ) ) {
							foreach ( $this->available_locations as $location ) {
								$defaults = $this->resolve_defaults( $location );
								echo '<option value="' . (int) $location->ID . '" data-defaults=\''
									// phpcs:ignore WordPress.Security.EscapeOutput -- WPCS bug: methods can't be globally ignored yet.
									. WPSEO_Utils::format_json_encode( $defaults )
									. '\'>' . $location->post_title . '</option>';
							}
						}
						?>
					</select>
					<input id="location_setting_add" type="button" class="button" value="<?php esc_attr_e( 'Add', 'yoast-local-seo' ); ?>">
				</td>
			</tr>
			</tfoot>
		</table>
	</td>
</tr>
