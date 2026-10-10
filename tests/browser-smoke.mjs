// Use only the loopback SSH-forwarded isolated stand; no real key or API calls.
import { strict as assert } from 'node:assert';
const { chromium } = await import(process.env.ZB_PLAYWRIGHT_MODULE || '@playwright/test');
const base=process.env.ZB_WP_TEST_URL || 'http://127.0.0.1:18990';
if(!['127.0.0.1','localhost'].includes(new URL(base).hostname))throw Error('Only an isolated loopback stand is allowed');
const browser=await chromium.launch({executablePath:process.env.ZB_CHROME_PATH,headless:true});
let stage='login', page;
try {
 const context=await browser.newContext();page=await context.newPage();
 await page.goto(base+'/wp-login.php');await page.locator('#user_login').fill('testadmin');await page.locator('#user_pass').fill('isolated-test-password');
 await page.locator('#wp-submit').click();await page.waitForURL(/wp-admin/);
 stage='admin settings';await page.goto(base+'/wp-admin/options-general.php?page=zbounce-email-validator');
 await page.getByRole('button',{name:'Check connection and balance',exact:true}).click();await page.getByText('Connected. Remaining verification credits: 1,234.',{exact:true}).waitFor();
 stage='status policy settings';
 const risky=page.locator('input[type="checkbox"][value="risky"]');
 await risky.uncheck();await page.getByRole('button',{name:'Save Settings',exact:true}).click();
 assert.equal(await page.locator('input[type="checkbox"][value="risky"]').isChecked(),false);
 await page.locator('input[type="checkbox"][value="risky"]').check();await page.getByRole('button',{name:'Save Settings',exact:true}).click();
 assert.equal(await page.locator('input[type="checkbox"][value="risky"]').isChecked(),true);
 console.log('Browser: risky status checkbox saved and restored through native settings form');
 stage='two independent widgets';await page.goto(base+'/?page_id='+process.env.ZB_WP_TEST_PAGE);
 const widgets=page.locator('.zb-email-validator');assert.equal(await widgets.count(),2);
 const first=widgets.nth(0),second=widgets.nth(1);
 async function check(widget,email,mailbox) {await widget.locator('.zb-email-input').fill(email);await widget.locator('.zb-validate-btn').click();await widget.locator('.zb-result-email').filter({hasText:email}).waitFor();assert.equal(await widget.locator('.zb-exists-status').innerText(),mailbox);}
 await check(first,'good-browser@example.org','Yes');await check(first,'rejected-browser@example.org','No');
 assert.equal(await first.locator('.zb-validity-badge').count(),1);
 await check(second,'catchall-browser@example.org','Not checked');assert.equal(await second.locator('.zb-acceptall-status').innerText(),'Yes (unreliable)');
 assert.equal(await first.locator('.zb-result-email').innerText(),'rejected-browser@example.org');
 await first.locator('.zb-email-input').fill('failure-browser@example.org');await first.locator('.zb-validate-btn').click();
 await first.locator('.zb-status-value').filter({hasText:'ZBounce could not complete this check.'}).waitFor();assert(await first.locator('.zb-validate-btn').isEnabled());
 stage='response text escaping and duplicate submission';let requests=0;
 await page.route('**/wp-admin/admin-ajax.php',async route=>{requests++;await new Promise(resolve=>setTimeout(resolve,200));await route.fulfill({contentType:'application/json',body:JSON.stringify({success:true,data:{result:{email:'<img src=x onerror="window.zbInjected=true">',valid:true,exists:true,disposable:false,accept_all:false}}})});});
 await first.locator('.zb-email-input').fill('escaped@example.org');
 await first.locator('.zb-validate-btn').evaluate(button=>{button.dispatchEvent(new MouseEvent('click',{bubbles:true}));button.dispatchEvent(new MouseEvent('click',{bubbles:true}));});
 await first.locator('.zb-result-email').filter({hasText:'<img'}).waitFor();assert.equal(requests,1);assert.equal(await first.locator('.zb-result-email img').count(),0);assert.equal(await page.evaluate(()=>window.zbInjected),undefined);
 await page.unroute('**/wp-admin/admin-ajax.php');
 console.log('Browser: balance, repeated results, independent widgets, undecided catch-all, outages, duplicate-click protection and response text escaping passed');
 stage='anonymous visibility and real CF7 submission';const anonymous=await browser.newContext();const publicPage=await anonymous.newPage();
 await publicPage.goto(base+'/?page_id='+process.env.ZB_WP_TEST_PAGE);assert.equal(await publicPage.locator('.zb-email-validator').count(),0);
 async function submit(email,status){await publicPage.reload();await publicPage.locator('input[name="your-email"]').fill(email);await publicPage.locator('input[name="your-email"]').press('Tab');const response=publicPage.waitForResponse(res=>decodeURIComponent(res.url()).includes('contact-form-7/')&&res.request().method()==='POST');await publicPage.locator('input[type="submit"]').click();assert.equal((await (await response).json()).status,status);await publicPage.waitForFunction(()=>document.querySelector('.wpcf7-form')?.dataset.status !== 'submitting');}
 await submit('rejected-public@example.org','validation_failed');await submit('catchall-public@example.org','mail_sent');await submit('failure-public@example.org','mail_sent');
 console.log('Browser: public tester hidden; real CF7 submits reject missing mailbox, allow catch-all and continue on service outage; mail intercepted');
 await anonymous.close();await context.close();
} catch(error){console.error('Browser failed at '+stage+': '+error.message);if(page){console.log('Buttons:',await page.getByRole('button').allTextContents());console.log('Body:',(await page.locator('body').innerText()).slice(0,1400));await page.screenshot({path:'/private/tmp/zbounce-wp-browser-failure.png'});}process.exitCode=1;}finally{await browser.close();}
