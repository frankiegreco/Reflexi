<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require_once __DIR__ . '/lib_invest_advisor.php';

$uid = require_auth();

if (method() !== 'GET') respond_error('Metodo non supportato', 405);

$amount = (float)($_GET['amount'] ?? 0);
$months = (int)($_GET['months'] ?? 0);
$count = isset($_GET['count']) ? (int)$_GET['count'] : INVEST_ADVISOR_DEFAULT_COUNT;
$riskProfile = $_GET['risk'] ?? INVEST_ADVISOR_DEFAULT_RISK_PROFILE;

if ($amount <= 0) respond_error('Importo non valido');
if ($months <= 0 || $months > INVEST_ADVISOR_MAX_MONTHS) respond_error('Orizzonte (mesi) non valido (1-' . INVEST_ADVISOR_MAX_MONTHS . ')');
if ($count <= 0 || $count > INVEST_ADVISOR_MAX_COUNT) respond_error('Numero di strumenti non valido (1-' . INVEST_ADVISOR_MAX_COUNT . ')');
if (!isset(INVEST_ADVISOR_RISK_PROFILES[$riskProfile])) respond_error('Livello di rischio non valido');

$universe = forecast_universe_computed(db(), $uid);
$result = compute_investment_allocation($universe['computed'], $universe['global_meta'], $amount, $months, $count, $riskProfile);

respond($result);
