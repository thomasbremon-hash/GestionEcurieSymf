# Conformité légale — Numérotation séquentielle & Factur-X

**Date :** 2026-04-08

## Périmètre

Deux sous-sujets indépendants :

1. **Numérotation séquentielle garantie** — empêcher tout trou ou doublon dans la séquence de numéros de factures, même sous charge concurrente.
2. **Anticipation e-invoicing 2026 (Factur-X MINIMUM)** — générer un fichier XML Factur-X téléchargeable depuis la liste des factures.

Le mécanisme avoir/rectificative est hors périmètre (déjà implémenté).

---

## 1. Numérotation séquentielle

### Problème actuel

Le controller extrait le dernier numéro via `preg_match('/\d{4}$/', ...)` sur le `numFacture` du dernier enregistrement trié par `id DESC`. Deux races conditions possibles :

- **Concurrence** : deux générations simultanées lisent le même "dernier numéro" avant que l'autre n'ait commité.
- **Fragile** : la logique est dupliquée dans `genererUtilisateur` et `corriger`, avec des filtres différents (l'une ne filtre pas `type='facture'`, l'autre oui).

### Solution retenue

Une entité `InvoiceCounter` à ligne unique (`id=1, counter=N`) protégée par un verrou pessimiste Doctrine (`LockMode::PESSIMISTIC_WRITE` → `SELECT … FOR UPDATE` en MySQL). Un service `InvoiceNumberService` encapsule l'incrément atomique et est injecté dans le controller.

#### InvoiceCounter

```
id: int (PK, pas d'auto-increment — on maîtrise l'init)
counter: int (séquence courante)
```

Initialisée en migration avec la valeur du MAX séquentiel existant en base.

#### InvoiceNumberService

```
reserveNumbers(int $count = 1): int
  → Démarre une transaction, verrouille la ligne, incrémente de $count, retourne le 1er numéro réservé
```

Le controller appelle `reserveNumbers(count($proprietaires))` avant la boucle de génération et `reserveNumbers(1)` dans `corriger`.

---

## 2. Factur-X MINIMUM (anticipation 2026)

### Décision de profil

Profil **MINIMUM** (niveau 1 de la norme Factur-X française) :
- Données obligatoires : identifiants facture, vendeur (nom + adresse + TVA), acheteur (nom), totaux.
- Pas de détail de lignes (facultatif au MINIMUM).
- Compatible avec toutes les plateformes PDP 2026.

### Approche : XML téléchargeable séparé

DOMPDF ne génère pas de PDF/A-3 (nécessaire pour l'embedding Factur-X hybride). L'embedding sera ajouté en 2025-2026 quand l'obligation entre en vigueur. Pour l'instant, l'XML est un fichier `.xml` téléchargeable depuis la liste des factures.

### Librairie

`horstoeko/zugferd` v1 (9 dépendances, installable sur PHP 8.3).

### Données mappées

| Champ Factur-X | Source |
|---|---|
| BT-1 N° facture | `facture.numFacture` |
| BT-2 Date | `facture.dateEmission` |
| BT-3 Type (380) | Commercial invoice (avoirs exclus) |
| BT-5 Devise | EUR |
| BT-27 Vendeur nom | `entreprise.nom` |
| Adresse vendeur | `entreprise.rue/cp/ville/pays` |
| BT-31 TVA vendeur | `entreprise.numTVA` |
| BT-44 Acheteur | `user.nom + ' ' + user.prenom` |
| BG-22 Totaux | `FactureCalculator` → totalHT/TVA/TTC |

### Bouton dans la liste

Nouveau bouton vert (icône `mdi-xml`) affiché uniquement pour `type='facture'`, entre le PDF téléchargeable et les boutons admin.

### Route

`GET /admin/facturation/facturx/{id}` → `app_admin_facturation_facturx`
Retourne un `Response` avec `Content-Type: application/xml` et `Content-Disposition: attachment`.
Guard : redirect si `type !== 'facture'`.

---

## Non-périmètre

- Embedding XML dans le PDF (PDF/A-3) — pour 2025-2026
- Profils BASIC/EN16931 — upgrade progressif
- Envoi via plateforme PDP — pour 2026
- Factur-X pour avoirs — type 381, implémentable ensuite
