// Stampa build/guida/guida.html in PDF A4 e salva l'anteprima della copertina
// (440 px di larghezza, mostrata a 220 px sulla pagina della guida). Gira nel
// container Playwright lanciato da generate.py: /guida = build/guida,
// /out = webroot/private (non pubblica), /og = webroot/og.
const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  await page.goto('file:///guida/guida.html', { waitUntil: 'load' });
  await page.evaluate(() => document.fonts.ready);
  await page.pdf({ path: '/out/guida-finanza-personale-7-passi.pdf', format: 'A4', printBackground: true, preferCSSPageSize: true });
  // A4 = 794 px a 96 dpi: 440 / 794 per un'immagine larga 440 px.
  const cover = await browser.newPage({ viewport: { width: 794, height: 1123 }, deviceScaleFactor: 440 / 794 });
  await cover.goto('file:///guida/guida.html', { waitUntil: 'load' });
  await cover.evaluate(() => document.fonts.ready);
  await cover.locator('.cover').screenshot({ path: '/og/guida-pdf-copertina.png' });
  await browser.close();
})();
