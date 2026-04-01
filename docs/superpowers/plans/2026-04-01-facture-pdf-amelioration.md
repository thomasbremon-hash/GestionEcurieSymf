# Amélioration du template PDF de facture — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ajouter les champs email, codeAPE, iban, bic à l'entité Entreprise et mettre à jour le template PDF de facture pour correspondre à une facture professionnelle réelle.

**Architecture:** Ajout de 4 champs nullable à l'entité `Entreprise` + migration Doctrine, mise à jour du formulaire admin, et refonte complète du template Twig `pdf.html.twig` généré via dompdf.

**Tech Stack:** Symfony 6/7, Doctrine ORM, Twig, dompdf

---

## Fichiers impactés

| Fichier | Action |
|---------|--------|
| `src/Entity/Entreprise.php` | Ajout des champs email, codeAPE, iban, bic |
| `migrations/` | Nouveau fichier généré par `make:migration` |
| `src/Form/EntrepriseType.php` | Ajout des 4 champs dans le form type |
| `templates/admin/entreprise/entreprise.form.html.twig` | Ajout d'une section "Coordonnées bancaires & légales" |
| `templates/admin/facturation/pdf.html.twig` | Refonte complète de la présentation |

---

## Task 1 : Ajout des champs à l'entité Entreprise

**Files:**
- Modify: `src/Entity/Entreprise.php`

- [ ] **Step 1 : Ajouter les 4 propriétés après `$numTVA` (ligne ~74)**

Dans `src/Entity/Entreprise.php`, après le bloc `$numTVA`, ajouter :

```php
#[ORM\Column(length: 255, nullable: true)]
private ?string $email = null;

#[ORM\Column(length: 10, nullable: true)]
private ?string $codeAPE = null;

#[ORM\Column(length: 34, nullable: true)]
private ?string $iban = null;

#[ORM\Column(length: 11, nullable: true)]
private ?string $bic = null;
```

- [ ] **Step 2 : Ajouter les getters/setters à la fin de la classe, avant la dernière accolade `}`**

```php
public function getEmail(): ?string
{
    return $this->email;
}

public function setEmail(?string $email): static
{
    $this->email = $email;
    return $this;
}

public function getCodeAPE(): ?string
{
    return $this->codeAPE;
}

public function setCodeAPE(?string $codeAPE): static
{
    $this->codeAPE = $codeAPE;
    return $this;
}

public function getIban(): ?string
{
    return $this->iban;
}

public function setIban(?string $iban): static
{
    $this->iban = $iban;
    return $this;
}

public function getBic(): ?string
{
    return $this->bic;
}

public function setBic(?string $bic): static
{
    $this->bic = $bic;
    return $this;
}
```

- [ ] **Step 3 : Commit**

```bash
git add src/Entity/Entreprise.php
git commit -m "feat: add email, codeAPE, iban, bic fields to Entreprise entity"
```

---

## Task 2 : Générer et exécuter la migration

**Files:**
- Create: `migrations/VersionXXXXXX.php` (généré automatiquement)

- [ ] **Step 1 : Générer la migration**

```bash
php bin/console make:migration
```

Résultat attendu : `[OK] Next: Review the new migration...`

- [ ] **Step 2 : Vérifier le contenu de la migration générée**

Le fichier dans `migrations/` doit contenir 4 `ADD COLUMN` :
- `email VARCHAR(255) DEFAULT NULL`
- `code_a_p_e VARCHAR(10) DEFAULT NULL`
- `iban VARCHAR(34) DEFAULT NULL`
- `bic VARCHAR(11) DEFAULT NULL`

- [ ] **Step 3 : Exécuter la migration**

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

Résultat attendu : `[OK] Successfully executed 1 migration`

- [ ] **Step 4 : Commit**

```bash
git add migrations/
git commit -m "feat: migration add email/codeAPE/iban/bic to entreprise table"
```

---

## Task 3 : Mettre à jour EntrepriseType

**Files:**
- Modify: `src/Form/EntrepriseType.php`

- [ ] **Step 1 : Ajouter les 4 champs dans `buildForm()`, après le bloc `->add('telephone', ...)`**

```php
->add('email', TextType::class, [
    'label' => 'Email',
    'required' => false,
    'attr' => ['class' => 'input', 'placeholder' => 'Email de l\'entreprise'],
])
->add('codeAPE', TextType::class, [
    'label' => 'Code APE',
    'required' => false,
    'attr' => ['class' => 'input', 'placeholder' => 'Ex: 0143Z'],
])
->add('iban', TextType::class, [
    'label' => 'IBAN',
    'required' => false,
    'attr' => ['class' => 'input', 'placeholder' => 'FR76...'],
])
->add('bic', TextType::class, [
    'label' => 'BIC',
    'required' => false,
    'attr' => ['class' => 'input', 'placeholder' => 'Ex: CMCIFRPP'],
])
```

- [ ] **Step 2 : Commit**

```bash
git add src/Form/EntrepriseType.php
git commit -m "feat: add email/codeAPE/iban/bic fields to EntrepriseType form"
```

---

## Task 4 : Mettre à jour le formulaire admin Entreprise

**Files:**
- Modify: `templates/admin/entreprise/entreprise.form.html.twig`

- [ ] **Step 1 : Ajouter un champ email dans la section "Coordonnées" existante (après le téléphone)**

Repérer le bloc section "Coordonnées" (ligne ~51) et ajouter après le champ téléphone :

```twig
<div class="fields-row cols-1" style="margin-top:1.25rem;">
  <div class="form-field">
    {{ form_label(formEntreprise.email) }}
    {{ form_widget(formEntreprise.email) }}
    <div class="field-error">{{ form_errors(formEntreprise.email) }}</div>
  </div>
</div>
```

- [ ] **Step 2 : Ajouter le Code APE dans la section "Informations légales" existante (après numTVA)**

Repérer le bloc `{% if formEntreprise.numTVA is defined %}` (ligne ~114) et ajouter après :

```twig
<div class="fields-row cols-1" style="margin-top:1.25rem;">
  <div class="form-field">
    {{ form_label(formEntreprise.codeAPE) }}
    {{ form_widget(formEntreprise.codeAPE) }}
    <div class="field-error">{{ form_errors(formEntreprise.codeAPE) }}</div>
  </div>
</div>
```

- [ ] **Step 3 : Ajouter une nouvelle section "Coordonnées bancaires" avant le footer du formulaire (avant `{# ── Footer ── #}`)**

```twig
{# ── Coordonnées bancaires ── #}
<div class="form-group" style="margin-bottom:0;">
  <div class="form-group-title">
    <i class="mdi mdi-bank-outline"></i> Coordonnées bancaires
  </div>
  <div class="fields-row cols-2">
    <div class="form-field">
      {{ form_label(formEntreprise.iban) }}
      {{ form_widget(formEntreprise.iban) }}
      <div class="field-error">{{ form_errors(formEntreprise.iban) }}</div>
    </div>
    <div class="form-field">
      {{ form_label(formEntreprise.bic) }}
      {{ form_widget(formEntreprise.bic) }}
      <div class="field-error">{{ form_errors(formEntreprise.bic) }}</div>
    </div>
  </div>
</div>
```

- [ ] **Step 4 : Tester le formulaire dans le navigateur**

Aller sur `/admin/entreprise/edit/{id}` et vérifier que :
- Le champ email apparaît dans la section Coordonnées
- Le champ Code APE apparaît dans la section Informations légales
- Les champs IBAN et BIC apparaissent dans la nouvelle section Coordonnées bancaires
- La soumission du formulaire enregistre bien les nouvelles valeurs

- [ ] **Step 5 : Commit**

```bash
git add templates/admin/entreprise/entreprise.form.html.twig
git commit -m "feat: add email/codeAPE/iban/bic fields to entreprise admin form"
```

---

## Task 5 : Refonte du template PDF

**Files:**
- Modify: `templates/admin/facturation/pdf.html.twig`

Variables disponibles dans le template :
- `facture` → `FacturationUtilisateur` (`.numFacture`, `.statut`, `.entreprise`)
- `facture.entreprise` → `Entreprise` (`.nom`, `.rue`, `.cp`, `.ville`, `.telephone`, `.email`, `.siren`, `.siret`, `.numTVA`, `.codeAPE`, `.iban`, `.bic`)
- `user` → `User` (`.prenom`, `.nom`, `.email`, `.rue`, `.cp`, `.ville`, `.pays`)
- `mois` → `MoisDeGestion` (`.mois`, `.annee`)
- `lignesParCheval` → tableau indexé par nom du cheval, chaque ligne a `.description`, `.quantite`, `.prixUnitaire`, `.montantHT`, `.tauxTVA`, `.montantTVA`, `.pourcentage`
- `totalHT` → float
- `totalTVA` → array `[taux => montantTVA]`
- `totalTTC` → float

- [ ] **Step 1 : Remplacer intégralement le contenu de `pdf.html.twig`**

```twig
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <style>
      * { margin: 0; padding: 0; box-sizing: border-box; }

      body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 8.5px;
        color: #1a1714;
        background: #fff;
        padding: 20px 24px;
        line-height: 1.4;
      }

      /* ═══ EN-TÊTE ═══ */
      .header {
        display: table;
        width: 100%;
        margin-bottom: 10px;
      }
      .header-left {
        display: table-cell;
        vertical-align: top;
        width: 55%;
      }
      .header-right {
        display: table-cell;
        vertical-align: top;
        width: 45%;
        text-align: left;
        padding-left: 20px;
      }

      .logo { height: 32px; display: block; margin-bottom: 4px; }

      .company-name { font-size: 11px; font-weight: bold; color: #1f2937; margin-bottom: 2px; }
      .company-info { font-size: 7.5px; color: #444; line-height: 1.6; }

      .client-label { font-size: 7px; font-weight: bold; color: #888; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 3px; }
      .client-name { font-size: 9px; font-weight: bold; color: #1f2937; margin-bottom: 2px; }
      .client-address { font-size: 8px; color: #444; line-height: 1.6; }

      /* ═══ TITRE FACTURE ═══ */
      .facture-title-block {
        margin: 10px 0 8px 0;
        border-top: 2px solid #1f2937;
        border-bottom: 1px solid #ddd;
        padding: 6px 0;
        display: table;
        width: 100%;
      }
      .facture-title-cell {
        display: table-cell;
        vertical-align: middle;
        width: 30%;
      }
      .facture-title {
        font-size: 16px;
        font-weight: bold;
        color: #1f2937;
        letter-spacing: 0.05em;
      }
      .facture-meta-cell {
        display: table-cell;
        vertical-align: middle;
        width: 70%;
      }
      .facture-meta-table { width: 100%; border-collapse: collapse; }
      .facture-meta-table td { font-size: 8px; padding: 1px 6px; }
      .facture-meta-table td.label { color: #888; width: 40%; }
      .facture-meta-table td.value { color: #1a1714; font-weight: bold; }

      /* ═══ TABLEAU DES LIGNES ═══ */
      table.lignes {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
      }
      table.lignes thead tr { background: #1f2937; }
      table.lignes thead th {
        color: #fff;
        padding: 5px 7px;
        font-size: 7px;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        font-weight: bold;
        text-align: left;
      }
      table.lignes thead th.r { text-align: right; }
      table.lignes thead th.c { text-align: center; }

      table.lignes tbody tr { border-bottom: 1px solid #f0ede8; }
      table.lignes tbody tr:last-child { border-bottom: none; }
      table.lignes tbody td {
        padding: 4px 7px;
        font-size: 8px;
        color: #1a1714;
        vertical-align: middle;
      }
      table.lignes tbody td.r { text-align: right; }
      table.lignes tbody td.c { text-align: center; color: #6b6560; }

      .cheval-row td {
        background: #eef0f5;
        font-weight: bold;
        font-size: 7.5px;
        color: #1f2937;
        padding: 3px 7px;
        border-top: 1px solid #ddd;
        border-bottom: none !important;
        letter-spacing: 0.03em;
        text-transform: uppercase;
      }

      /* ═══ BAS DE PAGE ═══ */
      .bottom-row {
        display: table;
        width: 100%;
        margin-bottom: 10px;
      }
      .bottom-left {
        display: table-cell;
        width: 50%;
        vertical-align: top;
        padding-right: 12px;
      }
      .bottom-right {
        display: table-cell;
        width: 50%;
        vertical-align: top;
      }

      /* Tableau récap TVA */
      table.recap-tva {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
      }
      table.recap-tva thead tr { background: #1f2937; }
      table.recap-tva thead th {
        color: #fff;
        padding: 4px 7px;
        font-size: 7px;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        text-align: right;
      }
      table.recap-tva thead th:first-child { text-align: left; }
      table.recap-tva tbody td {
        padding: 3px 7px;
        font-size: 8px;
        border-bottom: 1px solid #f0ede8;
        text-align: right;
      }
      table.recap-tva tbody td:first-child { text-align: left; }
      table.recap-tva tbody tr:last-child td { border-bottom: none; }

      /* Tableau totaux */
      table.totaux {
        width: 100%;
        border-collapse: collapse;
      }
      table.totaux td {
        padding: 3px 7px;
        font-size: 8px;
        border-bottom: 1px solid #f0ede8;
      }
      table.totaux td.r { text-align: right; font-weight: bold; }
      table.totaux tr:last-child td { border-bottom: none; }

      .ttc-row td {
        background: #1f2937 !important;
        color: #fff !important;
        font-size: 10px !important;
        font-weight: bold !important;
        padding: 5px 7px !important;
        border: none !important;
      }
      .ttc-row td.r { text-align: right; }

      /* Coordonnées bancaires */
      .bank-block {
        margin-top: 8px;
        border: 1px solid #ddd;
        border-radius: 3px;
        padding: 5px 8px;
        font-size: 7.5px;
        color: #444;
        line-height: 1.7;
      }
      .bank-block strong { color: #1f2937; }

      /* Talon de règlement */
      .talon {
        margin-top: 12px;
        border-top: 1px dashed #999;
        padding-top: 6px;
      }
      .talon-label {
        font-size: 6.5px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #999;
        margin-bottom: 4px;
      }
      .talon-row {
        display: table;
        width: 100%;
      }
      .talon-cell {
        display: table-cell;
        font-size: 8px;
        vertical-align: middle;
      }
      .talon-cell strong { font-size: 8.5px; color: #1f2937; }
    </style>
  </head>
  <body>

    {# ── En-tête ── #}
    <div class="header">
      <div class="header-left">
        <img src="assets/img/logopdf.png" class="logo" />
        <div class="company-name">{{ facture.entreprise.nom }}</div>
        <div class="company-info">
          {{ facture.entreprise.rue }}<br />
          {{ facture.entreprise.cp }} {{ facture.entreprise.ville }}<br />
          {% if facture.entreprise.telephone %}Mob. {{ facture.entreprise.telephone }}{% endif %}
          {% if facture.entreprise.telephone and facture.entreprise.email %} — {% endif %}
          {% if facture.entreprise.email %}E-mail : {{ facture.entreprise.email }}{% endif %}<br />
          SIRET : {{ facture.entreprise.siret }} — SIREN : {{ facture.entreprise.siren }}<br />
          {% if facture.entreprise.numTVA %}TVA intra : {{ facture.entreprise.numTVA }}<br />{% endif %}
          {% if facture.entreprise.codeAPE %}Code APE : {{ facture.entreprise.codeAPE }}<br />{% endif %}
          Paiement de la TVA sur encaissement
        </div>
      </div>
      <div class="header-right">
        <div class="client-label">Facturé à</div>
        <div class="client-name">{{ user.prenom }} {{ user.nom }}</div>
        <div class="client-address">
          {{ user.rue }}<br />
          {{ user.cp }} {{ user.ville }}<br />
          {{ user.pays }}
        </div>
      </div>
    </div>

    {# ── Titre FACTURE + méta ── #}
    <div class="facture-title-block">
      <div class="facture-title-cell">
        <div class="facture-title">FACTURE</div>
      </div>
      <div class="facture-meta-cell">
        <table class="facture-meta-table">
          <tr>
            <td class="label">Référence :</td>
            <td class="value">{{ facture.numFacture }}</td>
          </tr>
          <tr>
            <td class="label">Type d'opération :</td>
            <td class="value">Prestation de services</td>
          </tr>
          <tr>
            <td class="label">Date :</td>
            <td class="value">{{ 'now'|date('d/m/Y') }}</td>
          </tr>
          <tr>
            <td class="label">Échéance :</td>
            <td class="value">À la réception de la facture</td>
          </tr>
        </table>
      </div>
    </div>

    {# ── Tableau des lignes ── #}
    <table class="lignes">
      <thead>
        <tr>
          <th>Désignation</th>
          <th class="c">Qté</th>
          <th class="r">Prix unitaire HT</th>
          <th class="r">Montant net HT</th>
          <th class="c">Taux TVA</th>
        </tr>
      </thead>
      <tbody>
        {% for cheval, lignes in lignesParCheval %}
          <tr class="cheval-row">
            <td colspan="5">{{ cheval }} — {{ lignes[0].pourcentage }} %</td>
          </tr>
          {% for ligne in lignes %}
            <tr>
              <td style="padding-left:14px;">{{ ligne.description }}</td>
              <td class="c">{{ ligne.quantite }}</td>
              <td class="r">{{ ligne.prixUnitaire|number_format(2, ',', ' ') }} €</td>
              <td class="r">{{ ligne.montantHT|number_format(2, ',', ' ') }} €</td>
              <td class="c">{{ ligne.tauxTVA }} %</td>
            </tr>
          {% endfor %}
        {% endfor %}
      </tbody>
    </table>

    {# ── Bas de page ── #}
    <div class="bottom-row">
      <div class="bottom-left">
        {# Tableau récapitulatif TVA #}
        <table class="recap-tva">
          <thead>
            <tr>
              <th>Taux</th>
              <th>Total HT</th>
              <th>Montant TVA</th>
            </tr>
          </thead>
          <tbody>
            {% for taux, montantTVA in totalTVA %}
              <tr>
                <td>{{ taux }} %</td>
                <td>{{ (montantTVA / (taux / 100))|number_format(2, ',', ' ') }} €</td>
                <td>{{ montantTVA|number_format(2, ',', ' ') }} €</td>
              </tr>
            {% endfor %}
          </tbody>
        </table>
      </div>
      <div class="bottom-right">
        <table class="totaux">
          <tbody>
            <tr>
              <td>Total HT</td>
              <td class="r">{{ totalHT|number_format(2, ',', ' ') }} €</td>
            </tr>
            {% for taux, montant in totalTVA %}
              <tr>
                <td>TVA {{ taux }} %</td>
                <td class="r">{{ montant|number_format(2, ',', ' ') }} €</td>
              </tr>
            {% endfor %}
            <tr class="ttc-row">
              <td>Total TTC</td>
              <td class="r">{{ totalTTC|number_format(2, ',', ' ') }} €</td>
            </tr>
            <tr>
              <td><strong>Net à payer</strong></td>
              <td class="r"><strong>{{ totalTTC|number_format(2, ',', ' ') }} €</strong></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    {# ── Coordonnées bancaires ── #}
    {% if facture.entreprise.iban or facture.entreprise.bic %}
      <div class="bank-block">
        <strong>Coordonnées bancaires</strong> —
        {% if facture.entreprise.bic %}BIC : {{ facture.entreprise.bic }}{% endif %}
        {% if facture.entreprise.iban %} · IBAN : {{ facture.entreprise.iban }}{% endif %}
      </div>
    {% endif %}

    {# ── Talon de règlement ── #}
    <div class="talon">
      <div class="talon-label">Talon de règlement — à retourner avec votre paiement</div>
      <div class="talon-row">
        <div class="talon-cell" style="width:40%;">
          Référence : <strong>{{ facture.numFacture }}</strong>
        </div>
        <div class="talon-cell" style="width:30%;">
          Date : <strong>{{ 'now'|date('d/m/Y') }}</strong>
        </div>
        <div class="talon-cell" style="width:30%; text-align:right;">
          Montant : <strong>{{ totalTTC|number_format(2, ',', ' ') }} €</strong>
        </div>
      </div>
    </div>

  </body>
</html>
```

- [ ] **Step 2 : Tester la génération du PDF**

Aller sur `/admin/facturation/pdf/{id}` avec une facture existante et vérifier :
- En-tête : nom, adresse, téléphone, email, SIRET, SIREN, TVA intra, code APE, mention TVA sur encaissement
- Adresse client affichée en haut à droite
- Bloc FACTURE avec Référence, Type d'opération, Date, Échéance
- Tableau des lignes sans colonne Unité
- Tableau récap TVA en bas à gauche (Taux | Total HT | Montant TVA)
- Totaux en bas à droite avec "Net à payer"
- Coordonnées bancaires (si IBAN/BIC renseignés)
- Talon de règlement en bas avec référence, date, montant

- [ ] **Step 3 : Commit**

```bash
git add templates/admin/facturation/pdf.html.twig
git commit -m "feat: refonte template PDF facture avec infos légales et talon de règlement"
```
