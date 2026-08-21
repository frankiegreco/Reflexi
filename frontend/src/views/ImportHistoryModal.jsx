import { useEffect, useState } from 'react';
import { Trash2 } from 'lucide-react';
import Modal from '../components/Modal';
import { apiGet, apiDelete } from '../api';
import { formatDate } from '../lib/format';

export default function ImportHistoryModal({ open, onClose, onUndo }) {
  const [batches, setBatches] = useState([]);
  const [loading, setLoading] = useState(false);

  async function load() {
    setLoading(true);
    const data = await apiGet('import.php?action=batches');
    setBatches(data);
    setLoading(false);
  }

  useEffect(() => { if (open) load(); }, [open]);

  async function handleUndo(b) {
    if (!confirm(`Annullare l'import "${b.source}" su ${b.account_name} del ${formatDate(b.created_at.slice(0, 10))}? Verranno eliminate ${b.imported_count} transazioni.`)) return;
    await apiDelete(`import.php?action=batch&id=${b.id}`);
    await load();
    onUndo?.();
  }

  return (
    <Modal open={open} onClose={onClose} title="Cronologia importazioni" size="lg">
      {loading ? (
        <p className="text-sm text-muted">Caricamento...</p>
      ) : batches.length === 0 ? (
        <p className="text-sm text-muted">Nessuna importazione effettuata finora.</p>
      ) : (
        <div className="space-y-2">
          {batches.map((b) => (
            <div key={b.id} className="flex items-center justify-between rounded-lg border border-border p-3 text-sm">
              <div>
                <div className="font-semibold">{b.source} — {b.account_name}</div>
                <div className="text-xs text-muted">
                  {formatDate(b.created_at.slice(0, 10))} · {b.imported_count} importate
                  {b.duplicate_count > 0 && `, ${b.duplicate_count} duplicati saltati`}
                  {b.error_count > 0 && `, ${b.error_count} errori`}
                  {b.filename && ` · ${b.filename}`}
                </div>
              </div>
              <button className="text-muted hover:text-expense" title="Annulla import" onClick={() => handleUndo(b)}>
                <Trash2 size={16} />
              </button>
            </div>
          ))}
        </div>
      )}
    </Modal>
  );
}
