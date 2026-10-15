<?php
// Guida pilastro per la keyword "gestione finanza personale": raccoglie i
// passi di base e rimanda ad articoli News, calcolatori e funzionalità.
// Le domande in fondo generano anche lo schema FAQPage (stesso testo).
require_once __DIR__ . '/_seo.php';
require_once __DIR__ . '/_news_db.php';

$pageTitle = 'Gestione della finanza personale: guida in 7 passi';
$pageDescription = 'Come gestire la finanza personale passo per passo: entrate e spese, budget, fondo di emergenza, debiti, investimenti e obiettivi, con esempi e tabelle.';

$relatedArticles = news_related(['budget-familiare-mensile-come-farlo', 'tasso-di-risparmio-cosa-e-come-calcolarlo', 'gestire-i-soldi-a-20-30-40-50-anni']);

// Classi delle tabelle, le stesse degli articoli News.
const GFP_TH = 'px-3 py-2 font-semibold sm:px-4 sm:py-2.5';
const GFP_TD = 'px-3 py-2 sm:px-4 sm:py-2.5';
const GFP_NUM = 'px-3 py-2 sm:px-4 sm:py-2.5 whitespace-nowrap text-right font-mono';

/** Tabella in stile articolo. $numCols = indici delle colonne numeriche (allineate a destra). */
function gfp_table(string $caption, array $head, array $rows, array $numCols = [], ?string $note = null, bool $totalRow = false): void
{
    echo '<figure class="my-6"><figcaption class="mb-2 text-sm font-semibold text-gray-900">' . $caption . '</figcaption>'
        . '<div class="overflow-x-auto rounded-xl border border-border bg-white"><table class="w-full text-left text-[13px] text-gray-700 sm:text-sm">'
        . '<thead class="bg-gray-50 text-[11px] uppercase tracking-wide text-muted"><tr>';
    foreach ($head as $i => $h) {
        echo '<th class="' . GFP_TH . (in_array($i, $numCols, true) ? ' text-right' : '') . '">' . $h . '</th>';
    }
    echo '</tr></thead><tbody>';
    $last = count($rows) - 1;
    foreach ($rows as $r => $row) {
        $bold = $totalRow && $r === $last ? ' font-semibold text-gray-900' : '';
        echo '<tr class="border-t border-border">';
        foreach ($row as $i => $cell) {
            echo '<td class="' . (in_array($i, $numCols, true) ? GFP_NUM : GFP_TD) . $bold . '">' . $cell . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
    if ($note) echo '<p class="mt-2 text-xs text-muted">' . $note . '</p>';
    echo '</figure>';
}

$faq = [
    ['Cosa significa gestione della finanza personale?',
     'È l\'insieme delle decisioni con cui amministri i tuoi soldi: sapere quanto entra e quanto esce, decidere un budget, mettere da parte un fondo di emergenza, tenere sotto controllo i debiti, investire il risparmio e darti degli obiettivi. Non serve essere esperti: serve un metodo e un controllo regolare.'],
    ['Da dove si comincia a gestire le proprie finanze?',
     'Dai numeri reali: ricostruisci entrate e spese degli ultimi tre mesi, raggruppate per categoria. Solo dopo ha senso fissare un budget e una quota di risparmio, perché parti da quanto spendi davvero e non da una stima.'],
    ['Meglio un foglio Excel o un\'app di finanza personale?',
     'Un foglio di calcolo va bene per iniziare ed è gratuito, ma va aggiornato a mano e non collega le diverse parti: rata del mutuo, budget e investimenti restano separati. Un\'app di finanza personale calcola saldi, budget e patrimonio da sola e ti avvisa prima di sforare. La scelta giusta è quella che riesci a usare con costanza.'],
    ['Quanto tempo richiede la gestione della finanza personale?',
     'Dopo la prima impostazione bastano pochi minuti a settimana per registrare o importare le spese, più una revisione di mezz\'ora a fine mese per confrontare budget e speso. Una volta l\'anno conviene rivedere obiettivi, investimenti e coperture assicurative.'],
    ['Quanto dovrei risparmiare ogni mese?',
     'Un riferimento diffuso è il 20% del reddito netto, come nella regola 50/30/20, ma la cifra giusta dipende da reddito, spese fisse e obiettivi. Più importante della percentuale è la costanza: un risparmio automatico a inizio mese, anche piccolo, funziona meglio di quello che avanza a fine mese.'],
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?> — Reflexi</title>
<meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/gestione-finanza-personale">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="article">
<meta property="og:url" content="https://reflexi.it/gestione-finanza-personale">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="<?= $pageTitle ?>">
<meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
<meta property="og:image" content="<?= seo_og_image() ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $pageTitle ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($pageDescription) ?>">
<meta name="twitter:image" content="<?= seo_og_image() ?>">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<style>
  details summary::-webkit-details-marker { display: none; }
  details[open] .faq-chevron { transform: rotate(180deg); }
</style>
<?php
seo_breadcrumb([['Gestione della finanza personale', '/gestione-finanza-personale']]);
seo_jsonld([
    '@type' => 'Article',
    'headline' => $pageTitle,
    'description' => $pageDescription,
    'url' => SEO_BASE_URL . '/gestione-finanza-personale',
    'image' => seo_og_image(),
    'inLanguage' => 'it-IT',
    'dateModified' => date('c', filemtime(__FILE__)),
    'author' => ['@id' => SEO_BASE_URL . '/#organization'],
    'publisher' => seo_organization(),
]);
seo_jsonld([
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn($qa) => [
        '@type' => 'Question',
        'name' => $qa[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($qa[1])],
    ], $faq),
]);
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php $active = 'guida'; include '_nav.php'; ?>

<!-- HERO -->
<section class="px-6 pb-8 pt-14 text-center sm:pt-20">
  <span class="mb-4 inline-block rounded-full bg-accentSoft px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-accent">Guida</span>
  <h1 class="mx-auto mb-4 max-w-3xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Gestione della finanza personale: la guida pratica in 7 passi</h1>
  <p class="mx-auto max-w-2xl text-lg text-muted">Entrate e spese, budget, fondo di emergenza, debiti, investimenti e obiettivi: un metodo semplice per sapere sempre dove vanno i tuoi soldi e decidere invece di subire.</p>
</section>

<main class="mx-auto max-w-3xl px-6 py-8">
  <article>
    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">La <strong>gestione della finanza personale</strong> non riguarda solo chi ha grandi patrimoni. Riguarda chiunque abbia uno stipendio, un affitto o un mutuo, qualche abbonamento e il desiderio di arrivare a fine mese con un margine. Il punto non è rinunciare a tutto, ma avere un quadro chiaro: quanto entra, quanto esce, quanto resta e dove va a finire.</p>
    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">Le finanze personali si possono dividere in cinque aree. Ognuna ha un numero da tenere d'occhio: se li conosci tutti e cinque, sai già più della maggior parte delle persone sulla tua situazione.</p>

    <?php gfp_table('Le cinque aree della finanza personale', ['Area', 'Domanda chiave', 'Numero da seguire'], [
        ['Entrate e spese', 'Dove vanno i miei soldi?', 'Spesa mensile per categoria'],
        ['Risparmio', 'Quanto metto da parte?', 'Tasso di risparmio (%)'],
        ['Debiti', 'Quanto pesa ciò che devo restituire?', 'Rapporto rata/reddito (%)'],
        ['Investimenti', 'Il mio capitale cresce più dell\'inflazione?', 'Rendimento reale annuo'],
        ['Patrimonio', 'Quanto valgo al netto dei debiti?', 'Patrimonio netto (€)'],
    ]); ?>

    <h2 class="mt-10 mb-3 text-xl font-bold text-gray-900">1. Fotografa entrate e spese reali</h2>
    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">Ogni gestione delle finanze parte dai dati, non dalle impressioni. Riprendi gli estratti conto degli ultimi tre mesi e raggruppa ogni uscita per categoria: casa, spesa alimentare, trasporti, bollette, abbonamenti, tempo libero. Tre mesi bastano a far emergere le abitudini senza farsi ingannare da un mese anomalo.</p>
    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">Distingui subito le <a href="/news/spese-fisse-e-variabili-differenza" class="text-accent hover:underline font-medium">spese fisse da quelle variabili</a>: le prime cambiano poco, le seconde sono dove puoi davvero intervenire. Controlla anche gli addebiti ricorrenti: spesso emergono <a href="/news/abbonamenti-dimenticati-come-scovare-spese-ricorrenti" class="text-accent hover:underline font-medium">abbonamenti dimenticati</a> che pesano più di quanto sembri.</p>

    <h2 class="mt-10 mb-3 text-xl font-bold text-gray-900">2. Dai un tetto a ogni categoria con un budget</h2>
    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">Una volta che sai quanto spendi, puoi decidere quanto vuoi spendere. Il budget non è un divieto: è una decisione presa in anticipo, a mente fredda. Ecco un esempio per una persona con 2.400 euro netti al mese.</p>

    <?php gfp_table('Esempio: budget mensile con 2.400 euro netti (importi in euro)', ['Categoria', 'Budget', 'Quota'], [
        ['Casa (affitto o rata del mutuo)', '750', '31%'],
        ['Spesa alimentare', '400', '17%'],
        ['Trasporti', '200', '8%'],
        ['Bollette e utenze', '150', '6%'],
        ['Abbonamenti', '50', '2%'],
        ['Tempo libero e ristoranti', '250', '10%'],
        ['Varie e spese annuali accantonate', '120', '5%'],
        ['Risparmio e investimenti', '480', '20%'],
        ['Totale', '2.400', '100%'],
    ], [1, 2], 'Il risparmio è una voce del budget come le altre, messa da parte a inizio mese. Le spese annuali (bollo, assicurazioni, regali) sono divise per dodici.', true); ?>

    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">Se preferisci un punto di partenza semplice, la <a href="/news/regola-50-30-20-vs-budget-per-categoria" class="text-accent hover:underline font-medium">regola 50/30/20</a> divide il reddito in necessità, desideri e risparmio; puoi provarla con il <a href="/calcolatore-budget-503020" class="text-accent hover:underline font-medium">calcolatore budget 50/30/20</a>. Per un metodo più rigoroso c'è il <a href="/news/budget-a-base-zero-come-funziona" class="text-accent hover:underline font-medium">budget a base zero</a>.</p>

    <h2 class="mt-10 mb-3 text-xl font-bold text-gray-900">3. Costruisci un fondo di emergenza</h2>
    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">Prima di investire, metti al sicuro l'imprevisto: una caldaia da cambiare, una spesa medica, un periodo senza lavoro. Il riferimento più diffuso è tenere da parte da <strong>3 a 6 mesi di spese essenziali</strong> su un conto facilmente accessibile.</p>

    <?php gfp_table('Quanto fondo di emergenza serve (spese essenziali di 1.500 euro al mese)', ['Situazione', 'Mesi coperti', 'Fondo'], [
        ['Dipendente a tempo indeterminato, senza figli', '3', '4.500 €'],
        ['Famiglia monoreddito o con figli', '6', '9.000 €'],
        ['Autonomo o reddito variabile', '6–12', '9.000–18.000 €'],
    ], [1, 2], 'Spese essenziali: casa, spesa, utenze, trasporti, rate. Tempo libero e abbonamenti non contano.'); ?>

    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">Approfondisci nella guida al <a href="/news/fondo-di-emergenza-quanto-tenere-da-parte" class="text-accent hover:underline font-medium">fondo di emergenza</a>. Per decidere dove tenerlo, guarda il confronto tra <a href="/news/etf-monetari-o-conto-deposito" class="text-accent hover:underline font-medium">ETF monetari e conto deposito</a>.</p>

    <h2 class="mt-10 mb-3 text-xl font-bold text-gray-900">4. Tieni sotto controllo i debiti</h2>
    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">Mutuo, prestiti e finanziamenti a rate sono parte della finanza personale tanto quanto il risparmio. Il numero da seguire è il <a href="/news/rapporto-debito-reddito-dti-cosa-e" class="text-accent hover:underline font-medium">rapporto tra rate e reddito</a>: le banche italiane in genere non concedono un mutuo se la rata supera il 30–35% del reddito netto, ed è un buon limite anche per te.</p>
    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">Conosci il tuo <a href="/news/ammortamento-alla-francese-guida-mutuo-rata-costante" class="text-accent hover:underline font-medium">piano di ammortamento</a>: sapere quanta parte della rata è interesse aiuta a valutare una <a href="/news/surroga-mutuo-cosa-e-come-funziona" class="text-accent hover:underline font-medium">surroga</a> o un <a href="/news/rientro-anticipato-mutuo-ridurre-durata-o-rata" class="text-accent hover:underline font-medium">rientro anticipato</a>. I debiti più costosi, come carte revolving e prestiti al consumo, vanno chiusi per primi.</p>

    <h2 class="mt-10 mb-3 text-xl font-bold text-gray-900">5. Fai lavorare il risparmio con gli investimenti</h2>
    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">Il denaro fermo sul conto perde valore ogni anno per effetto dell'<a href="/news/inflazione-potere-acquisto-perche-il-capitale-deve-crescere" class="text-accent hover:underline font-medium">inflazione</a>. Una volta coperto il fondo di emergenza, il risparmio che non ti serve nei prossimi anni può essere investito, per esempio con un <a href="/news/pac-piano-accumulo-etf-come-funziona" class="text-accent hover:underline font-medium">piano di accumulo (PAC) in ETF</a>. La tabella mostra cosa può fare il tempo con 200 euro al mese.</p>

    <?php gfp_table('200 euro al mese investiti: valore finale con rendimento annuo ipotetico', ['Anni', 'Versato', 'Al 3%', 'Al 5%'], [
        ['10', '24.000 €', '27.900 €', '31.100 €'],
        ['20', '48.000 €', '65.700 €', '82.200 €'],
        ['30', '96.000 €', '116.500 €', '166.500 €'],
    ], [1, 2, 3], 'Interesse composto con capitalizzazione mensile, valori arrotondati, al lordo di tasse e costi. I rendimenti passati non garantiscono quelli futuri.'); ?>

    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">Fai le tue simulazioni con il <a href="/calcolatore-piano-investimento" class="text-accent hover:underline font-medium">calcolatore piano di investimento</a> o il <a href="/calcolatore-interesse-composto" class="text-accent hover:underline font-medium">calcolatore di interesse composto</a>, e prima di iniziare leggi le guide su <a href="/news/asset-allocation-cos-e-come-sceglierla" class="text-accent hover:underline font-medium">asset allocation</a> e <a href="/news/diversificazione-portafoglio-cosa-significa-davvero" class="text-accent hover:underline font-medium">diversificazione</a>.</p>

    <h2 class="mt-10 mb-3 text-xl font-bold text-gray-900">6. Dai un obiettivo e una scadenza al risparmio</h2>
    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">Risparmiare "per sicurezza" funziona peggio che risparmiare per qualcosa: l'anticipo della casa, l'auto, un anno sabbatico, la pensione. Un obiettivo con importo e scadenza si trasforma in una cifra mensile precisa, che puoi calcolare con il <a href="/calcolatore-obiettivo-risparmio" class="text-accent hover:underline font-medium">calcolatore obiettivo di risparmio</a>.</p>
    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">Per gli obiettivi di lungo periodo pensa anche alla previdenza: <a href="/news/pensione-integrativa-fondo-pensione-quanto-serve" class="text-accent hover:underline font-medium">fondo pensione</a>, <a href="/news/tfr-in-azienda-o-fondo-pensione" class="text-accent hover:underline font-medium">destinazione del TFR</a> e, se ti interessa smettere di lavorare prima, il <a href="/news/indipendenza-finanziaria-fire-come-calcolare-il-numero" class="text-accent hover:underline font-medium">numero FIRE</a>.</p>

    <h2 class="mt-10 mb-3 text-xl font-bold text-gray-900">7. Rivedi tutto con una routine regolare</h2>
    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">La gestione della finanza personale non si imposta una volta per sempre. Bastano pochi minuti a intervalli regolari:</p>
    <ul class="mb-4 list-disc space-y-2 pl-5 text-[15px] leading-relaxed text-gray-700">
      <li><strong>Ogni settimana:</strong> registra o importa le spese e controlla le categorie vicine al limite.</li>
      <li><strong>Ogni mese:</strong> confronta budget e speso, calcola il <a href="/news/tasso-di-risparmio-cosa-e-come-calcolarlo" class="text-accent hover:underline font-medium">tasso di risparmio</a> e sposta il risparmio dove deve andare.</li>
      <li><strong>Ogni anno:</strong> aggiorna il <a href="/news/patrimonio-netto-cos-e-perche-seguirlo-nel-tempo" class="text-accent hover:underline font-medium">patrimonio netto</a>, <a href="/news/ribilanciamento-portafoglio-cosa-e-perche" class="text-accent hover:underline font-medium">ribilancia il portafoglio</a>, rivedi obiettivi e <a href="/news/assicurazioni-che-servono-davvero" class="text-accent hover:underline font-medium">assicurazioni</a>.</li>
    </ul>

    <h2 class="mt-10 mb-3 text-xl font-bold text-gray-900">Foglio Excel o app di finanza personale?</h2>
    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">Per mettere in pratica questi passi ti serve uno strumento. Le opzioni principali sono tre, e ognuna ha pro e contro.</p>

    <?php gfp_table('Strumenti per la gestione delle finanze personali a confronto', ['', 'Quaderno', 'Foglio Excel', 'App di finanza personale'], [
        ['Costo', 'Gratis', 'Gratis', 'Gratis o abbonamento'],
        ['Tempo richiesto', 'Alto', 'Medio-alto', 'Basso'],
        ['Saldi e totali', 'A mano', 'Formule da scrivere', 'Automatici'],
        ['Avvisi sul budget', 'No', 'No', 'Sì'],
        ['Mutuo e investimenti collegati', 'No', 'Solo se li costruisci tu', 'Dipende dall\'app'],
        ['Previsioni', 'No', 'Complesse', 'Dipende dall\'app'],
    ]); ?>

    <p class="mb-4 text-[15px] leading-relaxed text-gray-700">Un'app di finanza personale ha senso quando il foglio di calcolo diventa troppo lavoro, o quando vuoi vedere insieme le diverse aree: quanto pesa la rata del mutuo sul budget, quanto contribuiscono gli investimenti al patrimonio. Ne parliamo anche nel confronto tra <a href="/news/tracciare-spese-manualmente-vs-collegamento-bancario" class="text-accent hover:underline font-medium">tracciamento manuale e collegamento bancario</a>.</p>

    <h3 class="mt-6 mb-2 text-lg font-bold text-gray-900">Cosa cercare in un'app di finanza personale</h3>
    <ul class="mb-4 list-disc space-y-2 pl-5 text-[15px] leading-relaxed text-gray-700">
      <li><strong>Privacy chiara:</strong> dove sono i server, chi vede i dati, se vengono venduti a terzi.</li>
      <li><strong>Import delle transazioni</strong> da file della banca, per non dover inserire tutto a mano.</li>
      <li><strong>Budget per categoria</strong> con avvisi prima di sforare, non solo a consuntivo.</li>
      <li><strong>Tutte le aree insieme:</strong> conti, mutuo, investimenti e patrimonio nello stesso posto.</li>
      <li><strong>Pensata per l'Italia:</strong> mutui con ammortamento alla francese, tassazione al 26%, titoli di Borsa Italiana.</li>
    </ul>
    <p class="mb-4 text-[15px] leading-relaxed text-gray-700"><a href="/" class="text-accent hover:underline font-medium">Reflexi</a> è un'app di finanza personale nata per questo: <a href="/budget" class="text-accent hover:underline font-medium">budget per categoria</a>, <a href="/mutui" class="text-accent hover:underline font-medium">mutuo con piano di ammortamento</a>, <a href="/investimenti" class="text-accent hover:underline font-medium">investimenti con quotazioni reali</a>, <a href="/obiettivi" class="text-accent hover:underline font-medium">obiettivi di risparmio</a>, <a href="/patrimonio" class="text-accent hover:underline font-medium">patrimonio netto</a> e <a href="/analisi" class="text-accent hover:underline font-medium">previsioni</a> nello stesso posto. Importi le transazioni da CSV o Excel e i server sono in Italia.</p>
  </article>

  <!-- Domande frequenti (stesso testo dello schema FAQPage nel <head>) -->
  <h2 class="mt-10 mb-3 text-xl font-bold text-gray-900">Domande frequenti</h2>
  <div class="space-y-2">
    <?php foreach ($faq as [$q, $a]): ?>
    <details class="group card overflow-hidden p-0">
      <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4 text-sm font-semibold">
        <?= $q ?>
        <svg aria-hidden="true" class="faq-chevron h-4 w-4 flex-shrink-0 text-muted transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </summary>
      <div class="border-t border-border px-5 py-4 text-sm text-gray-700"><?= $a ?></div>
    </details>
    <?php endforeach; ?>
  </div>
</main>

<!-- Articoli News correlati -->
<?php if (!empty($relatedArticles)): ?>
<section class="border-t border-border bg-white px-6 py-16">
  <div class="mx-auto max-w-5xl">
    <h2 class="mb-6 text-center text-xl font-bold">Continua a leggere</h2>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
      <?php foreach ($relatedArticles as $r): ?>
      <a href="/news/<?= htmlspecialchars($r['slug']) ?>" class="group flex flex-col rounded-2xl border border-border bg-bg p-6 transition hover:border-accent hover:bg-accentSoft">
        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-white text-xl"><?= htmlspecialchars($r['cover_emoji']) ?></div>
        <h3 class="mb-1.5 text-base font-bold"><?= htmlspecialchars($r['title']) ?></h3>
        <p class="text-sm text-muted"><?= htmlspecialchars($r['excerpt']) ?></p>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="px-6 py-20">
  <div class="mx-auto max-w-2xl rounded-3xl bg-gradient-to-br from-accent via-accentDark to-indigo-900 p-10 text-center text-white sm:p-14">
    <h2 class="mb-3 text-2xl font-bold sm:text-3xl">Metti in pratica la guida con Reflexi</h2>
    <p class="mb-8 text-white/80">Budget, mutuo, investimenti e obiettivi in un'unica app. 6 mesi gratis, senza carta di credito.</p>
    <a href="/app/?mode=register" class="inline-block rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-accent shadow-lg transition hover:bg-gray-50">Crea il tuo account</a>
  </div>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
