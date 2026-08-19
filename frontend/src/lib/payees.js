// Esercenti comuni italiani, per compilare velocemente la descrizione di una
// transazione senza doverla scrivere da zero ogni volta. Suggeriamo anche la
// categoria più probabile (category_hint deve corrispondere al nome esatto
// di una categoria di default, altrimenti l'abbinamento automatico non scatta
// e l'utente sceglie comunque la categoria a mano — nessun problema).
//
// L'icona è la favicon del sito reale dell'esercente, presa tramite un
// servizio pubblico di favicon (lo stesso meccanismo usato da browser e
// password manager per mostrare l'icona di un sito qualsiasi) — non è un
// logo scaricato o ridistribuito da noi. Se il dominio non risultasse
// corretto o la favicon non si carichi, l'app mostra automaticamente un
// badge colorato con l'iniziale, mai un'icona rotta.
export const KNOWN_PAYEES = [
  // Carburante
  { name: 'Eni Station', category_hint: 'Trasporti', domain: 'eni.com', color: '#F7C325' },
  { name: 'Q8', category_hint: 'Trasporti', domain: 'q8.it', color: '#E30613' },
  { name: 'IP (Italiana Petroli)', category_hint: 'Trasporti', domain: 'ip-mobility.it', color: '#00843D' },
  { name: 'Tamoil', category_hint: 'Trasporti', domain: 'tamoil.it', color: '#004A93' },
  { name: 'Esso', category_hint: 'Trasporti', domain: 'esso.it', color: '#EC1C24' },
  { name: 'TotalEnergies', category_hint: 'Trasporti', domain: 'totalenergies.it', color: '#D2122E' },
  { name: 'Autostrade (pedaggio)', category_hint: 'Trasporti', domain: 'autostrade.it', color: '#00563F' },

  // Utenze / bollette
  { name: 'Enel Energia', category_hint: 'Casa e bollette', domain: 'enel.it', color: '#00843D' },
  { name: 'Eni Plenitude', category_hint: 'Casa e bollette', domain: 'eniplenitude.com', color: '#F7C325' },
  { name: 'A2A Energia', category_hint: 'Casa e bollette', domain: 'a2a.it', color: '#004B87' },
  { name: 'Iren', category_hint: 'Casa e bollette', domain: 'gruppoiren.it', color: '#00A0DC' },
  { name: 'Acea', category_hint: 'Casa e bollette', domain: 'acea.it', color: '#0072BC' },
  { name: 'Hera', category_hint: 'Casa e bollette', domain: 'gruppohera.it', color: '#00A9E0' },
  { name: 'Sorgenia', category_hint: 'Casa e bollette', domain: 'sorgenia.it', color: '#E4032E' },
  { name: 'Edison Energia', category_hint: 'Casa e bollette', domain: 'edison.it', color: '#003DA5' },

  // Telefonia / internet
  { name: 'TIM', category_hint: 'Casa e bollette', domain: 'tim.it', color: '#0026FF' },
  { name: 'Vodafone', category_hint: 'Casa e bollette', domain: 'vodafone.it', color: '#E60000' },
  { name: 'WindTre', category_hint: 'Casa e bollette', domain: 'windtre.it', color: '#F5852D' },
  { name: 'Iliad', category_hint: 'Casa e bollette', domain: 'iliad.it', color: '#EB1928' },
  { name: 'Fastweb', category_hint: 'Casa e bollette', domain: 'fastweb.it', color: '#00B4E6' },
  { name: 'PosteMobile', category_hint: 'Casa e bollette', domain: 'postemobile.it', color: '#FFCC00' },

  // Supermercati / spesa
  { name: 'Esselunga', category_hint: 'Spesa', domain: 'esselunga.it', color: '#E4032E' },
  { name: 'Coop', category_hint: 'Spesa', domain: 'e-coop.it', color: '#E4032E' },
  { name: 'Conad', category_hint: 'Spesa', domain: 'conad.it', color: '#E4002B' },
  { name: 'Carrefour', category_hint: 'Spesa', domain: 'carrefour.it', color: '#004E9F' },
  { name: 'Lidl', category_hint: 'Spesa', domain: 'lidl.it', color: '#0050AA' },
  { name: 'Eurospin', category_hint: 'Spesa', domain: 'eurospin.it', color: '#E4032E' },
  { name: 'Pam', category_hint: 'Spesa', domain: 'pampanorama.it', color: '#00A651' },
  { name: 'Iper, La grande i', category_hint: 'Spesa', domain: 'iper.it', color: '#E4032E' },
  { name: 'MD', category_hint: 'Spesa', domain: 'mdspa.it', color: '#E4002B' },
  { name: 'Penny Market', category_hint: 'Spesa', domain: 'penny.it', color: '#C8102E' },

  // Altro comune
  { name: 'Farmacia', category_hint: 'Salute', domain: null, color: '#00A651' },
  { name: 'Amazon', category_hint: 'Shopping', domain: 'amazon.it', color: '#FF9900' },
  { name: 'IKEA', category_hint: 'Shopping', domain: 'ikea.it', color: '#0058A3' },
  { name: 'Poste Italiane', category_hint: 'Altre uscite', domain: 'poste.it', color: '#FFCC00' },
];
