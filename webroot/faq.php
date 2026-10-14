<?php $active = 'faq'; ?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Domande frequenti su Reflexi: sicurezza, prezzi e funzioni</title>
<meta name="description" content="Tutto quello che c'è da sapere su Reflexi: come funziona, sicurezza dei dati, mutui, investimenti, budget e abbonamento.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://reflexi.it/faq">
<!-- Open Graph (Facebook, WhatsApp, LinkedIn, Telegram...) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://reflexi.it/faq">
<meta property="og:site_name" content="Reflexi">
<meta property="og:title" content="Domande frequenti su Reflexi: sicurezza, prezzi e funzioni">
<meta property="og:description" content="Tutto quello che c'è da sapere su Reflexi: come funziona, sicurezza dei dati, mutui, investimenti, budget e abbonamento.">
<meta property="og:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="it_IT">

<!-- Twitter/X Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Domande frequenti su Reflexi: sicurezza, prezzi e funzioni">
<meta name="twitter:description" content="Tutto quello che c'è da sapere su Reflexi: come funziona, sicurezza dei dati, mutui, investimenti, budget e abbonamento.">
<meta name="twitter:image" content="<?php require_once __DIR__ . '/_seo.php'; echo seo_og_image(); ?>">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preload" href="/fonts/inter-v20-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/css/site.css?v=<?= filemtime(__DIR__ . '/css/site.css') ?>">
<style>
  details summary::-webkit-details-marker { display: none; }
  details[open] .faq-chevron { transform: rotate(180deg); }
</style>
<?php
require_once __DIR__ . '/_seo.php';
seo_breadcrumb([['Domande frequenti', '/faq']]);
?>
</head>
<body class="bg-bg text-gray-900 antialiased">

<?php include '_nav.php'; ?>

<section class="px-6 pb-8 pt-14 text-center sm:pt-20">
  <h1 class="mx-auto mb-3 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Come possiamo aiutarti?</h1>
  <p class="mx-auto max-w-xl text-lg text-muted">Le domande più comuni su Reflexi, organizzate per argomento.</p>
</section>

<main class="mx-auto max-w-3xl px-6 py-10">

<?php
// Accumula tutte le coppie domanda/risposta mostrate qui sotto, per generare
// alla fine lo schema FAQPage con lo stesso identico contenuto (niente
// duplicazione, il markup visivo resta l'unica fonte di verità).
$faq_schema_items = [];
function faq_category($title, $items) {
    global $faq_schema_items;
    echo '<div class="mb-10"><h2 class="mb-3 text-lg font-bold text-gray-900">' . $title . '</h2><div class="space-y-2">';
    foreach ($items as [$q, $a]) {
        echo '<details class="group card overflow-hidden p-0">
            <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4 text-sm font-semibold">
                ' . $q . '
                <svg aria-hidden="true" class="faq-chevron h-4 w-4 flex-shrink-0 text-muted transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </summary>
            <div class="border-t border-border px-5 py-4 text-sm text-gray-700">' . $a . '</div>
        </details>';
        $faq_schema_items[] = [
            '@type' => 'Question',
            'name' => strip_tags($q),
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($a)],
        ];
    }
    echo '</div></div>';
}
?>

<?php faq_category('Iniziare con Reflexi', [
    ['Cos\'è Reflexi?', 'Reflexi è un\'app che mette insieme mutuo, investimenti, budget e spese quotidiane in un unico posto, così hai un quadro reale della tua situazione finanziaria invece di tenerla su strumenti scollegati.'],
    ['Come funziona il periodo di prova?', 'Alla registrazione parte automaticamente un periodo di prova gratuita di 6 mesi (180 giorni), con accesso completo a tutte le funzionalità. Scaduto il trial, puoi scegliere un piano mensile o annuale per continuare.'],
    ['Reflexi tiene davvero i miei soldi?', '<strong>No.</strong> Reflexi non è un istituto di pagamento e non tocca mai un euro reale — traccia solo i numeri che inserisci tu (saldi, transazioni, investimenti). I tuoi soldi restano sempre nella tua banca vera. L\'unico pagamento reale che passa attraverso Reflexi è l\'abbonamento al servizio stesso, gestito interamente da Stripe (un processore di pagamenti certificato), non da noi direttamente.'],
]); ?>

<?php faq_category('Conti e Transazioni', [
    ['Come si calcola il saldo di un conto?', 'Il saldo si calcola da solo: saldo iniziale che imposti alla creazione, più/meno tutte le transazioni registrate su quel conto. Non serve mai aggiornarlo a mano.'],
    ['Posso avere più conti?', 'Sì, quanti vuoi — conto corrente, contanti, carta di credito, conto deposito, ecc. Ogni transazione va assegnata a un conto specifico.'],
    ['Cosa sono gli "interessi sui conti"?', 'Se un conto matura interessi (deposito, vincolato o corrente remunerato), puoi attivarlo dalla scheda del conto indicando tasso e frequenza. L\'interesse maturato diventa una vera transazione che aumenta il saldo reale, generabile dalla sezione Investimenti.'],
]); ?>

<?php faq_category('Mutui', [
    ['Come calcola la rata Reflexi?', 'Con il metodo di ammortamento francese (il più comune in Italia), a rata costante: cambia solo la proporzione tra quota capitale e quota interessi nel tempo.'],
    ['Cosa succede se il mutuo è cointestato?', 'Indichi la percentuale di tua proprietà (es. 50%) e Reflexi calcola sempre la tua quota reale di debito residuo e interessi — non l\'importo totale, che confonderebbe il resto dei tuoi conti.'],
    ['Cosa significa "simulazione estinzione anticipata"?', 'Una stima di quanto risparmieresti in interessi se estinguessi il mutuo oggi (o a una data futura), rispetto a continuare a pagarlo fino alla scadenza naturale.'],
    ['Posso simulare un rientro anticipato con versamenti extra?', 'Sì. Nel dettaglio del mutuo, scheda "Rientro anticipato", ipotizzi versamenti in conto capitale sporadici o periodici (ogni mese, trimestre, semestre o anno) e scegli se usarli per ridurre la durata o la rata. Reflexi confronta il piano con e senza versamenti: interessi risparmiati, nuova scadenza o nuova rata.'],
    ['Il risparmio tiene conto della detrazione fiscale sugli interessi?', 'Sì. Pagando meno interessi si perde parte della detrazione IRPEF del 19% (su massimo 4.000 € di interessi l\'anno per il mutuo prima casa): Reflexi la calcola anno per anno e la sottrae, insieme all\'eventuale penale, per mostrarti il risparmio netto. Percentuale e tetto sono modificabili, e la detrazione si può disattivare.'],
    ['Come registro un versamento che ho fatto davvero?', 'Dalla stessa scheda, con "Registra versamento" (o "Registra" accanto a un versamento simulato). Il debito residuo, il piano di ammortamento, il patrimonio netto e la rata ricorrente collegata si aggiornano; se vuoi, l\'uscita viene registrata anche sul conto.'],
]); ?>

<?php faq_category('Investimenti', [
    ['Da dove arrivano le quotazioni?', 'Da Yahoo Finance, aggiornate quando apri la sezione Investimenti o premi "Aggiorna quotazioni". Copre ETF, azioni e criptovalute (formato ticker tipo BTC-USD).'],
    ['Come funziona la ricerca titoli?', 'Cerchi per nome (es. "Vanguard All World"), Reflexi interroga Yahoo Finance e ti propone i ticker corrispondenti — non serve conoscerlo a memoria.'],
    ['Cos\'è un PAC in Reflexi?', 'Un piano di accumulo capitale: imposti un importo fisso a intervalli regolari, e quando premi "Genera acquisti dovuti" l\'app calcola da sola quante quote comprare al prezzo di chiusura reale di ogni data dovuta.'],
    ['Come viene calcolata l\'imposta sulle plusvalenze?', 'Al 26% (aliquota standard italiana, modificabile per singolo strumento) sul guadagno di ogni vendita, calcolato col metodo del costo medio di carico. È una semplificazione dichiarata: non tiene conto della compensazione di minusvalenze pregresse — per la tua situazione fiscale reale, consulta un commercialista.'],
]); ?>

<?php faq_category('Budget', [
    ['Come imposto un budget?', 'Vai su Categorie, scegli un limite mensile per la categoria che vuoi controllare (es. Ristoranti).'],
    ['Cos\'è il rollover del budget?', 'Se lo attivi su una categoria, l\'eccedenza non spesa un mese si riporta al mese successivo invece di azzerarsi — utile per categorie con spese irregolari nel tempo.'],
]); ?>

<?php faq_category('Abbonamenti (spese ricorrenti)', [
    ['Come fa Reflexi a rilevare i miei abbonamenti?', 'Analizza le tue transazioni cercando pattern di spesa che si ripetono a intervalli regolari ma non sono dichiarati come ricorrenti — capita spesso di dimenticarsi di un rinnovo.'],
    ['Come dichiaro un abbonamento rilevato?', 'Dalla sezione Abbonamenti, premi "Dichiara" sul pattern rilevato: i campi si compilano da soli, basta confermare.'],
]); ?>

<?php faq_category('Obiettivi di risparmio', [
    ['Come viene calcolato il progresso di un obiettivo?', 'Somma automaticamente le tue entrate meno le uscite (esclusi trasferimenti e investimenti) dalla data di inizio dell\'obiettivo ad oggi, su tutti i conti insieme.'],
]); ?>

<?php faq_category('Analisi predittiva', [
    ['Cos\'è la simulazione Monte Carlo?', 'Migliaia di scenari calcolati sulla variabilità storica reale delle tue finanze, per stimare una fascia di probabilità sul tuo risparmio futuro — non una singola previsione ottimista.'],
    ['Quanto sono affidabili le proiezioni?', 'Sono stime statistiche basate sul passato, non garanzie. Più storico hai nell\'app, più accurate diventano.'],
]); ?>

<?php faq_category('Il tuo abbonamento a Reflexi', [
    ['Quanto costa Reflexi dopo il periodo di prova?', 'Puoi scegliere un piano mensile o annuale (con sconto) dal tuo Profilo — i prezzi esatti sono sempre visibili lì prima di confermare.'],
    ['Come annullo l\'abbonamento?', 'Dal Profilo, premi "Gestisci abbonamento": si apre il portale sicuro di Stripe dove puoi annullare l\'abbonamento in autonomia. Per passare da mensile ad annuale (o viceversa) scrivici a <a href="mailto:info@reflexi.it" class="text-accent hover:underline">info@reflexi.it</a>.'],
    ['Cosa succede ai miei dati se l\'abbonamento scade?', 'Restano al sicuro e intatti — semplicemente l\'accesso all\'app si blocca fino a quando non riattivi un abbonamento.'],
]); ?>

<?php faq_category('Sicurezza e Privacy', [
    ['Come proteggete i miei dati?', 'Connessione sempre cifrata (HTTPS), password mai salvate in chiaro, dati isolati per ogni utente, server in Italia, nessuna pubblicità o vendita dei tuoi dati a terzi.'],
    ['Posso cancellare il mio account?', 'Sì, in qualsiasi momento — scrivi a <a href="mailto:privacy@reflexi.it" class="text-accent hover:underline">privacy@reflexi.it</a> e cancelliamo account e dati completamente.'],
    ['Dove trovo tutti i dettagli legali?', 'Nella <a href="/privacy" class="text-accent hover:underline">Privacy Policy</a> e nei <a href="/termini" class="text-accent hover:underline">Termini di Servizio</a>.'],
]); ?>

<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => $faq_schema_items,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>

</main>

<section class="px-6 py-16 text-center">
  <h2 class="mb-2 text-xl font-bold">Non hai trovato quello che cercavi?</h2>
  <p class="mb-6 text-muted">Scrivici, leggiamo davvero ogni messaggio.</p>
  <a href="mailto:info@reflexi.it" class="inline-block rounded-xl bg-accent px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark">Scrivi a info@reflexi.it</a>
</section>

<?php include '_footer.php'; ?>

</body>
</html>
