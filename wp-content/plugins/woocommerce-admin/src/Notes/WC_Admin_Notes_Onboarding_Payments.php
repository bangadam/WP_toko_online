<?php
/**
 * WooCommerce Admin: Payments reminder note.
 *
 * Adds a notes to complete the payment methods.
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\Features\Onboarding;

/**
 * WC_Admin_Notes_Onboarding_Payments.
 */
class WC_Admin_Notes_Onboarding_Payments {
	const NOTE_NAME = 'wc-admin-onboarding-payments-reminder';

	/**
	 * Creates a note to remind store owners to set up payments.
	 */
	public static function possibly_add_onboarding_payments_note() {
		// This note should only be added if the task list is still shown.
		if ( ! Onboarding::should_show_tasks() ) {
			return;
		}

		// Make sure payments task was skipped at least 3 days ago.
		$three_days_in_seconds = 3 * DAY_IN_SECONDS;
		$payments_task         = get_option( 'woocommerce_task_list_payments', array() );
		if (
			! isset( $payments_task['skipped'] ) ||
			! isset( $payments_task['timestamp'] ) ||
			( time() - $payments_task['timestamp'] ) < $three_days_in_seconds
		) {
			return;
		}

		// Check to see if any gateways have been added.
		$gateways         = WC()->payment_gateways->get_available_payment_gateways();
		$enabled_gateways = array_filter(
			$gateways,
			function( $gateway ) {
				return 'yes' === $gateway->enabled;
			}
		);
		if ( ! empty( $enabled_gateways ) ) {
			return;
		}

		// Don't add this note if previously added.
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );
		if ( ! empty( $note_ids ) ) {
			return;
		}

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Start accepting payments on your store!', 'woocommerce-admin' ) );
		$note->set_content( __( 'Take payments with the provider that’s right for you - choose from 100+ payment gateways for WooCommerce.', 'woocommerce-admin' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_icon( 'credit-card' );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'view-payment-gateways',
			__( 'Learn more', 'woocommerce-admin' ),
			'https://woocommerce.com/product-category/woocommerce-extensions/payment-gateways/',
			'actioned',
			true
		);

		$note->save();
	}
}
