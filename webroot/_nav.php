<?php
// $active (opzionale) indica quale voce evidenziare, impostata dalla pagina
// che include questo file PRIMA dell'include, es: $active = 'mutui';
$active = $active ?? '';
function navlink($href, $key, $label, $active) {
    $isActive = $active === $key;
    $cls = $isActive ? 'text-accent' : 'text-gray-600 hover:text-accent';
    return "<a href=\"{$href}\" class=\"whitespace-nowrap {$cls}\">{$label}</a>";
}
?>
<header class="sticky top-0 z-50 border-b border-border bg-white/80 backdrop-blur-md">
  <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
    <a href="/" class="flex flex-shrink-0 items-center gap-2.5">
      <img src="/favicon.svg" alt="Reflexi" class="h-9 w-9 rounded-xl">
      <span class="text-lg font-bold tracking-tight">Reflexi</span>
    </a>
    <nav class="hidden items-center gap-3 text-sm font-medium xl:flex">
      <?= navlink('/mutui.php', 'mutui', 'Mutui', $active) ?>
      <?= navlink('/investimenti.php', 'investimenti', 'Investimenti', $active) ?>
      <?= navlink('/budget.php', 'budget', 'Budget', $active) ?>
      <?= navlink('/analisi.php', 'analisi', 'Analisi predittiva', $active) ?>
      <?= navlink('/calcolatori.php', 'calcolatori', 'Calcolatori', $active) ?>
      <?= navlink('/news', 'news', 'News', $active) ?>
      <?= navlink('/piani.php', 'piani', 'Piani', $active) ?>
      <?= navlink('/about.php', 'about', 'Chi siamo', $active) ?>
      <?= navlink('/faq.php', 'faq', 'FAQ', $active) ?>
    </nav>
    <div class="flex flex-shrink-0 items-center gap-3">
      <a href="/app/" class="whitespace-nowrap rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark hover:shadow-accent/40">
        Accedi
      </a>
      <button onclick="document.getElementById('mobile-nav-overlay').classList.remove('hidden')" class="text-gray-700 xl:hidden" aria-label="Apri il menu">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>
      </button>
    </div>
  </div>
</header>

<!-- Overlay mobile a schermo intero -->
<div id="mobile-nav-overlay" class="fixed inset-0 z-[60] hidden bg-bg xl:hidden">
  <div class="flex items-center justify-between border-b border-border bg-white px-6 py-4">
    <a href="/" class="flex items-center gap-2.5">
      <img src="/favicon.svg" alt="Reflexi" class="h-9 w-9 rounded-xl">
      <span class="text-lg font-bold tracking-tight">Reflexi</span>
    </a>
    <button onclick="document.getElementById('mobile-nav-overlay').classList.add('hidden')" class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-700" aria-label="Chiudi il menu">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <nav class="flex flex-col gap-2 px-6 py-6">
    <?php
    $items = [
        ['mutui.php', 'mutui', 'Mutui'],
        ['investimenti.php', 'investimenti', 'Investimenti'],
        ['budget.php', 'budget', 'Budget'],
        ['analisi.php', 'analisi', 'Analisi predittiva'],
        ['about.php', 'about', 'Chi siamo'],
        ['faq.php', 'faq', 'FAQ'],
        ['calcolatori.php', 'calcolatori', 'Calcolatori'],
        ['news', 'news', 'News'],
        ['piani.php', 'piani', 'Piani'],
    ];
    foreach ($items as [$href, $key, $label]) {
        $isActive = $active === $key;
        $cls = $isActive ? 'border-accent/30 bg-accentSoft text-accent' : 'border-border bg-white text-gray-700';
        echo "<a href=\"/{$href}\" class=\"rounded-xl border {$cls} px-4 py-3.5 text-base font-medium\">{$label}</a>";
    }
    ?>
    <a href="/app/" class="mt-3 rounded-xl bg-accent px-4 py-3.5 text-center text-base font-semibold text-white shadow-lg shadow-accent/25">Accedi</a>
  </nav>
</div>
