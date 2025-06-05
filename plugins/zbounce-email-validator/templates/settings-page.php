<div class="wrap zb-email-validator-settings">
    <h1>ZBounce Email Validator Settings</h1>

    <form method="post" action="options.php">
        <?php
        settings_fields('zb_email_validator_settings');
        do_settings_sections('zb-email-validator');
        submit_button('Save Settings');
        ?>
    </form>

    <div class="zb-settings-info">
        <h3>Usage Instructions</h3>
        <p>Add the email validator to any page using the shortcode: <code>[zb_email_validator]</code></p>

        <h4>For Form Integration</h4>
        <p>The plugin automatically integrates with:</p>
        <ul>
            <li>Contact Form 7</li>
            <li>WooCommerce checkout</li>
            <li>WordPress registration</li>
        </ul>

        <h4>API Information</h4>
        <p>Without an API key, the plugin uses ZBounce's demo endpoint with limited capabilities.</p>
        <p>For production use, get your API key at <a href="https://zbounce.net" target="_blank">zbounce.net</a></p>
    </div>
</div>