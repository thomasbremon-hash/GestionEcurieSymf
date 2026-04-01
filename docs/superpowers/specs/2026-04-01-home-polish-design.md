# Spec — Amélioration visuelle de la page Home

**Date :** 2026-04-01
**Branche :** `feature/nouveau-style-home`
**Fichiers modifiés :** `public/assets/css/front.css` (copie dans le worktree)

---

## Contexte

La page d'accueil (`templates/app/home/home.html.twig`) est une landing page marketing pour l'application de gestion d'écurie. Elle possède déjà un design soigné avec une palette beige/vert/foncé. L'objectif est d'améliorer la qualité visuelle sans changer la palette de couleurs ni la structure HTML.

**Fichier CSS source :** `public/assets/css/front.css` (3150 lignes, section HOME à partir de la ligne ~1079)

---

## Périmètre

Modifications uniquement dans `public/assets/css/front.css`. Aucune modification du template Twig.

---

## Améliorations détaillées

### 1. Hero — Eyebrow doré
- `.hero-eyebrow` : background `rgba(160,124,48,0.12)` + border `rgba(160,124,48,0.25)` + color `rgba(212,168,75,0.9)`
- Remplace le fond blanc/transparent actuel pour aligner avec l'accent doré existant

### 2. Hero — Ligne d'accent en haut
- `.hero::before` : fine ligne (2px) dégradée `linear-gradient(90deg, transparent 10%, rgba(160,124,48,0.6) 40%, rgba(160,124,48,0.6) 60%, transparent 90%)`
- Ancre visuellement le bloc hero

### 3. Hero — Soulignement titre dégradé
- `.hero-title .underline::after` : height `3px → 4px`, background `#a07c30 → linear-gradient(90deg, #a07c30, #d4a84b)`

### 4. Hero — Outline plus lisible
- `.hero-title .outline` : `-webkit-text-stroke: 1.5px rgba(255,255,255,0.4) → 2px rgba(255,255,255,0.55)`

### 5. Hero — Description plus lisible
- `.hero-desc` : `color: rgba(255,255,255,0.55) → rgba(255,255,255,0.65)`

### 6. Hero — Stats avec séparateurs verticaux
- `.hero-stats` : retirer `gap: 2.5rem`, utiliser flex avec `padding-right` sur chaque stat
- `.hero-stats > div:not(:last-child)` : `border-right: 1px solid rgba(255,255,255,0.08)` + `margin-right: 2rem` + `padding-right: 2rem`

### 7. Hero — Second blob lumineux
- `.hero-bg` : ajouter un 3e gradient radial `radial-gradient(ellipse 50% 60% at 10% 90%, rgba(160,124,48,0.12) 0%, transparent 55%)` dans la liste existante

### 8. Hero cards — Ombre colorée au hover
- `.hero-card:hover` : ajouter `box-shadow` colorée subtile selon la couleur d'icône de la carte
  - Vert : `0 8px 24px rgba(61,107,82,0.15)`
  - Or : `0 8px 24px rgba(160,124,48,0.15)`
  - Bleu : `0 8px 24px rgba(45,95,166,0.15)`
- Implémenter via `.hero-card:has(.hero-card-icon.green):hover` etc.

### 9. Cartes services — Barre colorée toujours visible
- `.svc-card::after` : `opacity: 0 → 0.35` par défaut
- `.svc-card:hover::after` : `opacity: 1` (inchangé)

### 10. Cartes services — Scale icône au hover
- `.svc-card:hover .svc-icon` : `transform: scale(1.08)`
- `.svc-icon` : ajouter `transition: transform 0.22s ease`

### 11. Section Écurie — Ombre mockup teintée verte
- `.ecurie-mockup` : `box-shadow: var(--shadow-lg) → 0 20px 60px rgba(61,107,82,0.12), 0 4px 16px rgba(26,23,20,0.08)`

### 12. Section Écurie — Checkmarks à la place des dots
- `.ecurie-feat-dot` : remplacer le cercle vert par un carré arrondi avec checkmark
  - `width: 20px; height: 20px; border-radius: 6px; background: var(--green-light); color: var(--green); display: flex; align-items: center; justify-content: center; font-size: 0.75rem;`
  - Nécessite d'ajouter `content: "✓"` via `::before` ou de modifier le HTML (préférer CSS `content` sur un pseudo-élément)
  - **Note :** puisqu'on ne modifie pas le Twig, on réalise le checkmark via `::before` sur `.ecurie-feat-dot`

### 13. Animations reveal — Stagger pour les cartes services
- Les 6 cartes ont des classes `reveal` avec d1/d2 pour certaines. Pour un stagger uniforme sans modifier le HTML, utiliser des sélecteurs `nth-child` qui définissent `transition-delay` directement :
  - `.services-grid .svc-card:nth-child(1)` → `transition-delay: 0ms`
  - `.services-grid .svc-card:nth-child(2)` → `transition-delay: 80ms`
  - `.services-grid .svc-card:nth-child(3)` → `transition-delay: 160ms`
  - `.services-grid .svc-card:nth-child(4)` → `transition-delay: 240ms`
  - `.services-grid .svc-card:nth-child(5)` → `transition-delay: 320ms`
  - `.services-grid .svc-card:nth-child(6)` → `transition-delay: 400ms`
- Ces règles sont plus spécifiques que les classes génériques et s'appliquent uniquement aux cartes de `.services-grid`

---

## Architecture

- **1 seul fichier modifié** : `public/assets/css/front.css`
- Aucun changement Twig
- Aucune nouvelle dépendance
- Réversible : `git checkout public/assets/css/front.css` (ou suppression de la branche)

---

## Critères de succès

- Page home visuellement plus riche et raffinée sans rupture de cohérence
- Toutes les couleurs existantes conservées
- Aucune régression sur mobile (breakpoints 1024px, 768px, 480px inchangés)
- Aucun changement de comportement fonctionnel
