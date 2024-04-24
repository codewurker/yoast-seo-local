<?php
/**
 * Shipping Methods Display
 *
 * In 2.1 we show methods per package. This allows for multiple methods per order if so desired.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

$formatted_destination    = ( $formatted_destination ?? WC()->countries->get_formatted_address( $package['destination'], ', ' ) );
$has_calculated_shipping  = ! empty( $has_calculated_shipping );
$show_shipping_calculator = ! empty( $show_shipping_calculator );
$calculator_text          = '';

// Custom WPSEO Local variables.
$yoast_seo_subset_started = false;
$yoast_seo_subset_ended   = true;
$settings                 = get_option( 'woocommerce_yoast_wcseo_local_pickup_settings' );
$radiobuttons             = [];

?>
<tr class="woocommerce-shipping-totals shipping">
	<th><?php echo wp_kses_post( $package_name ); ?></th>
	<td data-title="<?php echo esc_attr( $package_name ); ?>">
		<?php if ( count( $available_methods ) > 1 ) : ?>
		<ul id="shipping_method" class="woocommerce-shipping-methods">
			<?php foreach ( $available_methods as $method ) : ?>

				<?php

				// Is the method one of our local pickup stores?
				if ( $method->method_id === 'yoast_wcseo_local_pickup' ) {

					// And if we haven't yet started our subset...
					if ( ! $yoast_seo_subset_started ) {

						// Check/uncheck the parent aka the-toggler.
						$parent_method = false;
						if ( strpos( $chosen_method, 'yoast_wcseo_local_pickup_' ) === 0 ) {
							$parent_method = 'yoast_wcseo_local_pickup';
						}

						// Output the parent toggler which enables us to show/hide the subset.
						printf(
							'<li class="parent-toggler"><input type="radio" name="yoast-local-seo-shipping_method_toggle" id="yoast-local-seo-shipping_method_toggle" class="yoast-local-seo-shipping_method_toggle shipping_method" %1$s /><label for="yoast-local-seo-shipping_method_toggle">%2$s</label>',
							checked( 'yoast_wcseo_local_pickup', $parent_method, false ),
							esc_html__( 'Local store pickup', 'yoast-local-seo' )
						);

						// Output the subset wrapper as a list or as a dropdown.
						if ( $settings['checkout_mode'] !== 'radio' ) {
							echo '<select class="shipping_method shipping_method_subset" id="shipping_method_select">';
						}

						// Flag that we have started the subset but not yet ended it!
						$yoast_seo_subset_started = true;
						$yoast_seo_subset_ended   = false;
					}
				}
				// If this is not a local pickup store we may need to end our subset-loop.
				elseif ( $yoast_seo_subset_started && ( ! $yoast_seo_subset_ended ) ) {
					// Close the radio-list or the checkbox.
					if ( $settings['checkout_mode'] !== 'radio' ) {
						echo '</select><!-- .shipping_method_subset -->';
					}

					// Close the paren toggler.
					echo '</li><!-- .parent-toggler -->';

					// Flag that we have ended our subset, and we have not started a new one.
					$yoast_seo_subset_started = false;
					$yoast_seo_subset_ended   = true;
				}

				// Show a Local pickup store in a different way then other shipping methods.
				if ( $method->method_id === 'yoast_wcseo_local_pickup' ) {

					// Output radio with some extra address data.
					$radiobuttons[] = sprintf(
						'<li><input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s /><label for="shipping_method_%1$d_%2$s">%5$s <small class="shipping_method_address">%6$s</small></label></li>',
						$index,
						sanitize_title( $method->id ),
						esc_attr( $method->id ),
						checked( $method->id, $chosen_method, false ),
						wc_cart_totals_shipping_method_label( $method ),
						yoast_seo_local_woocommerce_get_address_for_method_id( $method->id )
					);

					// Or do we desire options inside a dropdown?
					if ( $settings['checkout_mode'] !== 'radio' ) {

						// Output option with some extra address data.
						printf(
							'<option value="%3$s" class="shipping_method_option" %4$s >%5$s - %6$s</option>',
							$index,
							sanitize_title( $method->id ),
							esc_attr( $method->id ),
							selected( $method->id, $chosen_method, false ),
							wc_cart_totals_shipping_method_label( $method ),
							yoast_seo_local_woocommerce_get_address_for_method_id( $method->id )
						);
					}

					// Show all other shipping methods in the regular way ( radios without extra data ).
				}
				else {

					// Regular radio button.
					printf(
						'<li><input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s /><label for="shipping_method_%1$d_%2$s">%5$s</label></li>',
						$index,
						sanitize_title( $method->id ),
						esc_attr( $method->id ),
						checked( $method->id, $chosen_method, false ),
						wc_cart_totals_shipping_method_label( $method )
					);
				}

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.
				do_action( 'woocommerce_after_shipping_rate', $method, $index );

				?>

			<?php endforeach; ?>

			<?php

			// All done? We may need to end our subset-loop.
			if ( $yoast_seo_subset_started && ( ! $yoast_seo_subset_ended ) ) {

				// Close the radio-list or the checkbox.
				if ( $settings['checkout_mode'] !== 'radio' ) {
					echo '</select><!-- .shipping_method_subset -->';
				}

				if ( ! empty( $radiobuttons ) ) {
					echo '<ul class="shipping_method_subset" ' . ( ( $settings['checkout_mode'] !== 'radio' ) ? 'style="display:none;"' : '' ) . '>';
					foreach ( $radiobuttons as $radiobutton ) {
						echo $radiobutton;
					}
					echo '</div>';
				}
				echo '</ul><!-- .shipping_method_subset -->';

				// Close the parent toggler.
				echo '</li><!-- .parent-toggler -->';

				// Flag that we have ended our subset, and we have not started a new one.
				$yoast_seo_subset_started = false;
				$yoast_seo_subset_ended   = true;
			}

			// End all shipping methods list.
			echo '</ul><!-- #shipping_method -->';
			?>
			<?php if ( is_cart() ) : ?>
				<p class="woocommerce-shipping-destination">
					<?php
					if ( $formatted_destination ) {
						/* Translators: $s shipping destination. */
						printf( esc_html__( 'Shipping to %s.', 'yoast-local-seo' ) . ' ', '<strong>' . esc_html( $formatted_destination ) . '</strong>' );
						$calculator_text = __( 'Change address', 'yoast-local-seo' );
					}
					else {
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.
						echo wp_kses_post( apply_filters( 'woocommerce_shipping_estimate_html', __( 'Shipping options will be updated during checkout.', 'yoast-local-seo' ) ) );
					}
					?>
				</p>
			<?php endif; ?>
			<?php elseif ( count( $available_methods ) === 1 ) : ?>
				<?php
				$method = current( $available_methods );
				if ( $method->method_id === 'yoast_wcseo_local_pickup' ) {
					printf( '%3$s <small class="shipping_method_address">%4$s</small> <input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d" value="%2$s" class="shipping_method" />', $index, esc_attr( $method->id ), wc_cart_totals_shipping_method_label( $method ), yoast_seo_local_woocommerce_get_address_for_method_id( $method->id ) );
				}
				else {
					printf( '%3$s <input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d" value="%2$s" class="shipping_method" />', $index, esc_attr( $method->id ), wc_cart_totals_shipping_method_label( $method ) );
				}
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.
				do_action( 'woocommerce_after_shipping_rate', $method, $index );

			elseif ( ! WC()->customer->has_calculated_shipping() ) :
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.
				echo wp_kses_post( apply_filters( 'woocommerce_shipping_may_be_available_html', __( 'Enter your address to view shipping options.', 'yoast-local-seo' ) ) );
			else :
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.
				echo wp_kses_post( apply_filters( 'woocommerce_no_shipping_available_html', __( 'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'yoast-local-seo' ) ) );
			endif;
			?>

			<?php if ( $show_package_details ) : ?>
				<?php echo '<p class="woocommerce-shipping-contents"><small>' . esc_html( $package_details ) . '</small></p>'; ?>
			<?php endif; ?>

			<?php if ( $show_shipping_calculator ) : ?>
				<?php woocommerce_shipping_calculator( $calculator_text ); ?>
			<?php endif; ?>
	</td>
</tr>

<script>

	jQuery(document).ready(function ($) {

		// Select2 Enhancement if it exists.
		if ($().select2 && (yoast_wcseo_local_translations.select2 == 'enabled')) {
			$('#shipping_method_select').select2({width: 'resolve'});
		}

		$('input.shipping_method').on('change', function (e) {

			$('#shipping_method_select').remove();
		});

		// Init change toggle on our radio-btn.
		$('#yoast-local-seo-shipping_method_toggle').on('change', function (e) {

			$this = $(this);
			var checked = $this.prop('checked');
			var $subset = $this.parent().find('.shipping_method_subset');

			if (checked) {

				// If it is checked...
				if ($subset.find('input').length > 0) { // Look for inputs inside the subset.

					// Look for a checked item in the subset radiolist.
					if ($subset.find('input:checked').length == 0) {

						/*
						 * If no checked items are found, make sure the first item is
						 * checked and triggered ( so Woo's calculator fires correctly ).
						 */
						$subset.find('input:first').trigger('click');
					}

				} else {

					if ($subset.find('input:selected').length == 0) {
						$subset.find('option:first').prop('selected', true);
					}

					$subset.trigger('change');
				}
			}
		});
	});

</script>

<style>
	.shipping_method_subset {
		display: none;
		list-style: none;
	}

	input:checked ~ .shipping_method_subset {
		display: block;
		width: 100%;
		max-width: 100%;
	}

	select.shipping_method_subset {
		margin: 10px 0;
	}

	.woocommerce ul#shipping_method li .select2-container {
		text-indent: 0;
		max-width: 300px;
	}
</style>
