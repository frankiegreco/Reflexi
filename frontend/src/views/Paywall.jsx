import { useRef, useState } from 'react';
import { Capacitor } from '@capacitor/core';
import { apiSend } from '../api';
import { useAuth } from '../context/AuthContext';
import CheckoutForm from '../components/CheckoutForm';
import { usePricing, planLabels, PRICE_UNAVAILABLE } from '../lib/pricing';

// Nell'app nativa niente prezzi né pulsanti d'acquisto: l'abbonamento si
// gestisce solo dal sito web.
const isNative = Capacitor.isNativePlatform();

export default function Paywall() {
  const { user, logout } = useAuth();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [clientSecret, setClientSecret] = useState(null);
  // Blocca un secondo click prima che il re-render disabiliti il pulsante.
  const busy = useRef(false);
  const prices = planLabels(usePricing({ enabled: !isNative }));

  async function subscribe(plan) {
    if (busy.current) return;
    busy.current = true;
    setLoading(true);
    setError('');
    try {
      const res = await apiSend('stripe_checkout.php', 'POST', { plan });
      if (res.client_secret) {
        setClientSecret(res.client_secret);
        setLoading(false);
        busy.current = false;
      } else {
        window.location.href = res.url;
      }
    } catch (err) {
      setError(err.message);
      // Abbonamento già esistente (es. pagamento in ritardo): niente secondo
      // checkout, si va al portale per aggiornare il metodo di pagamento.
      if (err.code === 'subscription_exists') {
        try {
          const portal = await apiSend('stripe_portal.php', 'POST', {});
          window.location.href = portal.url;
          return;
        } catch (portalErr) {
          setError(portalErr.message);
        }
      }
      setLoading(false);
      busy.current = false;
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-bg p-4">
      <div className="w-full max-w-md">
        <div className="mb-6 flex items-center justify-center gap-2">
          <img src="/favicon.svg" alt="Reflexi" className="h-10 w-10 rounded-xl" />
          <span className="text-lg font-bold tracking-tight">Reflexi</span>
        </div>
        <div className="card p-6 text-center">
          <h2 className="mb-2 text-lg font-semibold">Il tuo periodo di prova è finito</h2>
          <p className="mb-6 text-sm text-muted">
            {isNative
              ? 'I tuoi dati sono al sicuro e ti aspettano.'
              : 'I tuoi dati sono al sicuro e ti aspettano — scegli un piano per continuare a usare Reflexi.'}
          </p>

          {isNative ? (
            <p className="text-sm text-muted">Gestisci il tuo abbonamento dal tuo account web.</p>
          ) : clientSecret ? (
            <CheckoutForm clientSecret={clientSecret} onCancel={() => setClientSecret(null)} />
          ) : (
            <>
              {error && <div className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{error}</div>}

              <div className="space-y-3">
                {!prices.showMonthly && !prices.showAnnual && (
                  <p className="text-sm text-muted">Gli abbonamenti non sono disponibili in questo momento. Riprova più tardi.</p>
                )}
                {prices.showMonthly && (
                  <button
                    onClick={() => subscribe('mensile')}
                    disabled={loading}
                    className="w-full rounded-xl border-2 border-accent bg-white px-4 py-4 text-left transition hover:bg-accentSoft"
                  >
                    <div className="flex items-baseline justify-between">
                      <span className="font-semibold">Mensile</span>
                      {prices.monthly
                        ? <span className="font-mono text-lg font-bold">{prices.monthly}<span className="text-xs font-normal text-muted">/mese</span></span>
                        : <span className="text-xs text-muted">{PRICE_UNAVAILABLE}</span>}
                    </div>
                  </button>
                )}
                {prices.showAnnual && (
                  <button
                    onClick={() => subscribe('annuale')}
                    disabled={loading}
                    className="relative w-full rounded-xl border-2 border-accent bg-accentSoft px-4 py-4 text-left transition hover:brightness-95"
                  >
                    {prices.monthsSaved && (
                      <span className="absolute -top-2.5 right-4 rounded-full bg-income px-2.5 py-0.5 text-[10px] font-bold text-white">
                        RISPARMI {prices.monthsSaved} {prices.monthsSaved === 1 ? 'MESE' : 'MESI'}
                      </span>
                    )}
                    <div className="flex items-baseline justify-between">
                      <span className="font-semibold">Annuale</span>
                      {prices.annual
                        ? <span className="font-mono text-lg font-bold">{prices.annual}<span className="text-xs font-normal text-muted">/anno</span></span>
                        : <span className="text-xs text-muted">{PRICE_UNAVAILABLE}</span>}
                    </div>
                    {prices.annualPerMonth && <div className="mt-0.5 text-right text-xs text-muted">circa {prices.annualPerMonth}/mese</div>}
                  </button>
                )}
              </div>

              {loading && <p className="mt-4 text-xs text-muted">Ti stiamo portando al pagamento sicuro...</p>}

              <div className="mt-5 flex items-center justify-center gap-1.5 text-xs text-muted">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Pagamento sicuro gestito da Stripe — non vediamo né conserviamo i dati della tua carta
              </div>
            </>
          )}

          <button onClick={logout} className="mt-6 text-xs text-muted hover:underline">
            Esci ({user?.email})
          </button>
        </div>
      </div>
    </div>
  );
}
