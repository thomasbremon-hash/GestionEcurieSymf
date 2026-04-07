# Facturation — Modification & Avoir Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permettre la correction d'une facture : modification directe si non envoyée, avoir + nouvelle facture si déjà envoyée.

**Architecture:** Deux nouvelles routes sur `FacturationUtilisateurController` (`edit` et `corriger`), un nouveau `FacturationUtilisateurType`, deux nouveaux templates, et des modifications de l'entité + liste. L'avoir est une `FacturationUtilisateur` avec `type='avoir'` et `total` négatif.

**Tech Stack:** Symfony 7.4, Doctrine ORM, Twig, PHP 8.2, DOMPDF (déjà en place)

---

## Fichiers impactés

| Fichier | Action |
|---|---|
| `src/Entity/FacturationUtilisateur.php` | Modifié — ajout `type`, `factureOrigine` |
| `src/Form/FacturationUtilisateurType.php` | Créé |
| `src/Controller/Admin/FacturationUtilisateurController.php` | Modifié — routes `edit` + `corriger`, index mis à jour |
| `templates/admin/facturation/facturation.edit.html.twig` | Créé |
| `templates/admin/facturation/facturation.corriger.html.twig` | Créé |
| `templates/admin/facturation/liste.html.twig` | Modifié — boutons, badges, montants avoirs |
| `public/assets/css/admin.css` | Modifié — `.pill-annulee`, `.pill-avoir` |
| Migration Doctrine | Créé — colonnes `type`, `facture_origine_id` |

---

### Task 1: Ajouter les champs `type` et `factureOrigine` à l'entité + migration

**Files:**
- Modify: `src/Entity/FacturationUtilisateur.php`
- Create: migration (auto-générée)

- [ ] **Step 1: Ajouter les champs dans l'entité**

Ouvrir `src/Entity/FacturationUtilisateur.php`. Après le champ `$mailEnvoye`, ajouter :

```php
#[ORM\Column(length: 20)]
private string $type = 'facture';

#[ORM\ManyToOne(targetEntity: self::class)]
#[ORM\JoinColumn(nullable: true)]
private ?FacturationUtilisateur $factureOrigine = null;
```

- [ ] **Step 2: Ajouter les getters/setters**

À la fin de la classe, avant la dernière accolade fermante, ajouter :

```php
public function getType(): string
{
    return $this->type;
}

public function setType(string $type): static
{
    $this->type = $type;
    return $this;
}

public function getFactureOrigine(): ?self
{
    return $this->factureOrigine;
}

public function setFactureOrigine(?self $factureOrigine): static
{
    $this->factureOrigine = $factureOrigine;
    return $this;
}
```

- [ ] **Step 3: Générer la migration**

```bash
php bin/console doctrine:migrations:diff
```

Attendu : un nouveau fichier `migrations/Version<timestamp>.php`

- [ ] **Step 4: Vérifier la migration générée**

Ouvrir le fichier de migration généré. Il doit contenir :
- `ALTER TABLE facturation_utilisateur ADD type VARCHAR(20) NOT NULL DEFAULT 'facture'`
- `ALTER TABLE facturation_utilisateur ADD facture_origine_id INT DEFAULT NULL`
- Une contrainte FK vers `facturation_utilisateur(id)` pour `facture_origine_id`

Si la migration contient autre chose d'inattendu, NE PAS l'exécuter — signaler le problème.

- [ ] **Step 5: Exécuter la migration**

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

Attendu : `[notice] Migrating up to Version<timestamp>`

- [ ] **Step 6: Vérifier en base**

```bash
php bin/console doctrine:query:sql "DESCRIBE facturation_utilisateur"
```

Attendu : colonnes `type` et `facture_origine_id` présentes.

- [ ] **Step 7: Commit**

```bash
git add src/Entity/FacturationUtilisateur.php migrations/
git commit -m "feat: add type and factureOrigine fields to FacturationUtilisateur"
```

---

### Task 2: Créer `FacturationUtilisateurType`

**Files:**
- Create: `src/Form/FacturationUtilisateurType.php`

- [ ] **Step 1: Créer le fichier**

```php
<?php

namespace App\Form;

use App\Entity\Entreprise;
use App\Entity\MoisDeGestion;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class FacturationUtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('utilisateur', EntityType::class, [
                'class'        => User::class,
                'choice_label' => fn(User $u) => $u->getNom() . ' ' . $u->getPrenom(),
                'placeholder'  => 'Choisir un client',
                'required'     => true,
            ])
            ->add('moisDeGestion', EntityType::class, [
                'class'        => MoisDeGestion::class,
                'choice_label' => fn(MoisDeGestion $m) => sprintf('%02d / %d', $m->getMois(), $m->getAnnee()),
                'placeholder'  => 'Choisir un mois',
                'required'     => true,
            ])
            ->add('entreprise', EntityType::class, [
                'class'        => Entreprise::class,
                'choice_label' => 'nom',
                'placeholder'  => 'Choisir une entreprise',
                'required'     => true,
            ]);
    }
}
```

- [ ] **Step 2: Vérifier la syntaxe**

```bash
php bin/console lint:container
```

Attendu : aucune erreur.

- [ ] **Step 3: Commit**

```bash
git add src/Form/FacturationUtilisateurType.php
git commit -m "feat: add FacturationUtilisateurType form"
```

---

### Task 3: Ajouter la route `edit` dans le controller (facture non envoyée)

**Files:**
- Modify: `src/Controller/Admin/FacturationUtilisateurController.php`

- [ ] **Step 1: Ajouter l'import `FacturationUtilisateurType` dans le controller**

En haut du fichier, dans le bloc `use`, ajouter :

```php
use App\Form\FacturationUtilisateurType;
```

- [ ] **Step 2: Ajouter la méthode `edit`**

Après la méthode `payer` (ligne ~121), ajouter :

```php
#[Route('/edit/{id}', name: 'app_admin_facturation_edit', methods: ['GET', 'POST'])]
public function edit(FacturationUtilisateur $facture, Request $request, FactureCalculator $calculator): Response
{
    $this->requireAdminAccess();

    if ($facture->isMailEnvoye() || $facture->getType() !== 'facture') {
        $this->addFlash('danger', 'Cette facture ne peut pas être modifiée directement.');
        return $this->redirectToRoute('app_admin_facturation_utilisateur');
    }

    $form = $this->createForm(FacturationUtilisateurType::class, $facture);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $calculator->calculerFactureUtilisateur($facture->getUtilisateur(), $facture->getMoisDeGestion());
        $facture->setTotal($data['totalTTC']);
        $facture->setDateEmission(new \DateTimeImmutable());
        $this->em->flush();

        $this->addFlash('success', 'Facture modifiée avec succès.');
        return $this->redirectToRoute('app_admin_facturation_utilisateur');
    }

    return $this->render('admin/facturation/facturation.edit.html.twig', [
        'form'    => $form,
        'facture' => $facture,
    ]);
}
```

- [ ] **Step 3: Vérifier les routes**

```bash
php bin/console debug:router | grep facturation
```

Attendu : `app_admin_facturation_edit` présente avec path `/admin/facturation/edit/{id}`.

- [ ] **Step 4: Commit**

```bash
git add src/Controller/Admin/FacturationUtilisateurController.php
git commit -m "feat: add facturation edit route for unsent invoices"
```

---

### Task 4: Créer le template `facturation.edit.html.twig`

**Files:**
- Create: `templates/admin/facturation/facturation.edit.html.twig`

- [ ] **Step 1: Créer le fichier**

```twig
{% extends 'layouts/base.admin.html.twig' %}

{% block title %}BackOffice | Modifier la facture {{ facture.numFacture }}{% endblock %}

{% block body %}
<div class="form-wrapper">
  <div class="breadcrumb-row">
    <a href="{{ path('app_admin_facturation_utilisateur') }}">Facturation</a>
    <span class="sep">›</span>
    <span class="current">Modifier {{ facture.numFacture }}</span>
  </div>

  <div class="page-top" style="margin-bottom:1.5rem;">
    <div>
      <div class="page-eyebrow">Admin › Gestion financière</div>
      <h1 class="page-title">Modifier la facture <span class="subtitle">{{ facture.numFacture }}</span></h1>
    </div>
  </div>

  <div class="form-card">
    <div class="form-card-header">
      <div class="section-icon"><i class="mdi mdi-pencil"></i></div>
      <h2 class="form-card-title">Modification directe</h2>
    </div>

    {{ form_start(form, { attr: { novalidate: 'novalidate' } }) }}
    {% include 'admin/_form_errors.html.twig' with { form: form } %}

    <div class="form-body">
      <div class="form-group" style="margin-bottom:0;">
        <div class="form-group-title"><i class="mdi mdi-tune"></i> Données de la facture</div>
        <div class="fields-row cols-3">
          <div class="form-field">
            {{ form_label(form.utilisateur) }}
            {{ form_widget(form.utilisateur) }}
            <div class="field-error">{{ form_errors(form.utilisateur) }}</div>
          </div>
          <div class="form-field">
            {{ form_label(form.moisDeGestion) }}
            {{ form_widget(form.moisDeGestion) }}
            <div class="field-error">{{ form_errors(form.moisDeGestion) }}</div>
          </div>
          <div class="form-field">
            {{ form_label(form.entreprise) }}
            {{ form_widget(form.entreprise) }}
            <div class="field-error">{{ form_errors(form.entreprise) }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="form-footer">
      <a href="{{ path('app_admin_facturation_utilisateur') }}" class="btn-ghost">
        <i class="mdi mdi-arrow-left"></i> Annuler
      </a>
      <button type="submit" class="btn-primary-custom">
        <i class="mdi mdi-content-save"></i> Enregistrer les modifications
      </button>
    </div>

    {{ form_end(form) }}
  </div>
</div>
{% endblock %}
```

- [ ] **Step 2: Tester visuellement**

Naviguer vers `/admin/facturation/edit/{id}` avec l'ID d'une facture non envoyée.
Attendu : formulaire affiché avec les 3 champs pré-remplis.

- [ ] **Step 3: Commit**

```bash
git add templates/admin/facturation/facturation.edit.html.twig
git commit -m "feat: add facturation edit template"
```

---

### Task 5: Ajouter la route `corriger` dans le controller (avoir + nouvelle facture)

**Files:**
- Modify: `src/Controller/Admin/FacturationUtilisateurController.php`

- [ ] **Step 1: Ajouter l'import `FacturationUtilisateurRepository` (déjà présent) et vérifier `EntityManagerInterface`**

Vérifier que ces imports sont présents (ils le sont déjà) :
```php
use App\Repository\FacturationUtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
```

- [ ] **Step 2: Ajouter la méthode `corriger`**

Après la méthode `edit`, ajouter :

```php
#[Route('/corriger/{id}', name: 'app_admin_facturation_corriger', methods: ['GET', 'POST'])]
public function corriger(
    FacturationUtilisateur $facture,
    Request $request,
    FactureCalculator $calculator,
    FacturationUtilisateurRepository $factureRepo
): Response {
    $this->requireAdminAccess();

    if (!$facture->isMailEnvoye() || $facture->getType() !== 'facture' || $facture->getStatut() === 'annulee') {
        $this->addFlash('danger', 'Cette facture ne peut pas être corrigée via un avoir.');
        return $this->redirectToRoute('app_admin_facturation_utilisateur');
    }

    $form = $this->createForm(FacturationUtilisateurType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $now = new \DateTimeImmutable();

        // 1. Créer l'avoir d'annulation
        $avoir = new FacturationUtilisateur();
        $avoir->setType('avoir');
        $avoir->setTotal(-$facture->getTotal());
        $avoir->setUtilisateur($facture->getUtilisateur());
        $avoir->setMoisDeGestion($facture->getMoisDeGestion());
        $avoir->setEntreprise($facture->getEntreprise());
        $avoir->setNumFacture('AV-' . $facture->getNumFacture());
        $avoir->setStatut('impayee');
        $avoir->setDateEmission($now);
        $avoir->setCreatedAt($now);
        $avoir->setMailEnvoye(false);
        $avoir->setFactureOrigine($facture);
        $this->em->persist($avoir);

        // 2. Marquer la facture originale comme annulée
        $facture->setStatut('annulee');

        // 3. Créer la nouvelle facture corrigée
        $dernierFacture = $factureRepo->createQueryBuilder('f')
            ->select('f.numFacture')
            ->where('f.type = :type')
            ->setParameter('type', 'facture')
            ->orderBy('f.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        $dernierNumero = 0;
        if ($dernierFacture && isset($dernierFacture['numFacture'])) {
            preg_match('/\d{4}$/', $dernierFacture['numFacture'], $matches);
            if (!empty($matches[0])) $dernierNumero = (int)$matches[0];
        }
        $nouveauNumero = $dernierNumero + 1;

        $nouvelleFacture = new FacturationUtilisateur();
        $nouvelleFacture->setType('facture');
        $nouvelleFacture->setUtilisateur($form->get('utilisateur')->getData());
        $nouvelleFacture->setMoisDeGestion($form->get('moisDeGestion')->getData());
        $nouvelleFacture->setEntreprise($form->get('entreprise')->getData());

        $moisCorrige = $form->get('moisDeGestion')->getData();
        $data = $calculator->calculerFactureUtilisateur(
            $form->get('utilisateur')->getData(),
            $moisCorrige
        );
        $nouvelleFacture->setTotal($data['totalTTC']);
        $nouvelleFacture->setNumFacture(sprintf('%d-%02d-%04d', $moisCorrige->getAnnee(), $moisCorrige->getMois(), $nouveauNumero));
        $nouvelleFacture->setStatut('impayee');
        $nouvelleFacture->setDateEmission($now);
        $nouvelleFacture->setCreatedAt($now);
        $nouvelleFacture->setMailEnvoye(false);
        $this->em->persist($nouvelleFacture);

        $this->em->flush();

        $this->addFlash('success', sprintf(
            'Avoir %s créé. Facture originale annulée. Nouvelle facture %s créée.',
            $avoir->getNumFacture(),
            $nouvelleFacture->getNumFacture()
        ));
        return $this->redirectToRoute('app_admin_facturation_utilisateur');
    }

    return $this->render('admin/facturation/facturation.corriger.html.twig', [
        'form'    => $form,
        'facture' => $facture,
    ]);
}
```

- [ ] **Step 3: Vérifier les routes**

```bash
php bin/console debug:router | grep facturation
```

Attendu : `app_admin_facturation_corriger` présente avec path `/admin/facturation/corriger/{id}`.

- [ ] **Step 4: Commit**

```bash
git add src/Controller/Admin/FacturationUtilisateurController.php
git commit -m "feat: add facturation corriger route with avoir workflow"
```

---

### Task 6: Créer le template `facturation.corriger.html.twig`

**Files:**
- Create: `templates/admin/facturation/facturation.corriger.html.twig`

- [ ] **Step 1: Créer le fichier**

```twig
{% extends 'layouts/base.admin.html.twig' %}

{% block title %}BackOffice | Corriger la facture {{ facture.numFacture }}{% endblock %}

{% block body %}
<div class="form-wrapper">
  <div class="breadcrumb-row">
    <a href="{{ path('app_admin_facturation_utilisateur') }}">Facturation</a>
    <span class="sep">›</span>
    <span class="current">Corriger {{ facture.numFacture }}</span>
  </div>

  <div class="page-top" style="margin-bottom:1.5rem;">
    <div>
      <div class="page-eyebrow">Admin › Gestion financière</div>
      <h1 class="page-title">Corriger la facture <span class="subtitle">{{ facture.numFacture }}</span></h1>
    </div>
  </div>

  {# Résumé de la facture originale #}
  <div class="form-card" style="margin-bottom:1.25rem;border-left:3px solid var(--red);">
    <div class="form-card-header">
      <div class="section-icon" style="background:var(--red-light);color:var(--red);"><i class="mdi mdi-file-document-alert"></i></div>
      <h2 class="form-card-title">Facture originale</h2>
    </div>
    <div class="form-body">
      <div class="fields-row cols-4" style="gap:1rem;">
        <div>
          <div style="font-size:0.75rem;color:var(--ink-muted);margin-bottom:0.25rem;">N° Facture</div>
          <div style="font-weight:600;">{{ facture.numFacture }}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--ink-muted);margin-bottom:0.25rem;">Client</div>
          <div style="font-weight:600;">{{ facture.utilisateur.nom }} {{ facture.utilisateur.prenom }}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--ink-muted);margin-bottom:0.25rem;">Mois</div>
          <div style="font-weight:600;">{{ '%02d'|format(facture.moisDeGestion.mois) }}/{{ facture.moisDeGestion.annee }}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--ink-muted);margin-bottom:0.25rem;">Total TTC</div>
          <div style="font-weight:600;">{{ facture.total|number_format(2, ',', ' ') }} €</div>
        </div>
      </div>
    </div>
  </div>

  {# Avertissement #}
  <div style="background:var(--red-light);border:1px solid rgba(185,28,28,0.2);border-radius:var(--radius);padding:1rem 1.25rem;margin-bottom:1.25rem;color:var(--red);display:flex;gap:0.75rem;align-items:flex-start;">
    <i class="mdi mdi-alert" style="font-size:1.25rem;margin-top:0.1rem;flex-shrink:0;"></i>
    <div>
      <strong>Cette facture a déjà été envoyée au client.</strong><br>
      La validation créera un avoir d'annulation (montant négatif) et une nouvelle facture corrigée prête à être renvoyée.
    </div>
  </div>

  {# Formulaire de correction #}
  <div class="form-card">
    <div class="form-card-header">
      <div class="section-icon" style="background:var(--red-light);color:var(--red);"><i class="mdi mdi-file-replace-outline"></i></div>
      <h2 class="form-card-title">Données corrigées</h2>
    </div>

    {{ form_start(form, { attr: { novalidate: 'novalidate' } }) }}
    {% include 'admin/_form_errors.html.twig' with { form: form } %}

    <div class="form-body">
      <div class="form-group" style="margin-bottom:0;">
        <div class="form-group-title"><i class="mdi mdi-tune"></i> Nouvelles données</div>
        <div class="fields-row cols-3">
          <div class="form-field">
            {{ form_label(form.utilisateur) }}
            {{ form_widget(form.utilisateur) }}
            <div class="field-error">{{ form_errors(form.utilisateur) }}</div>
          </div>
          <div class="form-field">
            {{ form_label(form.moisDeGestion) }}
            {{ form_widget(form.moisDeGestion) }}
            <div class="field-error">{{ form_errors(form.moisDeGestion) }}</div>
          </div>
          <div class="form-field">
            {{ form_label(form.entreprise) }}
            {{ form_widget(form.entreprise) }}
            <div class="field-error">{{ form_errors(form.entreprise) }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="form-footer">
      <a href="{{ path('app_admin_facturation_utilisateur') }}" class="btn-ghost">
        <i class="mdi mdi-arrow-left"></i> Annuler
      </a>
      <button type="submit" class="btn-danger" onclick="return confirm('Confirmer la création de l\'avoir et de la nouvelle facture ?')">
        <i class="mdi mdi-file-replace-outline"></i> Valider la correction
      </button>
    </div>

    {{ form_end(form) }}
  </div>
</div>
{% endblock %}
```

- [ ] **Step 2: Vérifier que `btn-danger` existe dans le CSS**

```bash
grep -n "btn-danger" public/assets/css/admin.css | head -5
```

Attendu : au moins une définition trouvée. Si absent, utiliser `btn-primary-custom` avec un style inline `background:var(--red)`.

- [ ] **Step 3: Commit**

```bash
git add templates/admin/facturation/facturation.corriger.html.twig
git commit -m "feat: add facturation corriger template"
```

---

### Task 7: Mettre à jour `liste.html.twig` (boutons + badges + montants avoirs)

**Files:**
- Modify: `templates/admin/facturation/liste.html.twig`
- Modify: `src/Controller/Admin/FacturationUtilisateurController.php` (method `index`)

**Context:** La liste est groupée par entreprise + mois. Les avoirs stockent leur `total` comme valeur négative mais `totauxTtc` recalcule via FactureCalculator. Pour les avoirs, on affiche `facture.total` directement.

- [ ] **Step 1: Mettre à jour le compteur de factures dans le `page-title`**

Dans `liste.html.twig`, remplacer la ligne du subtitle :

Avant :
```twig
<span class="subtitle">{{ factures|length }}
    facture{{ factures|length > 1 ? 's' }}</span>
```

Après :
```twig
<span class="subtitle">{{ factures|length }}
    document{{ factures|length > 1 ? 's' }}</span>
```

- [ ] **Step 2: Mettre à jour l'en-tête du tableau pour afficher le type**

Dans le `<thead>`, la colonne `N° Facture` doit aussi indiquer le type. Remplacer :

```twig
<th>N° Facture</th>
```

Par :

```twig
<th>N° / Type</th>
```

- [ ] **Step 3: Mettre à jour la cellule N° Facture pour afficher le badge avoir + lien facture d'origine**

Remplacer la cellule `<td>` du numéro de facture :

Avant :
```twig
<td>
    <span class="invoice-num">{{ facture.numFacture }}</span>
</td>
```

Après :
```twig
<td>
    <div style="display:flex;flex-direction:column;gap:0.25rem;">
        <span class="invoice-num">{{ facture.numFacture }}</span>
        {% if facture.type == 'avoir' %}
            <span class="pill pill-avoir" style="font-size:0.7rem;padding:0.1rem 0.5rem;width:fit-content;">Avoir</span>
            {% if facture.factureOrigine %}
                <span style="font-size:0.72rem;color:var(--ink-muted);">Annule la facture {{ facture.factureOrigine.numFacture }}</span>
            {% endif %}
        {% endif %}
    </div>
</td>
```

- [ ] **Step 4: Mettre à jour la cellule montant pour les avoirs**

Remplacer la cellule Total TTC :

Avant :
```twig
<td>
    <span class="amount-cell">{{ totauxTtc[facture.id]|number_format(2, ',', ' ') }}
        €</span>
</td>
```

Après :
```twig
<td>
    {% if facture.type == 'avoir' %}
        <span class="amount-cell" style="color:var(--red);">-{{ (facture.total|abs)|number_format(2, ',', ' ') }} €</span>
    {% else %}
        <span class="amount-cell">{{ totauxTtc[facture.id]|number_format(2, ',', ' ') }} €</span>
    {% endif %}
</td>
```

- [ ] **Step 5: Mettre à jour la cellule Statut pour afficher "Annulée"**

Remplacer le bloc `{% if facture.statut == 'payee' %}...{% endif %}` :

Avant :
```twig
{% if facture.statut == 'payee' %}
    <span class="pill pill-active">
        <i class="mdi mdi-check-circle"></i>
        Payée</span>
{% elseif facture.statut == 'impayee' %}
    <span class="pill pill-admin">
        <i class="mdi mdi-alert-circle"></i>
        Impayée</span>
{% else %}
    <span class="pill pill-pending">
        <i class="mdi mdi-clock-outline"></i>
        En attente</span>
{% endif %}
```

Après :
```twig
{% if facture.statut == 'payee' %}
    <span class="pill pill-active">
        <i class="mdi mdi-check-circle"></i>
        Payée</span>
{% elseif facture.statut == 'annulee' %}
    <span class="pill pill-annulee">
        <i class="mdi mdi-cancel"></i>
        Annulée</span>
{% elseif facture.statut == 'impayee' %}
    <span class="pill pill-admin">
        <i class="mdi mdi-alert-circle"></i>
        Impayée</span>
{% else %}
    <span class="pill pill-pending">
        <i class="mdi mdi-clock-outline"></i>
        En attente</span>
{% endif %}
```

- [ ] **Step 6: Mettre à jour les boutons d'action**

Remplacer le bloc des boutons d'action admin (le `{% if is_granted('ROLE_ADMIN') %}`) :

Avant :
```twig
{% if is_granted('ROLE_ADMIN') %}
    {% if facture.statut != 'payee' %}
        <a href="{{ path('app_admin_facturation_payer', {id: facture.id}) }}" class="action-btn" style="color:var(--green);border-color:rgba(61,107,82,0.3);background:var(--green-light);" title="Marquer payée">
            <i class="mdi mdi-cash-plus"></i>
        </a>
    {% endif %}
    {% if not facture.mailEnvoye %}
        <a href="{{ path('app_admin_facturation_envoyer_mail', {id: facture.id}) }}" class="action-btn" style="color:var(--gold);border-color:rgba(160,124,48,0.3);background:var(--gold-light);" title="Envoyer le mail">
            <i class="mdi mdi-email-send"></i>
        </a>
    {% endif %}
{% endif %}
```

Après :
```twig
{% if is_granted('ROLE_ADMIN') %}
    {% if facture.type == 'facture' and not facture.mailEnvoye and facture.statut != 'annulee' %}
        <a href="{{ path('app_admin_facturation_edit', {id: facture.id}) }}" class="action-btn" style="color:var(--sidebar-accent);border-color:rgba(79,128,196,0.3);background:rgba(79,128,196,0.08);" title="Modifier">
            <i class="mdi mdi-pencil"></i>
        </a>
    {% endif %}
    {% if facture.type == 'facture' and facture.mailEnvoye and facture.statut != 'annulee' %}
        <a href="{{ path('app_admin_facturation_corriger', {id: facture.id}) }}" class="action-btn" style="color:var(--red);border-color:rgba(185,28,28,0.3);background:var(--red-light);" title="Corriger (avoir)">
            <i class="mdi mdi-file-replace-outline"></i>
        </a>
    {% endif %}
    {% if facture.statut == 'impayee' and facture.type != 'avoir' %}
        <a href="{{ path('app_admin_facturation_payer', {id: facture.id}) }}" class="action-btn" style="color:var(--green);border-color:rgba(61,107,82,0.3);background:var(--green-light);" title="Marquer payée">
            <i class="mdi mdi-cash-plus"></i>
        </a>
    {% endif %}
    {% if not facture.mailEnvoye and facture.type == 'facture' and facture.statut != 'annulee' %}
        <a href="{{ path('app_admin_facturation_envoyer_mail', {id: facture.id}) }}" class="action-btn" style="color:var(--gold);border-color:rgba(160,124,48,0.3);background:var(--gold-light);" title="Envoyer le mail">
            <i class="mdi mdi-email-send"></i>
        </a>
    {% endif %}
{% endif %}
```

- [ ] **Step 7: Mettre à jour la méthode `index` du controller pour ne pas calculer les avoirs**

Dans `FacturationUtilisateurController::index()`, remplacer la boucle `$totauxTtc` :

Avant :
```php
$totauxTtc = [];
foreach ($factures as $facture) {
    $data = $calculator->calculerFactureUtilisateur($facture->getUtilisateur(), $facture->getMoisDeGestion());
    $totauxTtc[$facture->getId()] = $data['totalTTC'];
}
```

Après :
```php
$totauxTtc = [];
foreach ($factures as $facture) {
    if ($facture->getType() === 'avoir') {
        $totauxTtc[$facture->getId()] = $facture->getTotal();
        continue;
    }
    $data = $calculator->calculerFactureUtilisateur($facture->getUtilisateur(), $facture->getMoisDeGestion());
    $totauxTtc[$facture->getId()] = $data['totalTTC'];
}
```

- [ ] **Step 8: Tester visuellement**

Naviguer vers `/admin/facturation/liste`.
- Vérifier que les factures normales affichent les boutons corrects selon leur état
- Les avoirs (s'il y en a) doivent afficher le badge "Avoir" et le montant en rouge

- [ ] **Step 9: Commit**

```bash
git add templates/admin/facturation/liste.html.twig src/Controller/Admin/FacturationUtilisateurController.php
git commit -m "feat: update facturation list with edit/corriger buttons, avoir and annulee badges"
```

---

### Task 8: Ajouter les styles CSS pour `.pill-annulee` et `.pill-avoir`

**Files:**
- Modify: `public/assets/css/admin.css`

- [ ] **Step 1: Trouver la section des `.pill-*` dans admin.css**

```bash
grep -n "pill-active\|pill-admin\|pill-pending" public/assets/css/admin.css | head -10
```

Attendu : lignes avec les styles `.pill-active`, `.pill-admin`, `.pill-pending`.

- [ ] **Step 2: Ajouter les nouveaux styles à la suite des `.pill-*` existants**

Après la dernière entrée `.pill-*` dans `admin.css`, ajouter :

```css
.pill-annulee { background: var(--surface-2); color: var(--ink-muted); border: 1px solid var(--border); }
.pill-avoir   { background: var(--red-light); color: var(--red); border: 1px solid rgba(185,28,28,0.2); }
```

- [ ] **Step 3: Vérifier le rendu**

Naviguer vers la liste facturation. Si des factures annulées ou avoirs existent, vérifier que les badges sont affichés correctement.

- [ ] **Step 4: Commit**

```bash
git add public/assets/css/admin.css
git commit -m "feat: add pill-annulee and pill-avoir CSS styles"
```

---

## Self-Review Checklist

Après toutes les tâches :

- [ ] Facture non envoyée (`mailEnvoye=false`, `type='facture'`) → bouton "Modifier" visible, edit fonctionne
- [ ] Facture envoyée non payée non annulée → bouton "Corriger" visible, workflow avoir fonctionne
- [ ] Facture payée → uniquement Voir PDF + Télécharger
- [ ] Facture annulée → uniquement Voir PDF + Télécharger
- [ ] Avoir → badge "Avoir" + montant négatif rouge + "Annule la facture XXXX" + uniquement Voir PDF + Télécharger
- [ ] Numéro de la nouvelle facture est séquentiel (basé sur `type='facture'` uniquement)
- [ ] Numéro de l'avoir = `AV-` + numéro original
- [ ] La migration s'est bien appliquée (`type` default='facture', `facture_origine_id` nullable)
- [ ] Factures existantes ont `type='facture'` par défaut (grâce au DEFAULT en base)
