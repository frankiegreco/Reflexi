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
// Due tendine: "Funzionalità" (le funzioni dell'app, ognuna con la sua pagina
// pubblica) e "Strumenti" (gli strumenti gratuiti), separate di proposito;
// poi le pagine del sito.
$featureItems = [
    ['/mutui.php', 'mutui', 'Mutui', 'Ammortamento ed estinzione anticipata'],
    ['/investimenti.php', 'investimenti', 'Investimenti', 'Portafoglio con quotazioni reali'],
    ['/budget.php', 'budget', 'Budget', 'Limiti per categoria e avvisi'],
    ['/analisi.php', 'analisi', 'Analisi predittiva', 'Proiezioni e simulazione Monte Carlo'],
    ['/previsioni.php', 'previsioni', 'Previsioni', 'Stime a 12 mesi e proposte di investimento'],
    ['/simulazione-trading.php', 'trading', 'Simulazione trading', 'Fai pratica con capitale virtuale'],
    ['/fire-pensione.php', 'fire', 'FIRE e pensione', 'Indipendenza finanziaria e fondi pensione'],
    ['/obiettivi.php', 'obiettivi', 'Obiettivi', 'Traguardi di risparmio con un piano'],
    ['/abbonamenti.php', 'abbonamenti', 'Abbonamenti', 'Spese ricorrenti, anche quelle nascoste'],
    ['/patrimonio.php', 'patrimonio', 'Patrimonio e report', 'Patrimonio netto, immobili e report'],
];
$toolItems = [
    ['/mercati.php', 'mercati', 'Mercati', 'Andamenti di azioni, ETF e crypto'],
    ['/calcolatori.php', 'calcolatori', 'Tutti i calcolatori', 'La pagina con tutti gli strumenti di calcolo'],
];
// I singoli calcolatori, in forma compatta sotto i due strumenti principali.
// Tutte le pagine calcolatore usano $active = 'calcolatori'.
$calculatorItems = [
    ['/calcolatore-interesse-composto.php', 'Interesse composto'],
    ['/calcolatore-piano-investimento.php', 'Piano di investimento'],
    ['/calcolatore-obiettivo-risparmio.php', 'Obiettivo di risparmio'],
    ['/calcolatore-rendimento.php', 'Rendimento (ROI)'],
    ['/calcolatore-recupero-perdite.php', 'Recupero perdite'],
    ['/calcolatore-inflazione.php', 'Inflazione'],
    ['/calcolatore-budget-503020.php', 'Regola 50/30/20'],
    ['/calcolatore-patrimonio-netto.php', 'Patrimonio netto'],
    ['/calcolatore-pensione.php', 'Pensione'],
];
// Pagina corrente, per evidenziare la voce giusta dentro le tendine (la chiave
// $active non basta: è la stessa per tutti i calcolatori).
$currentPath = $_SERVER['SCRIPT_NAME'] ?? '';
$siteItems = [
    ['/news', 'news', 'News'],
    ['/piani.php', 'piani', 'Piani'],
    ['/about.php', 'about', 'Chi siamo'],
    ['/faq.php', 'faq', 'FAQ'],
];

/**
 * Tendina desktop: si apre al passaggio del mouse e con il focus (CSS) e al
 * click/tap (JS, classe .is-open: Safari non dà il focus ai pulsanti al click).
 * $cols = 2 per la tendina larga su due colonne; $compact = elenco opzionale
 * di voci senza descrizione [href, label], sotto un'intestazione $compactTitle.
 */
function navdropdown($id, $label, $items, $active, $currentPath, $cols = 1, $compact = [], $compactTitle = '') {
    $isActive = in_array($active, array_column($items, 1), true);
    $btnCls = $isActive ? 'text-accent' : 'text-gray-600 hover:text-accent';
    $gridCls = $cols === 2 ? 'grid grid-cols-2 gap-1' : '';
    $widthCls = ($cols === 2 || $compact) ? 'w-[36rem]' : 'w-80';
    $links = '';
    foreach ($items as [$href, $key, $itemLabel, $desc]) {
        $labelCls = $currentPath === $href ? 'text-accent' : 'text-gray-900';
        $links .= "<a href=\"{$href}\" class=\"block rounded-lg px-3 py-2.5 hover:bg-accentSoft\">"
            . "<span class=\"block font-semibold {$labelCls}\">{$itemLabel}</span>"
            . "<span class=\"block text-xs font-normal text-muted\">{$desc}</span></a>";
    }
    $links = "<div class=\"{$gridCls}\">{$links}</div>";
    if ($compact) {
        $links .= "<div class=\"mx-3 mt-2 border-t border-border pt-3 text-xs font-semibold uppercase tracking-wide text-muted\">{$compactTitle}</div>"
            . '<div class="grid grid-cols-2 gap-x-1 px-0 pb-1 pt-1">';
        foreach ($compact as [$href, $itemLabel]) {
            $cls = $currentPath === $href ? 'text-accent' : 'text-gray-700';
            $links .= "<a href=\"{$href}\" class=\"block rounded-lg px-3 py-1.5 text-sm {$cls} hover:bg-accentSoft\">{$itemLabel}</a>";
        }
        $links .= '</div>';
    }
    return <<<HTML
      <div class="nav-dropdown group relative" id="{$id}">
        <button type="button" onclick="toggleNavDropdown(event, '{$id}')" class="flex items-center gap-1 whitespace-nowrap {$btnCls}" aria-haspopup="true" aria-expanded="false">
          {$label} <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="transition group-hover:rotate-180 group-[.is-open]:rotate-180"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="invisible absolute left-1/2 top-full z-50 -translate-x-1/2 pt-3 opacity-0 transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 group-[.is-open]:visible group-[.is-open]:opacity-100">
          <div class="{$widthCls} rounded-xl border border-border bg-white p-2 shadow-lg">{$links}</div>
        </div>
      </div>
HTML;
}
?>
<header class="sticky top-0 z-50 border-b border-border bg-white/80 backdrop-blur-md">
  <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
    <a href="/" class="flex flex-shrink-0 items-center gap-2.5">
      <img src="/favicon.svg" alt="Reflexi" class="h-9 w-9 rounded-xl">
      <span class="text-lg font-bold tracking-tight">Reflexi</span>
    </a>
    <nav class="hidden items-center gap-5 text-sm font-medium lg:flex">
<?= navdropdown('nav-features', 'Funzionalità', $featureItems, $active, $currentPath, 2) ?>
<?= navdropdown('nav-tools', 'Strumenti', $toolItems, $active, $currentPath, 2, $calculatorItems, 'Calcolatori') ?>
      <?php foreach ($siteItems as [$href, $key, $label]) echo navlink($href, $key, $label, $active); ?>
    </nav>
    <div class="flex flex-shrink-0 items-center gap-3">
      <a href="/app/" class="whitespace-nowrap rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark hover:shadow-accent/40">
        Accedi
      </a>
      <button type="button" onclick="openMobileNav()" class="-mr-1 flex h-10 w-10 items-center justify-center text-gray-700 lg:hidden" aria-label="Apri il menu" aria-controls="mobile-nav-overlay" aria-expanded="false" id="mobile-nav-toggle">
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
<div id="mobile-nav-overlay" class="fixed inset-0 z-[60] hidden lg:hidden">
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
        function mobilenavlink($href, $key, $label, $active, $extraCls = 'px-4 py-3 text-base') {
            $isActive = ($key !== null && $active === $key) || ($_SERVER['SCRIPT_NAME'] ?? '') === $href;
            $cls = $isActive ? 'border-accent/30 bg-accentSoft text-accent' : 'border-border bg-white text-gray-700';
            return "<a href=\"{$href}\" class=\"rounded-xl border {$cls} {$extraCls} font-medium\">{$label}</a>";
        }
        $heading = fn($t) => "<div class=\"mt-3 px-1 text-xs font-semibold uppercase tracking-wide text-muted first:mt-0\">{$t}</div>";
        echo $heading('Funzionalità');
        echo '<div class="grid grid-cols-2 gap-2">';
        foreach ($featureItems as [$href, $key, $label]) echo mobilenavlink($href, $key, $label, $active, 'px-3 py-2.5 text-sm');
        echo '</div>';
        echo $heading('Strumenti');
        echo '<div class="grid grid-cols-2 gap-2">';
        foreach ($toolItems as [$href, $key, $label]) echo mobilenavlink($href, null, $label, '', 'px-3 py-2.5 text-sm');
        echo '</div>';
        echo $heading('Calcolatori');
        echo '<div class="grid grid-cols-2 gap-2">';
        foreach ($calculatorItems as [$href, $label]) echo mobilenavlink($href, null, $label, '', 'px-3 py-2 text-sm');
        echo '</div>';
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
// Tendine desktop (.nav-dropdown): si aprono anche al passaggio del mouse e
// con il focus (solo CSS), ma il tap va gestito qui perché Safari non dà il
// focus ai pulsanti al click. Alla chiusura va tolto anche il focus,
// altrimenti group-focus-within (che serve da tastiera) le terrebbe aperte.
function closeNavDropdowns(except) {
  document.querySelectorAll('.nav-dropdown.is-open').forEach(function (dd) {
    if (dd === except) return;
    dd.classList.remove('is-open');
    dd.querySelector('button').setAttribute('aria-expanded', 'false');
    if (dd.contains(document.activeElement)) document.activeElement.blur();
  });
}
function toggleNavDropdown(e, id) {
  e.stopPropagation();
  var dd = document.getElementById(id);
  if (dd.classList.contains('is-open')) { closeNavDropdowns(); return; }
  closeNavDropdowns(dd);
  dd.classList.add('is-open');
  dd.querySelector('button').setAttribute('aria-expanded', 'true');
}
document.addEventListener('click', function (e) {
  if (!e.target.closest('.nav-dropdown')) closeNavDropdowns();
});
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') { closeMobileNav(); closeNavDropdowns(); }
});
// Se si passa a desktop (>= lg) con l'overlay aperto, resterebbe nascosto da
// lg:hidden ma con lo scroll della pagina ancora bloccato.
window.addEventListener('resize', function () {
  if (window.innerWidth >= 1024) closeMobileNav();
});
</script>
