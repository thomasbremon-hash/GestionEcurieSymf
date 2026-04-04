# CLAUDE.md — Gestion Ecurie Symf

## Stack technique

- **Framework** : Symfony 7.4 / PHP 8.2+
- **Base de données** : MySQL 8.0.32 (Doctrine ORM + Migrations)
- **PDF** : DOMPDF 3.1
- **Frontend** : Twig, Stimulus, UX Turbo
- **Auth** : SymfonyCasts Reset Password Bundle
- **Icons** : MDI 4.9.95 (attention : `mdi-horse` n'existe pas, utiliser `mdi-horseshoe`)
- **CSS** : Admin = inline dans `templates/Layouts/base.admin.html.twig` / Front = `public/assets/css/front.css`
- **Variables CSS admin** : `--sidebar-accent (#4f80c4)`, `--border`, `--ink-soft`, `--surface`, etc.
- **Variables CSS front** : `--nav-h: 90px`, smooth scrolling

## Entites (16)

User, ResetPasswordRequest, Cheval, ChevalProprietaire, ChevalProduit, Structure, DistanceStructure, Entreprise, Course, Participation, Deplacement, Produit, ProduitEntrepriseTaxes, Taxes, FacturationUtilisateur, MoisDeGestion

## Controllers admin (13)

AdminController, UserController, ChevalController, StructureController, CourseController, DeplacementController, EntrepriseController, ProduitController, TaxesController, ProduitEntrepriseTaxesController, DistanceController, MoisDeGestionController, FacturationUtilisateurController

---

## Travail realise

### Securite — Routes DELETE (POST + CSRF)
- **13 controllers** : Toutes les routes de suppression sont passees de GET a `methods: ['POST']` avec validation CSRF `$this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token'))`
- **13 templates modals** : `<a href>` remplace par `<form method="post">` avec `<input type="hidden" name="_token" value="{{ csrf_token('delete' ~ entity.id) }}">`
- Controllers concernes : Cheval, Course (x2), Deplacement, Distance, Entreprise, Produit, Structure, User, FacturationUtilisateur, MoisDeGestion, Taxes, ProduitEntrepriseTaxes

### Formulaires — Affichage des erreurs
- **`templates/admin/_form_errors.html.twig`** : Partial Twig reutilisable (bloc resume des erreurs)
- **CSS** : `.form-error-summary`, `.form-field.has-error`, `.field-error` dans `base.admin.html.twig`
- **JS** : Highlighting automatique des champs en erreur + scroll vers le resume
- Inclus dans les **11 formulaires admin** via `{% include 'admin/_form_errors.html.twig' with { form: formXxx } %}`

### Front — Corrections espacement
- **Hero** : `min-height: calc(100vh - var(--nav-h))`, padding reduit (suppression double compensation nav-h car `.site-main` l'ajoute deja)
- **Login / Reset password** : Meme correction de double padding
- **Bouton "Defiler"** : `<div>` -> `<a href="#services">` avec smooth scroll

### Page detail Cheval (nouvelle)
- **Route** : `#[Route('/show/{id}', name: 'app_admin_cheval_show')]` dans ChevalController
- **Template** : `templates/admin/cheval/show.html.twig` — breadcrumb, hero header, KPIs, infos generales, proprietaires avec pourcentages, deplacements (10 derniers)
- **Attention** : Le getter dans l'entite Cheval est `getDeplacement()` (singulier), donc utiliser `cheval.deplacement` dans Twig
- **Liste chevaux** : Nom rendu cliquable + bouton oeil pour acceder au detail

### Entreprise — Chevaux affilies
- **`templates/admin/entreprise/show.html.twig`** : Section "Chevaux affilies" avec tableau (nom, race, sexe, proprietaires, action)
- **`src/Form/ChevalType.php`** : Ajout champ `entreprise` (EntityType) pour affilier un cheval a une entreprise

### User — Pourcentage propriete
- **`templates/admin/user/show.html.twig`** : Cards chevaux affichent `{{ cp.pourcentage }}%` en badge

### Facturation — Bouton voir PDF inline
- **Route** : `app_admin_facturation_voir_utilisateur` — affiche le PDF dans le navigateur sans telecharger
- **Methode privee** : `generatePdf()` refactoree avec parametre `$disposition` (inline/attachment)
- **Template liste** : Bouton oeil bleu ajoute avant le bouton telecharger rouge

### Mois de gestion — Listes deroulantes
- **`src/Form/MoisDeGestionType.php`** : `mois` = ChoiceType (Janvier->Decembre), `annee` = ChoiceType (annee-2 -> annee+2)
- **Bouton import** : Restylise avec couleur accent bleue

### Listes groupees par entreprise
- **Distances** (`DistanceController`) : QueryBuilder `orderBy('e.nom')->addOrderBy('d.distance')` + separateurs entreprise dans le template
- **Deplacements** (`DeplacementController`) : `orderBy('e.nom')->addOrderBy('d.date', 'DESC')` + double groupement (entreprise + mois)

### Conformite legale factures
- **Entite `FacturationUtilisateur`** :
  - Champs ajoutes : `dateEmission` (datetime_immutable), `datePaiement` (datetime_immutable, nullable), `createdAt` (datetime_immutable)
  - Contrainte unique sur `numFacture`
- **Entite `Entreprise`** :
  - Champs ajoutes : `formeJuridique` (string 50, nullable), `capitalSocial` (string 50, nullable), `rcs` (string 100, nullable)
- **Entite `MoisDeGestion`** :
  - Contrainte unique sur `(mois, annee)`
- **Controller facturation** :
  - `dateEmission` et `createdAt` renseignes a la generation
  - `datePaiement` renseigne au paiement
  - Suppression bloquee (obligation legale conservation 10 ans — Article L123-22 du Code de Commerce)
- **Formulaire EntrepriseType** : 3 nouveaux champs (formeJuridique, capitalSocial, rcs)
- **Template entreprise.form** : Champs affiches dans la section "Informations legales"
- **Template entreprise/show** : Affichage forme juridique, capital social, RCS
- **Template PDF (`pdf.html.twig`)** :
  - En-tete : forme juridique + capital + RCS affiches
  - Date : `facture.dateEmission|date` au lieu de `'now'|date`
  - Echeance : "A reception — delai de paiement : 30 jours"
  - Mentions legales obligatoires ajoutees : penalites de retard (3x taux legal, art. L441-10), indemnite forfaitaire recouvrement 40 EUR (art. D441-5), escompte ("Pas d'escompte pour paiement anticipe")
- **Template facturation/liste** : Bouton supprimer et modals de suppression retires
- **Migration** : `Version20260403123542.php` — verification conditionnelle des colonnes/index existants avant creation
- **Format numero facture** : `YYYY-MM-NNNN` (deja en place : `sprintf('%d-%02d-%04d', ...)`)

### Copyright dynamique
- **`base.html.twig`** et **`base.admin.html.twig`** : `© 2025` remplace par `© {{ 'now'|date('Y') }}`

### Sidebar — Plusieurs sous-menus ouverts simultanement
- **`base.admin.html.twig`** (JS) : Suppression du pattern "close all before open" — chaque sous-menu s'ouvre/ferme independamment via `classList.toggle()`

### Tooltips CSS sur boutons d'action
- **`admin.css`** : Tooltips pure CSS via `::after` + `attr(title)` sur `.action-btn[title]` — apparaissent au hover au-dessus du bouton avec fleche
- Les boutons utilisent deja l'attribut `title` dans les templates (Voir, Modifier, Supprimer)

### Indicateur champs obligatoires
- **`admin.css`** : Asterisque rouge `*` ajoute via CSS `::after` sur `.form-field label.required` — Symfony ajoute automatiquement la classe `required` sur les labels des champs obligatoires

### Spinner anti double-clic
- **`admin.css`** : Classes `.is-loading` sur `.btn-primary-custom` et `.btn-danger` — desactive le bouton, masque le label, affiche un spinner CSS anime
- **`base.admin.html.twig`** (JS) : Au `submit` de chaque `<form>`, le bouton submit recoit automatiquement la classe `is-loading`

### Breadcrumbs sur les pages liste admin
- **9 templates liste** : `page-eyebrow` transforme en breadcrumb cliquable `<a href="{{ path('app_admin') }}">Dashboard</a> › <span>NomPage</span>`
- Templates modifies : cheval/list, user/list, entreprise/list, structure/liste, deplacement/liste, distance/liste, produit/liste, facturation/liste, mois_gestion/liste
- **`admin.css`** : Styles `.page-eyebrow a` avec hover accent bleu

### Liens placeholder footer front
- **`base.html.twig`** : Liens sociaux `href="#"` remplaces par liens generiques (`https://facebook.com`, etc.) avec `target="_blank" rel="noopener"`
- **`base.html.twig`** : Liens legaux (Mentions legales, Confidentialite, CGU) transformes en `<span class="footer-link-disabled">` en attendant la creation des pages
- **`front.css`** : Style `.footer-link-disabled` (texte grise, cursor default)

---

## A faire — Problemes et ameliorations identifies

### Problemes importants (UX)

- [x] **Boutons d'action = icones seules** — Tooltips CSS ajoutees via `::after` + `attr(title)` sur `.action-btn`
- [ ] **Aucune action en masse** — Impossible de selectionner plusieurs elements pour supprimer, exporter ou envoyer des factures en lot. Ajouter des checkboxes + barre d'actions groupees.
- [ ] **Pas de tri sur les colonnes des tableaux** — On ne peut pas trier par nom, date, montant, etc. Implementer un tri JS ou cote serveur.
- [x] **Breadcrumbs absents sur ~90% des pages admin** — Breadcrumbs cliquables ajoutes sur les 9 pages liste avec lien Dashboard
- [x] **Pas de bouton de chargement** — Spinner CSS + disable automatique au submit sur tous les formulaires
- [x] **Sous-menu sidebar : une seule section ouverte a la fois** — Sous-menus independants, plusieurs ouverts simultanement

### Ameliorations de praticite

- [ ] **Pas de recherche globale** — Ajouter une barre de recherche en haut qui cherche dans toutes les entites (chevaux, utilisateurs, factures...).
- [ ] **Pas d'export CSV/Excel** — Aucun moyen d'exporter les donnees depuis les listes. Ajouter un bouton export sur chaque liste.
- [ ] **Texte d'aide absent sur les champs de formulaire** — Pas de placeholders ni indications sous les champs. Ajouter des `attr.placeholder` et `help` dans les FormTypes.
- [ ] **Confirmations de suppression basiques** — Le modal ne previent pas des donnees liees (ex: "Ce cheval a 3 courses associees"). Enrichir avec un comptage des relations.
- [ ] **Filtres espace client perdus au rechargement** — Les filtres factures (Toutes/Payees/Impayees) sont en JS pur, pas dans l'URL. Utiliser des query params.
- [x] **Pas d'indicateur de champs obligatoires** — Asterisque rouge `*` via CSS `::after` sur `label.required`

### Problemes de design / coherence

- [ ] **Valeurs en dur dans les templates** — Numero de telephone, email, adresse "A completer" dans le footer front. Devrait venir de l'entite Entreprise.
- [ ] **Styles de boutons incoherents** — `.btn-primary-custom` en admin, `.nav-btn` en front, `.login-submit` pour le login. Pas de systeme unifie.
- [ ] **Formatage des dates variable** — Tantot `d/m/Y`, tantot `format_datetime(locale='fr')`. Uniformiser.
- [x] **Copyright "2025" en dur** — Remplace par `{{ 'now'|date('Y') }}` dans les 2 templates base
- [x] **Liens sociaux et footer en placeholder (`href="#"`)** — Sociaux: liens generiques avec target=_blank / Legaux: transformes en `<span>` desactives

### Conformite legale (restant)

- [ ] **Reforme e-invoicing 2026** — Anticiper Factur-X (PDF hybride XML) et les plateformes PDP pour la facturation electronique obligatoire.
- [ ] **Numero de facture sequentiel garanti** — Verifier qu'il n'y a pas de trou dans la numerotation (obligation legale).
- [ ] **Avoir / facture rectificative** — Pas de mecanisme pour emettre un avoir si une facture doit etre corrigee (puisque la suppression est interdite).

---

## Conventions et pieges

- **Getter Cheval deplacements** : `getDeplacement()` (singulier) -> utiliser `cheval.deplacement` dans Twig
- **MDI 4.9.95** : `mdi-horse` n'existe pas, utiliser `mdi-horseshoe`
- **CSS admin** : Pas de fichier CSS separe, tout est dans `base.admin.html.twig` sauf `public/assets/css/admin.css` (nouveau)
- **CSS front** : `public/assets/css/front.css` — `.site-main` ajoute deja `padding-top: var(--nav-h)`, ne pas le re-ajouter dans les sections
- **Routes DELETE** : Toujours POST + CSRF, jamais GET
- **Factures** : Suppression interdite, conservation 10 ans obligatoire
- **Communication** : En francais
