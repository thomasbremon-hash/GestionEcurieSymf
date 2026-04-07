# Design : Modification de facture + Avoir

**Date :** 2026-04-07
**Périmètre :** Facturation — correction de factures (modification directe ou avoir)

---

## Contexte

Les factures ne peuvent pas être supprimées (Article L123-22 du Code de Commerce, conservation 10 ans). Mais une erreur dans une facture doit pouvoir être corrigée.

Deux cas selon l'état de la facture :

| État | `mailEnvoye` | Action autorisée |
|---|---|---|
| Non envoyée | `false` | Modification directe |
| Envoyée | `true` | Avoir d'annulation + nouvelle facture corrigée |

---

## Modifications de l'entité `FacturationUtilisateur`

### Champs ajoutés

```php
#[ORM\Column(length: 20)]
private string $type = 'facture'; // 'facture' ou 'avoir'

#[ORM\ManyToOne(targetEntity: self::class)]
#[ORM\JoinColumn(nullable: true)]
private ?FacturationUtilisateur $factureOrigine = null;
```

### Migration Doctrine
Une migration est nécessaire pour ajouter `type` (varchar 20, default 'facture') et `facture_origine_id` (int nullable, FK self-referential).

---

## Cas 1 : Facture non envoyée — Modification directe

### Condition
`facture.mailEnvoye === false`

### UX
- Dans la liste des factures, un bouton **"Modifier"** (icône `mdi-pencil`, couleur accent bleu) apparaît dans les actions, uniquement si `mailEnvoye = false` et `type = 'facture'`
- Cliquer ouvre la page de modification

### Formulaire `FacturationUtilisateurType`
Champs modifiables :
- `moisDeGestion` (EntityType, liste des MoisDeGestion)
- `entreprise` (EntityType)
- `utilisateur` (EntityType)

### Comportement à la sauvegarde
1. Mettre à jour les relations (mois, entreprise, utilisateur)
2. Recalculer le total via `FactureCalculator::calculerFactureUtilisateur($user, $mois)`
3. Mettre à jour `dateEmission` = now
4. `numFacture` inchangé
5. Redirect vers la liste + flash success

### Route
`#[Route('/edit/{id}', name: 'app_admin_facturation_edit', methods: ['GET', 'POST'])]`

### Template
`templates/admin/facturation/facturation.edit.html.twig`

---

## Cas 2 : Facture envoyée — Avoir + Nouvelle facture

### Condition
`facture.mailEnvoye === true` et `facture.type === 'facture'` et `facture.statut !== 'annulee'`

### UX
- Dans la liste, le bouton "Modifier" est remplacé par **"Corriger"** (icône `mdi-file-replace-outline`, couleur rouge)
- La page de correction affiche :
  - Un résumé de la facture originale (numéro, client, mois, total TTC)
  - Un avertissement : *"Cette facture a déjà été envoyée au client. La validation créera un avoir d'annulation (montant négatif) et une nouvelle facture corrigée prête à être renvoyée."*
  - Le même formulaire que la modification : mois, entreprise, utilisateur

### Comportement à la validation
Tout se passe dans une transaction :

**1. Créer l'avoir**
```
type          = 'avoir'
total         = -(total de la facture originale)
utilisateur   = utilisateur original
moisDeGestion = mois original
entreprise    = entreprise originale
numFacture    = 'AV-' + numFacture original (ex: AV-2026-04-0001)
statut        = 'impayee' (les avoirs ne sont pas payés)
dateEmission  = now
createdAt     = now
mailEnvoye    = false
factureOrigine = facture originale
```

**2. Marquer la facture originale**
```
statut = 'annulee'
```

**3. Créer la nouvelle facture corrigée**
```
type          = 'facture'
utilisateur   = données corrigées du formulaire
moisDeGestion = données corrigées du formulaire
entreprise    = données corrigées du formulaire
total         = recalcul via FactureCalculator
numFacture    = prochain numéro séquentiel (même logique que la génération)
statut        = 'impayee'
dateEmission  = now
createdAt     = now
mailEnvoye    = false
factureOrigine = null
```

### Routes
- `#[Route('/corriger/{id}', name: 'app_admin_facturation_corriger', methods: ['GET', 'POST'])]`

### Template
`templates/admin/facturation/facturation.corriger.html.twig`

---

## Affichage dans la liste

### Statut "Annulée"
Badge gris : `<span class="pill pill-annulee">Annulée</span>`

### Type "Avoir"
Badge rouge : `<span class="pill pill-avoir">Avoir</span>` — affiché à côté du numéro de facture

### Montant avoir
Affiché en rouge avec un `-` devant : `-150,00 €`

### Lien vers la facture d'origine (sur l'avoir)
Texte sous le numéro : *"Annule la facture YYYY-MM-NNNN"*

### Boutons d'action par état

| État | Boutons visibles |
|---|---|
| Facture non envoyée | Voir PDF, Télécharger, Modifier, Marquer payée |
| Facture envoyée non payée | Voir PDF, Télécharger, Corriger, Marquer payée |
| Facture payée | Voir PDF, Télécharger |
| Facture annulée | Voir PDF, Télécharger |
| Avoir | Voir PDF, Télécharger |

### CSS nouveaux
```css
.pill-annulee { background: var(--surface-2); color: var(--ink-muted); border: 1px solid var(--border); }
.pill-avoir   { background: var(--red-light); color: var(--red); border: 1px solid rgba(185,28,28,0.2); }
```

---

## Fichiers impactés

| Fichier | Type | Rôle |
|---|---|---|
| `src/Entity/FacturationUtilisateur.php` | Modifié | Ajout champs `type`, `factureOrigine` |
| `src/Form/FacturationUtilisateurType.php` | Créé | Formulaire modification/correction |
| `src/Controller/Admin/FacturationUtilisateurController.php` | Modifié | Routes edit + corriger |
| `templates/admin/facturation/facturation.edit.html.twig` | Créé | Page modification directe |
| `templates/admin/facturation/facturation.corriger.html.twig` | Créé | Page avoir + nouvelle facture |
| `templates/admin/facturation/liste.html.twig` | Modifié | Boutons, badges, affichage avoir/annulée |
| `public/assets/css/admin.css` | Modifié | `.pill-annulee`, `.pill-avoir` |
| Migration Doctrine | Créé | Ajout colonnes `type`, `facture_origine_id` |

---

## Statuts complets de l'entité

| Valeur | Signification | Utilisé sur |
|---|---|---|
| `'impayee'` | Facture émise, non payée | Facture normale + avoir |
| `'payee'` | Facture payée | Facture normale uniquement |
| `'annulee'` | Facture annulée par un avoir | Facture normale uniquement |

---

## Hors périmètre

- Envoi du mail de l'avoir au client (l'avoir est créé mais l'envoi se fait manuellement comme les factures normales)
- Numérotation séquentielle garantie sans trou (déjà en place, réutilisée)
- Interface de consultation des liens avoir ↔ facture d'origine (le lien textuel dans la liste suffit)
