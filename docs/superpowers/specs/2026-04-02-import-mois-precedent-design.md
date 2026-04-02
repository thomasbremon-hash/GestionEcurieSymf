# Design : Import des quantités du mois précédent

**Date :** 2026-04-02
**Statut :** Approuvé

---

## Contexte

La création d'un mois de gestion nécessite de remplir manuellement les quantités pour chaque combinaison cheval × produit. La plupart du temps, les quantités sont identiques d'un mois à l'autre. L'objectif est d'ajouter un bouton "Importer le mois précédent" qui pré-remplit la grille.

---

## Comportement

### Par défaut
Le formulaire de création d'un mois affiche une grille vierge (quantités à 0/null), comme actuellement.

### Avec le bouton "Importer le mois précédent"
1. Un bouton "Importer le mois précédent" est affiché au-dessus du tableau de quantités
2. Au clic, le système récupère le dernier `MoisDeGestion` existant (le plus récent par année puis mois)
3. Les quantités de chaque `ChevalProduit` du mois précédent sont copiées dans les champs correspondants (même cheval + même produit)
4. Les chevaux présents ce mois mais absents du mois précédent gardent leurs quantités à 0
5. Le formulaire reste entièrement modifiable après l'import
6. L'admin ajuste ce qui a changé, puis sauvegarde normalement

### Cas limites
- **Pas de mois précédent** : le bouton est masqué ou désactivé
- **Nouveau cheval** : ajouté à la grille avec quantités à 0 (comportement actuel)
- **Cheval supprimé** : ses données du mois précédent sont ignorées

---

## Approche technique

### Option retenue : endpoint AJAX

Un endpoint API dans `MoisDeGestionController` retourne les quantités du dernier mois au format JSON. Le bouton déclenche un appel AJAX qui remplit les inputs du formulaire côté client.

### Endpoint
- Route : `/admin/mois-gestion/api/dernier-mois`
- Méthode : GET
- Retour : JSON `{ "chevalId-produitId": quantite, ... }`

### Côté formulaire
- Chaque input de quantité a un attribut `data-cheval` et `data-produit` pour identifier la combinaison
- Le JavaScript parcourt la réponse JSON et remplit les inputs correspondants

---

## Fichiers impactés

| Fichier | Action |
|---------|--------|
| `src/Controller/Admin/MoisDeGestionController.php` | Ajout endpoint API `dernierMois()` |
| `templates/admin/mois_gestion/mois_gestion.form.html.twig` | Ajout bouton + data-attributes sur les inputs + JS |

---

## Ce qui ne change pas

- La logique de sauvegarde du formulaire reste identique
- Le calcul des totaux reste côté serveur
- La génération des déplacements reste identique
- Le formulaire d'édition d'un mois existant n'est pas impacté
