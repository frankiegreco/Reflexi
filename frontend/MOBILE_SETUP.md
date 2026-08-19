# Reflexi su Android — guida Capacitor

Questa guida presuppone che tu abbia già Node.js installato (lo usi già per
compilare il frontend). Tutti i comandi vanno lanciati da dentro la cartella
`frontend/`, sul tuo PC — non sul VPS.

## 1. Installa Android Studio

Scarica da https://developer.android.com/studio (gratuito). Include l'SDK
Android e Java — non serve installare altro a parte.

Alla prima apertura, lascialo scaricare i componenti che propone di default
(SDK, emulatore, ecc.) — ci mette qualche minuto.

## 2. Installa le dipendenze Capacitor

```powershell
cd frontend
npm install
```

## 3. Aggiungi la piattaforma Android

```powershell
npx cap add android
```

Questo crea una cartella `frontend/android/` — un intero progetto Android
Studio. Non c'è bisogno di modificarlo a mano per iniziare: la configurazione
in `capacitor.config.json` (già pronta) dice all'app di caricare
`https://reflexi.it/app/` — quindi ogni aggiornamento che fai al sito si
riflette subito anche nell'app, senza dover ricompilare/ripubblicare nulla.

## 4. Apri il progetto in Android Studio

```powershell
npx cap open android
```

Si apre Android Studio con il progetto pronto.

## 5. Icona dell'app

Ho preparato un'icona sorgente ad alta risoluzione: `frontend/android-icon-source.png`.

In Android Studio:
1. Click destro sulla cartella `app/res` nel pannello di sinistra
2. **New → Image Asset**
3. Come "Foreground Layer", scegli il file `android-icon-source.png`
4. Segui la procedura guidata (genera automaticamente tutte le densità/dimensioni necessarie)

## 6. Testa l'app

Con un emulatore Android (Android Studio te ne propone di crearne uno alla
prima apertura) o un telefono Android collegato via USB (con debug USB attivo
nelle impostazioni sviluppatore del telefono):
- Premi il tasto ▶ verde in alto in Android Studio

Dovresti vedere la landing di Reflexi caricarsi dentro l'app — è lo stesso
sito, solo dentro un contenitore nativo installabile.

## 7. Pubblicazione su Google Play — cosa serve prima

- **Account Google Play Console**: https://play.google.com/console, **25$ una tantum**
- **Build firmata**: Android Studio → Build → Generate Signed Bundle/APK. La prima volta
  ti fa creare un file "keystore" (una specie di password permanente per firmare le
  tue app) — **conservalo con estrema cura e fanne un backup**: se lo perdi, non
  potrai più pubblicare aggiornamenti alla stessa app, dovresti crearne una nuova da zero.
- **URL della Privacy Policy**: Google Play la richiede obbligatoriamente per
  qualsiasi app che tratta dati personali (il tuo caso, essendo dati
  finanziari) — è lo stesso documento ancora da scrivere, segnalato più volte
  nella roadmap. Va pubblicato come pagina web raggiungibile (es.
  `reflexi.it/privacy`) prima di poter sottomettere l'app.
- **Screenshot e descrizione** per la scheda dello store — quando arrivi lì, posso aiutarti a scriverli.

## Nota sulla revisione Apple (per quando vorrai fare anche iOS)

Google Play accetta esplicitamente questo tipo di app (le chiama "Trusted Web
Activity"). Apple è più severa e a volte respinge app percepite come "solo un
sito in un contenitore" senza funzionalità native aggiuntive — da tenere a
mente se in futuro estendi a iOS, magari aggiungendo qualche funzione nativa
reale (notifiche push, login biometrico) prima di sottomettere.
