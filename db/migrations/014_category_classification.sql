-- Migration 014: classificazione categorie per il riquadro "Spending Ratios"
-- nella pagina Budget ridisegnata (necessità / desideri / risparmio).

ALTER TABLE categories ADD COLUMN classification ENUM('necessita','desiderio','risparmio') DEFAULT NULL;
