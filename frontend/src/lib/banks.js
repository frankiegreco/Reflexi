// Principali banche e neobanche operative in Italia, per l'icona del conto
// (favicon del sito reale, stesso meccanismo usato per gli esercenti).
export const KNOWN_BANKS = [
  { name: 'Intesa Sanpaolo', domain: 'intesasanpaolo.com', color: '#0a3d91' },
  { name: 'UniCredit', domain: 'unicredit.it', color: '#e2001a' },
  { name: 'BPER Banca', domain: 'bper.it', color: '#004a94' },
  { name: 'Banco BPM', domain: 'bancobpm.it', color: '#003057' },
  { name: 'Fineco', domain: 'finecobank.com', color: '#00549f' },
  { name: 'Poste Italiane (BancoPosta)', domain: 'poste.it', color: '#FFCC00' },
  { name: 'Credem', domain: 'credem.it', color: '#0066b3' },
  { name: 'Credit Agricole Italia', domain: 'credit-agricole.it', color: '#007A5E' },
  { name: 'MPS (Monte dei Paschi)', domain: 'mps.it', color: '#00447c' },
  { name: 'ING', domain: 'ing.it', color: '#FF6200' },
  { name: 'Revolut', domain: 'revolut.com', color: '#0666EB' },
  { name: 'N26', domain: 'n26.com', color: '#48AC93' },
  { name: 'Hype', domain: 'hype.it', color: '#00E6B4' },
  { name: 'Widiba', domain: 'widiba.it', color: '#8A2BE2' },
  { name: 'illimity Bank', domain: 'illimity.com', color: '#1A1A1A' },
  { name: 'Banca Sella', domain: 'sella.it', color: '#E4032E' },
  { name: 'Directa SIM', domain: 'directa.it', color: '#004A94' },
  { name: 'Degiro', domain: 'degiro.it', color: '#003D5B' },
  { name: 'Trade Republic', domain: 'traderepublic.com', color: '#000000' },
];

/** Trova la banca riconosciuta il cui nome e' contenuto nel testo dato (nome conto, nome istituto in una tabella tassi, ecc.). */
export function matchBank(text) {
  if (!text) return null;
  const n = text.toLowerCase();
  return KNOWN_BANKS.find((b) => n.includes(b.name.toLowerCase())) || null;
}
