import { strict as assert } from 'node:assert';
const {chromium}=await import(process.env.ZB_PLAYWRIGHT_MODULE || '@playwright/test');
const browser=await chromium.launch({executablePath:process.env.ZB_CHROME_PATH,headless:true});
const base='http://127.0.0.1:18990';
const context=await browser.newContext();const page=await context.newPage();
try{
 await page.goto(base+'/?add-to-cart=17');await page.goto(base+'/?page_id=8');
 await page.locator('#email').fill('rejected-block@example.org');

 await page.locator('#billing-first_name').fill('Isolated');await page.locator('#billing-last_name').fill('Test');
 await page.locator('#billing-address_1').fill('123 Test Street');await page.locator('#billing-city').fill('San Francisco');await page.locator('#billing-postcode').fill('94103');
 if(await page.locator('#radio-control-wc-payment-method-options-cod').count())await page.locator('#radio-control-wc-payment-method-options-cod').check();
 await page.locator('#billing-postcode').press('Tab');
 await page.waitForTimeout(1500);

 const [response]=await Promise.all([page.waitForResponse(r=>decodeURIComponent(r.url()).includes('/checkout')&&r.request().method()==='POST'),page.getByRole('button',{name:/Place order/i}).click()]);
 const result=await response.json();assert.equal(result.code,'zbounce_email_rejected');
 await page.getByText('Email address does not exist',{exact:false}).first().waitFor();
 console.log('Checkout Block: rejected email displays ZBounce error in real browser');
}catch(e){console.error(e);console.log((await page.locator('body').innerText()).slice(0,1800));await page.screenshot({path:'/private/tmp/zb-wp-block.png'});process.exitCode=1;}
finally{await browser.close();}
