import { useEffect, useState } from 'react';
import { apiGet } from '../api';

// Prezzi dei piani letti da Stripe via api/pricing.php (stessa fonte di
// piani.php). Se mancano, chi li mostra scrive "prezzo visibile al checkout":
// mai cifre scritte a mano.
export const PRICE_UNAVAILABLE = 'prezzo visibile al checkout';

export function usePricing({ enabled = true } = {}) {
  const [pricing, setPricing] = useState(null);
  useEffect(() => {
    if (!enabled) return;
    let cancelled = false;
    apiGet('pricing.php')
      .then((p) => { if (!cancelled) setPricing(p); })
      .catch(() => {});
    return () => { cancelled = true; };
  }, [enabled]);
  return pricing;
}

// 7.99 -> "7,99€", 79 -> "79€"; "+ IVA" se il prezzo Stripe è al netto (tax_behavior exclusive).
export function formatPlanPrice(amount, currency, taxBehavior) {
  if (amount == null) return null;
  const decimals = Number.isInteger(amount) ? 0 : 2;
  const num = amount.toLocaleString('it-IT', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
  const symbol = (currency || '').toLowerCase() === 'eur' ? '€' : ` ${(currency || '').toUpperCase()}`;
  return num + symbol + (taxBehavior === 'exclusive' ? ' + IVA' : '');
}

// Etichette pronte per Paywall/Profilo: { monthly, annual, annualPerMonth, monthsSaved }.
// monthly/annual sono null se il prezzo non è disponibile.
export function planLabels(pricing) {
  const tax = pricing?.tax_behavior;
  const m = pricing?.monthly;
  const a = pricing?.annual;
  return {
    monthly: m ? formatPlanPrice(m.amount, m.currency, tax) : null,
    annual: a ? formatPlanPrice(a.amount, a.currency, tax) : null,
    annualPerMonth: a && pricing.annual_per_month != null ? formatPlanPrice(pricing.annual_per_month, a.currency, tax) : null,
    monthsSaved: pricing?.months_saved >= 1 ? pricing.months_saved : null,
  };
}
