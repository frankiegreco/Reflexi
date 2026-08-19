# Roadmap — Sparagno

Nome di lavoro confermato: **Sparagno**. Web app di finanza personale con
mutui (piano di ammortamento, quota di cointestazione), investimenti (prezzi
live, costo medio), budget, e analisi predittiva (medie storiche, Monte Carlo,
rilevamento automatico di ricorrenze nascoste). Backend PHP + MariaDB,
frontend React + Tailwind, multi-utente con autenticazione a sessione.

Nessun concorrente trovato combina questi tre ambiti (mutuo + investimenti +
analisi predittiva) nello stesso prodotto — questo resta il posizionamento
distintivo.

---

## Fase 1 — Completare i test dell'ambiente attuale (PROSSIMO PASSO)

Tutto il resto dipende da qui: prima di pensare a hosting/pagamenti, va
verificato che quello che abbiamo costruito funzioni davvero end-to-end.

- [ ] `npm install` + `npm run build` nel frontend, verificato senza errori
- [ ] Copia della build in `webroot/`, verificato che la schermata di login
      compaia (non più la vecchia interfaccia Bootstrap)
- [ ] `composer install` in `webroot/` per PHPMailer
- [ ] Migration `005_auth_multitenancy.sql` applicata
- [ ] Utente legacy: email aggiornata + password impostata via "password
      dimenticata"
- [ ] SMTP configurato (consigliato: Mailtrap per i test, poi Brevo/Gmail)
- [ ] Flusso completo testato: registrazione → email di verifica → login →
      dati isolati per utente

## Fase 2 — Sicurezza prima di aprire a utenti reali

- [ ] Validazione incrociata: verificare che `account_id`/`category_id`/ecc.
      passati dal client appartengano davvero all'utente loggato (limite
      segnalato in precedenza, da chiudere prima del lancio)
- [ ] Rate limiting su login/registrazione (protezione da tentativi ripetuti)
- [ ] Verifica cookie di sessione in produzione (flag `Secure` quando c'è HTTPS)
- [ ] Repository/backup del codice sorgente in un posto sicuro (non solo il
      tuo PC)

## Fase 3 — Infrastruttura di produzione

- [ ] Registrazione dominio (`sparagno.it`, eventualmente anche `.app`)
- [ ] VPS (Hetzner CX22 o DigitalOcean equivalente, 2 vCPU/4GB indicati in
      precedenza per la fase promo)
- [ ] Reverse proxy con HTTPS automatico (Caddy consigliato, si integra bene
      col Docker Compose che hai già)
- [ ] Deploy dello stack Docker Compose esistente sul VPS
- [ ] SMTP di produzione (Brevo piano gratuito copre il lancio promo)
- [ ] Backup automatici schedulati (mysqldump + storage esterno tipo Hetzner
      Storage Box o Backblaze)

## Fase 4 — Pagamenti e modello di trial

- [ ] Integrazione Stripe per abbonamento ricorrente
- [ ] Campi su `users`: stato trial/abbonamento (`trialing` / `active` /
      `scaduto`), data fine trial
- [ ] Logica di accesso: primo mese gratis, poi blocco se non c'è abbonamento
      attivo
- [ ] Pagina pubblica di pricing

## Fase 5 — Legale e conformità

- [ ] Termini di Servizio
- [ ] Privacy Policy (GDPR — dati finanziari, attenzione particolare qui)
- [ ] Cookie policy se servono cookie non tecnici

## Fase 6 — Branding e lancio

- [ ] Identità visiva coerente col naming (palette, font, eventuale mascotte
      ispirata al proverbio "chi sparagna, la gatta magna")
- [ ] Landing page pubblica
- [ ] Lancio promo: primo mese di accesso gratuito, poi abbonamento

---

## Completato (ex-backlog)

- **App mobile**: Capacitor integrato (`frontend/android/`), WebView punta a
  `https://reflexi.it/app/`, gestione status bar e tasto indietro nativo. Vedi
  sezione dedicata in CLAUDE.md.
- **Preventivatore mutui**: sezione "Comparatore" (`Comparator.jsx` +
  `reference_rates.php` + tabella `reference_mortgage_rates`) — calcolatore
  interno su tabella di riferimento aggiornata manualmente (non dati Races),
  con ricalcolo rata reale, LTV, margine d'età, sostenibilità reddito.
- **Storico prezzi investimenti**: grafico andamento portafoglio e per
  singolo strumento (`price_history.php`, `portfolio_history.php`, UI in
  `Investments.jsx`).
- **Calcolo imposte su plusvalenze**: aliquota configurabile per strumento
  (`tax_rate`, default 26%), imposta calcolata e trattenuta ad ogni vendita
  (`investments.php`, UI in `Investments.jsx`).

## Backlog — in standby, da riprendere quando serve

- Conversione multi-valuta automatica nei totali di portafoglio (oggi restano
  separati per valuta di proposito)

---

## Note di metodo

- Ogni fase presuppone quella precedente sostanzialmente conclusa — in
  particolare, non ha senso investire in pagamenti/legale prima che
  l'autenticazione sia verificata stabile in Fase 1.
- Gli item di Fase 2 (sicurezza) sono bloccanti prima di aprire la
  registrazione a sconosciuti, non prima dei tuoi test personali.
