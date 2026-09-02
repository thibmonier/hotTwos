import './stimulus_bootstrap.js';

/**
 * HotOnes — point d'entrée AssetMapper (US-061, T-061-02)
 *
 * Ordre de chargement CSS (important pour la cascade) :
 *  1. Bootstrap 5 compilé (Skote)  → classes utilitaires, reset, grid
 *  2. tokens.css                   → custom properties clair/sombre
 *  3. components.css               → composants tokenisés
 *  4. app.css                      → styles applicatifs migrés vers tokens
 */
import './styles/vendor/bootstrap.min.css';
import './styles/tokens.css';
import './styles/components.css';
import './styles/layout.css';
import './styles/app.css';
