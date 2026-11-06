/* Read-only Joomla public storefront smoke; requires real local Joomla and Playwright.
 * Data: demo SQL 0.5.59+ and 20 sample PNGs with demo-promo.mp4.
 */
import assert from 'node:assert/strict';
import { chromium } from '@playwright/test';
const root = process.env.MM_E2E_URL || 'http://minimarket.local/';
const url = new URL('index.php?option=com_minimarket&view=product&id=1012',
  root.endsWith('/') ? root : root + '/');
const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
const failures = [], warnings = [], checks = [];
page.on('pageerror', error => failures.push('JS runtime: '+error.message));
page.on('console', message => {
  if (message.type() === 'error') warnings.push(message.text());
});
async function check(label, callback) {
  try { await callback(); checks.push(label); console.log('PASS',label); }
  catch(error) { const message=label+': '+String(error?.message||error);
    failures.push(message); console.error('FAIL',message); }
}
try {
  await check('Open published demo product', async()=>{
    const res=await page.goto(url.href,{waitUntil:'networkidle',timeout:20000});
    assert.ok(res && res.ok(),'Product HTTP '+res?.status());
    await page.locator('[data-mm-variants]').waitFor({timeout:10000});
  });
  if(await page.locator('[data-mm-variants]').count()){
    const select=page.locator('select[data-mm-variant-axis]');
    await check('Two product-local order options',async()=>{
      assert.equal(await select.count(),2);
      assert.equal(await select.nth(0).getAttribute('data-mm-variant-axis'),'set');
      assert.equal(await select.nth(1).getAttribute('data-mm-variant-axis'),'color');
    });
    await check('Changing component size selects a real SKU without URL change',async()=>{
      await select.nth(0).selectOption('v:set120');
      assert.match(await page.locator('[data-mm-variant-sku]').innerText(),/SKU-1012-SET120-BLUE/);
      assert.equal(page.url().split('#')[0],url.href);
    });
    await check('Inactive red SKU is not offered as an orderable option',async()=>{
      const red=select.nth(1).locator('option[value="v:red"]');
      assert.equal(await red.count(),1);
      assert.equal(await red.isDisabled(),true);
    });
    await check('Main SKU gallery loads real image and thumbnails',async()=>{
      const main=page.locator('[data-mm-gallery-main]');
      await main.waitFor();
      const src=await main.getAttribute('src');
      assert.ok(src && src.includes('/images/minimarket/'),'Main image source: '+src);
      const image=await page.request.get(new URL(src,page.url()).href);
      assert.ok(image.ok(),'Image HTTP '+image.status());
      assert.ok(await page.locator('[data-mm-gallery-thumb]').count()>=2);
    });
  }
  const promo=page.locator('[data-mm-promo]');
  await check('Floating promo expands when video surface is clicked',async()=>{
    await promo.waitFor({timeout:10000});
    const video=promo.locator('[data-mm-promo-player]');
    await video.click();
    assert.equal(await promo.locator('[data-mm-promo-panel]').isVisible(),true);
    assert.equal(await video.getAttribute('aria-expanded'),'true');
  });
  await check('No fake checkout: promo buy action is disabled',async()=>{
    const buy=promo.locator('[data-mm-promo-panel] button');
    assert.equal(await buy.count(),1);
    assert.equal(await buy.isDisabled(),true);
  });
  await check('Demo promo MP4 exists on HTTP server',async()=>{
    const source=await promo.locator('video source').getAttribute('src');
    assert.ok(source && source.includes('demo-promo.mp4'),'Source: '+source);
    const response=await page.request.get(new URL(source,page.url()).href);
    assert.ok(response.ok(),'MP4 HTTP '+response.status());
    assert.ok((await response.body()).length>1000,'Empty MP4 file');
  });
  await check('Promo closes on X',async()=>{
    await promo.locator('[data-mm-promo-close]').click();
    assert.equal(await promo.isVisible(),false);
  });
  if(failures.length)throw new Error(failures.join('\n'));
  console.log('MM_JOOMLA_PUBLIC_E2E_OK:',checks.length,'checks');
  if(warnings.length)console.log('Browser console errors:',warnings.join(' | ').slice(0,1200));
} finally { await browser.close(); }
