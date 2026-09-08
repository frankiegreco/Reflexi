-- Migration 040: commissioni simulate sul trading in carta, per rendere la
-- pratica più realistica (senza commissioni un piccolo trade non ha mai
-- controindicazioni, mentre nella realtà le commissioni fisse penalizzano
-- proprio i trade piccoli/frequenti — una lezione che vale la pena far
-- vivere anche nel simulatore). Stessa cifra flat già usata per l'advisor
-- investimenti (INVEST_ADVISOR_FEE_PER_TRADE, lib_invest_advisor.php): 1€
-- per operazione, sia in acquisto che in vendita.

ALTER TABLE paper_trading_transactions
    ADD COLUMN fees_eur DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER cash_impact_eur;
