import { useState, useEffect } from 'react';
import { Menu, X } from 'lucide-react';

// Navbar del sito pubblico, mostrata sopra la pagina di login dell'app.
// Replica webroot/_nav.php (stesse voci, stesso stile, stesso overlay mobile):
// se cambi le voci lì, aggiornale anche qui.
const NAV_ITEMS = [
  ['/mutui.php', 'Mutui'],
  ['/investimenti.php', 'Investimenti'],
  ['/mercati.php', 'Mercati'],
  ['/budget.php', 'Budget'],
  ['/analisi.php', 'Analisi predittiva'],
  ['/calcolatori.php', 'Calcolatori'],
  ['/news', 'News'],
  ['/piani.php', 'Piani'],
  ['/about.php', 'Chi siamo'],
  ['/faq.php', 'FAQ'],
];

const XL = 1280; // breakpoint xl di Tailwind, come in _nav.php

function Logo() {
  return (
    <a href="/" className="flex flex-shrink-0 items-center gap-2.5">
      <img src="/favicon.svg" alt="Reflexi" className="h-9 w-9 rounded-xl" />
      <span className="text-lg font-bold tracking-tight">Reflexi</span>
    </a>
  );
}

// `cta` = { label, onClick }: nel login il pulsante a destra non può essere
// "Accedi" come nel sito (ci sei già), quindi lo decide la pagina.
export default function SiteNav({ cta }) {
  const [open, setOpen] = useState(false);

  // Come in _nav.php: blocca lo scroll sotto l'overlay, chiude con Esc e
  // quando si passa a desktop.
  useEffect(() => {
    if (!open) return;
    document.documentElement.style.overflow = 'hidden';
    const onKey = (e) => { if (e.key === 'Escape') setOpen(false); };
    const onResize = () => { if (window.innerWidth >= XL) setOpen(false); };
    window.addEventListener('keydown', onKey);
    window.addEventListener('resize', onResize);
    return () => {
      document.documentElement.style.overflow = '';
      window.removeEventListener('keydown', onKey);
      window.removeEventListener('resize', onResize);
    };
  }, [open]);

  const ctaButton = (className) => (
    <button type="button" onClick={() => { setOpen(false); cta.onClick(); }} className={className}>
      {cta.label}
    </button>
  );

  return (
    <>
      <header className="sticky top-0 z-50 border-b border-border bg-white/80 backdrop-blur-md">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
          <Logo />
          <nav className="hidden items-center gap-3 text-sm font-medium xl:flex">
            {NAV_ITEMS.map(([href, label]) => (
              <a key={href} href={href} className="whitespace-nowrap text-gray-600 hover:text-accent">{label}</a>
            ))}
          </nav>
          <div className="flex flex-shrink-0 items-center gap-3">
            {ctaButton('whitespace-nowrap rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-accent/25 transition hover:bg-accentDark hover:shadow-accent/40')}
            <button
              type="button"
              onClick={() => setOpen(true)}
              className="-mr-1 flex h-10 w-10 items-center justify-center text-gray-700 xl:hidden"
              aria-label="Apri il menu"
              aria-expanded={open}
            >
              <Menu size={26} />
            </button>
          </div>
        </div>
      </header>

      {open && (
        <div className="fixed inset-0 z-[60] xl:hidden">
          <div className="flex h-full max-h-full flex-col bg-bg">
            <div className="flex flex-shrink-0 items-center justify-between border-b border-border bg-white px-6 py-4">
              <Logo />
              <button
                type="button"
                onClick={() => setOpen(false)}
                className="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-700"
                aria-label="Chiudi il menu"
              >
                <X size={20} />
              </button>
            </div>
            <nav className="min-h-0 flex-1 overflow-y-auto overscroll-contain px-6 py-5">
              <div className="flex flex-col gap-2">
                {NAV_ITEMS.map(([href, label]) => (
                  <a key={href} href={href} className="rounded-xl border border-border bg-white px-4 py-3 text-base font-medium text-gray-700">{label}</a>
                ))}
              </div>
            </nav>
            <div className="flex-shrink-0 border-t border-border bg-white px-6 py-4 pb-[calc(1rem_+_env(safe-area-inset-bottom))]">
              {ctaButton('block w-full rounded-xl bg-accent px-4 py-3.5 text-center text-base font-semibold text-white shadow-lg shadow-accent/25')}
            </div>
          </div>
        </div>
      )}
    </>
  );
}
