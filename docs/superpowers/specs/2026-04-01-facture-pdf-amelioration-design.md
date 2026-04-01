# Design : Amélioration du template PDF de facture

**Date :** 2026-04-01
**Statut :** Approuvé

---

## Contexte

Le template PDF de facture (`templates/admin/facturation/pdf.html.twig`) manque d'informations légales et visuelles présentes sur une facture réelle. L'objectif est de le mettre en conformité avec un exemple de facture professionnelle.

---

## Approche retenue

**Option B — Complète** : ajout de champs en base de données + mise à jour du formulaire admin + modification du template PDF.

---

## 1. Nouveaux champs — Entité `Entreprise`

Quatre champs nullable ajoutés à `src/Entity/Entreprise.php` :

| Champ      | Type Doctrine       | Description                        |
|------------|---------------------|------------------------------------|
| `email`    | string(255), nullable | Email de contact de l'entreprise |
| `codeAPE`  | string(10), nullable  | Code APE (ex: "0143Z")           |
| `iban`     | string(34), nullable  | IBAN bancaire                    |
| `bic`      | string(11), nullable  | BIC/SWIFT                        |

Tous nullable pour ne pas casser les entreprises existantes.
Une migration Doctrine sera générée.

---

## 2. Formulaire admin Entreprise

Mise à jour du form type de l'entreprise pour exposer les 4 nouveaux champs, regroupés dans une section "Coordonnées bancaires & légales".

Fichiers concernés :
- `src/Form/` (form type Entreprise)
- `src/Controller/Admin/EntrepriseController.php`
- Template du formulaire entreprise

---

## 3. Modifications du template PDF

Fichier : `templates/admin/facturation/pdf.html.twig`

### En-tête émetteur (haut gauche)
- Afficher téléphone et email de l'entreprise (Mob. / Email)
- Afficher SIRET, SIREN, TVA intracom, Code APE
- Mention en dur : "Paiement de la TVA sur encaissement"

### Bloc informations facture
Remplace le bloc 3 colonnes actuel par une présentation en deux zones :
- **Adresse client** (haut droite)
- **Méta-facture** (sous l'en-tête) :
  - Référence : `numFacture`
  - Type d'opération : "Prestation de services" (en dur)
  - Date : date de génération (`'now'|date('d/m/Y')`)
  - Échéance : "À la réception de la facture" (en dur)

### Tableau des lignes
- Colonnes : Désignation | Quantité | Prix unitaire HT | Montant net HT | Taux TVA
- Pas de colonne Unité

### Bas de page gauche
Remplacer la note-box par un tableau récapitulatif TVA :

| Taux | Total HT | Montant TVA |
|------|----------|-------------|
| x %  | xxx €    | xxx €       |

### Bas de page droite — Totaux
- Total HT
- TVA (par taux)
- Total TTC
- **Net à payer** (= Total TTC)

### Coordonnées bancaires
Bloc sous les totaux affichant BIC et IBAN de l'entreprise (si renseignés).

### Talon de règlement
Bande découpable en bas de page (bordure pointillée) contenant :
- Référence (numFacture)
- Date (date de génération)
- Montant (Net à payer / Total TTC)

---

## 4. Ce qui reste en dur dans le template

- Type d'opération : "Prestation de services"
- Échéance : "À la réception de la facture"
- Mention TVA : "Paiement de la TVA sur encaissement"

---

## Fichiers impactés

| Fichier | Action |
|---------|--------|
| `src/Entity/Entreprise.php` | Ajout de 4 champs |
| Migration Doctrine | À générer |
| `src/Form/EntrepriseType.php` (ou équivalent) | Ajout des 4 champs |
| `src/Controller/Admin/EntrepriseController.php` | Si nécessaire |
| Template form entreprise | Ajout des champs dans l'UI |
| `templates/admin/facturation/pdf.html.twig` | Refonte de la présentation |
