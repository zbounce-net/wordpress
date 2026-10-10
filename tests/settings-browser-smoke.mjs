// Isolated LAN stand only; restore saved settings afterwards through wp-cli.
import fs from 'node:fs';
import {strict as assert} from 'node:assert';
const {chromium}=await import(process.env.ZB_PLAYWRIGHT_MODULE || '@playwright/test');
const access=JSON.parse(fs.readFileSync(process.env.ZB_WP_ACCESS_FILE,'utf8'));
if(new URL(access.url).hostname !== '192.168.77.252')throw Error('Only the isolated pi LAN stand is allowed');
const browser=await chromium.launch({executablePath:process.env.ZB_CHROME_PATH,headless:true});
try{
 const page=await browser.newPage();await page.goto(access.url+'/wp-login.php');await page.locator('#user_login').fill(access.login);await page.locator('#user_pass').fill(access.password);await page.locator('#wp-submit').click();await page.waitForURL(/wp-admin/);
 await page.goto(access.url+'/wp-admin/options-general.php?page=zbounce-email-validator');
 assert.equal(await page.locator('a[href="https://zbounce.net/license.html"]').count(),1);
 const license=page.locator('input[name="zb_email_validator_settings[license_accepted]"]');
 await license.uncheck();await page.locator('input[name="zb_email_validator_settings[api_key]"]').fill('isolated-fixture-key-not-real');await page.locator('input[name="zb_email_validator_settings[service_enabled]"]').check();await page.getByRole('button',{name:'Save Settings',exact:true}).click();
 await page.getByText('Accept the ZBounce service license agreement before enabling verification.',{exact:true}).first().waitFor();
 assert.equal(await page.locator('.zb-email-validator').count(),1);assert(await page.locator('.zb-validate-btn').isDisabled());assert((await page.locator('.zb-key-balance').innerText()).includes('—'));
 await license.check();await page.locator('input[name="zb_email_validator_settings[service_enabled]"]').check();await page.getByRole('button',{name:'Save Settings',exact:true}).click();
 await page.locator('.zb-key-balance').filter({hasText:'Remaining credits: 1,234'}).waitFor();
 const widget=page.locator('.zb-email-validator');assert.equal(await widget.count(),1);
 const email='catchall-admin-'+Date.now()+'@example.org';
 async function check(useCache,fromCache){await widget.locator('.zb-use-cache').setChecked(useCache);await widget.locator('.zb-email-input').fill(email);const [response]=await Promise.all([page.waitForResponse(r=>r.url().includes('admin-ajax.php')&&r.request().postData()?.includes('zb_create_validation_task')),widget.locator('.zb-validate-btn').click()]);const body=await response.json();assert.equal(body.data.from_cache,fromCache);assert.equal(body.data.allowed,true);await page.waitForFunction(()=>document.querySelector('.zb-validate-btn').disabled===false);assert.equal(await widget.locator('.zb-result-source').innerText(),fromCache?'Cache':'ZBounce API');}
 await check(true,false);await check(true,true);await check(false,false);
 await page.getByRole('button',{name:'Check connection and balance',exact:true}).click();await page.getByText('Connected. Remaining verification credits: 1,234.',{exact:true}).waitFor();
 await page.screenshot({path:'/private/tmp/zbounce-wp-settings-1.2.1.png',fullPage:true});
 console.log('Browser: required license acceptance, persistent balance, settings tester, cached/fresh/cached-bypass checks, policy and result source passed');
}finally{await browser.close();}
