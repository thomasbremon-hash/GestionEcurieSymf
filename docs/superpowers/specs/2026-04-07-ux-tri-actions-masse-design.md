# Design : Tri des colonnes + Actions en masse

**Date :** 2026-04-07
**Périmètre :** UX Priorité haute — listes admin

---

## Contexte

Les listes admin ont déjà une pagination et une recherche JS côté client. Deux features manquent :
- Tri par colonne (aucune colonne n'est triable)
- Suppression en masse (impossible de sélectionner plusieurs éléments)

La facturation est **exclue** du périmètre (suppression interdite légalement).

---

## Architecture

### Fichier JS unique : `public/assets/js/admin-table.js`

Le JS existant (pagination, recherche, per-page) est extrait de `base.admin.html.twig` vers ce fichier. Les deux nouvelles features y sont ajoutées.

Chargement : une balise `<script src="...admin-table.js">` dans le `<head>` de `templates/layouts/base.admin.html.twig`, en remplacement du bloc JS inline actuel.

### Activation par attributs `data-*`

Chaque `<table>` déclare ses capacités :

```html
<table class="data-table"
  data-sortable="true"
  data-bulk-delete="true"
  data-bulk-delete-url="/admin/cheval/delete-bulk"
  data-bulk-csrf-token="{{ csrf_token('bulk-delete') }}">
```

Les deux features sont indépendantes.

---

## Feature 1 : Tri des colonnes

### Activation

Chaque `<th>` triable reçoit un attribut `data-sort` :

```html
<th data-sort="string">Nom</th>
<th data-sort="number">Distance</th>
<th data-sort="date">Date</th>
```

### Comportement JS

1. Au chargement, les `th[data-sort]` reçoivent une icône `mdi-unfold-more-horizontal` et `cursor: pointer`
2. Au clic : tri du `<tbody>` en mémoire (stable sort), cycle asc → desc → neutre
3. Icône mise à jour : `mdi-arrow-up` (asc), `mdi-arrow-down` (desc), `mdi-unfold-more-horizontal` (neutre)
4. La colonne active est mise en évidence via une classe CSS `.th-sorted`
5. Le tri s'applique sur les lignes visibles après filtrage

### Types de tri

- `string` : comparaison `localeCompare` (français)
- `number` : comparaison numérique sur `parseFloat`
- `date` : comparaison sur `Date.parse` (format `dd/mm/yyyy` converti)

### Colonnes triables par liste

| Liste | Colonnes |
|---|---|
| Chevaux | Nom (string), Race (string), Date de naissance (date) |
| Utilisateurs | Nom (string), Statut (string) |
| Entreprises | Nom (string) |
| Structures | Nom (string) |
| Déplacements | Nom (string), Date (date) |
| Distances | Distance (number) |
| Produits | Nom (string) |
| Mois de gestion | Mois (number), Année (number) |
| Taxes | Nom (string) |

### CSS

```css
th[data-sort] { cursor: pointer; user-select: none; }
th[data-sort]:hover { color: var(--sidebar-accent); }
th.th-sorted { color: var(--sidebar-accent); }
th.th-sorted .sort-icon { color: var(--sidebar-accent); }
```

---

## Feature 2 : Suppression en masse

### Listes concernées (9)

Chevaux, Utilisateurs, Entreprises, Structures, Déplacements, Distances, Produits, Mois de gestion, Taxes.

### UX — Sélection

- Colonne checkbox ajoutée en première position dans `<thead>` et `<tbody>`
- Checkbox `<thead>` : sélectionner/désélectionner toutes les lignes visibles
- Checkbox `<tbody>` : sélectionner une ligne individuelle
- Quand ≥1 ligne cochée → barre d'actions flottante visible

### Barre d'actions flottante

Position : `fixed` en bas de l'écran, centrée, z-index élevé.

Contenu :
```
[ X éléments sélectionnés ]  [ Désélectionner tout ]  [ 🗑 Supprimer la sélection ]
```

Style : fond `--surface`, bordure `--border`, box-shadow légère, border-radius `var(--radius)`.
Transition CSS `opacity`/`transform` pour apparition fluide.

### Modal de confirmation

Modal générique réutilisant le style existant (`.modal-overlay`, `.modal-box`) :

```
Supprimer X éléments ?
Cette action est irréversible.
[ Annuler ]  [ Supprimer ]
```

### Flux de soumission

1. Clic "Supprimer" dans la barre → modal de confirmation
2. Confirmation → `POST /admin/{entite}/delete-bulk`
3. Body : `ids[]=1&ids[]=2&...&_token=<csrf>`
4. Controller : boucle de suppression, redirect + flash success/danger
5. La barre disparaît, checkboxes décochées

### Backend — Route par controller

Route ajoutée dans chacun des 8 controllers :

```php
#[Route('/delete-bulk', name: 'app_admin_{entite}_delete_bulk', methods: ['POST'])]
public function deleteBulk(Request $request, EntityManagerInterface $em): Response
{
    if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
        throw $this->createAccessDeniedException();
    }

    $ids = $request->request->all('ids');
    // boucle find + remove
    $em->flush();

    $this->addFlash('success', "X éléments supprimés.");
    return $this->redirectToRoute('app_admin_{entite}_liste');
}
```

Le CSRF token utilisé : `bulk-delete` (distinct des tokens de suppression individuelle `delete{id}`).

### Accès

La colonne checkbox et la barre flottante ne s'affichent que si `is_granted('ROLE_ADMIN')`, cohérent avec les boutons de suppression individuels existants.

---

## Fichiers impactés

### Nouveaux fichiers
- `public/assets/js/admin-table.js` — JS unique (pagination + recherche + tri + bulk delete)

### Fichiers modifiés
- `templates/layouts/base.admin.html.twig` — supprimer JS inline, ajouter `<script src="admin-table.js">`, ajouter CSS tri + barre flottante
- `public/assets/css/admin.css` — styles tri (`.th-sorted`, `.sort-icon`) + barre flottante (`.bulk-action-bar`)

### Templates liste (8) — modifications mineures
- `templates/admin/cheval/list.html.twig`
- `templates/admin/user/list.html.twig`
- `templates/admin/entreprise/list.html.twig`
- `templates/admin/structure/liste.html.twig`
- `templates/admin/deplacement/liste.html.twig`
- `templates/admin/distance/liste.html.twig`
- `templates/admin/produit/liste.html.twig`
- `templates/admin/mois_gestion/liste.html.twig`
- `templates/admin/taxes/liste.html.twig`
- `templates/admin/produit_entreprise/liste.html.twig` *(si applicable)*

Pour chaque template :
1. Ajouter `data-sortable`, `data-bulk-delete`, `data-bulk-delete-url`, `data-bulk-csrf-token` sur `<table>`
2. Ajouter `data-sort="..."` sur les `<th>` concernés
3. Ajouter colonne checkbox (wrappée dans `{% if is_granted('ROLE_ADMIN') %}`)
4. Ajuster `colspan` des lignes vides

### Controllers (8) — nouvelle route `delete-bulk`
- `src/Controller/Admin/ChevalController.php`
- `src/Controller/Admin/UserController.php`
- `src/Controller/Admin/EntrepriseController.php`
- `src/Controller/Admin/StructureController.php`
- `src/Controller/Admin/DeplacementController.php`
- `src/Controller/Admin/DistanceController.php`
- `src/Controller/Admin/ProduitController.php`
- `src/Controller/Admin/MoisDeGestionController.php`
- `src/Controller/Admin/TaxesController.php`

---

## Hors périmètre

- Export CSV (non demandé)
- Actions contextuelles (marquer payé, changer rôle)
- Facturation (suppression interdite légalement)
- Tri côté serveur
- Persistence du tri en localStorage
