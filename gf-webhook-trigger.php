<?php
/**
 * Plugin Name: GF Webhook Trigger
 * Description: Sends Gravity Forms submissions to an external webhook (e.g. Webhook.site) after a form is submitted.
 * Version: 1.0.0
 * Author: Raphael Shawn Taurai
 */

// === CONFIG: CHANGE THESE TO MATCH YOUR SETUP ====================

// Gravity Form ID you want to send (check in Forms > hover the form name)
define( 'GFWT_FORM_ID', 1 );

// Your Webhook URL from https://webhook.site/
define( 'GFWT_WEBHOOK_URL', 'https://webhook.site/7971eec8-f015-4033-ba6b-f363a8c3e556' );

// ================================================================

/**
 * Hook into Gravity Forms after submission for a specific form.
 * This runs only for the form with ID GFWT_FORM_ID.
 */
add_action( 'gform_after_submission', 'gfwt_send_entry_to_webhook', 10, 2 );

function gfwt_send_entry_to_webhook( $entry, $form ) {

    // Only run for the configured form ID
    if ( (int) rgar( $entry, 'form_id' ) !== (int) GFWT_FORM_ID ) {
        return;
    }

    // Build a nice payload with all fields
    $payload = array(
        'entry_id'     => rgar( $entry, 'id' ),
        'form_id'      => rgar( $entry, 'form_id' ),
        'submitted_at' => rgar( $entry, 'date_created' ),
        'form_title'   => rgar( $form, 'title' ),
        'fields'       => array(),
    );

    // Loop through fields to send label + value
    foreach ( $form['fields'] as $field ) {
        $field_id    = (string) $field->id;
        $field_label = $field->label;
        $field_value = rgar( $entry, $field_id );

        $payload['fields'][] = array(
            'id'    => $field_id,
            'label' => $field_label,
            'value' => $field_value,
        );
    }

    // Prepare POST request
    $args = array(
        'method'      => 'POST',
        'timeout'     => 20,
        'redirection' => 5,
        'httpversion' => '1.1',
        'blocking'    => true,
        'headers'     => array(
            'Content-Type' => 'application/json',
        ),
        'body'        => wp_json_encode( $payload ),
    );

    // Send to Webhook.site (or any external service)
    $response = wp_remote_post( GFWT_WEBHOOK_URL, $args );

    // Optional logging for debugging
    if ( is_wp_error( $response ) ) {
        error_log( 'GF Webhook Trigger error: ' . $response->get_error_message() );
    } else {
        error_log( 'GF Webhook Trigger success: ' . wp_remote_retrieve_body( $response ) );
    }
}
