# CLAUDE.md — Gestion Ecurie Symf

## Stack technique

- **Framework** : Symfony 7.4 / PHP 8.2+
- **Base de données** : MySQL 8.0.32 (Doctrine ORM + Migrations)
- **PDF** : DOMPDF 3.1
- **Factur-X** : horstoeko/zugferd v1.0.122 (XML MINIMUM profile)
- **Frontend** : Twig, Stimulus, UX Turbo
- **Auth** : SymfonyCasts Reset Password Bundle
- **Icons** : MDI 4.9.95 (attention : `mdi-horse` n'existe pas, utiliser `mdi-horseshoe`)
- **CSS** : Admin = `public/assets/css/admin.css` + inline dans `base.admin.html.twig` / Front = `public/assets/css/front.css`
- **JS** : `public/assets/js/admin.js` (tri + suppression en masse, activation via `data-*`)
- **Variables CSS admin** : `--sidebar-accent (#4f80c4)`, `--border`, `--ink-soft`, `--surface`, `--surface-2`, `--ink-muted`, `--red`, `--red-light`, `--green`, `--green-light`, `--gold`, `--gold-light`
- **Variables CSS front** : `--nav-h: 90px`, smooth scrolling

## Entites (17)

User, ResetPasswordRequest, Cheval, ChevalProprietaire, ChevalProduit, Structure, DistanceStructure, Entreprise, Course, Participation, Deplacement, Produit, ProduitEntrepriseTaxes, Taxes, FacturationUtilisateur, MoisDeGestion, **InvoiceCounter**

## Controllers admin (13)

AdminController, UserController, ChevalController, StructureController, CourseController, DeplacementController, EntrepriseController, ProduitController, TaxesController, ProduitEntrepriseTaxesController, DistanceController, MoisDeGestionController, FacturationUtilisateurController

## Services

- `FactureCalculator` — calcule totalHT/TVA/TTC depuis User + MoisDeGestion
- `InvoiceNumberService` — `reserveNumbers(int $count): int` avec SELECT FOR UPDATE (verrou pessimiste)
- `FacturXService` — `generateXml(FacturationUtilisateur): string` — Factur-X MINIMUM
- `DeplacementToChevalProduitService` — génère ChevalProduit depuis les déplacements

---

## Travail realise

### Securite — Routes DELETE (POST + CSRF)
- **13 controllers** : Toutes les routes de suppression sont passees de GET a `methods: ['POST']` avec validation CSRF `$this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token'))`
- **13 templates modals** : `<a href>` remplace par `<form method="post">` avec `<input type="hidden" name="_token" value="{{ csrf_token('delete' ~ entity.id) }}">`

### Formulaires — Affichage des erreurs
- **`templates/admin/_form_errors.html.twig`** : Partial Twig reutilisable (bloc resume des erreurs)
- **CSS** : `.form-error-summary`, `.form-field.has-error`, `.field-error`
- **JS** : Highlighting automatique des champs en erreur + scroll vers le resume
- Inclus dans les **11 formulaires admin**

### Page detail Cheval
- **Route** : `app_admin_cheval_show` — breadcrumb, KPIs, proprietaires, 10 derniers deplacements
- **Attention** : Le getter est `getDeplacement()` (singulier) → utiliser `cheval.deplacement` dans Twig

### Conformite legale factures
- **`FacturationUtilisateur`** : `dateEmission`, `datePaiement`, `createdAt`, `mailEnvoye`, contrainte unique `numFacture`
- **`Entreprise`** : `formeJuridique`, `capitalSocial`, `rcs`
- **`MoisDeGestion`** : contrainte unique `(mois, annee)`
- **PDF** : mentions legales obligatoires, penalites retard (art. L441-10), indemnite 40€ (art. D441-5)
- **Suppression bloquee** : obligation legale 10 ans (Article L123-22)
- **Format numero** : `YYYY-MM-NNNN` via `sprintf('%d-%02d-%04d', ...)`

### Facturation — Modification et avoir (session 2026-04)
- **`FacturationUtilisateur`** : champs `type` (string 20, default='facture') et `factureOrigine` (ManyToOne self, nullable)
- **`FacturationUtilisateurType`** : form avec utilisateur, moisDeGestion, entreprise
- **Route `edit`** : modification directe si `mailEnvoye=false` + `type='facture'` — recalcule le total
- **Route `corriger`** : workflow avoir si facture envoyee — cree un avoir (total negatif, type='avoir', numFacture='AV-XXXX') + annule l'original (statut='annulee') + cree une nouvelle facture corrigee
- **Liste** : badges `pill-annulee` / `pill-avoir`, montants avoirs en rouge, boutons contextuels (Modifier / Corriger / Payer / Envoyer)
- **Migration** : `Version20260407123636.php`

### Numérotation séquentielle garantie (session 2026-04)
- **`InvoiceCounter`** : entite ligne unique (id=1, counter=N) seedee au MAX existant
- **`InvoiceNumberService`** : `reserveNumbers(int $count=1): int` — `wrapInTransaction` + `LockMode::PESSIMISTIC_WRITE` → SELECT FOR UPDATE — impossible d'avoir deux factures avec le meme numero meme sous charge concurrente
- **Controller** : les deux blocs `preg_match` supprimes, remplace par le service dans `genererUtilisateur` et `corriger`
- **Migration** : `Version20260408091612.php`

### Factur-X MINIMUM (anticipation 2026) (session 2026-04)
- **`horstoeko/zugferd` v1.0.122** installe
- **`FacturXService`** : genere XML profil MINIMUM — vendeur (nom, adresse, TVA), acheteur, totaux HT/TVA/TTC depuis FactureCalculator
- **Route** `app_admin_facturation_facturx` → `/admin/facturation/facturx/{id}` — telecharge `facturx_YYYY-MM-NNNN.xml`
- **Liste** : bouton vert XML uniquement pour `type='facture'`
- **Note** : embedding XML dans le PDF (PDF/A-3 hybride) prevu pour 2025-2026 quand obligation entre en vigueur

### UX — Tri + Suppression en masse (session 2026-04)
- **`public/assets/js/admin.js`** : activation via `data-sortable="true"` et `data-bulk-delete="true"` sur `<table>`
- **Tri JS client-side** : `th[data-sort="string|number|date"]` — cycle asc/desc/reset, icones MDI
- **Suppression en masse** : checkboxes, barre flottante, modal confirmation, POST + CSRF vers route `delete-bulk`
- **9 listes admin** : cheval, user, entreprise, structure, deplacement, distance, produit, mois_gestion, taxes
- **9 routes `delete-bulk`** ajoutees dans les controllers correspondants
- **CSS** : `.bulk-action-bar`, `.bulk-checkbox`, `.th-sorted`, `.sort-icon` dans `admin.css`

### Design / Cohérence (session 2026-04)
- **Boutons** : `.login-submit` refactorise en extension de `.nav-btn` (suppression du CSS duplique)
- **Templates login/reset** : `class="nav-btn login-submit"` sur les 3 boutons
- **Dates** : `user/show.html.twig` — `d M Y` corrige en `d/m/Y`

### Divers
- **Copyright** : `{{ 'now'|date('Y') }}` dans `base.html.twig` et `base.admin.html.twig`
- **Sidebar** : sous-menus independants (plusieurs ouverts simultanement)
- **Tooltips CSS** : `.action-btn[title]::after` — apparaissent au hover
- **Indicateur champs obligatoires** : `label.required::after` — asterisque rouge
- **Spinner anti double-clic** : `.is-loading` sur les boutons submit au `submit` de chaque form
- **Breadcrumbs** : sur les 9 pages liste admin
- **Pill CSS** : `.pill-annulee`, `.pill-avoir` dans `admin.css`

---

## A faire — Backlog priorise

### 🔴 Fort impact, peu de travail

- [ ] **Recherche globale** — Barre de recherche dans la sidebar (ou header) cherchant en temps reel dans chevaux, utilisateurs, factures, deplacements. Route AJAX `/admin/search?q=...` retournant JSON, rendu en dropdown. Entites a chercher : Cheval (nom), User (nom+prenom+email), FacturationUtilisateur (numFacture), Deplacement (cheval+date).
- [ ] **Filtres factures dans l'URL** — Les filtres Toutes/Payees/Impayees sont en JS pur et perdus au rechargement. Passer a des query params `?statut=payee` dans `FacturationUtilisateurController::index()` + `Request $request` pour filtrer la liste cote serveur.
- [ ] **Placeholders et aide sur les formulaires** — Aucun placeholder ni texte d'aide sur les champs. Ajouter `attr: {placeholder: '...'}` et `help: '...'` dans tous les FormTypes (ChevalType, EntrepriseType, UserType, etc.). Priorite : les champs non-evidents (SIREN, IBAN, BIC, numTVA, codeAPE).

### 🟠 Fort impact, travail moyen

- [ ] **Dashboard enrichi (KPIs)** — Le dashboard `AdminController` est probablement vide ou basique. Ajouter : nb factures impayees + montant total, nb deplacements du mois en cours, nb chevaux actifs, derniers mouvements. Injecter les repositories dans `AdminController`.
- [ ] **Export CSV** — Bouton "Exporter CSV" sur les listes chevaux, deplacements, factures. Symfony `StreamedResponse` + `fputcsv`. Pas de librairie externe necessaire.
- [ ] **Confirmations de suppression enrichies** — Le modal affiche juste "Confirmer ?". Enrichir avec un comptage des relations : "Ce cheval a 3 courses et 2 deplacements associes. La suppression echouera." Calculer en Twig ou via un endpoint AJAX.
- [ ] **Actions en masse sur les factures** — Envoyer les mails de plusieurs factures en une fois. Etendre le systeme `bulk-delete` existant avec une action "envoyer mail" groupee sur la liste facturation.

### 🟡 Impact moyen

- [ ] **Espace client — verification et amelioration** — `ClientController` existe mais son etat est inconnu. Verifier que les proprietaires voient bien leurs chevaux, factures et deplacements. Ajouter pagination si les listes sont longues.
- [ ] **Notifications factures impayees** — Badge ou alerte dans la sidebar quand des factures sont impayees depuis plus de 30 jours. Requete simple dans `base.admin.html.twig` via Twig global ou controller event.
- [ ] **Pagination sur les listes admin** — Aucune pagination sur les listes. Si la base grossit, les listes deviendront lentes. Utiliser `KnpPaginatorBundle` ou pagination manuelle Doctrine.
- [ ] **Historique des modifications** — Pas de trace de qui a modifie quoi. Utile pour audit. Librairie `stof/doctrine-extensions-bundle` avec `Loggable`.

### ⚪ Nice-to-have / Futur

- [ ] **Factur-X — upgrade profil** — Passer du profil MINIMUM au profil BASIC ou EN16931 (avec lignes de detail). Obligatoire pour la reforme 2026 complete.
- [ ] **Factur-X — PDF/A-3 hybride** — Embarquer le XML dans le PDF (format hybride officiel). DOMPDF ne supporte pas nativement PDF/A-3 — necessite un passage post-rendu avec horstoeko ou FPDI.
- [ ] **Passage par une PDP** — Quand l'obligation entre en vigueur, integrer une Plateforme de Dematerialisation Partenaire (payant) ou le Portail Public de Facturation (gratuit) pour la transmission electronique.
- [ ] **Mentions legales / CGU / Confidentialite** — Pages front actuellement en `<span>` desactive. Creer des pages statiques Twig avec contenu.
- [ ] **Footer front depuis Entreprise** — Telephone, email, adresse en dur dans `base.html.twig`. Injecter via Twig Extension + `EntrepriseRepository::findOneBy([])`.

---

## Conventions et pieges

- **Getter Cheval deplacements** : `getDeplacement()` (singulier) → utiliser `cheval.deplacement` dans Twig
- **MDI 4.9.95** : `mdi-horse` n'existe pas, utiliser `mdi-horseshoe`
- **CSS admin** : `public/assets/css/admin.css` (nouveau) + inline dans `base.admin.html.twig`
- **CSS front** : `public/assets/css/front.css` — `.site-main` ajoute deja `padding-top: var(--nav-h)`, ne pas le re-ajouter
- **JS admin** : `public/assets/js/admin.js` — activation tri via `data-sortable="true"` sur `<table>`, bulk via `data-bulk-delete="true"` + `data-bulk-delete-url` + `data-bulk-csrf-token`
- **Routes DELETE** : Toujours POST + CSRF, jamais GET
- **Factures** : Suppression interdite, conservation 10 ans (Article L123-22)
- **Factures type** : `'facture'` (normal) ou `'avoir'` (credit note) — champ `type` sur `FacturationUtilisateur`
- **Numérotation** : Toujours via `InvoiceNumberService::reserveNumbers()` — jamais de preg_match manuel
- **Séparateurs de lignes** (deplacement/distance) : colspan ajuste automatiquement par `initBulkDelete()` en JS — ne pas modifier le colspan Twig des separateurs
- **DistanceController** : pas de prefix de route au niveau classe → toutes les routes utilisent le chemin absolu `/admin/distance/...`
- **TaxesController** : utilise `#[IsGranted]` au lieu de `BackofficeAccessTrait`
- **Communication** : En francais
