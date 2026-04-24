# Refonte Facturation Utilisateur — Snapshot & Cycle de Vie Légal

**Date :** 2026-04-24
**Contexte :** Préparation conformité réforme facturation électronique 2026 (Factur-X / PDP) et résolution des limitations actuelles du module facturation.

---

## 1. Problème

Le module facturation actuel repose sur 3 entités qui collaborent pour produire une facture : `ChevalProduit`, `MoisDeGestion`, `FacturationUtilisateur`. Seul le total est stocké sur `FacturationUtilisateur` ; **le contenu (lignes de facture) est recalculé à la volée** par `FactureCalculator` depuis les `ChevalProduit` à chaque affichage, PDF, mail ou génération XML Factur-X.

Conséquences identifiées :

- **Pas d'intangibilité** — Modifier un `ChevalProduit` après émission d'une facture modifie rétroactivement le PDF et le XML de cette facture. Non conforme à l'Article 289 du CGI.
- **Impossible de corriger le contenu d'une facture** — Aucun champ modifiable côté facture ; la seule correction passe par `ChevalProduit`.
- **Workflow d'erreur disproportionné** — La moindre erreur sur facture envoyée impose un avoir total + nouvelle facture (trois documents pour corriger une ligne).
- **Snapshot émetteur/destinataire absent** — Si l'entreprise change d'adresse ou de forme juridique, toutes les factures historiques changent aussi.
- **Factur-X fragile** — Le XML se génère depuis le même calculateur, donc même faille pour 2026.

## 2. Objectifs

1. Rendre chaque facture **intangible après émission** (conformité légale + 2026).
2. Permettre la **modification fine des lignes** tant qu'une facture est en brouillon (non numérotée, non émise).
3. Permettre une **correction partielle par avoir** (sélection de lignes), sans annulation complète.
4. Introduire un **cycle de vie explicite** : brouillon → émise → (payée | corrigée par avoir).
5. Conserver la compatibilité Factur-X MINIMUM et préparer le passage à BASIC.

## 3. Non-objectifs

- Ne pas modifier le module `ChevalProduit` / `Deplacement` / `MoisDeGestion` (restent la source de vérité **de gestion**, découplés de la facturation).
- Ne pas migrer vers Factur-X BASIC / EN16931 dans cette itération (préparation seulement).
- Ne pas intégrer une PDP (Plateforme de Dématérialisation Partenaire) — sera une itération ultérieure.
- Ne pas réécrire le calcul métier (prix, pourcentages propriétaires) — reste dans `FactureCalculator` au moment de la création du brouillon uniquement.

## 4. Décisions clés (issues du brainstorming)

| # | Décision | Choisie |
|---|----------|---------|
| 1 | Pain points | Modifier contenu avant envoi (A) + Avoir partiel après envoi (B) |
| 2 | Stratégie snapshot | Hybride : snapshot à l'émission + possibilité de lignes libres (C) |
| 3 | Workflow avoir partiel | Sélection de lignes, pas de re-facturation automatique (A) |
| 4 | Cycle de vie | Statut "brouillon" sans numéro, émission explicite (A) |
| 5 | Migration | Snapshot rétroactif simple (A) |
| 6 | Modèle persistance lignes | Entité `FactureLigne` dédiée (1) |

## 5. Architecture cible

### 5.1 Nouvelle entité `FactureLigne`

```
FactureLigne
├── id : int (PK)
├── facture : ManyToOne → FacturationUtilisateur (not null)
├── position : int (ordre d'affichage sur le PDF)
├── chevalNom : string(255) — figé au moment de la création de la ligne
├── pourcentagePropriete : decimal(5,2) — figé
├── description : text — figé (nom produit ou commentaire déplacement)
├── quantite : decimal(10,2)
├── prixUnitaireHT : decimal(10,4)
├── tauxTVA : decimal(5,2) — figé
├── montantHT : decimal(10,2) — calculé (quantite × prixUnitaireHT)
├── montantTVA : decimal(10,2) — calculé (montantHT × tauxTVA / 100)
├── montantTTC : decimal(10,2) — calculé (montantHT + montantTVA)
├── origineChevalProduit : ManyToOne → ChevalProduit (nullable, onDelete: SET NULL)
│   └── trace non contractuelle ; si le ChevalProduit est supprimé, la ligne reste
└── ligneOrigine : ManyToOne → self (nullable)
    └── utilisé uniquement pour les lignes d'avoir partiel (pointe la ligne créditée)
```

**Invariants :**
- Les montants sont recalculés par la méthode `recomputeMontants()` sur chaque `setQuantite`/`setPrixUnitaireHT`/`setTauxTVA`.
- Les montants d'une ligne d'avoir sont **négatifs**.

### 5.2 Modifications de `FacturationUtilisateur`

**Champs ajoutés :**

```
+ lignes : OneToMany → FactureLigne (cascade: persist, orphanRemoval: true)
+ totalHT : decimal(10,2)
+ totalTVA : json — {"20.00": 120.50, "5.50": 3.30}
+ totalTTC : decimal(10,2)

-- Snapshot émetteur (copié au moment de l'émission, intangible) --
+ entrepriseNom : string(255)
+ entrepriseAdresse : text
+ entrepriseSiret : string(20)
+ entrepriseTva : string(20)
+ entrepriseFormeJuridique : string(50)
+ entrepriseCapitalSocial : decimal(12,2), nullable
+ entrepriseRcs : string(100), nullable

-- Snapshot destinataire (copié au moment de l'émission, intangible) --
+ clientNom : string(100)
+ clientPrenom : string(100)
+ clientEmail : string(180)
+ clientAdresse : text, nullable
```

**Champs modifiés :**

```
  numFacture : nullable désormais (null en brouillon)
  dateEmission : nullable désormais
  statut : nouveau littéral 'brouillon' ajouté (brouillon | impayee | payee | annulee)
  total : DEPRECATED — gardé pour compat temporaire, non utilisé dans la nouvelle logique
```

**Règle métier :** une facture est **immuable** (lignes + snapshot) dès que `statut != 'brouillon'`.

### 5.3 Services

| Service | Rôle |
|---------|------|
| `FactureLigneBuilder` | Nouveau. `fromChevalProduit(ChevalProduit, float $pourcentage): FactureLigne` — construit une ligne à partir d'une consommation cheval. |
| `FactureSnapshotService` | Nouveau. `emettre(FacturationUtilisateur): void` — transition brouillon → émise : numérotation, snapshot entreprise + client, date d'émission. Idempotent-safe (erreur si déjà émise). |
| `FactureEditionGuard` | Nouveau. `ensureEditable(FacturationUtilisateur): void` — garde appelée par tous les controllers d'édition de lignes. |
| `AvoirPartielService` | Nouveau. `creer(FacturationUtilisateur $source, array $ligneIds, array $quantitesPartielles): FacturationUtilisateur` — crée un avoir avec lignes négatives sélectionnées. |
| `FactureCalculator` | Utilisé **uniquement** à la génération du brouillon (plus pour affichage/PDF/mail/XML). |
| `InvoiceNumberService` | Inchangé. Appelé **uniquement** par `FactureSnapshotService::emettre()`. |
| `FacturXService` | Modifié. Lit `facture.lignes` au lieu de recalculer via `FactureCalculator`. |

## 6. Workflows

### 6.1 Génération mensuelle (brouillons)

Route : `app_admin_facturation_generer_utilisateur` (existante, logique refondue).

1. Pour chaque propriétaire concerné par le mois :
   - Créer un `FacturationUtilisateur` avec `statut='brouillon'`, `numFacture=null`, `dateEmission=null`.
   - Utiliser `FactureCalculator` pour obtenir les lignes théoriques.
   - Pour chaque ligne théorique : créer une `FactureLigne` via `FactureLigneBuilder::fromChevalProduit()`.
   - Recalculer `totalHT` / `totalTVA` / `totalTTC` sur la facture depuis les lignes.
2. **Pas de numérotation** à cette étape.
3. Flash : "N brouillons générés. Vérifiez-les avant émission."

### 6.2 Édition brouillon

Nouvelle route : `GET|POST /admin/facturation/{id}/lignes` — nom `app_admin_facturation_edit_lignes`.

- Guard : `FactureEditionGuard::ensureEditable()`.
- Formulaire Symfony : `CollectionType` sur `FacturationUtilisateur.lignes` avec `allow_add` + `allow_delete`.
- Chaque ligne : champs `chevalNom`, `description`, `quantite`, `prixUnitaireHT`, `tauxTVA` éditables.
- "Ajouter une ligne" → crée une `FactureLigne` vide (`origineChevalProduit=null`).
- À la soumission : recalcul des totaux de la facture + flush.

### 6.3 Émission formelle

Nouvelle route : `POST /admin/facturation/{id}/emettre` — nom `app_admin_facturation_emettre`.

- Guard : statut doit être `brouillon`.
- CSRF obligatoire.
- Appelle `FactureSnapshotService::emettre($facture)` :
  1. Vérifie que la facture a au moins 1 ligne avec `quantite > 0`.
  2. Réserve un numéro via `InvoiceNumberService::reserveNumbers(1)`.
  3. Assigne `numFacture = sprintf('%d-%02d-%04d', annee, mois, numero)`.
  4. Assigne `dateEmission = now()`.
  5. **Copie** les champs entreprise (nom, adresse, siret, tva, forme juridique, capital, rcs) depuis `facture.entreprise`.
  6. **Copie** les champs client (nom, prenom, email, adresse) depuis `facture.utilisateur`.
  7. Passe `statut = 'impayee'`.
  8. Flush.
- **Irréversible.**

### 6.4 Envoi mail

Route existante `app_admin_facturation_envoyer_mail` + bulk : inchangée dans sa structure. Seul le rendu du PDF change (voir 6.7).

Précondition ajoutée : `statut != 'brouillon'`.

### 6.5 Marquer comme payée

Route existante `app_admin_facturation_payer` : inchangée. Précondition : `statut = 'impayee'`.

### 6.6 Avoir partiel

Nouvelle route : `GET|POST /admin/facturation/{id}/avoir-partiel` — nom `app_admin_facturation_avoir_partiel`.

- Préconditions : facture d'origine émise (`statut != 'brouillon'`), `type = 'facture'`, `statut != 'annulee'`.
- Écran : tableau des lignes avec case à cocher + champ "quantité à créditer" (par défaut = quantité totale de la ligne).
- À la soumission :
  1. Créer `FacturationUtilisateur` avec `type='avoir'`, `statut='brouillon'`, `factureOrigine=$source`.
  2. Pour chaque ligne cochée : créer une `FactureLigne` avec `quantite = -quantite_partielle`, `prixUnitaireHT` identique, `ligneOrigine = ligne_source`, `description = "Avoir sur: " + description_source`.
  3. Appeler `FactureSnapshotService::emettre($avoir)` qui réserve un **numéro frais** via `InvoiceNumberService::reserveNumbers(1)` et l'applique au format `AV-YYYY-MM-NNNN`. Chaque avoir (même plusieurs sur la même facture d'origine) a donc son propre numéro → pas de collision sur la contrainte unique.
  4. La facture d'origine **n'est pas annulée** — elle garde son statut (impayee ou payee).

**Note sur la numérotation des avoirs :** compteur **partagé** avec les factures normales (via `InvoiceCounter`) pour éviter d'introduire une seconde table. Seul le préfixe `AV-` différencie un avoir d'une facture dans le numéro affiché.

### 6.7 PDF / Factur-X / Mail

- **PDF** (`admin/facturation/pdf.html.twig`) : reçoit directement `facture.lignes` (collection Doctrine). Grouping par `chevalNom` côté Twig (boucle sur lignes + regroupement). Toutes les mentions légales (entreprise, SIRET, TVA, etc.) sont lues depuis les **champs snapshot de la facture**, jamais depuis `facture.entreprise` (qui peut avoir changé depuis).
- **Factur-X XML** : `FacturXService::generateXml()` lit directement `facture.totalHT`, `facture.totalTVA`, `facture.totalTTC` (profil MINIMUM n'a pas besoin des lignes). Lecture des champs snapshot pour le vendeur et l'acheteur. Le passage à BASIC (plus tard) exploitera `facture.lignes`.
- **Mail** : corps inchangé ; PDF joint régénéré mais toujours identique puisque les lignes et snapshot sont figés.

## 7. Migration des données existantes

Commande Symfony : `php bin/console app:migrate:factures-snapshot`.

**Stratégie rétroactive (Option A validée) :**

1. Pour chaque `FacturationUtilisateur` existante où `numFacture IS NOT NULL` :
   - Calculer les lignes via `FactureCalculator::calculerFactureUtilisateur($user, $mois)`.
   - Persister chaque ligne en `FactureLigne` (avec `origineChevalProduit` quand possible).
   - Copier les champs entreprise et client depuis les entités liées (état **actuel** — honest best effort, documenté).
   - Calculer `totalHT`, `totalTVA`, `totalTTC` depuis les lignes migrées.
   - Si le nouveau `totalTTC` diffère de l'ancien `total` de plus de 0.01€ : log warning avec l'ID facture. **Ne pas corriger** le total stocké (intangibilité).
2. Pour les avoirs existants (`type='avoir'`) : créer une ligne unique avec `quantite=1`, `prixUnitaireHT = ancien total` (négatif), `description = "Avoir sur facture " + factureOrigine.numFacture`, `tauxTVA=0`.
3. Pour toutes les factures existantes : `statut` reste tel quel (elles étaient déjà émises, on ne les repasse pas en brouillon).
4. Marquer les factures migrées (optionnel : flag `migratedAt` en commentaire de commit, pas en champ DB).

**Reversibilité :** la migration est idempotente — la relancer n'ajoute pas de lignes en double (vérif par `COUNT(lignes) > 0` sur la facture avant migration).

## 8. Invariants & garde-fous

- **Service-level :** `FactureEditionGuard::ensureEditable()` appelé par :
  - `app_admin_facturation_edit_lignes` (GET et POST)
  - toute route future modifiant les lignes ou les totaux
  Lève `LogicException` si `statut != 'brouillon'`.
- **Numérotation :** `numFacture` unique (contrainte DB existante conservée).
- **DB :** pas de contrainte `CHECK` SQL sur l'immuabilité des lignes (portabilité Doctrine). Garde service-level suffisante.
- **Twig :** helpers ou `is_granted`-like : les boutons "Éditer lignes" et "Émettre" ne s'affichent que si `statut == 'brouillon'`. Le bouton "Avoir partiel" seulement si `statut in ['impayee', 'payee']` et `type == 'facture'`.
- **Tests :**
  - Unit : `FactureLigneBuilder`, `FactureLigne::recomputeMontants`, `FactureEditionGuard`, `AvoirPartielService`.
  - Integration : workflow complet brouillon → édition → émission → avoir partiel.
  - Commande migration : testée sur fixtures représentatives.

## 9. Impact — Récapitulatif des modifications

**Entités :**
- ✨ Nouvelle : `FactureLigne` (+ migration Doctrine)
- 🔧 Modifiée : `FacturationUtilisateur` (13 nouveaux champs, 2 champs devenus nullable, nouveau statut, relation OneToMany)

**Services :**
- ✨ Nouveaux : `FactureLigneBuilder`, `FactureSnapshotService`, `FactureEditionGuard`, `AvoirPartielService`
- 🔧 Modifié : `FacturXService` (lecture depuis lignes)
- ♻️ Usage réduit : `FactureCalculator` (uniquement à la génération brouillon)

**Controllers :**
- 🔧 `FacturationUtilisateurController::genererUtilisateur()` : crée des brouillons
- ✨ Nouvelle route : `edit_lignes` (GET+POST)
- ✨ Nouvelle route : `emettre` (POST+CSRF)
- ✨ Nouvelle route : `avoir_partiel` (GET+POST+CSRF)
- 🔧 `envoyer_mail`, `payer` : ajout guard sur statut
- ♻️ `edit` (ancien) et `corriger` (ancien) : **supprimées**, remplacées par les nouveaux workflows

**Templates :**
- 🔧 `admin/facturation/liste.html.twig` : badge brouillon, boutons contextuels mis à jour, colonnes N° facture nullable
- 🔧 `admin/facturation/pdf.html.twig` : lecture depuis `facture.lignes`, champs snapshot de la facture pour les mentions
- ✨ `admin/facturation/edit_lignes.html.twig` : formulaire CollectionType éditable
- ✨ `admin/facturation/avoir_partiel.html.twig` : sélection des lignes à créditer
- 🔧 `admin/facturation/mail.html.twig` : inchangé
- ❌ `admin/facturation/facturation.edit.html.twig`, `facturation.corriger.html.twig` : **supprimés**

**Commandes :**
- ✨ `app:migrate:factures-snapshot`

**CSS / JS :**
- Ajout d'un badge `.pill-brouillon` (gris) dans `admin.css`.
- JS éventuel pour recalculer les totaux côté client dans le form d'édition des lignes (pas obligatoire — recalcul serveur au submit suffit).

## 10. Ordre de mise en œuvre suggéré (à détailler dans le plan)

1. Créer `FactureLigne` + migration + ajouter les nouveaux champs à `FacturationUtilisateur` (sans rien casser).
2. Implémenter `FactureLigneBuilder`, `FactureEditionGuard`, `FactureSnapshotService`.
3. Commande de migration rétroactive + lancer en dev/staging.
4. Refondre `genererUtilisateur()` pour créer des brouillons.
5. Ajouter routes `edit_lignes` et `emettre` + templates.
6. Refondre `pdf.html.twig` et `FacturXService` pour lire depuis `lignes`.
7. Implémenter `AvoirPartielService` + route + template.
8. Supprimer `edit` / `corriger` anciennes + templates associés.
9. Mettre à jour `liste.html.twig` (badges, boutons contextuels).
10. Tests + lint + Q/A manuelle.

## 11. Risques & points de vigilance

- **Migration sur prod** : à faire avec backup DB complet préalable. La migration est lourde (crée potentiellement des centaines de lignes).
- **Divergence total historique** : si la migration détecte un écart de total sur une facture existante, on log mais on ne corrige pas. Ces cas doivent être revus manuellement à la main.
- **Snapshot entreprise figé** : une fois émise, si l'entreprise change de SIRET ou forme juridique, toutes les factures déjà émises affichent l'ancien état — c'est **voulu** (conformité) mais peut surprendre.
- **Factur-X MINIMUM** ne contient toujours pas de détail de lignes dans le XML — le passage à BASIC (une itération suivante) bénéficiera directement des `FactureLigne` stockées.
- **Pas de PDP** dans cette itération — la facture reste en format PDF classique ; le XML Factur-X est téléchargeable séparément.

---

## Annexe — Glossaire

- **Brouillon** : facture en préparation, sans numéro, sans valeur légale, entièrement éditable.
- **Émission** : transition irréversible qui assigne un numéro, fige les lignes et le snapshot, confère à la facture sa valeur légale.
- **Intangibilité** : obligation légale (Article 289 CGI) qu'une facture émise ne soit jamais modifiée.
- **Avoir partiel** : document de correction ne reprenant qu'une partie des lignes de la facture d'origine, laissant cette dernière dans son statut.
- **Snapshot** : copie figée des données émetteur (entreprise) et destinataire (client) au moment de l'émission.
- **Ligne libre** : ligne de facture sans `origineChevalProduit` — saisie manuellement (ajustement, frais exceptionnel).
