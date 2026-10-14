import { useEffect, useRef, useState } from 'react';

// Embedded form del Checkout Stripe (STRIPE_CHECKOUT_UI=form lato server).
// Stripe.js arriva da js.stripe.com via <script> in index.html, non dal bundle:
// Stripe chiede di non impacchettarlo né ospitarne una copia (PCI).
const PUBLISHABLE_KEY = import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY;

let stripeInstance = null;
function getStripe() {
  if (!stripeInstance) {
    stripeInstance = window.Stripe(PUBLISHABLE_KEY);
  }
  return stripeInstance;
}

export default function CheckoutForm({ clientSecret, onCancel }) {
  const containerRef = useRef(null);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!window.Stripe) { setError('Impossibile caricare il modulo di pagamento. Ricarica la pagina e riprova.'); return; }
    if (!PUBLISHABLE_KEY) { setError('Configurazione Stripe mancante (chiave pubblicabile).'); return; }

    let cancelled = false;
    const checkout = getStripe().initCheckoutFormSdk({ clientSecret });
    const form = checkout.createForm({ layout: 'expanded' });
    form.mount(containerRef.current);

    checkout.loadActions().then((result) => {
      if (cancelled) return;
      if (result.type !== 'success') {
        setError(result.error?.message || 'Impossibile avviare il pagamento.');
        return;
      }
      // Dopo il pagamento Stripe reindirizza al return_url della sessione
      // (/app/?checkout=return), dove AuthContext ricarica auth/me.php.
      form.on('confirm', async (event) => {
        setError('');
        try {
          await result.actions.confirm({ formConfirmEvent: event });
        } catch (err) {
          setError(err.message || 'Errore durante la conferma del pagamento.');
        }
      });
    });

    return () => {
      cancelled = true;
      form.destroy();
    };
  }, [clientSecret]);

  return (
    <div className="text-left">
      {error && <div className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{error}</div>}
      <div ref={containerRef} />
      {onCancel && (
        <button onClick={onCancel} className="mt-4 w-full text-center text-xs text-muted hover:underline">
          Torna alla scelta del piano
        </button>
      )}
    </div>
  );
}
