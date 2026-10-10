<?php
$options=\ZbEmailValidator\Settings::DEFAULT_SETTINGS;
$options['api_key']='isolated-fixture-key-not-real';$options['service_enabled']=true;$options['license_accepted']=true;
update_option(\ZbEmailValidator\Settings::OPTION_KEY,$options);
wp_set_current_user(1);
$form=WPCF7_ContactForm::get_template();$form->set_title('ZBounce isolated CF7');
$form->set_properties(['form'=>'<label>Email [email* your-email]</label> [submit "Submit form"]']);$form->save();
$page=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'ZBounce isolated widgets','post_content'=>'[zb_email_validator][zb_email_validator][contact-form-7 id="'.$form->id().'"]']);
update_option('zbounce_test_page',$page);update_option('zbounce_test_request_count',0);
global $wpdb;$wpdb->query("DELETE FROM {$wpdb->prefix}zb_email_cache");
WP_CLI::success('Browser fixture ready; API and mail intercepted');
