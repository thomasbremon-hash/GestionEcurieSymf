# Home Page Visual Polish — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Améliorer la qualité visuelle de la page home en appliquant 13 raffinements CSS sans changer la palette de couleurs ni la structure HTML.

**Architecture:** Modifications uniquement dans `public/assets/css/front.css` (fichier non-tracké dans git, situé dans le repo principal). Sauvegarde préalable pour réversibilité. Aucune modification Twig.

**Tech Stack:** CSS3, variables CSS existantes (`--green`, `--gold`, `--accent`, etc.)

---

## Fichiers

- **Modifié :** `C:/Code/Git_gestion_ecurie_symf/gestionEcurieSymf/public/assets/css/front.css`
- **Sauvegarde :** `C:/Code/Git_gestion_ecurie_symf/gestionEcurieSymf/public/assets/css/front.css.backup`
- **Spec :** `docs/superpowers/specs/2026-04-01-home-polish-design.md`

> Note : `public/assets/css/front.css` est gitignore dans le repo. Les modifications s'appliquent directement dans le repo principal. La sauvegarde `.backup` permet de revenir en arrière en 1 commande si besoin.

---

## Task 1 : Sauvegarde et préparation

**Fichiers :**
- Backup : `public/assets/css/front.css.backup`

- [ ] **Étape 1 : Sauvegarder front.css**

```bash
cp "C:/Code/Git_gestion_ecurie_symf/gestionEcurieSymf/public/assets/css/front.css" \
   "C:/Code/Git_gestion_ecurie_symf/gestionEcurieSymf/public/assets/css/front.css.backup"
```

Vérifier :
```bash
ls -la "C:/Code/Git_gestion_ecurie_symf/gestionEcurieSymf/public/assets/css/"
```
Attendu : `front.css` et `front.css.backup` présents avec la même taille.

> **Pour revenir en arrière à tout moment :**
> ```bash
> cp "C:/Code/Git_gestion_ecurie_symf/gestionEcurieSymf/public/assets/css/front.css.backup" \
>    "C:/Code/Git_gestion_ecurie_symf/gestionEcurieSymf/public/assets/css/front.css"
> ```

---

## Task 2 : Hero — Eyebrow doré (amélioration #1)

**Fichiers :**
- Modifier : `public/assets/css/front.css` lignes ~1145-1165

- [ ] **Étape 1 : Remplacer le style de `.hero-eyebrow`**

Trouver :
```css
.hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 100px;
    padding: 0.35rem 1rem;
    font-family: "Sora", sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 1.5rem;
}
```

Remplacer par :
```css
.hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(160, 124, 48, 0.12);
    border: 1px solid rgba(160, 124, 48, 0.25);
    border-radius: 100px;
    padding: 0.35rem 1rem;
    font-family: "Sora", sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: rgba(212, 168, 75, 0.9);
    margin-bottom: 1.5rem;
}
```

- [ ] **Étape 2 : Vérifier visuellement dans le navigateur**

Ouvrir la page home. Le badge "Plateforme de gestion équestre" doit avoir une teinte dorée au lieu de blanc/transparent.

---

## Task 3 : Hero — Ligne d'accent dorée en haut (#2) + Second blob lumineux (#7)

**Fichiers :**
- Modifier : `public/assets/css/front.css` lignes ~1082-1106

- [ ] **Étape 1 : Ajouter `::before` sur `.hero` pour la ligne dorée**

Trouver :
```css
.hero {
    min-height: 100vh;
    display: flex;
    align-items: center;
    background: var(--accent);
    position: relative;
    overflow: hidden;
}
```

Remplacer par :
```css
.hero {
    min-height: 100vh;
    display: flex;
    align-items: center;
    background: var(--accent);
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(
        90deg,
        transparent 10%,
        rgba(160, 124, 48, 0.6) 40%,
        rgba(160, 124, 48, 0.6) 60%,
        transparent 90%
    );
    z-index: 2;
    pointer-events: none;
}
```

- [ ] **Étape 2 : Ajouter le second blob doré dans `.hero-bg`**

Trouver :
```css
.hero-bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(
            ellipse 70% 60% at 75% 40%,
            rgba(61, 107, 82, 0.2) 0%,
            transparent 60%
        ),
        radial-gradient(
            ellipse 40% 70% at 15% 80%,
            rgba(160, 124, 48, 0.1) 0%,
            transparent 50%
        );
    pointer-events: none;
}
```

Remplacer par :
```css
.hero-bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(
            ellipse 70% 60% at 75% 40%,
            rgba(61, 107, 82, 0.2) 0%,
            transparent 60%
        ),
        radial-gradient(
            ellipse 40% 70% at 15% 80%,
            rgba(160, 124, 48, 0.1) 0%,
            transparent 50%
        ),
        radial-gradient(
            ellipse 50% 60% at 10% 90%,
            rgba(160, 124, 48, 0.12) 0%,
            transparent 55%
        );
    pointer-events: none;
}
```

- [ ] **Étape 3 : Vérifier visuellement**

Fine ligne dorée visible en haut du hero. Fond légèrement plus chaud en bas à gauche.

---

## Task 4 : Hero — Titre : soulignement dégradé (#3) + outline plus lisible (#4)

**Fichiers :**
- Modifier : `public/assets/css/front.css` lignes ~1176-1195

- [ ] **Étape 1 : Améliorer le soulignement sous "écurie"**

Trouver :
```css
.hero-title .underline::after {
    content: "";
    position: absolute;
    bottom: 2px;
    left: 0;
    right: 0;
    height: 3px;
    background: #a07c30;
    border-radius: 2px;
}
```

Remplacer par :
```css
.hero-title .underline::after {
    content: "";
    position: absolute;
    bottom: 2px;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #a07c30, #d4a84b);
    border-radius: 2px;
}
```

- [ ] **Étape 2 : Rendre "digitalisée" plus lisible**

Trouver :
```css
.hero-title .outline {
    color: transparent;
    -webkit-text-stroke: 1.5px rgba(255, 255, 255, 0.4);
}
```

Remplacer par :
```css
.hero-title .outline {
    color: transparent;
    -webkit-text-stroke: 2px rgba(255, 255, 255, 0.55);
}
```

- [ ] **Étape 3 : Vérifier visuellement**

Le trait sous "écurie" doit être légèrement plus épais avec un dégradé or. "digitalisée" en outline doit être plus visible.

---

## Task 5 : Hero — Description + Stats (#5 et #6)

**Fichiers :**
- Modifier : `public/assets/css/front.css` lignes ~1197-1275

- [ ] **Étape 1 : Améliorer la lisibilité de `.hero-desc`**

Trouver :
```css
.hero-desc {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.55);
    line-height: 1.75;
    margin-bottom: 2.5rem;
    max-width: 460px;
}
```

Remplacer par :
```css
.hero-desc {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.65);
    line-height: 1.75;
    margin-bottom: 2.5rem;
    max-width: 460px;
}
```

- [ ] **Étape 2 : Ajouter les séparateurs verticaux entre les stats**

Trouver :
```css
.hero-stats {
    display: flex;
    gap: 2.5rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}
```

Remplacer par :
```css
.hero-stats {
    display: flex;
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.hero-stats > div {
    flex: 1;
}

.hero-stats > div:not(:last-child) {
    border-right: 1px solid rgba(255, 255, 255, 0.08);
    margin-right: 2rem;
    padding-right: 2rem;
}
```

- [ ] **Étape 3 : Mettre à jour le responsive mobile pour les stats**

Trouver (dans la media query `@media (max-width: 768px)`) :
```css
    .hero-stats {
        gap: 1.5rem;
    }
```

Remplacer par :
```css
    .hero-stats > div:not(:last-child) {
        margin-right: 1.25rem;
        padding-right: 1.25rem;
    }
```

- [ ] **Étape 4 : Vérifier visuellement**

Description légèrement plus lisible. Stats avec fines lignes verticales entre elles.

---

## Task 6 : Hero cards — Ombres colorées au hover (#8)

**Fichiers :**
- Modifier : `public/assets/css/front.css` lignes ~1283-1337

- [ ] **Étape 1 : Ajouter les ombres colorées par type de carte**

Après le bloc `.hero-card:hover { ... }` existant, ajouter :

```css
.hero-card:has(.hero-card-icon.green):hover {
    box-shadow: 0 8px 24px rgba(61, 107, 82, 0.2);
}

.hero-card:has(.hero-card-icon.gold):hover {
    box-shadow: 0 8px 24px rgba(160, 124, 48, 0.2);
}

.hero-card:has(.hero-card-icon.blue):hover {
    box-shadow: 0 8px 24px rgba(45, 95, 166, 0.2);
}
```

- [ ] **Étape 2 : Vérifier visuellement**

Survoler chaque carte hero à droite : chacune doit avoir une légère lueur colorée correspondant à son icône.

---

## Task 7 : Cartes services — Barre toujours visible + Scale icône (#9 et #10)

**Fichiers :**
- Modifier : `public/assets/css/front.css` lignes ~1416-1513

- [ ] **Étape 1 : Rendre la barre colorée toujours visible (subtil)**

Trouver :
```css
.svc-card::after {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-radius: var(--radius) var(--radius) 0 0;
    opacity: 0;
    transition: opacity 0.22s;
}
```

Remplacer par :
```css
.svc-card::after {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-radius: var(--radius) var(--radius) 0 0;
    opacity: 0.35;
    transition: opacity 0.22s;
}
```

- [ ] **Étape 2 : Ajouter le scale de l'icône au hover**

Trouver :
```css
.svc-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    margin-bottom: 1.25rem;
}
```

Remplacer par :
```css
.svc-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    margin-bottom: 1.25rem;
    transition: transform 0.22s ease;
}

.svc-card:hover .svc-icon {
    transform: scale(1.08);
}
```

- [ ] **Étape 3 : Vérifier visuellement**

Chaque carte service doit montrer sa couleur de barre en haut (subtil). Au hover, la barre s'intensifie et l'icône grossit légèrement.

---

## Task 8 : Section Écurie — Ombre teintée + Checkmarks (#11 et #12)

**Fichiers :**
- Modifier : `public/assets/css/front.css` lignes ~1566-1560

- [ ] **Étape 1 : Ombre teintée verte sur le mockup**

Trouver :
```css
.ecurie-mockup {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
}
```

Remplacer par :
```css
.ecurie-mockup {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: 0 20px 60px rgba(61, 107, 82, 0.12), 0 4px 16px rgba(26, 23, 20, 0.08);
    overflow: hidden;
}
```

- [ ] **Étape 2 : Remplacer les dots par des checkmarks**

Trouver :
```css
.ecurie-feat-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--green);
    flex-shrink: 0;
    margin-top: 6px;
}
```

Remplacer par :
```css
.ecurie-feat-dot {
    width: 22px;
    height: 22px;
    border-radius: 7px;
    background: var(--green-light);
    color: var(--green);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    margin-top: 1px;
}

.ecurie-feat-dot::before {
    content: "✓";
}
```

- [ ] **Étape 3 : Vérifier visuellement**

Le mockup a une ombre légèrement verte. Les 4 features de la section Écurie montrent des petits carreaux verts avec ✓ à la place des points.

---

## Task 9 : Animations — Stagger cartes services (#13)

**Fichiers :**
- Modifier : `public/assets/css/front.css` (section HOME, après les styles `.svc-card`)

- [ ] **Étape 1 : Ajouter le stagger via nth-child**

Après le bloc `.svc-desc { ... }` (fin des styles de cartes services, vers ligne ~1513), ajouter :

```css
/* ── STAGGER REVEAL CARTES SERVICES ── */
.services-grid .svc-card:nth-child(1) { transition-delay: 0ms; }
.services-grid .svc-card:nth-child(2) { transition-delay: 80ms; }
.services-grid .svc-card:nth-child(3) { transition-delay: 160ms; }
.services-grid .svc-card:nth-child(4) { transition-delay: 240ms; }
.services-grid .svc-card:nth-child(5) { transition-delay: 320ms; }
.services-grid .svc-card:nth-child(6) { transition-delay: 400ms; }
```

- [ ] **Étape 2 : Vérifier visuellement**

Scroller jusqu'à la section "Fonctionnalités" depuis le haut de la page. Les 6 cartes doivent apparaître en cascade (légèrement décalées) plutôt que toutes en même temps.

---

## Task 10 : Commit final

- [ ] **Étape 1 : Committer la spec et le plan dans le worktree**

```bash
cd "C:/Code/Git_gestion_ecurie_symf/gestionEcurieSymf/.claude/worktrees/modest-swartz"
git add docs/superpowers/plans/2026-04-01-home-polish.md
git commit -m "docs: add home page polish implementation plan

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>"
```

- [ ] **Étape 2 : Vérification finale complète**

Parcourir la page home du haut en bas et vérifier :
- [ ] Eyebrow hero doré ✓
- [ ] Fine ligne dorée en haut du hero ✓
- [ ] Soulignement "écurie" en dégradé ✓
- [ ] "digitalisée" outline plus visible ✓
- [ ] Description plus lisible ✓
- [ ] Stats avec séparateurs verticaux ✓
- [ ] Fond hero plus riche (blob doré bas-gauche) ✓
- [ ] Hero cards : ombres colorées au hover ✓
- [ ] Barres services toujours visibles ✓
- [ ] Icônes services scale au hover ✓
- [ ] Mockup ombre teintée verte ✓
- [ ] Features Écurie avec checkmarks ✓
- [ ] Cartes services en stagger au scroll ✓
- [ ] Aucune régression mobile (redimensionner à 768px) ✓

---

## Réversibilité

Pour annuler toutes les modifications :
```bash
cp "C:/Code/Git_gestion_ecurie_symf/gestionEcurieSymf/public/assets/css/front.css.backup" \
   "C:/Code/Git_gestion_ecurie_symf/gestionEcurieSymf/public/assets/css/front.css"
```
