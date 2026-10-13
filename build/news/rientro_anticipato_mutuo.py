"""Articolo News: rientro anticipato del mutuo (slug rientro-anticipato-mutuo-ridurre-durata-o-rata).

Uso:  python3 build/news/rientro_anticipato_mutuo.py > /tmp/body.html
I numeri vengono dal simulatore dell'app (frontend/src/lib/loanPrepay.js) su un
mutuo da 150.000 euro al 3,5% in 25 anni, dopo 5 anni (rata 750,94 euro,
detrazione 19% su max 4.000 euro di interessi l'anno).
"""
import os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from news_html import a, p, h2, h3, ul, table, bars, euro  # noqa: E402

SLUG = 'rientro-anticipato-mutuo-ridurre-durata-o-rata'
TITLE = "Rientro anticipato del mutuo: meglio ridurre la durata o la rata?"
SEO_TITLE = "Rientro anticipato mutuo: ridurre durata o rata?"
EXCERPT = "Con un versamento extra il mutuo costa meno, ma la scelta tra accorciare la durata e abbassare la rata cambia molto il risparmio. Un esempio con i numeri, versamenti periodici e detrazione fiscale inclusa."
META = "Rientro anticipato del mutuo: ridurre la durata o la rata? Esempio con i numeri, versamenti periodici, penali e detrazione fiscale persa."
BODY = ''.join([
 p("Non serve avere da parte tutto il debito residuo per alleggerire il mutuo. Con il <strong>rientro anticipato</strong>, o estinzione parziale, versi alla banca una somma in più rispetto alla rata: va tutta a ridurre il capitale e da quel momento gli interessi si calcolano su un debito più basso. Il mutuo resta aperto, ma costa meno. Resta però da decidere una cosa: usare quel versamento per <strong>accorciare la durata</strong> o per <strong>abbassare la rata</strong>."),
 h2("Come funziona il rientro anticipato"),
 p("Di solito basta una richiesta scritta alla banca, che indica l'importo e la data in cui verrà addebitato: spesso coincide con la scadenza di una rata. La somma versata non contiene interessi, è tutta capitale. Con l'" + a('/news/ammortamento-alla-francese-guida-mutuo-rata-costante', 'ammortamento alla francese') + ", ogni euro restituito in anticipo cancella tutti gli interessi che quell'euro avrebbe prodotto fino alla fine del mutuo."),
 p("Per i mutui stipulati da persone fisiche dal 2 febbraio 2007 per acquistare o ristrutturare un immobile adibito ad abitazione o alla propria attività <strong>le penali di estinzione anticipata, totale o parziale, non sono ammesse</strong>. Negli altri casi la penale dipende dal contratto: va sempre controllata, perché riduce il vantaggio. Alcune banche fissano anche un importo minimo per ogni versamento."),
 h2("Ridurre la durata o ridurre la rata"),
 p("Dopo il versamento il piano di ammortamento va ricalcolato, e le strade sono due:"),
 ul("<strong>Riduzione della durata</strong>: la rata resta uguale e il mutuo finisce prima.",
    "<strong>Riduzione della rata</strong>: la scadenza resta la stessa e la rata scende, perché lo stesso numero di rate restituisce un capitale più basso."),
 p("Non tutte le banche offrono entrambe le opzioni: chiedilo prima di fare il bonifico. Per capire la differenza prendiamo un esempio: mutuo prima casa da 150.000 euro al 3,5% in 25 anni. Dopo 5 anni il debito residuo è di circa 129.500 euro e la rata è di 750,94 euro. A questo punto versi una volta 10.000 euro."),
 ul("<strong>Riducendo la durata</strong> la rata resta di 750,94 euro e il mutuo finisce 2 anni e 1 mese prima. Gli interessi risparmiati sono circa <strong>9.340 euro</strong>.",
    "<strong>Riducendo la rata</strong> la scadenza non cambia e la rata scende a 692,77 euro, circa 58 euro in meno al mese. Gli interessi risparmiati sono circa <strong>3.900 euro</strong>."),
 table("Esempio: versamento di 10.000 euro dopo 5 anni di mutuo (importi in euro)",
   ["", "Senza versamento", "Riduci la durata", "Riduci la rata"],
   [["Rata mensile", "750,94", "750,94", "692,77"],
    ["Rate ancora da pagare", "240", "215", "240"],
    ["Interessi ancora da pagare", euro(50744), euro(50744 - 9343), euro(50744 - 3901)],
    ["Interessi risparmiati", "—", euro(9343), euro(3901)]],
   note="Mutuo prima casa da 150.000 euro al 3,5% in 25 anni, tasso fisso. Valori arrotondati all'euro."),
 p("La differenza è grande. Con la rata invariata il debito continua a scendere al ritmo di prima, partendo però da un livello più basso, quindi si azzera prima e smette di produrre interessi. Abbassando la rata, invece, rallenti la restituzione del capitale, che produce interessi più a lungo."),
 h3("Quando conviene ridurre la rata"),
 p("Ridurre la durata fa risparmiare di più, ma non è sempre la scelta giusta. Abbassare la rata ha senso se il tuo bilancio mensile è stretto, se vuoi più margine per gli imprevisti o se hai un mutuo a tasso variabile e temi rialzi della rata. Una via di mezzo: chiedi la riduzione della rata e continua a mettere da parte la differenza, che potrai versare di nuovo in futuro o tenere come cuscinetto."),
 h2("Un versamento subito o tanti piccoli versamenti?"),
 p("Il rientro anticipato non deve essere per forza una somma grande. Puoi anche versare un importo fisso ogni anno, per esempio parte della tredicesima. Nello stesso mutuo dell'esempio, versando 2.000 euro all'anno e riducendo la durata, il mutuo finisce quasi 5 anni prima. In totale versi circa 30.500 euro e risparmi circa 13.800 euro di interessi."),
 p("Però conta anche il momento. Ogni 1.000 euro versati subito fanno risparmiare molto di più di 1.000 euro versati tra dieci anni, perché restano più anni di interessi da tagliare. Se la somma ce l'hai già e non ti serve, versarla prima rende di più che dividerla nel tempo."),
 bars("Risparmio netto per ogni 1.000 euro versati",
   [("Una volta subito, riduci la durata", 771, "accent"),
    ("2.000 euro all'anno, riduci la durata", 368, "accent"),
    ("Una volta subito, riduci la rata", 330, "loan"),
    ("2.000 euro all'anno, riduci la rata", 162, "loan")],
   fmt=lambda v: euro(v) + " €",
   note="Stesso mutuo dell'esempio. Risparmio netto: interessi risparmiati meno la detrazione IRPEF persa."),
 h2("Il conto che quasi nessuno fa: la detrazione persa"),
 p("Se il mutuo è per l'abitazione principale, ogni anno recuperi il 19% degli interessi pagati, su un massimo di 4.000 euro di interessi (lo spieghiamo nella guida alla " + a('/news/detrazione-interessi-mutuo-prima-casa', 'detrazione degli interessi del mutuo') + "). Pagando meno interessi, recuperi anche meno detrazione: il risparmio reale è più basso di quello che indica il piano della banca."),
 p("Nel nostro esempio, con la riduzione della durata si perdono circa 1.630 euro di detrazione e il risparmio netto scende da 9.340 a circa <strong>7.710 euro</strong>. Con la riduzione della rata si perdono circa 600 euro, e il risparmio netto è di circa <strong>3.300 euro</strong>."),
 table("Dal risparmio lordo a quello netto",
   ["", "Riduci la durata", "Riduci la rata"],
   [["Interessi risparmiati", euro(9343) + " €", euro(3901) + " €"],
    ["Detrazione IRPEF persa", "− " + euro(1631) + " €", "− " + euro(600) + " €"],
    ["Risparmio netto", euro(7712) + " €", euro(3301) + " €"]],
   highlight_last=True,
   note="Detrazione del 19% sugli interessi pagati ogni anno, fino a 4.000 euro di interessi."),
 p("Come mai nel secondo caso la detrazione persa non arriva al 19% del risparmio? Perché il tetto di 4.000 euro vale ogni anno. Nei primi anni dell'esempio gli interessi superano i 4.000 euro, e la parte sopra il tetto non era comunque detraibile: risparmiarla non costa nulla in detrazione. Per questo il calcolo va fatto anno per anno."),
 p("Ricorda anche che la detrazione si recupera solo fino all'IRPEF che paghi. Se hai poca imposta da cui detrarre, ne recuperi meno, e il rientro anticipato conviene di più."),
 h2("Conviene davvero? Il confronto con le alternative"),
 p("Il risparmio di interessi funziona come un rendimento certo e senza rischio, pari al tasso del mutuo. Se però sei sotto il tetto della detrazione, quel rendimento va ridotto del 19%: un mutuo al 3,5% rende circa il 2,8% netto. È questo il numero da confrontare con le alternative, anch'esse al netto delle tasse. Per esempio un " + a('/news/etf-monetari-o-conto-deposito', 'conto deposito') + " al 3% lordo rende circa il 2,2% dopo l'imposta del 26%. Gli investimenti a lungo termine possono rendere di più, ma senza garanzie: è il confronto che approfondiamo in " + a('/news/estinzione-anticipata-mutuo-quando-conviene', 'quando conviene l\'estinzione anticipata') + "."),
 p("Prima di versare, verifica tre cose:"),
 ul("Hai un " + a('/news/fondo-di-emergenza-quanto-tenere-da-parte', 'fondo di emergenza') + " sufficiente? I soldi versati al mutuo non tornano indietro se ne hai bisogno.",
    "Il contratto prevede penali o importi minimi? E la banca permette di scegliere tra durata e rata?",
    "Hai debiti più cari, come prestiti personali o carte revolving? Di solito conviene estinguere prima quelli."),
 h2("Simula il tuo mutuo"),
 p("Con " + a('/mutui', 'la sezione mutui di Reflexi') + " puoi simulare il rientro anticipato sul tuo mutuo reale: versamenti una tantum o periodici, riduzione della durata o della rata, con nuova scadenza, nuova rata e interessi risparmiati. Il risparmio netto tiene già conto della detrazione persa anno per anno e di un'eventuale penale. Quando il versamento lo fai davvero, lo registri: debito residuo, piano di ammortamento e patrimonio netto si aggiornano da soli."),
])


if __name__ == '__main__':
    print(BODY)
