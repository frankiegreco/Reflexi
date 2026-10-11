import { useState, useEffect, useRef } from 'react';
import { Menu, X, ChevronDown } from 'lucide-react';

// Navbar del sito pubblico, mostrata sopra la pagina di login dell'app.
// Replica webroot/_nav.php (stesse voci, stesso stile, stesso overlay mobile):
// se cambi le voci lì, aggiornale anche qui. APP_ITEMS sono le funzioni
// dell'app, TOOL_ITEMS gli strumenti gratuiti nella tendina "Strumenti".
const APP_ITEMS = [
  ['/mutui.php', 'Mutui'],
  ['/investimenti.php', 'Investimenti'],
  ['/budget.php', 'Budget'],
  ['/analisi.php', 'Analisi predittiva'],
];
const TOOL_ITEMS = [
  ['/mercati.php', 'Mercati', 'Andamenti di azioni, ETF e crypto'],
  ['/calcolatori.php', 'Calcolatori', 'Interesse composto, pensione, inflazione e altri'],
];
const SITE_ITEMS = [
  ['/news', 'News'],
  ['/piani.php', 'Piani'],
  ['/about.php', 'Chi siamo'],
  ['/faq.php', 'FAQ'],
];

const linkCls = 'whitespace-nowrap text-gray-600 hover:text-accent';
const mobileLinkCls = 'rounded-xl border border-border bg-white px-4 py-3 text-base font-medium text-gray-700';

// Tendina desktop: si apre al passaggio del mouse e al click/tap, si chiude
// con un click fuori o con Esc. Hover e click sono stati separati: così un
// click dopo l'hover non la richiude, e sul touch (niente hover vero, solo
// pointerType 'touch') il tap la apre e la chiude.
function ToolsMenu() {
  const [hover, setHover] = useState(false);
  const [clicked, setClicked] = useState(false);
  const open = hover || clicked;
  const setOpen = (v) => { setHover(v); setClicked(v); };
  const ref = useRef(null);

  useEffect(() => {
    if (!open) return;
    const onClick = (e) => { if (!ref.current?.contains(e.target)) setOpen(false); };
    const onKey = (e) => { if (e.key === 'Escape') setOpen(false); };
    document.addEventListener('click', onClick);
    window.addEventListener('keydown', onKey);
    return () => {
      document.removeEventListener('click', onClick);
      window.removeEventListener('keydown', onKey);
    };
  }, [open]);

  return (
    <div
      ref={ref}
      className="relative"
      onPointerEnter={(e) => { if (e.pointerType === 'mouse') setHover(true); }}
      onPointerLeave={(e) => { if (e.pointerType === 'mouse') setHover(false); }}
    >
      <button
        type="button"
        onClick={() => setClicked((c) => !c)}
        className={`flex items-center gap-1 ${linkCls}`}
        aria-haspopup="true"
        aria-expanded={open}
      >
        Strumenti <ChevronDown size={14} className={`transition ${open ? 'rotate-180' : ''}`} />
      </button>
      <div className={`absolute left-1/2 top-full z-50 -translate-x-1/2 pt-3 transition ${open ? 'visible opacity-100' : 'invisible opacity-0'}`}>
        <div className="w-80 rounded-xl border border-border bg-white p-2 shadow-lg">
          {TOOL_ITEMS.map(([href, label, desc]) => (
            <a key={href} href={href} className="block rounded-lg px-3 py-2.5 hover:bg-accentSoft">
              <span className="block font-semibold text-gray-900">{label}</span>
              <span className="block text-xs font-normal text-muted">{desc}</span>
            </a>
          ))}
        </div>
      </div>
    </div>
  );
}

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
            {APP_ITEMS.map(([href, label]) => <a key={href} href={href} className={linkCls}>{label}</a>)}
            <ToolsMenu />
            {SITE_ITEMS.map(([href, label]) => <a key={href} href={href} className={linkCls}>{label}</a>)}
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
                {APP_ITEMS.map(([href, label]) => <a key={href} href={href} className={mobileLinkCls}>{label}</a>)}
                <div className="mt-3 px-1 text-xs font-semibold uppercase tracking-wide text-muted">Strumenti</div>
                {TOOL_ITEMS.map(([href, label, desc]) => (
                  <a key={href} href={href} className={mobileLinkCls}>
                    {label}
                    <span className="block text-xs font-normal text-muted">{desc}</span>
                  </a>
                ))}
                <div className="mt-3" />
                {SITE_ITEMS.map(([href, label]) => <a key={href} href={href} className={mobileLinkCls}>{label}</a>)}
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
