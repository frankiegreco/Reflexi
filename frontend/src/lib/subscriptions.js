// Elenco di servizi in abbonamento comuni, per compilare velocemente una
// ricorrenza senza dover scrivere tutto a mano.
//
// Le icone arrivano da Simple Icons (simpleicons.org), un progetto open
// source di icone semplificate per l'identificazione dei brand — non sono i
// loghi ufficiali delle aziende (che restano loro marchi registrati), ma
// rappresentazioni riconoscibili pensate apposta per questo uso. Alcuni brand
// (es. Disney) non sono presenti nella libreria per scelta dei suoi
// mantenitori proprio per prudenza sui marchi: in quei casi, e ovunque
// un'icona non si carichi, l'app mostra un badge colorato con l'iniziale del
// servizio invece di un'icona mancante o rotta.
export const KNOWN_SUBSCRIPTIONS = [
  { name: 'Netflix', hint: '~13-18€/mese', slug: 'netflix', color: '#E50914' },
  { name: 'Amazon Prime', hint: '~5€/mese o ~50€/anno', slug: 'amazon', color: '#FF9900' },
  { name: 'Spotify', hint: '~11€/mese', slug: 'spotify', color: '#1DB954' },
  { name: 'Disney+', hint: '~9-12€/mese', slug: null, color: '#113CCF' },
  { name: 'YouTube Premium', hint: '~13€/mese', slug: 'youtube', color: '#FF0000' },
  { name: 'Apple Music', hint: '~11€/mese', slug: 'applemusic', color: '#FA243C' },
  { name: 'Apple iCloud+', hint: '~1-10€/mese', slug: 'icloud', color: '#3693F3' },
  { name: 'DAZN', hint: '~35-40€/mese', slug: 'dazn', color: '#F8F8F5' },
  { name: 'Sky / NOW', hint: 'variabile', slug: null, color: '#00246B' },
  { name: 'PlayStation Plus', hint: '~9-17€/mese', slug: 'playstation', color: '#003791' },
  { name: 'Xbox Game Pass', hint: '~13-18€/mese', slug: 'xbox', color: '#107C10' },
  { name: 'Audible', hint: '~10€/mese', slug: 'audible', color: '#F8991C' },
  { name: 'Google One', hint: '~2-10€/mese', slug: null, color: '#4285F4' },
  { name: 'Dropbox', hint: '~10€/mese', slug: 'dropbox', color: '#0061FF' },
  { name: 'Adobe Creative Cloud', hint: '~24-60€/mese', slug: 'adobecreativecloud', color: '#DA1F26' },
  { name: 'Palestra / Fitness', hint: 'variabile', slug: null, color: '#4f46e5' },
  { name: 'Altro abbonamento', hint: '', slug: null, color: '#8b95a1' },
];
