// Renderizza le immagini di anteprima social (og:image, 1200x630 JPEG) a
// partire da items.json, prodotto da generate.py. Gira nel container
// Playwright lanciato da generate.py: /og = build/og, /webroot = webroot.
const fs = require('fs');
const { chromium } = require('playwright');

const items = JSON.parse(fs.readFileSync('/og/items.json', 'utf8'));
const logo = fs.readFileSync('/webroot/favicon.svg', 'utf8');
const font = (file) => `data:font/woff2;base64,${fs.readFileSync('/webroot/fonts/' + file).toString('base64')}`;

const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
// Titoli lunghi più piccoli, per stare sempre in tre righe al massimo.
const titleSize = (t) => (t.length <= 34 ? 74 : t.length <= 52 ? 62 : 52);

const page = (it) => `<!doctype html><html><head><meta charset="utf-8"><style>
@font-face { font-family: Inter; font-weight: 400 800; src: url(${font('inter-v20-latin.woff2')}) format('woff2'); }
@font-face { font-family: Inter; font-weight: 400 800; src: url(${font('inter-v20-latin-ext.woff2')}) format('woff2'); unicode-range: U+0100-02BA, U+1E00-1E9F; }
* { margin: 0; box-sizing: border-box; }
body { width: 1200px; height: 630px; overflow: hidden; font-family: Inter, sans-serif; color: #fff;
  background: linear-gradient(135deg, #4f46e5 0%, #4338ca 55%, #1e1b4b 100%); position: relative; }
.c1 { position: absolute; right: -120px; top: -160px; width: 460px; height: 460px; border-radius: 50%; background: rgba(255,255,255,.07); }
.c2 { position: absolute; left: -140px; bottom: -200px; width: 380px; height: 380px; border-radius: 50%; background: rgba(255,255,255,.06); }
.wrap { position: absolute; inset: 72px 80px 64px 80px; display: flex; flex-direction: column; }
.brand { display: flex; align-items: center; gap: 16px; font-size: 34px; font-weight: 800; letter-spacing: -0.5px; }
.brand svg { width: 58px; height: 58px; border-radius: 14px; }
.kicker { margin-top: 48px; align-self: flex-start; padding: 8px 20px; border-radius: 999px; background: rgba(255,255,255,.16);
  font-size: 22px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; }
.title { margin-top: 26px; max-width: 820px; font-weight: 800; line-height: 1.12; letter-spacing: -1.5px; }
.foot { margin-top: auto; font-size: 24px; color: rgba(255,255,255,.75); font-weight: 500; }
.emoji { position: absolute; right: 90px; top: 50%; transform: translateY(-50%); width: 220px; height: 220px; border-radius: 50%;
  background: rgba(255,255,255,.12); display: flex; align-items: center; justify-content: center; font-size: 120px; }
</style></head><body>
<div class="c1"></div><div class="c2"></div>
<div class="wrap">
  <div class="brand">${logo}<span>reflexi</span></div>
  <div class="kicker">${esc(it.kicker)}</div>
  <div class="title" style="font-size:${titleSize(it.title)}px">${esc(it.title)}</div>
  <div class="foot">reflexi.it · ${esc(it.foot)}</div>
</div>
<div class="emoji">${it.emoji}</div>
</body></html>`;

(async () => {
  const browser = await chromium.launch();
  const p = await browser.newPage({ viewport: { width: 1200, height: 630 } });
  for (const it of items) {
    await p.setContent(page(it), { waitUntil: 'load' });
    await p.evaluate(() => document.fonts.ready);
    fs.mkdirSync(require('path').dirname('/webroot/og/' + it.out), { recursive: true });
    await p.screenshot({ path: '/webroot/og/' + it.out, type: 'jpeg', quality: 86 });
  }
  await browser.close();
  console.log(`generate ${items.length} immagini`);
})();
