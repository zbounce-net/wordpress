# Upgrade, privacy and security

## Upgrade and rollback
Back up the WordPress database and current plugin directory using your site's backup procedure. Install the new plugin ZIP as an update; do not uninstall first. Deactivation preserves configuration and local cache. For rollback, deactivate, replace the plugin directory with the previous version and reactivate. Restore the database from backup if a complete prior site state is required. Validate forms and settings after either operation.

Deleting the plugin through WordPress runs uninstall and removes its settings and email cache table. Short-lived balance transients may remain until normal WordPress cleanup. Site backups may retain deleted data; manage those backups under your own retention policy.

## Data processing
After a key is saved and service agreement accepted, administrative balance requests can occur on opening settings or pressing the balance button. Manual checks send the entered address. Automatic form checks additionally require a separate opt-in switch. The API payload includes the email address and authentication key; it excludes other form fields, passwords, payment details and the visitor IP. The service also receives the WordPress server's network address and ordinary request metadata.

The key is stored in WordPress options. Local cache contains checked addresses and results in the WordPress database. Expired results are ignored on reads and cleaned during subsequent writes; TTL is not immediate physical deletion. WordPress personal-data export and erasure include the local cache, not remote-service records. WordPress/site backups can include settings and cache; protect access to them.

Service policies: https://zbounce.net/privacy.html, https://zbounce.net/terms.html and https://zbounce.net/license.html. Site owners must explain their own processing to visitors. Installing this plugin does not itself establish regulatory compliance.

## Security and reporting
Administrative API actions require `manage_options` and a valid nonce. Public forms use enabled integrations; the standalone tester is administrator-only. API keys stay on the server. Inputs are sanitized, rendered values escaped and browser results inserted as text. The plugin does not write its own diagnostic logs.

Use HTTPS, keep WordPress and integrations updated, restrict administrative and database access, and protect backups. Report suspected vulnerabilities privately to support@zbounce.net with reproduction details and versions. Remove credentials and personal data from reports. Never install the test stand's mock MU-plugin on a production site.
