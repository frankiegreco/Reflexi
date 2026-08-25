# Specifica: Piano Intelligente per gli Obiettivi di Risparmio

## L'idea

Estendere la funzionalità "Obiettivi di risparmio" già esistente: oggi mostra
solo il progresso reale verso un obiettivo. L'aggiunta è un **piano
suggerito** per raggiungerlo, basato sui dati reali dell'utente (non su medie
generiche o benchmark inventati) — quanto risparmi in media, quali categorie
di spesa sono sopra la tua norma recente, e cosa cambierebbe investendo
invece di tenere tutto liquido.

## Principio importante da rispettare

**Nessun consiglio di investimento con certezza finta.** I rendimenti attesi
devono essere ipotesi che l'utente può modificare (come già facciamo in FIRE
e nel Comparatore), mai numeri presentati come garantiti. Niente titoli o
fondi specifici consigliati per nome — solo categorie di asset generiche
("un ETF azionario diversificato", non "compra VWCE"). I confronti di spesa
per categoria vanno fatti **contro lo storico reale dello stesso utente**,
mai contro medie di popolazione che non abbiamo modo di verificare.

---

## 1. Dove vive: dentro Goals.jsx, non una sezione nuova

Ogni obiettivo esistente (in `savings_goals`) guadagna un pulsante **"Piano
per raggiungerlo"** che apre un pannello/modale con l'analisi. Non serve una
nuova tabella per gli obiettivi stessi — tutto il resto si calcola al volo.

## 2. Nuovo endpoint: `api/goal_plan.php`

**GET** `?goal_id=X&expected_return=Y` (return opzionale, default 5%)

Calcola e restituisce:

```
{
  "target_amount": ...,          // dall'obiettivo esistente
  "current_amount": ...,         // progresso reale gia' calcolato altrove, riusa la stessa logica
  "remaining_amount": target - current,

  "avg_monthly_savings": ...,    // media entrate-uscite ultimi 6 mesi, STESSA LOGICA gia' presente in fire.php — riusala, non riscriverla

  "scenario_savings_only": {
     "months_to_goal": remaining_amount / avg_monthly_savings   // proiezione lineare semplice
  },
  "scenario_invested": {
     "expected_return": Y,
     "months_to_goal": ...   // stessa formula di crescita composta usata in Fire.jsx/pension_funds, con versamento mensile = avg_monthly_savings
  },

  "category_opportunities": [
     // categorie di spesa dove la media ULTIMI 2 MESI supera la media dei
     // 6 mesi precedenti di almeno il 15% — segnala solo quelle, non tutte
     { "category_name": "Ristoranti", "recent_avg": 220, "baseline_avg": 150, "potential_monthly_saving": 70 }
  ],
  "scenario_with_adjustment": {
     // se venisse recuperato il 50% del potenziale risparmio totale dalle
     // categorie sopra, sommato al risparmio medio attuale
     "extra_monthly": sum(potential_monthly_saving) * 0.5,
     "months_to_goal": ...  // stessa proiezione investita, ma con importo mensile piu' alto
  }
}
```

### Query per `category_opportunities`

Per ogni categoria di spesa dell'utente:
- `recent_avg` = spesa media mensile ultimi 2 mesi
- `baseline_avg` = spesa media mensile dei 6 mesi precedenti a quei 2 (non sovrapposti)
- Includi la categoria nel risultato solo se `recent_avg > baseline_avg * 1.15` E `baseline_avg > 0` (evita falsi positivi su categorie usate raramente)
- `potential_monthly_saving` = `recent_avg - baseline_avg`
- Ordina per `potential_monthly_saving` decrescente, mostra al massimo le prime 3

## 3. Frontend: pannello "Piano per raggiungerlo" in Goals.jsx

Layout suggerito:

1. **Riepilogo in cima**: quanto manca (`remaining_amount`), risparmio medio mensile attuale
2. **Due card affiancate** (come lo stile già usato nel Comparatore/FIRE):
   - "Solo risparmiando" → mesi/anni stimati, con importo `avg_monthly_savings`
   - "Investendo il risparmio" → mesi/anni stimati, con un campo dove l'utente può cambiare il **rendimento atteso (%)** e vedere il numero aggiornarsi (esattamente come già succede in FIRE)
3. **Se `category_opportunities` non è vuoto**, una sezione "Dove potresti recuperare margine":
   - Per ogni categoria: nome, quanto stai spendendo di più rispetto alla tua norma recente, quanto libereresti al mese
   - Sotto, un terzo scenario "Con questo aggiustamento": mostra quanto si accorcerebbe il tempo per raggiungere l'obiettivo se recuperassi metà di quel margine e lo investissi
4. **Disclaimer visibile**, stesso tono già usato altrove: *"Proiezione basata sul tuo storico recente, non una promessa — i mercati reali oscillano e le tue spese possono cambiare. Non sostituisce una consulenza finanziaria personalizzata."*

## Riuso di codice esistente — non reinventare

- La formula di crescita composta con versamento mensile: **già scritta** in
  `fire.php` (proiezione anni al FIRE) e in `pension_funds.php`
  (`project_pension`) — estrarla in una funzione condivisa se non lo è già,
  invece di riscriverla una terza volta
- Il calcolo di `avg_monthly_savings`: **stessa query** già presente in
  `fire.php` (spesa/entrata media ultimi mesi, esclusi trasferimenti e
  investimenti) — riusala identica
- Lo stile delle card a doppio scenario con campo rendimento modificabile:
  stesso pattern già in `Fire.jsx`

## Casi limite

- Obiettivo già raggiunto (`remaining_amount <= 0`): mostra un messaggio di
  congratulazioni invece del piano, niente calcoli
- `avg_monthly_savings <= 0` (l'utente spende più di quanto guadagna in
  media): non mostrare una proiezione con mesi negativi o infiniti — mostra
  un messaggio chiaro tipo *"Al ritmo attuale l'obiettivo non è
  raggiungibile — prova ad esplorare le categorie qui sotto per trovare
  margine"*, e salta dritto alla sezione `category_opportunities`
- Meno di 6 mesi di storico transazioni disponibili: usa tutto lo storico
  disponibile invece di richiederne esattamente 6, con una nota che i numeri
  sono ancora poco affidabili (stesso avviso già usato in Fire.jsx)
