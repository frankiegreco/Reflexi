// Riconoscimento del marchio per uno strumento finanziario — copertura
// volutamente parziale (i ticker possibili sono migliaia, non un elenco
// finito come per conti/esercenti). Copriamo i principali emittenti ETF
// (riconosciuti dal nome dello strumento) e le big cap azionarie più comuni
// (per ticker) — tutto il resto mostra il badge colorato con l'iniziale.

// Emittenti ETF: abbinamento per parola chiave contenuta nel NOME dello
// strumento (i nomi degli ETF includono quasi sempre il marchio dell'emittente).
const ETF_ISSUERS = [
  { keyword: 'vanguard', domain: 'vanguard.com', color: '#96272D' },
  { keyword: 'ishares', domain: 'ishares.com', color: '#00539B' },
  { keyword: 'xtrackers', domain: 'xtrackers.com', color: '#00786A' },
  { keyword: 'amundi', domain: 'amundi.com', color: '#00558C' },
  { keyword: 'lyxor', domain: 'lyxoretf.com', color: '#E2001A' },
  { keyword: 'spdr', domain: 'ssga.com', color: '#FF6600' },
  { keyword: 'invesco', domain: 'invesco.com', color: '#001489' },
  { keyword: 'wisdomtree', domain: 'wisdomtree.com', color: '#003DA5' },
  { keyword: 'hsbc', domain: 'hsbc.com', color: '#DB0011' },
  { keyword: 'fidelity', domain: 'fidelity.com', color: '#4D8B31' },
];

// Big cap azionarie comuni, per ticker (senza suffisso di borsa tipo .MI/.US).
const KNOWN_STOCKS = {
  AAPL: { domain: 'apple.com', color: '#555555' },
  MSFT: { domain: 'microsoft.com', color: '#00A4EF' },
  GOOGL: { domain: 'google.com', color: '#4285F4' },
  GOOG: { domain: 'google.com', color: '#4285F4' },
  AMZN: { domain: 'amazon.com', color: '#FF9900' },
  META: { domain: 'meta.com', color: '#0668E1' },
  NVDA: { domain: 'nvidia.com', color: '#76B900' },
  TSLA: { domain: 'tesla.com', color: '#CC0000' },
  NFLX: { domain: 'netflix.com', color: '#E50914' },
  DIS: { domain: 'disney.com', color: '#113CCF' },
  KO: { domain: 'coca-cola.com', color: '#F40009' },
  NKE: { domain: 'nike.com', color: '#111111' },
  SBUX: { domain: 'starbucks.com', color: '#00704A' },
  V: { domain: 'visa.com', color: '#1A1F71' },
  MA: { domain: 'mastercard.com', color: '#EB001B' },
  JPM: { domain: 'jpmorganchase.com', color: '#5A2D81' },
  ENI: { domain: 'eni.com', color: '#F7C325' },
  ENEL: { domain: 'enel.com', color: '#00843D' },
  ISP: { domain: 'intesasanpaolo.com', color: '#0a3d91' },
  UCG: { domain: 'unicredit.it', color: '#e2001a' },
  STM: { domain: 'st.com', color: '#03234B' },
  RACE: { domain: 'ferrari.com', color: '#D40000' },
};

export function matchInstrumentBrand(instrument) {
  if (!instrument) return null;
  const name = (instrument.name || '').toLowerCase();
  const issuer = ETF_ISSUERS.find((i) => name.includes(i.keyword));
  if (issuer) return { domain: issuer.domain, color: issuer.color };

  const baseTicker = (instrument.ticker || '').split('.')[0].split('-')[0].toUpperCase();
  if (KNOWN_STOCKS[baseTicker]) return KNOWN_STOCKS[baseTicker];

  return null;
}
