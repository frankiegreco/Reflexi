<?php
// Dati strutturati schema.org (JSON-LD) condivisi dalle pagine del sito
// pubblico: breadcrumb, organizzazione, app con i prezzi reali. Da includere
// nel <head>. I prezzi devono restare allineati a piani.php: Google segnala
// i dati strutturati che non corrispondono a quanto mostrato in pagina.

const SEO_BASE_URL = 'https://reflexi.it';

function seo_jsonld(array $data): void
{
    echo '<script type="application/ld+json">'
        . json_encode(['@context' => 'https://schema.org'] + $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)
        . "</script>\n";
}

/**
 * Breadcrumb a partire dalla home (aggiunta in automatico).
 * $crumbs = [[nome, percorso], ...], es. [['Calcolatori', '/calcolatori'], ['Inflazione', '/calcolatore-inflazione']].
 */
function seo_breadcrumb(array $crumbs): void
{
    $items = [];
    foreach (array_merge([['Home', '/']], $crumbs) as $i => [$name, $path]) {
        $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => SEO_BASE_URL . $path];
    }
    seo_jsonld(['@type' => 'BreadcrumbList', 'itemListElement' => $items]);
}

function seo_organization(): array
{
    return [
        '@type' => 'Organization',
        '@id' => SEO_BASE_URL . '/#organization',
        'name' => 'Reflexi',
        'url' => SEO_BASE_URL . '/',
        'logo' => SEO_BASE_URL . '/favicon.svg',
        'email' => 'info@reflexi.it',
    ];
}

/** L'app con le due offerte di piani.php (prova gratuita di 6 mesi inclusa in entrambe). */
function seo_software_application(): array
{
    $offer = fn(string $name, string $price, string $unit) => [
        '@type' => 'Offer',
        'name' => $name,
        'price' => $price,
        'priceCurrency' => 'EUR',
        'url' => SEO_BASE_URL . '/piani',
        'priceSpecification' => [
            '@type' => 'UnitPriceSpecification',
            'price' => $price,
            'priceCurrency' => 'EUR',
            'unitCode' => $unit, // MON = mese, ANN = anno (codici UN/CEFACT)
        ],
    ];
    return [
        '@type' => 'SoftwareApplication',
        'name' => 'Reflexi',
        'url' => SEO_BASE_URL . '/',
        'applicationCategory' => 'FinanceApplication',
        'operatingSystem' => 'Web',
        'description' => "App di finanza personale che unisce mutuo, investimenti, budget e analisi predittiva in un'unica piattaforma.",
        'inLanguage' => 'it-IT',
        'publisher' => seo_organization(),
        'offers' => [$offer('Piano mensile', '7.99', 'MON'), $offer('Piano annuale', '79.00', 'ANN')],
    ];
}

/** Calcolatore gratuito, senza registrazione. */
function seo_calculator(string $name, string $path, string $description): void
{
    seo_jsonld([
        '@type' => 'WebApplication',
        'name' => $name,
        'url' => SEO_BASE_URL . $path,
        'description' => $description,
        'applicationCategory' => 'FinanceApplication',
        'operatingSystem' => 'Web',
        'inLanguage' => 'it-IT',
        'isAccessibleForFree' => true,
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR'],
        'publisher' => seo_organization(),
    ]);
}

/**
 * Immagine di anteprima social della pagina (og:image/twitter:image), generata
 * da build/og/generate.py in webroot/og/<nome>.jpg. $name di default è il
 * nome del file PHP (mutui.php -> og/mutui.jpg); per gli articoli
 * 'news/<slug>'. Se l'immagine non esiste ancora (pagina o articolo nuovo,
 * generatore non rilanciato) torna quella generica del sito.
 */
function seo_og_image(?string $name = null): string
{
    $name ??= basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
    $file = __DIR__ . '/og/' . $name . '.jpg';
    return is_file($file)
        ? SEO_BASE_URL . '/og/' . $name . '.jpg?v=' . filemtime($file)
        : SEO_BASE_URL . '/og-image.png?v=3';
}
