# Tri des colonnes + Actions en masse — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ajouter le tri par colonne et la suppression en masse sur les 9 listes admin (hors facturation).

**Architecture:** Tout le JS est centralisé dans `admin.js` (déjà existant). Les features sont activées via attributs `data-*` sur la balise `<table>`. Le CSS est ajouté dans `admin.css`. Chaque controller reçoit une route `delete-bulk` (POST + CSRF).

**Tech Stack:** Symfony 7.4, PHP 8.2, Twig, vanilla JS, CSS custom properties (MDI icons déjà chargés)

---

## Fichiers impactés

| Fichier | Type | Rôle |
|---|---|---|
| `public/assets/css/admin.css` | Modifié | Styles tri colonnes + barre flottante |
| `public/assets/js/admin.js` | Modifié | JS tri + bulk delete (ajout à la suite) |
| `templates/admin/cheval/list.html.twig` | Modifié | data-sort, data-id, checkbox, bulk attrs |
| `templates/admin/user/list.html.twig` | Modifié | idem |
| `templates/admin/entreprise/list.html.twig` | Modifié | idem |
| `templates/admin/structure/liste.html.twig` | Modifié | idem |
| `templates/admin/produit/liste.html.twig` | Modifié | idem |
| `templates/admin/mois_gestion/liste.html.twig` | Modifié | idem |
| `templates/admin/taxes/liste.html.twig` | Modifié | idem |
| `templates/admin/deplacement/liste.html.twig` | Modifié | idem + colspan séparateurs |
| `templates/admin/distance/liste.html.twig` | Modifié | idem + colspan séparateurs |
| `src/Controller/Admin/ChevalController.php` | Modifié | Route delete-bulk |
| `src/Controller/Admin/UserController.php` | Modifié | idem |
| `src/Controller/Admin/EntrepriseController.php` | Modifié | idem |
| `src/Controller/Admin/StructureController.php` | Modifié | idem |
| `src/Controller/Admin/DeplacementController.php` | Modifié | idem |
| `src/Controller/Admin/DistanceController.php` | Modifié | idem (chemin explicite, pas de préfixe classe) |
| `src/Controller/Admin/ProduitController.php` | Modifié | idem |
| `src/Controller/Admin/MoisDeGestionController.php` | Modifié | idem |
| `src/Controller/Admin/TaxesController.php` | Modifié | idem (pas de BackofficeAccessTrait) |

---

## Task 1 : CSS — tri des colonnes + barre d'actions

**Fichiers :**
- Modifier : `public/assets/css/admin.css` (ajouter à la fin du fichier)

- [ ] **Step 1 : Ajouter les styles à la fin de `admin.css`**

```css
/* ══════════════════════════════════════
   TRI DES COLONNES
══════════════════════════════════════ */
th[data-sort] {
    cursor: pointer;
    user-select: none;
    white-space: nowrap;
}
th[data-sort]:hover {
    color: var(--sidebar-accent, #4f80c4);
}
th.th-sorted {
    color: var(--sidebar-accent, #4f80c4);
}
.sort-icon {
    font-size: 0.85rem;
    margin-left: 0.3rem;
    opacity: 0.5;
    vertical-align: middle;
    pointer-events: none;
}
th.th-sorted .sort-icon {
    opacity: 1;
    color: var(--sidebar-accent, #4f80c4);
}

/* ══════════════════════════════════════
   BARRE D'ACTIONS EN MASSE
══════════════════════════════════════ */
.bulk-action-bar {
    position: fixed;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%) translateY(160%);
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 1rem;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 0.75rem 1.25rem;
    box-shadow: var(--shadow-lg);
    transition: transform 0.25s ease, opacity 0.25s ease;
    opacity: 0;
    white-space: nowrap;
}
.bulk-action-bar.visible {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
}
.bulk-count {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--ink);
}
.bulk-clear-btn {
    font-size: 0.82rem;
    color: var(--ink-soft);
    cursor: pointer;
    background: none;
    border: none;
    text-decoration: underline;
    padding: 0;
    font-family: inherit;
}
.bulk-clear-btn:hover {
    color: var(--ink);
}
.bulk-checkbox {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: var(--sidebar-accent, #4f80c4);
    flex-shrink: 0;
}
```

- [ ] **Step 2 : Vérifier visuellement** — Ouvrir n'importe quelle liste admin dans le navigateur. Pas de changement visible attendu à ce stade (les classes ne sont pas encore appliquées).

- [ ] **Step 3 : Commit**

```bash
git add public/assets/css/admin.css
git commit -m "feat: add CSS for column sorting and bulk action bar"
```

---

## Task 2 : JS — Tri des colonnes

**Fichiers :**
- Modifier : `public/assets/js/admin.js` (ajouter après la dernière ligne du `DOMContentLoaded`)

- [ ] **Step 1 : Ajouter les fonctions de tri AVANT le `document.addEventListener('DOMContentLoaded', ...)`**

Repérer la ligne 1 de `admin.js` (le commentaire `admin.js — Scripts partagés BackOffice`) et ajouter juste avant le bloc `document.addEventListener('DOMContentLoaded', ...)` (ligne 18) :

```javascript
// ══════════════════════════════════════
// 4. TRI DES COLONNES
// Activé sur <table data-sortable="true">
// Chaque <th data-sort="string|number|date"> devient triable.
// ══════════════════════════════════════

function parseDateFr(str) {
  var p = str.trim().split('/')
  if (p.length === 3) return new Date(p[2], p[1] - 1, p[0])
  return new Date(0)
}

function sortTableByCol(tbody, colIndex, type, dir) {
  var rows = Array.from(tbody.querySelectorAll('tr[data-index]'))
  rows.sort(function (a, b) {
    var aCell = a.cells[colIndex]
    var bCell = b.cells[colIndex]
    var aVal = aCell ? aCell.textContent.trim() : ''
    var bVal = bCell ? bCell.textContent.trim() : ''
    var cmp = 0
    if (type === 'number') {
      cmp = (parseFloat(aVal) || 0) - (parseFloat(bVal) || 0)
    } else if (type === 'date') {
      cmp = parseDateFr(aVal) - parseDateFr(bVal)
    } else {
      cmp = aVal.localeCompare(bVal, 'fr', { sensitivity: 'base' })
    }
    return dir === 'asc' ? cmp : -cmp
  })
  rows.forEach(function (r) { tbody.appendChild(r) })
}

function initSorting(table) {
  if (!table || table.dataset.sortable !== 'true') return
  var tbody = table.querySelector('tbody')
  if (!tbody) return

  table.querySelectorAll('th[data-sort]').forEach(function (th) {
    var icon = document.createElement('i')
    icon.className = 'mdi mdi-unfold-more-horizontal sort-icon'
    th.appendChild(icon)
    th.dataset.sortDir = 'none'

    th.addEventListener('click', function () {
      var dir = th.dataset.sortDir === 'asc' ? 'desc' : 'asc'

      // Reset tous les autres th
      table.querySelectorAll('th[data-sort]').forEach(function (other) {
        other.dataset.sortDir = 'none'
        other.classList.remove('th-sorted')
        var ico = other.querySelector('.sort-icon')
        if (ico) ico.className = 'mdi mdi-unfold-more-horizontal sort-icon'
      })

      th.dataset.sortDir = dir
      th.classList.add('th-sorted')
      var ico = th.querySelector('.sort-icon')
      if (ico) ico.className = 'mdi mdi-arrow-' + (dir === 'asc' ? 'up' : 'down') + ' sort-icon'

      sortTableByCol(tbody, th.cellIndex, th.dataset.sort, dir)
    })
  })
}
```

- [ ] **Step 2 : Appeler `initSorting` dans le `DOMContentLoaded`**

À la fin du bloc `document.addEventListener('DOMContentLoaded', () => {`, juste avant la dernière accolade `})`, ajouter :

```javascript
  // ══════════════════════════════════════
  // Init tri + bulk (après render initial)
  // ══════════════════════════════════════
  var sortableTables = document.querySelectorAll('table[data-sortable]')
  sortableTables.forEach(function (table) { initSorting(table) })
```

- [ ] **Step 3 : Commit**

```bash
git add public/assets/js/admin.js
git commit -m "feat: add column sorting to admin tables"
```

---

## Task 3 : JS — Suppression en masse

**Fichiers :**
- Modifier : `public/assets/js/admin.js`

- [ ] **Step 1 : Ajouter la fonction `initBulkDelete` AVANT le `document.addEventListener('DOMContentLoaded', ...)`** (après les fonctions de tri du Task 2)

```javascript
// ══════════════════════════════════════
// 5. SUPPRESSION EN MASSE
// Activé sur <table data-bulk-delete="true"
//   data-bulk-delete-url="/admin/.../delete-bulk"
//   data-bulk-csrf-token="...">
// Chaque <tr data-index="N" data-id="X"> reçoit une checkbox.
// ══════════════════════════════════════

function initBulkDelete(table) {
  if (!table || table.dataset.bulkDelete !== 'true') return
  var deleteUrl = table.dataset.bulkDeleteUrl
  var csrfToken = table.dataset.bulkCsrfToken
  var tbody = table.querySelector('tbody')
  var theadRow = table.querySelector('thead tr')
  if (!tbody || !theadRow || !deleteUrl) return

  // --- Checkbox "tout sélectionner" dans le thead ---
  var thCheck = document.createElement('th')
  thCheck.style.cssText = 'width:40px;padding-right:0;'
  var checkAll = document.createElement('input')
  checkAll.type = 'checkbox'
  checkAll.className = 'bulk-checkbox'
  checkAll.title = 'Tout sélectionner'
  thCheck.appendChild(checkAll)
  theadRow.insertBefore(thCheck, theadRow.firstChild)

  // --- Checkbox par ligne de données ---
  tbody.querySelectorAll('tr[data-index]').forEach(function (tr) {
    var td = document.createElement('td')
    td.style.cssText = 'width:40px;padding-right:0;'
    var cb = document.createElement('input')
    cb.type = 'checkbox'
    cb.className = 'bulk-row-check bulk-checkbox'
    cb.dataset.id = tr.dataset.id || ''
    td.appendChild(cb)
    tr.insertBefore(td, tr.firstChild)
  })

  // --- Ajuster colspan des séparateurs ---
  tbody.querySelectorAll('tr[data-separator]').forEach(function (tr) {
    var td = tr.querySelector('td[colspan]')
    if (td) td.setAttribute('colspan', parseInt(td.getAttribute('colspan')) + 1)
  })

  // --- Barre d'actions flottante ---
  var bar = document.createElement('div')
  bar.className = 'bulk-action-bar'
  bar.id = 'bulk-action-bar-' + Math.random().toString(36).slice(2)
  bar.innerHTML =
    '<span class="bulk-count"></span>' +
    '<button type="button" class="bulk-clear-btn">Désélectionner tout</button>' +
    '<button type="button" class="btn-danger bulk-delete-submit-btn">' +
      '<i class="mdi mdi-trash-can"></i> Supprimer la sélection' +
    '</button>'
  document.body.appendChild(bar)

  var countEl = bar.querySelector('.bulk-count')
  var clearBtn = bar.querySelector('.bulk-clear-btn')
  var deleteBtn = bar.querySelector('.bulk-delete-submit-btn')

  // --- Modal de confirmation générique ---
  var modal = document.createElement('div')
  modal.className = 'modal-overlay'
  modal.id = 'bulk-confirm-modal-' + bar.id
  modal.innerHTML =
    '<div class="modal-box">' +
      '<div class="modal-icon"><i class="mdi mdi-trash-can-outline"></i></div>' +
      '<div class="modal-title bulk-modal-title"></div>' +
      '<div class="modal-text">Cette action est irréversible.</div>' +
      '<div class="modal-actions">' +
        '<button type="button" class="btn-ghost bulk-modal-cancel">Annuler</button>' +
        '<button type="button" class="btn-danger bulk-modal-confirm">' +
          '<i class="mdi mdi-trash-can"></i> Supprimer' +
        '</button>' +
      '</div>' +
    '</div>'
  document.body.appendChild(modal)

  var modalTitle = modal.querySelector('.bulk-modal-title')
  var modalCancel = modal.querySelector('.bulk-modal-cancel')
  var modalConfirm = modal.querySelector('.bulk-modal-confirm')

  function getChecked() {
    return Array.from(table.querySelectorAll('.bulk-row-check:checked'))
  }

  function updateBar() {
    var checked = getChecked()
    var n = checked.length
    if (n > 0) {
      countEl.textContent = n + ' élément' + (n > 1 ? 's' : '') + ' sélectionné' + (n > 1 ? 's' : '')
      bar.classList.add('visible')
    } else {
      bar.classList.remove('visible')
    }
    // Sync checkAll
    var allVisible = Array.from(table.querySelectorAll('tr[data-index]:not(.page-hidden) .bulk-row-check'))
    checkAll.indeterminate = n > 0 && n < allVisible.length
    checkAll.checked = n > 0 && n === allVisible.length
  }

  // Écouter les changements de checkbox
  tbody.addEventListener('change', function (e) {
    if (e.target.classList.contains('bulk-row-check')) updateBar()
  })

  checkAll.addEventListener('change', function () {
    var visible = table.querySelectorAll('tr[data-index]:not(.page-hidden) .bulk-row-check')
    visible.forEach(function (cb) { cb.checked = checkAll.checked })
    updateBar()
  })

  clearBtn.addEventListener('click', function () {
    table.querySelectorAll('.bulk-row-check').forEach(function (cb) { cb.checked = false })
    checkAll.checked = false
    bar.classList.remove('visible')
  })

  deleteBtn.addEventListener('click', function () {
    var n = getChecked().length
    modalTitle.textContent = 'Supprimer ' + n + ' élément' + (n > 1 ? 's' : '') + ' ?'
    modal.classList.add('open')
  })

  modalCancel.addEventListener('click', function () { modal.classList.remove('open') })
  modal.addEventListener('click', function (e) { if (e.target === modal) modal.classList.remove('open') })

  modalConfirm.addEventListener('click', function () {
    modal.classList.remove('open')
    var ids = getChecked().map(function (cb) { return cb.dataset.id })

    var form = document.createElement('form')
    form.method = 'POST'
    form.action = deleteUrl
    form.style.display = 'none'

    var tokenInput = document.createElement('input')
    tokenInput.type = 'hidden'
    tokenInput.name = '_token'
    tokenInput.value = csrfToken
    form.appendChild(tokenInput)

    ids.forEach(function (id) {
      var input = document.createElement('input')
      input.type = 'hidden'
      input.name = 'ids[]'
      input.value = id
      form.appendChild(input)
    })

    document.body.appendChild(form)
    form.submit()
  })
}
```

- [ ] **Step 2 : Appeler `initBulkDelete` dans le `DOMContentLoaded`**, juste après l'appel `initSorting` ajouté au Task 2 :

```javascript
  var bulkTables = document.querySelectorAll('table[data-bulk-delete]')
  bulkTables.forEach(function (table) { initBulkDelete(table) })
```

**Important :** `initBulkDelete` doit être appelé AVANT `initSorting` pour que `th.cellIndex` soit correct (la checkbox décale tous les indices de 1). Modifier l'ordre d'appel dans `DOMContentLoaded` :

```javascript
  // Init bulk delete AVANT sorting (le bulk ajoute une colonne qui décale les cellIndex)
  var bulkTables = document.querySelectorAll('table[data-bulk-delete]')
  bulkTables.forEach(function (table) { initBulkDelete(table) })

  var sortableTables = document.querySelectorAll('table[data-sortable]')
  sortableTables.forEach(function (table) { initSorting(table) })
```

- [ ] **Step 3 : Commit**

```bash
git add public/assets/js/admin.js
git commit -m "feat: add bulk delete to admin tables"
```

---

## Task 4 : Template de référence — cheval/list.html.twig

Ce template est traité en premier et sert de référence pour les suivants.

**Fichiers :**
- Modifier : `templates/admin/cheval/list.html.twig`

- [ ] **Step 1 : Ajouter `data-*` sur la balise `<table>`**

Remplacer :
```html
<table class="data-table">
```
Par :
```html
<table class="data-table"
    data-sortable="true"
    data-bulk-delete="true"
    data-bulk-delete-url="{{ path('app_admin_cheval_delete_bulk') }}"
    data-bulk-csrf-token="{{ csrf_token('bulk-delete') }}">
```

- [ ] **Step 2 : Ajouter `data-sort` sur les colonnes `<th>` triables**

Remplacer le bloc `<thead>` :
```html
<thead>
    <tr>
        <th>Cheval</th>
        <th>Race</th>
        <th>Sexe</th>
        <th>Date de naissance</th>
        <th>Propriétaires</th>
        {% if is_granted('ROLE_ADMIN') %}
            <th style="text-align:right">Actions</th>
        {% endif %}
    </tr>
</thead>
```
Par :
```html
<thead>
    <tr>
        <th data-sort="string">Cheval</th>
        <th data-sort="string">Race</th>
        <th>Sexe</th>
        <th data-sort="date">Date de naissance</th>
        <th>Propriétaires</th>
        {% if is_granted('ROLE_ADMIN') %}
            <th style="text-align:right">Actions</th>
        {% endif %}
    </tr>
</thead>
```

- [ ] **Step 3 : Ajouter `data-id` sur chaque `<tr>` de données**

Remplacer :
```html
<tr data-index="{{ loop.index0 }}">
```
Par :
```html
<tr data-index="{{ loop.index0 }}" data-id="{{ cheval.id }}">
```

- [ ] **Step 4 : Ajuster le `colspan` de la ligne vide** (la checkbox ajoute 1 colonne)

Remplacer :
```html
<td colspan="{{ is_granted('ROLE_ADMIN') ? 6 : 5 }}">
```
Par :
```html
<td colspan="{{ is_granted('ROLE_ADMIN') ? 7 : 6 }}">
```

- [ ] **Step 5 : Vérifier dans le navigateur** — Ouvrir `/admin/cheval/liste`. Les `<th>` Cheval, Race, Date de naissance doivent afficher une icône de tri. Cocher des lignes doit faire apparaître la barre flottante. Le bouton "Supprimer la sélection" ouvre un modal. **Ne pas encore tester la soumission** (la route backend n'existe pas encore).

- [ ] **Step 6 : Commit**

```bash
git add templates/admin/cheval/list.html.twig
git commit -m "feat(cheval): add sorting and bulk delete to list"
```

---

## Task 5 : Templates simples (sans séparateurs)

Templates concernés : `user/list.html.twig`, `entreprise/list.html.twig`, `structure/liste.html.twig`, `produit/liste.html.twig`, `mois_gestion/liste.html.twig`, `taxes/liste.html.twig`

Pour chaque template, appliquer les **4 mêmes changements** que Task 4, avec les spécificités suivantes :

### user/list.html.twig

- [ ] **Step 1 : Modifier `<table>`**

Remplacer `<table class="data-table" id="user-table">` par :
```html
<table class="data-table" id="user-table"
    data-sortable="true"
    data-bulk-delete="true"
    data-bulk-delete-url="{{ path('app_admin_user_delete_bulk') }}"
    data-bulk-csrf-token="{{ csrf_token('bulk-delete') }}">
```

- [ ] **Step 2 : `<th>` triables**

```html
<th data-sort="string">Utilisateur</th>
<th>Adresse</th>
<th>Rôles</th>
<th data-sort="string">Statut</th>
<th style="text-align:right">Actions</th>
```

- [ ] **Step 3 : `data-id` sur les `<tr>`**

`<tr data-index="{{ loop.index0 }}" data-id="{{ item.id }}">`

- [ ] **Step 4 : `colspan` ligne vide** : 5 → 6 (admin) / 4 → 5 (non-admin)

- [ ] **Step 5 : Commit**

```bash
git add templates/admin/user/list.html.twig
git commit -m "feat(user): add sorting and bulk delete to list"
```

---

### entreprise/list.html.twig

- [ ] **Step 1 : Modifier `<table>`**

```html
<table class="data-table"
    data-sortable="true"
    data-bulk-delete="true"
    data-bulk-delete-url="{{ path('app_admin_entreprise_delete_bulk') }}"
    data-bulk-csrf-token="{{ csrf_token('bulk-delete') }}">
```

- [ ] **Step 2 : `<th>` triables** — Colonnes : Entreprise, Adresse, Pays, Utilisateurs, Chevaux, Actions.

```html
<th data-sort="string">Entreprise</th>
<th>Adresse</th>
<th data-sort="string">Pays</th>
<th>Utilisateurs</th>
<th>Chevaux</th>
<th style="text-align:right">Actions</th>
```

- [ ] **Step 3 : `data-id`** — `<tr data-index="{{ loop.index0 }}" data-id="{{ item.id }}">`

- [ ] **Step 4 : Ajuster `colspan`** (+1 pour admin).

- [ ] **Step 5 : Commit**

```bash
git add templates/admin/entreprise/list.html.twig
git commit -m "feat(entreprise): add sorting and bulk delete to list"
```

---

### structure/liste.html.twig

- [ ] **Step 1 : Modifier `<table>`**

```html
<table class="data-table"
    data-sortable="true"
    data-bulk-delete="true"
    data-bulk-delete-url="{{ path('app_admin_structure_delete_bulk') }}"
    data-bulk-csrf-token="{{ csrf_token('bulk-delete') }}">
```

- [ ] **Step 2 : `<th>` triables** — `data-sort="string"` sur Nom.

- [ ] **Step 3 : `data-id`** sur les `<tr>`.

- [ ] **Step 4 : Ajuster `colspan`** (+1).

- [ ] **Step 5 : Commit**

```bash
git add templates/admin/structure/liste.html.twig
git commit -m "feat(structure): add sorting and bulk delete to list"
```

---

### produit/liste.html.twig

- [ ] **Step 1 : Modifier `<table>`**

```html
<table class="data-table"
    data-sortable="true"
    data-bulk-delete="true"
    data-bulk-delete-url="{{ path('app_admin_produit_delete_bulk') }}"
    data-bulk-csrf-token="{{ csrf_token('bulk-delete') }}">
```

- [ ] **Step 2 : `<th>` triables** — `data-sort="string"` sur Nom.

- [ ] **Step 3 : `data-id`** sur les `<tr>`.

- [ ] **Step 4 : Ajuster `colspan`** (+1).

- [ ] **Step 5 : Commit**

```bash
git add templates/admin/produit/liste.html.twig
git commit -m "feat(produit): add sorting and bulk delete to list"
```

---

### mois_gestion/liste.html.twig

- [ ] **Step 1 : Modifier `<table>`**

```html
<table class="data-table"
    data-sortable="true"
    data-bulk-delete="true"
    data-bulk-delete-url="{{ path('app_admin_mois_gestion_delete_bulk') }}"
    data-bulk-csrf-token="{{ csrf_token('bulk-delete') }}">
```

- [ ] **Step 2 : `<th>` triables** — Colonnes actuelles : Période, Chevaux suivis, Total général, Actions. Appliquer `data-sort="string"` sur Période.

- [ ] **Step 3 : `data-id`** — `<tr data-index="{{ loop.index0 }}" data-id="{{ item.id }}">`

- [ ] **Step 4 : `colspan`** — Actuellement 4 (fixe, pas de condition). Passer à 5.

- [ ] **Step 5 : Commit**

```bash
git add templates/admin/mois_gestion/liste.html.twig
git commit -m "feat(mois-gestion): add sorting and bulk delete to list"
```

---

### taxes/liste.html.twig

- [ ] **Step 1 : Lire le template** pour identifier les colonnes et la variable de boucle.

- [ ] **Step 2 : Modifier `<table>`**

```html
<table class="data-table"
    data-sortable="true"
    data-bulk-delete="true"
    data-bulk-delete-url="{{ path('app_admin_taxes_delete_bulk') }}"
    data-bulk-csrf-token="{{ csrf_token('bulk-delete') }}">
```

- [ ] **Step 3 : `<th>` triables** — `data-sort="string"` sur Nom.

- [ ] **Step 4 : `data-id`** sur les `<tr>`.

- [ ] **Step 5 : Ajuster `colspan`** (+1).

- [ ] **Step 6 : Commit**

```bash
git add templates/admin/taxes/liste.html.twig
git commit -m "feat(taxes): add sorting and bulk delete to list"
```

---

## Task 6 : Templates avec séparateurs — deplacement et distance

Ces deux templates ont des lignes séparateurs (`<tr data-separator="true">`) dont le `colspan` est géré dynamiquement via `is_granted`. Le JS de `initBulkDelete` ajuste déjà le colspan (+1) sur ces lignes. Il faut donc aussi corriger la valeur Twig initiale pour les lignes vides.

### deplacement/liste.html.twig

- [ ] **Step 1 : Modifier `<table>`**

```html
<table class="data-table"
    data-sortable="true"
    data-bulk-delete="true"
    data-bulk-delete-url="{{ path('app_admin_deplacement_delete_bulk') }}"
    data-bulk-csrf-token="{{ csrf_token('bulk-delete') }}">
```

- [ ] **Step 2 : `<th>` triables** — Colonnes : Nom, Date, Chevaux, Structure, Entreprise, Distance, Actions.

```html
<th data-sort="string">Nom</th>
<th data-sort="date">Date</th>
<th>Chevaux</th>
<th>Structure</th>
<th>Entreprise</th>
<th>Distance</th>
{% if is_granted('ROLE_ADMIN') %}
    <th style="text-align:right">Actions</th>
{% endif %}
```

- [ ] **Step 3 : `data-id`** sur les `<tr data-index>` (pas sur les `<tr data-separator>`) :

Remplacer `<tr data-index="{{ loop.index0 }}" data-month="{{ moisDeplacement }}">` par :
```html
<tr data-index="{{ loop.index0 }}" data-month="{{ moisDeplacement }}" data-id="{{ deplacement.id }}">
```

- [ ] **Step 4 : `colspan` ligne vide** — pas de ligne vide explicite dans ce template (la liste ne peut pas être vide côté Twig), vérifier si `{% else %}` existe et ajuster si nécessaire.

- [ ] **Step 5 : Vérifier** — Les séparateurs d'entreprise et de mois doivent rester affichés correctement avec la checkbox en première colonne.

- [ ] **Step 6 : Commit**

```bash
git add templates/admin/deplacement/liste.html.twig
git commit -m "feat(deplacement): add sorting and bulk delete to list"
```

---

### distance/liste.html.twig

- [ ] **Step 1 : Modifier `<table>`**

```html
<table class="data-table"
    data-sortable="true"
    data-bulk-delete="true"
    data-bulk-delete-url="{{ path('app_admin_distance_delete_bulk') }}"
    data-bulk-csrf-token="{{ csrf_token('bulk-delete') }}">
```

- [ ] **Step 2 : `<th>` triables** — Colonnes : Distance, Entreprise, Structure, Actions.

```html
<th data-sort="number">Distance</th>
<th data-sort="string">Entreprise</th>
<th>Structure</th>
{% if is_granted('ROLE_ADMIN') %}
    <th style="text-align:right">Actions</th>
{% endif %}
```

- [ ] **Step 3 : `data-id`** sur les `<tr data-index>` :

```html
<tr data-index="{{ loop.index0 }}" data-id="{{ item.id }}">
```

- [ ] **Step 4 : `colspan` ligne vide** — Remplacer `colspan="{{ is_granted('ROLE_ADMIN') ? 4 : 3 }}"` par `colspan="{{ is_granted('ROLE_ADMIN') ? 5 : 4 }}"` dans la ligne `empty-row`.

- [ ] **Step 5 : Commit**

```bash
git add templates/admin/distance/liste.html.twig
git commit -m "feat(distance): add sorting and bulk delete to list"
```

---

## Task 7 : Backend — Routes delete-bulk (ChevalController, UserController, EntrepriseController)

**Fichiers :**
- Modifier : `src/Controller/Admin/ChevalController.php`
- Modifier : `src/Controller/Admin/UserController.php`
- Modifier : `src/Controller/Admin/EntrepriseController.php`

### ChevalController.php

**Note :** La suppression d'un cheval est bloquée s'il a des propriétaires (logique existante). En bulk, on saute les chevaux bloqués et on informe.

- [ ] **Step 1 : Ajouter la route `delete-bulk`** après la route `delete` existante :

```php
#[Route('/delete-bulk', name: 'app_admin_cheval_delete_bulk', methods: ['POST'])]
public function deleteBulk(Request $request): Response
{
    $this->requireAdminAccess();

    if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
        throw $this->createAccessDeniedException('Token CSRF invalide.');
    }

    $ids = $request->request->all('ids');
    $deleted = 0;
    $skipped = 0;

    foreach ($ids as $id) {
        $cheval = $this->em->find(\App\Entity\Cheval::class, (int) $id);
        if (!$cheval) { continue; }
        if (!$cheval->getChevalProprietaires()->isEmpty()) { $skipped++; continue; }
        $this->em->remove($cheval);
        $deleted++;
    }
    $this->em->flush();

    if ($deleted > 0) {
        $this->addFlash('success', "$deleted cheval(x) supprimé(s).");
    }
    if ($skipped > 0) {
        $this->addFlash('danger', "$skipped cheval(x) non supprimé(s) car associé(s) à un propriétaire.");
    }

    return $this->redirectToRoute('app_admin_chevaux');
}
```

- [ ] **Step 2 : Commit**

```bash
git add src/Controller/Admin/ChevalController.php
git commit -m "feat(cheval): add delete-bulk route"
```

---

### UserController.php

- [ ] **Step 1 : Ajouter la route `delete-bulk`** — Repérer la méthode `delete()` existante et ajouter après :

```php
#[Route('/delete-bulk', name: 'app_admin_user_delete_bulk', methods: ['POST'])]
public function deleteBulk(Request $request): Response
{
    $this->requireAdminAccess();

    if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
        throw $this->createAccessDeniedException('Token CSRF invalide.');
    }

    $ids = $request->request->all('ids');
    $deleted = 0;

    foreach ($ids as $id) {
        $user = $this->em->find(\App\Entity\User::class, (int) $id);
        if (!$user) { continue; }
        $this->em->remove($user);
        $deleted++;
    }
    $this->em->flush();

    if ($deleted > 0) {
        $this->addFlash('success', "$deleted utilisateur(s) supprimé(s).");
    }

    return $this->redirectToRoute('app_admin_users');
}
```

- [ ] **Step 2 : Commit**

```bash
git add src/Controller/Admin/UserController.php
git commit -m "feat(user): add delete-bulk route"
```

---

### EntrepriseController.php

- [ ] **Step 1 : Ajouter la route `delete-bulk`** — Repérer la méthode `delete()` existante et ajouter après :

```php
#[Route('/delete-bulk', name: 'app_admin_entreprise_delete_bulk', methods: ['POST'])]
public function deleteBulk(Request $request): Response
{
    $this->requireAdminAccess();

    if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
        throw $this->createAccessDeniedException('Token CSRF invalide.');
    }

    $ids = $request->request->all('ids');
    $deleted = 0;

    foreach ($ids as $id) {
        $entreprise = $this->em->find(\App\Entity\Entreprise::class, (int) $id);
        if (!$entreprise) { continue; }
        $this->em->remove($entreprise);
        $deleted++;
    }
    $this->em->flush();

    if ($deleted > 0) {
        $this->addFlash('success', "$deleted entreprise(s) supprimée(s).");
    }

    return $this->redirectToRoute('app_admin_entreprises');
}
```

- [ ] **Step 2 : Commit**

```bash
git add src/Controller/Admin/EntrepriseController.php
git commit -m "feat(entreprise): add delete-bulk route"
```

---

## Task 8 : Backend — Routes delete-bulk (StructureController, DeplacementController, DistanceController)

### StructureController.php

- [ ] **Step 1 : Ajouter la route `delete-bulk`** :

```php
#[Route('/delete-bulk', name: 'app_admin_structure_delete_bulk', methods: ['POST'])]
public function deleteBulk(Request $request): Response
{
    $this->requireAdminAccess();

    if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
        throw $this->createAccessDeniedException('Token CSRF invalide.');
    }

    $ids = $request->request->all('ids');
    $deleted = 0;

    foreach ($ids as $id) {
        $structure = $this->em->find(\App\Entity\Structure::class, (int) $id);
        if (!$structure) { continue; }
        $this->em->remove($structure);
        $deleted++;
    }
    $this->em->flush();

    if ($deleted > 0) {
        $this->addFlash('success', "$deleted structure(s) supprimée(s).");
    }

    return $this->redirectToRoute('app_admin_structures');
}
```

- [ ] **Step 2 : Commit** — `git add src/Controller/Admin/StructureController.php && git commit -m "feat(structure): add delete-bulk route"`

---

### DeplacementController.php

- [ ] **Step 1 : Ajouter la route `delete-bulk`** :

```php
#[Route('/delete-bulk', name: 'app_admin_deplacement_delete_bulk', methods: ['POST'])]
public function deleteBulk(Request $request): Response
{
    $this->requireAdminAccess();

    if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
        throw $this->createAccessDeniedException('Token CSRF invalide.');
    }

    $ids = $request->request->all('ids');
    $deleted = 0;

    foreach ($ids as $id) {
        $deplacement = $this->em->find(\App\Entity\Deplacement::class, (int) $id);
        if (!$deplacement) { continue; }
        $this->em->remove($deplacement);
        $deleted++;
    }
    $this->em->flush();

    if ($deleted > 0) {
        $this->addFlash('success', "$deleted déplacement(s) supprimé(s).");
    }

    return $this->redirectToRoute('app_admin_deplacements');
}
```

- [ ] **Step 2 : Vérifier l'import** — L'entité `Deplacement` est déjà importée en tête de `DeplacementController.php`. Sinon ajouter `use App\Entity\Deplacement;`.

- [ ] **Step 3 : Commit** — `git add src/Controller/Admin/DeplacementController.php && git commit -m "feat(deplacement): add delete-bulk route"`

---

### DistanceController.php

**Attention :** Ce controller n'a pas de préfixe `#[Route]` au niveau de la classe. La route doit avoir un chemin complet.

- [ ] **Step 1 : Ajouter l'import en tête du fichier** (si absent) :

```php
use App\Entity\DistanceStructure;  // déjà présent
```

- [ ] **Step 2 : Ajouter la route `delete-bulk`** avec chemin complet :

```php
#[Route('/admin/distance/delete-bulk', name: 'app_admin_distance_delete_bulk', methods: ['POST'])]
public function deleteBulk(Request $request): Response
{
    $this->requireAdminAccess();

    if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
        throw $this->createAccessDeniedException('Token CSRF invalide.');
    }

    $ids = $request->request->all('ids');
    $deleted = 0;

    foreach ($ids as $id) {
        $distance = $this->em->find(\App\Entity\DistanceStructure::class, (int) $id);
        if (!$distance) { continue; }
        $this->em->remove($distance);
        $deleted++;
    }
    $this->em->flush();

    if ($deleted > 0) {
        $this->addFlash('success', "$deleted distance(s) supprimée(s).");
    }

    return $this->redirectToRoute('app_admin_distances');
}
```

- [ ] **Step 3 : Commit** — `git add src/Controller/Admin/DistanceController.php && git commit -m "feat(distance): add delete-bulk route"`

---

## Task 9 : Backend — Routes delete-bulk (ProduitController, MoisDeGestionController, TaxesController)

### ProduitController.php

- [ ] **Step 1 : Ajouter la route `delete-bulk`** :

```php
#[Route('/delete-bulk', name: 'app_admin_produit_delete_bulk', methods: ['POST'])]
public function deleteBulk(Request $request): Response
{
    $this->requireAdminAccess();

    if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
        throw $this->createAccessDeniedException('Token CSRF invalide.');
    }

    $ids = $request->request->all('ids');
    $deleted = 0;

    foreach ($ids as $id) {
        $produit = $this->em->find(\App\Entity\Produit::class, (int) $id);
        if (!$produit) { continue; }
        $this->em->remove($produit);
        $deleted++;
    }
    $this->em->flush();

    if ($deleted > 0) {
        $this->addFlash('success', "$deleted produit(s) supprimé(s).");
    }

    return $this->redirectToRoute('app_admin_produits');
}
```

- [ ] **Step 2 : Commit** — `git add src/Controller/Admin/ProduitController.php && git commit -m "feat(produit): add delete-bulk route"`

---

### MoisDeGestionController.php

- [ ] **Step 1 : Ajouter la route `delete-bulk`** :

```php
#[Route('/delete-bulk', name: 'app_admin_mois_gestion_delete_bulk', methods: ['POST'])]
public function deleteBulk(Request $request): Response
{
    $this->requireAdminAccess();

    if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
        throw $this->createAccessDeniedException('Token CSRF invalide.');
    }

    $ids = $request->request->all('ids');
    $deleted = 0;

    foreach ($ids as $id) {
        $mois = $this->em->find(\App\Entity\MoisDeGestion::class, (int) $id);
        if (!$mois) { continue; }
        $this->em->remove($mois);
        $deleted++;
    }
    $this->em->flush();

    if ($deleted > 0) {
        $this->addFlash('success', "$deleted relevé(s) supprimé(s).");
    }

    return $this->redirectToRoute('app_admin_mois_gestion');
}
```

- [ ] **Step 2 : Commit** — `git add src/Controller/Admin/MoisDeGestionController.php && git commit -m "feat(mois-gestion): add delete-bulk route"`

---

### TaxesController.php

**Attention :** Ce controller utilise `#[IsGranted]` au niveau de la classe, pas `BackofficeAccessTrait`. Ajouter `#[IsGranted('ROLE_ADMIN')]` au niveau de la méthode (les classes-level IsGranted sont ORés en Symfony, donc on ajoute la restriction admin explicitement au niveau méthode).

**Note :** Vérifier en lisant le fichier si `EntityManagerInterface` est injecté dans le constructeur ou en paramètre de méthode.

- [ ] **Step 1 : Lire `TaxesController.php`** pour vérifier l'injection de l'`EntityManagerInterface`.

- [ ] **Step 2 : Ajouter la route `delete-bulk`** :

```php
#[IsGranted('ROLE_ADMIN')]
#[Route('/delete-bulk', name: 'app_admin_taxes_delete_bulk', methods: ['POST'])]
public function deleteBulk(Request $request): Response
{
    if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
        throw $this->createAccessDeniedException('Token CSRF invalide.');
    }

    $ids = $request->request->all('ids');
    $deleted = 0;

    foreach ($ids as $id) {
        $taxes = $this->em->find(\App\Entity\Taxes::class, (int) $id);
        if (!$taxes) { continue; }
        $this->em->remove($taxes);
        $deleted++;
    }
    $this->em->flush();

    if ($deleted > 0) {
        $this->addFlash('success', "$deleted taxe(s) supprimée(s).");
    }

    return $this->redirectToRoute('app_admin_taxes');
}
```

Si `TaxesController` n'a pas `$this->em` mais injecte `EntityManagerInterface $em` en paramètre de méthode, adapter la signature :

```php
public function deleteBulk(Request $request, EntityManagerInterface $em): Response
```
Et remplacer `$this->em` par `$em` dans la méthode.

- [ ] **Step 3 : Commit** — `git add src/Controller/Admin/TaxesController.php && git commit -m "feat(taxes): add delete-bulk route"`

---

## Task 10 : Test de bout en bout

- [ ] **Step 1 : Vider le cache Symfony**

```bash
php bin/console cache:clear
```

- [ ] **Step 2 : Tester le tri** — Ouvrir `/admin/cheval/liste`. Cliquer sur "Cheval" : les lignes se trient A→Z. Recliquer : Z→A. Cliquer "Date de naissance" : tri par date. ✓

- [ ] **Step 3 : Tester la sélection**
  - Cocher une ligne → barre apparaît avec "1 élément sélectionné"
  - Cocher plusieurs → compteur se met à jour
  - Cocher la checkbox du `<thead>` → toutes les lignes de la page sélectionnées
  - Cliquer "Désélectionner tout" → barre disparaît ✓

- [ ] **Step 4 : Tester la suppression en masse**
  - Cocher 2 éléments de test
  - Cliquer "Supprimer la sélection"
  - Modal de confirmation s'affiche
  - Confirmer → POST envoyé → redirection avec flash success
  - Les éléments ne sont plus dans la liste ✓

- [ ] **Step 5 : Tester le CSRF** — Envoyer manuellement un POST à `/admin/cheval/delete-bulk` sans token valide → doit retourner 403.

- [ ] **Step 6 : Vérifier cheval avec propriétaire** — Sélectionner un cheval qui a un propriétaire et un qui n'en a pas. Supprimer les deux. Flash "1 supprimé, 1 non supprimé car associé à un propriétaire." ✓

- [ ] **Step 7 : Commit final**

```bash
git add -A
git commit -m "feat: UX - tri colonnes et suppression en masse sur toutes les listes admin"
```
