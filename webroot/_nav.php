<?php
// $active (opzionale) indica quale voce evidenziare, impostata dalla pagina
// che include questo file PRIMA dell'include, es: $active = 'mutui';
$active = $active ?? '';
function navlink($href, $key, $label, $active) {
    $isActive = $active === $key;
    $cls = $isActive ? 'text-accent' : 'text-gray-600 hover:text-accent';
    return "<a href=\"{$href}\" class=\"whitespace-nowrap {$cls}\">{$label}</a>";
}
// Voci condivise fra nav desktop e overlay mobile: un solo elenco, così le due
// versioni non possono divergere (prima erano duplicate e in ordine diverso).
$navItems = [
    ['/mutui.php', 'mutui', 'Mutui'],
    ['/investimenti.php', 'investimenti', 'Investimenti'],
    ['/mercati.php', 'mercati', 'Mercati'],
    ['/budget.php', 'budget', 'Budget'],
    ['/analisi.php', 'analisi', 'Analisi predittiva'],
    ['/calcolatori.php', 'calcolatori', 'Calcolatori'],
    ['/news', 'news', 'News'],
    ['/piani.php', 'piani', 'Piani'],
    ['/about.php', 'about', 'Chi siamo'],
    ['/faq.php', 'faq', 'FAQ'],
];
?>
<header class="sticky top-0 z-50 border-b border-border bg-white/80 backdrop-blur-md">
  <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
    <a href="/" class="flex flex-shrink-0 items-center gap-2.5">
      <img src="/favicon.svg" alt="Reflexi" class="h-9 w-9 rounded-xl">
      <span class="text-lg font-bold tracking-tight">Reflexi</span>
    </a>
    <nav class="hidden items-center gap-3 text-sm font-medium xl:flex">
      <?php foreach ($navItems as [$href, $key, $label]) echo navlink($href, $key, $label, $active); ?>
    </nav>
    <div class="flex flex-shrink-0 items-center gap-3">
      <a href="/app/" class="whitespace-nowrap rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark hover:shadow-accent/40">
        Accedi
      </a>
      <button type="button" onclick="openMobileNav()" class="-mr-1 flex h-10 w-10 items-center justify-center text-gray-700 xl:hidden" aria-label="Apri il menu" aria-controls="mobile-nav-overlay" aria-expanded="false" id="mobile-nav-toggle">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>
      </button>
    </div>
  </div>
</header>

<!-- Overlay mobile a schermo intero.
     Il wrapper interno è il flex container (non il div con .hidden: `hidden` e
     `flex` sono entrambe utility di display e si pesterebbero a vicenda quando
     il JS toglie/rimette .hidden). Elenco voci scrollabile + CTA sempre
     visibile in basso: con 10 voci il contenuto supera l'altezza di un telefono
     corto, e senza area scrollabile le ultime voci erano irraggiungibili. -->
<div id="mobile-nav-overlay" class="fixed inset-0 z-[60] hidden xl:hidden">
  <div class="flex h-full max-h-full flex-col bg-bg">
    <div class="flex flex-shrink-0 items-center justify-between border-b border-border bg-white px-6 py-4">
      <a href="/" class="flex items-center gap-2.5">
        <img src="/favicon.svg" alt="Reflexi" class="h-9 w-9 rounded-xl">
        <span class="text-lg font-bold tracking-tight">Reflexi</span>
      </a>
      <button type="button" onclick="closeMobileNav()" class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-700" aria-label="Chiudi il menu">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <nav class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-6 py-5">
      <div class="flex flex-col gap-2">
        <?php
        foreach ($navItems as [$href, $key, $label]) {
            $isActive = $active === $key;
            $cls = $isActive ? 'border-accent/30 bg-accentSoft text-accent' : 'border-border bg-white text-gray-700';
            echo "<a href=\"{$href}\" class=\"rounded-xl border {$cls} px-4 py-3 text-base font-medium\">{$label}</a>";
        }
        ?>
      </div>
    </nav>
    <div class="flex-shrink-0 border-t border-border bg-white px-6 py-4 pb-[calc(1rem_+_env(safe-area-inset-bottom))]">
      <a href="/app/" class="block rounded-xl bg-accent px-4 py-3.5 text-center text-base font-semibold text-white shadow-lg shadow-accent/25">Accedi</a>
    </div>
  </div>
</div>
<script>
function openMobileNav() {
  document.getElementById('mobile-nav-overlay').classList.remove('hidden');
  document.getElementById('mobile-nav-toggle').setAttribute('aria-expanded', 'true');
  // Blocca lo scroll della pagina sotto: senza questo lo sfondo continua a
  // scorrere mentre l'overlay è aperto.
  document.documentElement.style.overflow = 'hidden';
}
function closeMobileNav() {
  document.getElementById('mobile-nav-overlay').classList.add('hidden');
  document.getElementById('mobile-nav-toggle').setAttribute('aria-expanded', 'false');
  document.documentElement.style.overflow = '';
}
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeMobileNav();
});
// Se si passa a desktop (>= xl) con l'overlay aperto, resterebbe nascosto da
// xl:hidden ma con lo scroll della pagina ancora bloccato.
window.addEventListener('resize', function () {
  if (window.innerWidth >= 1280) closeMobileNav();
});
</script>
