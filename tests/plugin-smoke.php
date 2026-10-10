<?php
// Run through `wp eval-file` in the isolated test WordPress, never production.
use ZbEmailValidator\ApiClient;
use ZbEmailValidator\Settings;
use ZbEmailValidator\Validator;
global $checks;
$checks = 0;
function zb_assert( $ok, $message ) {
    global $checks;
    if ( ! $ok ) { throw new RuntimeException( $message ); }
    ++$checks;
}
$requests = [];
$mode = 'ok';
add_filter( 'pre_http_request', function ( $pre, $args, $url ) use ( &$requests, &$mode ) {
    $requests[] = [ $url, $args ];
    if ( 'https://api.zbounce.net/v1/me' === $url ) { return 'balance_failure' === $mode ? [ 'response'=>['code'=>403], 'body'=>'{}' ] : [ 'response'=>['code'=>200], 'body'=>wp_json_encode(['remaining'=>'balance_zero' === $mode ? 0 : 1234]) ]; }
    if ( 'transport' === $mode ) { return new WP_Error( 'timeout', 'test timeout' ); }
    if ( '403' === $mode ) { return [ 'response' => [ 'code' => 403 ], 'body' => '{}' ]; }
    $body = [ 'email' => 'good@example.org', 'valid' => true, 'disposable' => false, 'accept_all' => false ];
    if ( 'ok' === $mode ) { $body['exists'] = true; }
    if ( 'rejected' === $mode ) { $body['exists'] = false; $body['permanent_error'] = true; $body['error_category'] = 'mailbox_not_found'; }
    if ( 'temporary' === $mode ) { $body['exists'] = false; $body['error_category'] = 'temporary_error'; }
    if ( 'blocked' === $mode ) { $body['exists'] = false; $body['permanent_error'] = true; $body['error_category'] = 'auth_required'; }
    if ( 'ttl' === $mode ) { $body['exists'] = true; $body['ttl'] = 1; }
    if ( 'acceptallfalse' === $mode ) { $body['exists'] = false; $body['accept_all'] = true; }
    if ( 'disposable' === $mode ) { $body['disposable'] = true; }
    if ( 'catchall' === $mode ) { $body['accept_all'] = true; }
    if ( 'malformed' === $mode ) { $body = [ 'valid' => 'false' ]; }
    return [ 'response' => [ 'code' => 200 ], 'body' => wp_json_encode( $body ) ];
}, 10, 3 );
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->prefix}zb_email_cache" );
update_option( Settings::OPTION_KEY, Settings::DEFAULT_SETTINGS );
zb_assert( null === ApiClient::sync_verify( 'good@example.org' ), 'No key must not call API' );
zb_assert( count( $requests ) === 0, 'No key leaked a request' );
$options = Settings::DEFAULT_SETTINGS;
$options['api_key'] = 'test-key-not-a-real-key';
update_option( Settings::OPTION_KEY, $options );
zb_assert( null === ApiClient::sync_verify( 'good@example.org' ), 'Key without opt-in must not call API' );
zb_assert( count( $requests ) === 0, 'Consent gate leaked request' );
$options['service_enabled'] = true;
update_option( Settings::OPTION_KEY, $options );
zb_assert( ! Settings::is_connected(), 'Missing license acceptance enabled the API' );
zb_assert( null === ApiClient::sync_verify( 'license@example.org' ) && 0 === count( $requests ), 'Unaccepted license leaked an API request' );
$options['license_accepted'] = true;
update_option( Settings::OPTION_KEY, $options );
$data = Validator::run_sync_validation( 'good@example.org' );
zb_assert( true === $data['valid'] && true === $data['exists'], 'Confirmed mailbox must normalize' );
zb_assert( 1 === count( $requests ), 'Expected one verification' );
zb_assert( $requests[0][0] === 'https://api.zbounce.net/v1/fast-verify', 'Wrong endpoint' );
zb_assert( $requests[0][1]['headers']['X-API-Key'] === $options['api_key'], 'Missing key header' );
zb_assert( json_decode( $requests[0][1]['body'], true ) === [ 'email' => 'good@example.org' ], 'Unexpected personal fields' );
Validator::run_sync_validation( 'good@example.org' );
zb_assert( 1 === count( $requests ), 'Cache did not prevent second charge' );
$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}zb_email_cache SET created = %s", gmdate( 'Y-m-d H:i:s', time() - 90000 ) ) );
Validator::run_sync_validation( 'good@example.org' );
zb_assert( 2 === count( $requests ), 'Expired cache did not recheck' );
foreach ( [ 'transport', '403', 'malformed', 'temporary', 'blocked' ] as $failure ) {
    $mode = $failure;
    $before = count( $requests );
    $data = Validator::run_sync_validation( $failure . '@example.org' );
    zb_assert( true === $data['valid'] && null === $data['exists'], 'Failure should preserve local format and undecided mailbox' );
    zb_assert( false === ApiClient::get_cached_result( md5( $failure . '@example.org' ) ), 'Failure was cached' );
    zb_assert( count( $requests ) === $before + 1, 'Error triggered demo fallback' );
}
$mode = 'ttl';
Validator::run_sync_validation( 'ttl@example.org' );
sleep( 2 );
zb_assert( false === ApiClient::get_cached_result( md5( 'ttl@example.org' ) ), 'Engine retry TTL not respected' );
$mode = 'acceptallfalse';
zb_assert( null === Validator::run_sync_validation( 'acceptallfalse@example.org' )['exists'], 'Catch-all negative must remain undecided' );
$wpdb->replace( $wpdb->prefix . 'zb_email_cache', [ 'email_hash' => md5( 'old@example.org' ), 'created' => gmdate('Y-m-d H:i:s'), 'validation_data' => wp_json_encode([ 'email'=>'old@example.org','valid'=>true,'exists'=>false,'disposable'=>false,'accept_all'=>false,'error_category'=>'temporary_error' ]) ] );
zb_assert( false === ApiClient::get_cached_result( md5('old@example.org') ), 'Legacy transient failure must not be reused' );
$mode = 'rejected';
zb_assert( false === Validator::run_sync_validation( 'rejected@example.org' )['exists'], 'Rejected mailbox lost' );
$mode = 'catchall';
zb_assert( true === Validator::run_sync_validation( 'catchall@example.org' )['accept_all'], 'Catch-all lost' );
$mode = 'disposable';
zb_assert( true === Validator::run_sync_validation( 'disposable@example.org' )['disposable'], 'Disposable lost' );
// Policy changes apply immediately to cached facts without another paid check.
$mode = 'catchall';
zb_assert( '' === Validator::validation_error( 'policy-risky@example.org' ), 'Default risky policy changed' );
$before = count( $requests );
$options['allowed_statuses'] = [ 'valid' ]; update_option( Settings::OPTION_KEY, $options );
zb_assert( '' !== Validator::validation_error( 'policy-risky@example.org' ), 'Risky rejection ignored' );
zb_assert( $before === count( $requests ), 'Policy change charged a cached check' );
$mode = 'temporary';
zb_assert( 'unknown' === Validator::run_sync_validation( 'policy-unknown@example.org' )['verification_status'], 'SMTP uncertainty misclassified as API outage' );
zb_assert( '' !== Validator::validation_error( 'policy-unknown@example.org' ), 'Unknown rejection ignored' );
$mode = 'transport';
zb_assert( 'unavailable' === Validator::run_sync_validation( 'policy-outage@example.org' )['verification_status'], 'Transport outage misclassified' );
zb_assert( '' !== Validator::validation_error( 'policy-outage@example.org' ), 'Outage rejection ignored' );
$options['allowed_statuses'] = [ 'disposable', 'mailbox_not_found' ]; update_option( Settings::OPTION_KEY, $options );
$mode = 'disposable'; zb_assert( '' === Validator::validation_error( 'policy-disposable@example.org' ), 'Disposable allow rule ignored' );
$mode = 'rejected'; zb_assert( '' === Validator::validation_error( 'policy-rejected@example.org' ), 'Missing mailbox allow rule ignored' );
zb_assert( [] === Settings::sanitize_settings( [ 'allowed_statuses' => [ '', 'made_up' ] ] )['allowed_statuses'], 'Unknown policy values accepted' );
$options['allowed_statuses'] = Settings::DEFAULT_SETTINGS['allowed_statuses']; update_option( Settings::OPTION_KEY, $options );
// Store API drafts must never spend verification credits.
$order = new WC_Order(); $order->set_billing_email( 'store-policy@example.org' );
$before = count( $requests );
foreach ( [ 'GET', 'PUT', 'PATCH' ] as $method ) {
    Validator::validate_store_api_email( $order, new WP_REST_Request( $method, '/wc/store/v1/checkout' ) );
}
zb_assert( $before === count( $requests ), 'Checkout autosave spent verification credits' );
$options['allowed_statuses'] = [ 'valid' ]; update_option( Settings::OPTION_KEY, $options );
$mode = 'catchall'; $blocked = false;
try { Validator::validate_store_api_email( $order, new WP_REST_Request( 'POST', '/wc/store/v1/checkout' ) ); }
catch ( \Automattic\WooCommerce\StoreApi\Exceptions\RouteException $error ) { $blocked = true; }
zb_assert( $blocked, 'Store API ignored risky rejection policy' );
$options['allowed_statuses'] = [ 'risky' ]; update_option( Settings::OPTION_KEY, $options );
$before = count( $requests );
Validator::validate_store_api_email( $order, new WP_REST_Request( 'POST', '/wc/store/v1/checkout' ) );
zb_assert( $before === count( $requests ), 'Store API policy change did not reuse cached facts' );
$options['allowed_statuses'] = Settings::DEFAULT_SETTINGS['allowed_statuses']; update_option( Settings::OPTION_KEY, $options );
$license_clean = Settings::sanitize_settings( [ 'api_key'=>'key', 'service_enabled'=>1 ] );
zb_assert( ! $license_clean['service_enabled'], 'Missing acceptance was not rejected on save' );
$license_clean = Settings::sanitize_settings( [ 'api_key'=>'key', 'service_enabled'=>1, 'license_accepted'=>1, 'license_accepted_at'=>123, 'license_accepted_by'=>999 ] );
zb_assert( $license_clean['service_enabled'] && $license_clean['license_accepted'], 'Accepted license did not enable API' );
zb_assert( $license_clean['license_accepted_at'] !== 123 && $license_clean['license_accepted_by'] !== 999, 'Client forged acceptance audit fields' );
$clean = Settings::sanitize_settings( [ 'api_key' => '<b>key</b>', 'service_enabled' => 1, 'cache_duration' => 999, 'gf_forms' => [ '3', '3', '-7', 0 ] ] );
zb_assert( 'key' === $clean['api_key'] && 168 === $clean['cache_duration'], 'Unsafe settings not normalized' );
zb_assert( [ 3, 7 ] === $clean['gf_forms'], 'Form IDs not normalized' );
zb_assert( false === $clean['wc_enable'] && false === $clean['reg_enable'], 'Unchecked toggles enabled' );
zb_assert( false === Settings::sanitize_settings( [ 'service_enabled' => 1 ] )['service_enabled'], 'Enabled without key' );
zb_assert( ! has_action( 'wp_ajax_nopriv_zb_create_validation_task' ), 'Anonymous tester exposes paid checks' );
wp_set_current_user( 0 );
zb_assert( '' === do_shortcode( '[zb_email_validator]' ), 'Tester visible to anonymous user' );
wp_set_current_user( 1 );
zb_assert( false !== strpos( do_shortcode( '[zb_email_validator]' ), 'zb-email-input' ), 'Admin tester absent' );
// Integration callbacks run inside WordPress with real free form plugins loaded.
$options['wpf_forms'] = [ 101 ];
$options['nf_forms'] = [ 102 ];
$options['gf_forms'] = [ 103 ];
update_option( Settings::OPTION_KEY, $options );
\ZbEmailValidator\Integrations::init();
zb_assert( false !== has_action( 'wpforms_process_validate_email', [ 'ZbEmailValidator\\Integrations', 'validate_wpforms' ] ), 'WPForms hook not registered' );
zb_assert( false !== has_filter( 'ninja_forms_submit_data', [ 'ZbEmailValidator\\Integrations', 'validate_ninja' ] ), 'Ninja hook not registered' );
$mode = 'rejected';
\ZbEmailValidator\Integrations::validate_wpforms( 1, 'wpforms-reject@example.org', [ 'id' => 101 ] );
zb_assert( ! empty( wpforms()->obj( 'process' )->errors[101][1] ), 'WPForms did not reject missing mailbox' );
$ninja = apply_filters( 'ninja_forms_submit_data', [ 'id' => 102, 'fields' => [ [ 'id' => 7, 'type' => 'email', 'value' => 'ninja-reject@example.org' ] ] ] );
zb_assert( ! empty( $ninja['errors']['fields'][7] ), 'Ninja did not attach field error' );
$untouched = [ 'id' => 999, 'fields' => [ [ 'id' => 7, 'type' => 'email', 'value' => 'other@example.org' ] ] ];
$before = count( $requests );
zb_assert( apply_filters( 'ninja_forms_submit_data', $untouched ) === $untouched, 'Unselected Ninja form changed' );
zb_assert( $before === count( $requests ), 'Unselected form made request' );
$gravity = \ZbEmailValidator\Integrations::validate_gravity( [ 'is_valid' => true ], 'gravity@example.org', [ 'id' => 103 ], (object) [ 'type' => 'email' ] );
zb_assert( false === $gravity['is_valid'], 'Gravity callback did not reject' );
$registration = apply_filters( 'registration_errors', new WP_Error(), 'test', 'register@example.org' );
zb_assert( $registration->has_errors(), 'Registration did not reject missing mailbox' );
$wc_errors = new WP_Error();
do_action( 'woocommerce_after_checkout_validation', [ 'billing_email' => 'checkout@example.org' ], $wc_errors );
zb_assert( $wc_errors->has_errors(), 'Classic checkout did not reject missing mailbox' );
$_POST['your-email'] = 'contact@example.org';
$tag = new WPCF7_FormTag( [ 'type' => 'email*', 'basetype' => 'email', 'name' => 'your-email' ] );
$cf_result = apply_filters( 'wpcf7_validate_email*', new WPCF7_Validation(), $tag );
zb_assert( ! $cf_result->is_valid(), 'Contact Form 7 did not reject missing mailbox' );
$mode = 'catchall';
$reg_catchall = apply_filters( 'registration_errors', new WP_Error(), 'test', 'catchall-register@example.org' );
zb_assert( ! $reg_catchall->has_errors(), 'Catch-all incorrectly rejected' );
$mode = 'transport';
$reg_outage = apply_filters( 'registration_errors', new WP_Error(), 'test', 'outage-register@example.org' );
zb_assert( ! $reg_outage->has_errors(), 'Transport error incorrectly blocked form' );
Settings::register_settings();
ob_start();
\ZbEmailValidator\Core::render_settings_page();
$settings_html = ob_get_clean();
zb_assert( false !== strpos( $settings_html, 'https://zbounce.net/pricing.html' ), 'Purchase link missing' );
zb_assert( false !== strpos( $settings_html, 'Check connection and balance' ), 'Connection button missing' );
zb_assert( false !== strpos( $settings_html, 'https://zbounce.net/privacy.html' ), 'Privacy missing' );
zb_assert( false !== strpos( $settings_html, 'https://zbounce.net/license.html' ), 'License link missing' );
zb_assert( false !== strpos( $settings_html, 'zb-use-cache' ), 'Settings tester/cache toggle missing' );
$exporters = apply_filters( 'wp_privacy_personal_data_exporters', [] );
$erasers = apply_filters( 'wp_privacy_personal_data_erasers', [] );
zb_assert( isset($exporters['zbounce-email-validator'], $erasers['zbounce-email-validator']), 'Privacy callbacks not registered' );
$before = count($requests);
$export = \ZbEmailValidator\Privacy::export('rejected@example.org');
zb_assert( count($export['data']) === 1 && $export['done'], 'Own cached result not exported' );
$erase = \ZbEmailValidator\Privacy::erase('rejected@example.org');
zb_assert( $erase['items_removed'] && !$erase['items_retained'], 'Own cached result not erased' );
zb_assert( false === ApiClient::get_cached_result(md5('rejected@example.org')), 'Erased cache remains readable' );
zb_assert( count($requests) === $before, 'Privacy tool made external request' );
$balance_before = count( $requests );
zb_assert( 1234 === ApiClient::get_balance()['remaining'], 'Balance missing from settings' );
ApiClient::get_balance();
zb_assert( $balance_before === count( $requests ), 'Balance transient not reused' );
$mode = 'balance_failure';
zb_assert( [] === ApiClient::get_balance( true ), 'Balance outage fabricated credits' );
$mode = 'balance_zero';
zb_assert( 0 === ApiClient::get_balance( true )['remaining'], 'Real zero balance was hidden' );
$mode = 'ok';
$options['license_accepted'] = false; update_option( Settings::OPTION_KEY, $options );
zb_assert( ! Settings::is_connected() && [] === ApiClient::get_balance(), 'Revoked acceptance still exposes balance/API' );
$options['license_accepted'] = true;
$options['service_enabled'] = false;
update_option( Settings::OPTION_KEY, $options );
$before = count( $requests );
Validator::run_sync_validation( 'disabled@example.org' );
zb_assert( count( $requests ) === $before, 'Disabling did not stop API calls' );
// Manual tools remain available while every automatic integration is paused.
$options['service_enabled'] = false; $options['license_accepted'] = true;
update_option( Settings::OPTION_KEY, $options ); wp_set_current_user( 1 );
zb_assert( Settings::is_api_configured() && ! Settings::is_verification_enabled(), 'API readiness still depends on auto toggle' );
$mode = 'rejected';
$manual = ApiClient::sync_verify( 'manual-denied@example.org', true );
zb_assert( false === $manual['exists'], 'Manual check blocked with automatic forms off' );
ApiClient::cache_result( md5('manual-denied@example.org'), $manual, true );
zb_assert( false === ApiClient::get_cached_result( md5('manual-denied@example.org'), true )['exists'], 'Manual cache inaccessible with auto off' );
$before = count( $requests );
zb_assert( null === ApiClient::sync_verify( 'manual-denied@example.org' ), 'Default automatic API call bypassed pause' );
zb_assert( false === ApiClient::get_cached_result( md5('manual-denied@example.org') ), 'Default automatic cache bypassed pause' );
zb_assert( '' === Validator::validation_error( 'manual-denied@example.org' ), 'Cached rejection blocks paused forms' );
zb_assert( ! apply_filters( 'registration_errors', new WP_Error(), 'test', 'manual-denied@example.org' )->has_errors(), 'Registration did not respect automatic pause' );
$paused_wc = new WP_Error(); do_action( 'woocommerce_after_checkout_validation', ['billing_email'=>'manual-denied@example.org'], $paused_wc );
zb_assert( ! $paused_wc->has_errors(), 'Classic checkout did not respect automatic pause' );
$paused_order = new WC_Order(); $paused_order->set_billing_email('manual-denied@example.org');
Validator::validate_store_api_email( $paused_order, new WP_REST_Request('POST','/wc/store/v1/checkout') );
$_POST['your-email'] = 'manual-denied@example.org';
zb_assert( apply_filters('wpcf7_validate_email*',new WPCF7_Validation(),$tag)->is_valid(), 'CF7 did not respect automatic pause' );
wpforms()->obj('process')->errors = [];
do_action('wpforms_process_validate_email',1,'manual-denied@example.org',['id'=>101,'fields'=>[1=>['id'=>1,'type'=>'email','required'=>true]]]);
zb_assert( empty(wpforms()->obj('process')->errors), 'WPForms did not respect automatic pause' );
$paused_ninja=['id'=>102,'fields'=>[['id'=>7,'type'=>'email','value'=>'manual-denied@example.org']]];
zb_assert( $paused_ninja === apply_filters('ninja_forms_submit_data',$paused_ninja), 'Ninja did not respect automatic pause' );
zb_assert( \ZbEmailValidator\Integrations::validate_gravity(['is_valid'=>true],'manual-denied@example.org',['id'=>103],(object)['type'=>'email'])['is_valid'], 'Gravity did not respect automatic pause' );
zb_assert( $before === count($requests), 'Paused integrations issued HTTP requests' );
wp_set_current_user( 0 );
zb_assert( null === ApiClient::sync_verify('guest@example.org',true), 'Guest manually used API' );
zb_assert( false === ApiClient::get_cached_result(md5('manual-denied@example.org'),true), 'Guest read manual cache' );
zb_assert( [] === ApiClient::get_balance() && '' === do_shortcode('[zb_email_validator]'), 'Guest saw admin balance/tester' );
$existing_subscriber=get_user_by('login','gate-subscriber');
$subscriber=$existing_subscriber ? $existing_subscriber->ID : wp_insert_user(['user_login'=>'gate-subscriber','user_pass'=>'isolated-test-only','user_email'=>'gate-subscriber@example.org','role'=>'subscriber']);
wp_set_current_user( $subscriber );
zb_assert( null === ApiClient::sync_verify('subscriber@example.org',true) && false === ApiClient::get_cached_result(md5('manual-denied@example.org'),true) && [] === ApiClient::get_balance(), 'Subscriber used administrator API tools' );
wp_set_current_user(1);
$options['license_accepted']=false;update_option(Settings::OPTION_KEY,$options);
zb_assert( ! Settings::is_api_configured() && null === ApiClient::sync_verify('revoked@example.org',true) && false === ApiClient::get_cached_result(md5('manual-denied@example.org'),true) && [] === ApiClient::get_balance(), 'Revoked consent left manual API/cache open' );
zb_assert( $before === count($requests), 'Role or revoked-consent gate issued HTTP' );
$options['license_accepted']=true;$options['api_key']='';update_option(Settings::OPTION_KEY,$options);
zb_assert( ! Settings::is_api_configured() && null === ApiClient::sync_verify('nokey@example.org',true), 'Missing key left manual API open' );
update_option( Settings::OPTION_KEY, Settings::DEFAULT_SETTINGS );
WP_CLI::success( $checks . ' assertions passed; all HTTP intercepted; no real API credits used.' );
