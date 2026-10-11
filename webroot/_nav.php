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
// ATTENZIONE: lo stesso menu è replicato in React per la pagina di login
// dell'app (frontend/src/components/SiteNav.jsx): se cambi le voci qui,
// aggiornale anche lì e ricompila il frontend.
// $appItems sono le funzioni dell'app; $toolItems gli strumenti gratuiti
// (dentro la tendina "Strumenti"), separati di proposito dalle funzioni.
$appItems = [
    ['/mutui.php', 'mutui', 'Mutui'],
    ['/investimenti.php', 'investimenti', 'Investimenti'],
    ['/budget.php', 'budget', 'Budget'],
    ['/analisi.php', 'analisi', 'Analisi predittiva'],
];
$toolItems = [
    ['/mercati.php', 'mercati', 'Mercati', 'Andamenti di azioni, ETF e crypto'],
    ['/calcolatori.php', 'calcolatori', 'Calcolatori', 'Interesse composto, pensione, inflazione e altri'],
];
$siteItems = [
    ['/news', 'news', 'News'],
    ['/piani.php', 'piani', 'Piani'],
    ['/about.php', 'about', 'Chi siamo'],
    ['/faq.php', 'faq', 'FAQ'],
];
$toolsActive = in_array($active, array_column($toolItems, 1), true);
$chevron = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="transition group-hover:rotate-180 group-[.is-open]:rotate-180"><polyline points="6 9 12 15 18 9"/></svg>';
?>
<header class="sticky top-0 z-50 border-b border-border bg-white/80 backdrop-blur-md">
  <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
    <a href="/" class="flex flex-shrink-0 items-center gap-2.5">
      <img src="/favicon.svg" alt="Reflexi" class="h-9 w-9 rounded-xl">
      <span class="text-lg font-bold tracking-tight">Reflexi</span>
    </a>
    <nav class="hidden items-center gap-3 text-sm font-medium xl:flex">
      <?php foreach ($appItems as [$href, $key, $label]) echo navlink($href, $key, $label, $active); ?>
      <div class="group relative" id="tools-menu">
        <button type="button" onclick="toggleToolsMenu(event)" class="flex items-center gap-1 whitespace-nowrap <?= $toolsActive ? 'text-accent' : 'text-gray-600 hover:text-accent' ?>" aria-haspopup="true" aria-expanded="false" id="tools-menu-toggle">
          Strumenti <?= $chevron ?>
        </button>
        <div class="invisible absolute left-1/2 top-full z-50 -translate-x-1/2 pt-3 opacity-0 transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 group-[.is-open]:visible group-[.is-open]:opacity-100">
          <div class="w-80 rounded-xl border border-border bg-white p-2 shadow-lg">
            <?php foreach ($toolItems as [$href, $key, $label, $desc]): ?>
              <a href="<?= $href ?>" class="block rounded-lg px-3 py-2.5 hover:bg-accentSoft">
                <span class="block font-semibold <?= $active === $key ? 'text-accent' : 'text-gray-900' ?>"><?= $label ?></span>
                <span class="block text-xs font-normal text-muted"><?= $desc ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php foreach ($siteItems as [$href, $key, $label]) echo navlink($href, $key, $label, $active); ?>
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
        function mobilenavlink($href, $key, $label, $active, $desc = null) {
            $isActive = $active === $key;
            $cls = $isActive ? 'border-accent/30 bg-accentSoft text-accent' : 'border-border bg-white text-gray-700';
            $sub = $desc ? "<span class=\"block text-xs font-normal text-muted\">{$desc}</span>" : '';
            return "<a href=\"{$href}\" class=\"rounded-xl border {$cls} px-4 py-3 text-base font-medium\">{$label}{$sub}</a>";
        }
        foreach ($appItems as [$href, $key, $label]) echo mobilenavlink($href, $key, $label, $active);
        echo '<div class="mt-3 px-1 text-xs font-semibold uppercase tracking-wide text-muted">Strumenti</div>';
        foreach ($toolItems as [$href, $key, $label, $desc]) echo mobilenavlink($href, $key, $label, $active, $desc);
        echo '<div class="mt-3"></div>';
        foreach ($siteItems as [$href, $key, $label]) echo mobilenavlink($href, $key, $label, $active);
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
// Tendina "Strumenti" su desktop: si apre anche al passaggio del mouse e con
// il focus (solo CSS), ma il tap va gestito qui perché Safari non dà il focus
// ai pulsanti al click, quindi focus-within da solo non basterebbe.
// Alla chiusura va tolto anche il focus dal pulsante: altrimenti la regola
// group-focus-within (che serve per chi naviga da tastiera) la terrebbe aperta.
function toggleToolsMenu(e) {
  e.stopPropagation();
  var menu = document.getElementById('tools-menu');
  if (menu.classList.contains('is-open')) closeToolsMenu();
  else {
    menu.classList.add('is-open');
    document.getElementById('tools-menu-toggle').setAttribute('aria-expanded', 'true');
  }
}
function closeToolsMenu() {
  var menu = document.getElementById('tools-menu');
  if (!menu) return;
  menu.classList.remove('is-open');
  document.getElementById('tools-menu-toggle').setAttribute('aria-expanded', 'false');
  if (menu.contains(document.activeElement)) document.activeElement.blur();
}
document.addEventListener('click', function (e) {
  var menu = document.getElementById('tools-menu');
  if (menu && !menu.contains(e.target)) closeToolsMenu();
});
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') { closeMobileNav(); closeToolsMenu(); }
});
// Se si passa a desktop (>= xl) con l'overlay aperto, resterebbe nascosto da
// xl:hidden ma con lo scroll della pagina ancora bloccato.
window.addEventListener('resize', function () {
  if (window.innerWidth >= 1280) closeMobileNav();
});
</script>
