<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

// Importazione massiva di transazioni da CSV (export di Trade Republic o di
// qualsiasi altra banca/broker). Non esiste un formato CSV universale, quindi
// il flusso e' sempre: 1) 'parse' analizza il file e propone un mapping
// colonne->campi + i valori distinti di ogni colonna (utile per la colonna
// "tipo" di export come quello di Trade Republic, che mescola movimenti di
// cassa con operazioni di trading — l'utente decide riga per valore quali
// tipi diventano entrata/uscita e quali vanno ignorati, invece di indovinare
// nomi di colonna specifici di un singolo broker che potrebbero cambiare).
// 2) 'commit' rifà il parsing con il mapping confermato e inserisce le righe
// valide in un'unica transazione DB, agganciate a un import_batches cosi'
// l'intero import e' annullabile con un click (vedi action=batch in DELETE).

$uid = require_auth();

const FIELD_ALIASES = [
    'date' => ['data', 'date', 'datum', 'data operazione', 'data valuta', 'data contabile', 'booking date', 'transaction date', 'value date', 'trade date'],
    'amount' => ['importo', 'amount', 'wert', 'value', 'betrag', 'ammontare', 'importo eur', 'amount eur', 'importo movimento'],
    'debit' => ['dare', 'uscite', 'debit', 'addebito', 'ausgang'],
    'credit' => ['avere', 'entrate', 'credit', 'accredito', 'eingang'],
    'description' => ['descrizione', 'description', 'causale', 'note', 'notiz', 'beschreibung', 'merchant', 'controparte', 'beneficiario ordinante', 'memo', 'details', 'narrative', 'nota'],
    'type' => ['tipo', 'type', 'typ', 'transaction type', 'event type'],
    'category' => ['categoria', 'category', 'kategorie'],
];

const DATE_FORMATS = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'm/d/Y', 'Y/m/d', 'd/m/y', 'd.m.y'];

/** Rimuove BOM e converte in UTF-8 qualunque encoding comune di export bancari europei. */
function decode_csv_bytes(string $raw): string
{
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    if (mb_check_encoding($raw, 'UTF-8')) return $raw;
    foreach (['Windows-1252', 'ISO-8859-1'] as $enc) {
        $converted = @iconv($enc, 'UTF-8//IGNORE', $raw);
        if ($converted !== false) return $converted;
    }
    return $raw;
}

/** Conta le occorrenze del separatore candidato solo nella prima riga, per indovinare quello usato. */
function detect_delimiter(string $firstLine): string
{
    $best = ';';
    $bestCount = 0;
    foreach ([';', ',', "\t"] as $d) {
        $c = substr_count($firstLine, $d);
        if ($c > $bestCount) { $bestCount = $c; $best = $d; }
    }
    return $bestCount > 0 ? $best : ';';
}

function parse_csv_text(string $text, string $delimiter): array
{
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, $text);
    rewind($stream);
    $rows = [];
    while (($row = fgetcsv($stream, 0, $delimiter, '"', '\\')) !== false) {
        if (count($row) === 1 && $row[0] === null) continue;
        $rows[] = $row;
    }
    fclose($stream);
    return $rows;
}

function normalize_header(string $h): string
{
    $h = trim(mb_strtolower($h));
    $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $h);
    if ($translit !== false) $h = $translit;
    return trim(preg_replace('/[^a-z0-9]+/', ' ', $h));
}

function guess_mapping(array $headers): array
{
    $normalized = [];
    foreach ($headers as $h) $normalized[$h] = normalize_header($h);

    $mapping = [];
    foreach (FIELD_ALIASES as $field => $aliases) {
        $found = null;
        foreach ($normalized as $original => $norm) {
            if (in_array($norm, $aliases, true)) { $found = $original; break; }
        }
        if (!$found) {
            foreach ($normalized as $original => $norm) {
                foreach ($aliases as $alias) {
                    if ($norm !== '' && str_contains($norm, $alias)) { $found = $original; break 2; }
                }
            }
        }
        $mapping[$field] = $found;
    }
    return $mapping;
}

function parse_date_value(string $raw, string $format): ?string
{
    $raw = trim($raw);
    if ($raw === '') return null;

    $tryFormats = $format === 'auto' ? DATE_FORMATS : [$format];
    foreach ($tryFormats as $fmt) {
        $d = DateTime::createFromFormat('!' . $fmt, $raw);
        $errors = DateTime::getLastErrors();
        // Da PHP 8.2, getLastErrors() ritorna false (non un array a conteggio zero)
        // quando il parsing non ha prodotto alcun errore/warning: va trattato come successo.
        $hasErrors = $errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
        if ($d && !$hasErrors) {
            return $d->format('Y-m-d');
        }
    }
    return null;
}

function parse_amount_value(string $raw, string $decimalSep): ?float
{
    $s = trim($raw);
    if ($s === '') return null;
    $s = str_replace(["€", "$", "£", "\xC2\xA0", ' '], '', $s);

    $neg = false;
    if (str_starts_with($s, '-')) { $neg = true; $s = substr($s, 1); }
    elseif (str_starts_with($s, '+')) { $s = substr($s, 1); }
    if (str_starts_with($s, '(') && str_ends_with($s, ')')) { $neg = true; $s = trim($s, '()'); }
    if ($s === '') return null;

    $sep = $decimalSep;
    if ($sep === 'auto') {
        $hasComma = str_contains($s, ',');
        $hasDot = str_contains($s, '.');
        if ($hasComma && $hasDot) {
            $sep = strrpos($s, ',') > strrpos($s, '.') ? ',' : '.';
        } else {
            $sep = $hasComma ? ',' : '.';
        }
    }
    $s = $sep === ',' ? str_replace(['.', ','], ['', '.'], $s) : str_replace(',', '', $s);

    if (!is_numeric($s)) return null;
    $v = (float)$s;
    return $neg ? -$v : $v;
}

/** Righe -> array associativi header=>valore, con padding/troncamento se una riga ha un numero di colonne diverso dall'header. */
function rows_to_assoc(array $headers, array $dataRows): array
{
    $count = count($headers);
    $out = [];
    foreach ($dataRows as $row) {
        if (count($row) < $count) $row = array_pad($row, $count, '');
        elseif (count($row) > $count) $row = array_slice($row, 0, $count);
        $out[] = array_combine($headers, $row);
    }
    return $out;
}

switch (method()) {
    case 'GET':
        $action = $_GET['action'] ?? 'batches';

        if ($action === 'mappings') {
            $stmt = db()->prepare("SELECT id, name, config, created_at FROM import_mappings WHERE user_id = :uid ORDER BY name");
            $stmt->execute(['uid' => $uid]);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$r) $r['config'] = json_decode($r['config'], true);
            respond($rows);
            break;
        }

        $stmt = db()->prepare("SELECT b.*, a.name AS account_name
            FROM import_batches b JOIN accounts a ON a.id = b.account_id
            WHERE b.user_id = :uid ORDER BY b.created_at DESC");
        $stmt->execute(['uid' => $uid]);
        respond($stmt->fetchAll());
        break;

    case 'POST':
        $action = $_GET['action'] ?? '';
        $in = json_input();

        if (!in_array($action, ['parse', 'commit'], true)) respond_error('Azione non valida');
        if (empty($in['csv_base64'])) respond_error('File CSV mancante');

        $raw = base64_decode($in['csv_base64'], true);
        if ($raw === false) respond_error('File CSV non decodificabile');
        $text = decode_csv_bytes($raw);

        $firstLineEnd = strcspn($text, "\r\n");
        $delimiter = $in['delimiter'] ?? detect_delimiter(substr($text, 0, $firstLineEnd));

        $allRows = parse_csv_text($text, $delimiter);
        if (count($allRows) < 1) respond_error('File CSV vuoto o non leggibile');

        $headers = array_map('trim', $allRows[0]);
        $dataRows = array_slice($allRows, 1);
        // Righe completamente vuote (riga finale del file, separatori multipli) non contano.
        $dataRows = array_values(array_filter($dataRows, fn($r) => count(array_filter($r, fn($v) => trim((string)$v) !== '')) > 0));
        $assocRows = rows_to_assoc($headers, $dataRows);

        if ($action === 'parse') {
            $mappingStmt = db()->prepare("SELECT id, name, config FROM import_mappings WHERE user_id = :uid ORDER BY name");
            $mappingStmt->execute(['uid' => $uid]);
            $savedMappings = $mappingStmt->fetchAll();
            foreach ($savedMappings as &$m) $m['config'] = json_decode($m['config'], true);

            $distinctValues = [];
            foreach ($headers as $h) {
                $vals = [];
                foreach ($assocRows as $r) {
                    $v = trim((string)$r[$h]);
                    if ($v === '') continue;
                    $vals[$v] = true;
                    if (count($vals) > 50) { $vals = null; break; }
                }
                $distinctValues[$h] = $vals === null ? null : array_keys($vals);
            }

            respond([
                'headers' => $headers,
                'delimiter' => $delimiter,
                'total_rows' => count($assocRows),
                'preview' => array_slice($assocRows, 0, 10),
                'distinct_values' => $distinctValues,
                'suggested_mapping' => guess_mapping($headers),
                'saved_mappings' => $savedMappings,
            ]);
            break;
        }

        // action === 'commit'
        if (empty($in['account_id'])) respond_error('Conto di destinazione mancante');
        assert_owned('accounts', $in['account_id'], $uid, 'conto');

        $columns = $in['columns'] ?? [];
        if (empty($columns['date'])) respond_error('Devi mappare la colonna della data');
        if (empty($columns['amount']) && empty($columns['debit']) && empty($columns['credit'])) {
            respond_error("Devi mappare la colonna dell'importo (oppure dare/avere)");
        }
        foreach (['date', 'amount', 'debit', 'credit', 'description', 'type', 'category'] as $f) {
            if (!empty($columns[$f]) && !in_array($columns[$f], $headers, true)) {
                respond_error("Colonna mappata per '$f' non presente nel file");
            }
        }

        $dateFormat = $in['date_format'] ?? 'auto';
        $decimalSep = $in['decimal_separator'] ?? 'auto';
        $typeActions = $in['type_actions'] ?? [];
        $defaultIncomeCat = $in['default_category_income_id'] ?? null;
        $defaultExpenseCat = $in['default_category_expense_id'] ?? null;
        $skipDuplicates = $in['skip_duplicates'] ?? true;
        $source = trim((string)($in['source'] ?? 'CSV')) ?: 'CSV';
        $dryRun = !empty($in['dry_run']);

        if ($defaultIncomeCat) assert_owned('categories', $defaultIncomeCat, $uid, 'categoria entrate predefinita');
        if ($defaultExpenseCat) assert_owned('categories', $defaultExpenseCat, $uid, 'categoria uscite predefinita');

        $categoryNameById = [];
        foreach ([$defaultIncomeCat, $defaultExpenseCat] as $cid) {
            if (!$cid) continue;
            $s = db()->prepare("SELECT name FROM categories WHERE id = :id");
            $s->execute(['id' => $cid]);
            $categoryNameById[$cid] = $s->fetchColumn();
        }

        // Transazioni gia' presenti sul conto: precaricate una sola volta per il
        // deduplicate, invece di una query per riga (un import puo' avere migliaia
        // di righe). Se non mappiamo alcuna data valida non serve nemmeno la query.
        $existingSet = [];
        if ($skipDuplicates) {
            $stmt = db()->prepare("SELECT date, type, amount, description FROM transactions WHERE user_id = :uid AND account_id = :aid");
            $stmt->execute(['uid' => $uid, 'aid' => $in['account_id']]);
            foreach ($stmt->fetchAll() as $t) {
                $key = $t['date'] . '|' . $t['type'] . '|' . number_format((float)$t['amount'], 2, '.', '') . '|' . mb_strtolower(trim((string)$t['description']));
                $existingSet[$key] = true;
            }
        }

        // Cache categorie: user_id implicito (siamo sempre nello stesso utente), chiave "type|nome_lowercase".
        $categoryCache = [];
        $catStmt = db()->prepare("SELECT id, name, type FROM categories WHERE user_id = :uid");
        $catStmt->execute(['uid' => $uid]);
        foreach ($catStmt->fetchAll() as $c) {
            $categoryCache[$c['type'] . '|' . mb_strtolower($c['name'])] = (int)$c['id'];
        }
        $createdCategories = [];

        $toInsert = [];
        $errors = [];
        $duplicateCount = 0;
        $ignoredCount = 0;

        foreach ($assocRows as $i => $row) {
            $rowNum = $i + 2; // +1 header, +1 1-based

            $date = parse_date_value((string)$row[$columns['date']], $dateFormat);
            if ($date === null) {
                $errors[] = ['row' => $rowNum, 'reason' => "Data non valida: '" . $row[$columns['date']] . "'"];
                continue;
            }

            $type = null;
            $amount = null;
            if (!empty($columns['debit']) || !empty($columns['credit'])) {
                $credit = !empty($columns['credit']) ? parse_amount_value((string)$row[$columns['credit']], $decimalSep) : null;
                $debit = !empty($columns['debit']) ? parse_amount_value((string)$row[$columns['debit']], $decimalSep) : null;
                if ($credit !== null && $credit != 0) { $type = 'entrata'; $amount = abs($credit); }
                elseif ($debit !== null && $debit != 0) { $type = 'uscita'; $amount = abs($debit); }
            } else {
                $amt = parse_amount_value((string)$row[$columns['amount']], $decimalSep);
                if ($amt !== null && $amt != 0) { $type = $amt > 0 ? 'entrata' : 'uscita'; $amount = abs($amt); }
            }

            if ($type === null || $amount === null) {
                $errors[] = ['row' => $rowNum, 'reason' => 'Importo mancante o non valido'];
                continue;
            }

            if (!empty($columns['type'])) {
                $rawTypeVal = trim((string)$row[$columns['type']]);
                $forced = $typeActions[$rawTypeVal] ?? null;
                if ($forced === 'ignora') { $ignoredCount++; continue; }
                if ($forced === 'entrata' || $forced === 'uscita') { $type = $forced; }
            }

            $description = !empty($columns['description']) ? trim((string)$row[$columns['description']]) : '';
            $description = $description !== '' ? $description : null;

            $categoryId = null;
            $categoryName = null;
            if (!empty($columns['category'])) {
                $catName = trim((string)$row[$columns['category']]);
                if ($catName !== '') {
                    $categoryName = $catName;
                    $cacheKey = $type . '|' . mb_strtolower($catName);
                    if (isset($categoryCache[$cacheKey])) {
                        $categoryId = $categoryCache[$cacheKey];
                    } elseif ($dryRun) {
                        // In anteprima non persistiamo nulla: un id fittizio basta a far
                        // proseguire il calcolo dei conteggi, tanto le righe non vengono inserite.
                        $categoryId = -1;
                        $categoryCache[$cacheKey] = -1;
                        $createdCategories[] = ['id' => null, 'name' => $catName, 'type' => $type];
                    } else {
                        $ins = db()->prepare("INSERT INTO categories (user_id, name, type, color) VALUES (:uid, :name, :type, :color)");
                        $ins->execute([
                            'uid' => $uid, 'name' => $catName, 'type' => $type,
                            'color' => $type === 'entrata' ? '#7fa876' : '#c1665a',
                        ]);
                        $categoryId = (int)db()->lastInsertId();
                        $categoryCache[$cacheKey] = $categoryId;
                        $createdCategories[] = ['id' => $categoryId, 'name' => $catName, 'type' => $type];
                    }
                }
            }
            if ($categoryId === null) {
                $categoryId = $type === 'entrata' ? $defaultIncomeCat : $defaultExpenseCat;
                $categoryName = $categoryId ? ($categoryNameById[$categoryId] ?? null) : null;
            }
            if (!$categoryId) {
                $errors[] = ['row' => $rowNum, 'reason' => 'Nessuna categoria disponibile (mappa una colonna categoria o imposta una categoria predefinita)'];
                continue;
            }

            $key = $date . '|' . $type . '|' . number_format($amount, 2, '.', '') . '|' . mb_strtolower(trim($description ?? ''));
            if ($skipDuplicates && isset($existingSet[$key])) {
                $duplicateCount++;
                continue;
            }
            $existingSet[$key] = true;

            $toInsert[] = [
                'date' => $date, 'type' => $type, 'amount' => $amount,
                'description' => $description, 'category_id' => $categoryId, 'category_name' => $categoryName,
            ];
        }

        $batchId = null;
        if ($toInsert && !$dryRun) {
            db()->beginTransaction();
            try {
                $batchStmt = db()->prepare("INSERT INTO import_batches
                    (user_id, account_id, source, filename, row_count, imported_count, duplicate_count, error_count)
                    VALUES (:uid, :aid, :source, :filename, :row_count, :imported, :dup, :err)");
                $batchStmt->execute([
                    'uid' => $uid, 'aid' => $in['account_id'], 'source' => $source,
                    'filename' => $in['filename'] ?? null, 'row_count' => count($assocRows),
                    'imported' => count($toInsert), 'dup' => $duplicateCount, 'err' => count($errors),
                ]);
                $batchId = (int)db()->lastInsertId();

                $txStmt = db()->prepare("INSERT INTO transactions
                    (user_id, account_id, category_id, amount, type, description, date, import_batch_id)
                    VALUES (:uid, :aid, :cid, :amount, :type, :description, :date, :batch_id)");
                foreach ($toInsert as $r) {
                    $txStmt->execute([
                        'uid' => $uid, 'aid' => $in['account_id'], 'cid' => $r['category_id'],
                        'amount' => $r['amount'], 'type' => $r['type'],
                        'description' => $r['description'], 'date' => $r['date'], 'batch_id' => $batchId,
                    ]);
                }
                db()->commit();
            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }
        }

        if (!$dryRun && !empty($in['save_mapping_as'])) {
            $config = [
                'columns' => $columns, 'date_format' => $dateFormat, 'decimal_separator' => $decimalSep,
                'type_actions' => $typeActions, 'default_category_income_id' => $defaultIncomeCat,
                'default_category_expense_id' => $defaultExpenseCat, 'source' => $source,
            ];
            db()->prepare("INSERT INTO import_mappings (user_id, name, config) VALUES (:uid, :name, :config)
                ON DUPLICATE KEY UPDATE config = VALUES(config), updated_at = CURRENT_TIMESTAMP")
                ->execute(['uid' => $uid, 'name' => trim($in['save_mapping_as']), 'config' => json_encode($config, JSON_UNESCAPED_UNICODE)]);
        }

        respond([
            'batch_id' => $batchId,
            'total_rows' => count($assocRows),
            'imported' => count($toInsert),
            'duplicates' => $duplicateCount,
            'ignored' => $ignoredCount,
            'errors' => $errors,
            'created_categories' => $createdCategories,
            'sample' => array_slice($toInsert, 0, 10),
        ], $dryRun ? 200 : 201);
        break;

    case 'DELETE':
        $action = $_GET['action'] ?? '';

        if ($action === 'mapping') {
            $id = $_GET['id'] ?? null;
            if (!$id) respond_error('ID mancante');
            db()->prepare("DELETE FROM import_mappings WHERE id = :id AND user_id = :uid")->execute(['id' => $id, 'uid' => $uid]);
            respond(['ok' => true]);
            break;
        }

        if ($action === 'batch') {
            $id = $_GET['id'] ?? null;
            if (!$id) respond_error('ID mancante');
            $stmt = db()->prepare("SELECT imported_count FROM import_batches WHERE id = :id AND user_id = :uid");
            $stmt->execute(['id' => $id, 'uid' => $uid]);
            $batch = $stmt->fetch();
            if (!$batch) respond_error('Import non trovato', 404);

            // Cancellare il batch elimina a cascata (FK ON DELETE CASCADE) solo le
            // transazioni create da questo import, mai quelle inserite manualmente.
            db()->prepare("DELETE FROM import_batches WHERE id = :id AND user_id = :uid")->execute(['id' => $id, 'uid' => $uid]);
            respond(['ok' => true, 'removed_transactions' => (int)$batch['imported_count']]);
            break;
        }

        respond_error('Azione non valida');
        break;

    default:
        respond_error('Metodo non supportato', 405);
}
