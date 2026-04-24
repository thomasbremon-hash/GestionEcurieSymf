# Plan d'implémentation — Refonte Facturation Snapshot

> **Pour les agents :** SOUS-SKILL REQUIS : Utilisez `superpowers:subagent-driven-development` (recommandé) ou `superpowers:executing-plans` pour exécuter ce plan tâche par tâche. Les étapes utilisent la syntaxe checkbox (`- [ ]`) pour le suivi.

**Goal :** Rendre les factures intangibles après émission en stockant les lignes sur la facture elle-même, introduire un cycle de vie brouillon → émise, et permettre l'avoir partiel par sélection de lignes.

**Architecture :** Nouvelle entité `FactureLigne` (OneToMany depuis `FacturationUtilisateur`). Snapshot entreprise + client copié sur la facture au moment de l'émission. Services dédiés : `FactureLigneBuilder`, `FactureEditionGuard`, `FactureSnapshotService`, `AvoirPartielService`. Nouveau statut `brouillon` (numéro de facture nullable tant que non émise).

**Tech Stack :** Symfony 7.4, PHP 8.2, Doctrine ORM + Migrations, MySQL 8, Twig, DOMPDF, horstoeko/zugferd.

**Note tests :** Le projet n'a **pas** de PHPUnit installé. Les vérifications après chaque tâche se font via `php bin/console lint:twig`, `lint:container`, `doctrine:schema:validate`, et parcours fonctionnel manuel. Les commits suivent la granularité des tâches (1 tâche = 1 commit minimum).

**Spec source :** [`docs/superpowers/specs/2026-04-24-facturation-snapshot-design.md`](../specs/2026-04-24-facturation-snapshot-design.md)

---

## Structure des fichiers

**Entités :**
- `src/Entity/FactureLigne.php` — nouvelle
- `src/Repository/FactureLigneRepository.php` — nouveau
- `src/Entity/FacturationUtilisateur.php` — 13 nouveaux champs + statut brouillon + nullable

**Services :**
- `src/Service/FactureLigneBuilder.php` — construit une ligne depuis un `ChevalProduit`
- `src/Service/FactureEditionGuard.php` — garde service-level sur brouillon
- `src/Service/FactureSnapshotService.php` — transition brouillon vers émise
- `src/Service/AvoirPartielService.php` — création avoir par sélection de lignes
- `src/Service/FacturXService.php` — lecture depuis snapshot
- `src/Service/FactureCalculator.php` — usage réduit (brouillon seulement)

**Forms :**
- `src/Form/FactureLigneType.php` — édition d'une ligne
- `src/Form/FactureLignesEditType.php` — CollectionType wrapper pour l'édition brouillon

**Commande :**
- `src/Command/MigrateFacturesSnapshotCommand.php` — migration rétroactive

**Controller :**
- `src/Controller/Admin/FacturationUtilisateurController.php` — nouvelles routes, anciennes supprimées

**Templates :**
- `templates/admin/facturation/liste.html.twig` — badges + boutons contextuels
- `templates/admin/facturation/pdf.html.twig` — lecture depuis `facture.lignes` + snapshot
- `templates/admin/facturation/mail.html.twig` — inchangé
- `templates/admin/facturation/edit_lignes.html.twig` — nouveau
- `templates/admin/facturation/avoir_partiel.html.twig` — nouveau
- `templates/admin/facturation/facturation.edit.html.twig` — supprimé en fin de plan
- `templates/admin/facturation/facturation.corriger.html.twig` — supprimé en fin de plan

**Migration :**
- `migrations/Version<timestamp>.php` — générée par Doctrine

**CSS :**
- `public/assets/css/admin.css` — `.pill-brouillon` ajouté

---

## Ordre d'exécution

Les tâches sont conçues pour laisser le système fonctionnel après chaque commit. Les anciennes routes (`edit`, `corriger`) ne sont supprimées qu'à la Task 20, une fois les nouveaux workflows validés. La commande de migration rétroactive (Task 8) **doit être lancée** avant que les nouvelles routes utilisent `facture.lignes` pour l'affichage (Task 14).

---

### Task 1 : Créer l'entité `FactureLigne` (squelette)

**Files:**
- Create: `src/Entity/FactureLigne.php`
- Create: `src/Repository/FactureLigneRepository.php`

- [ ] **Step 1 : Créer le repository vide**

Créer `src/Repository/FactureLigneRepository.php` :

```php
<?php

namespace App\Repository;

use App\Entity\FactureLigne;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FactureLigne>
 */
class FactureLigneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactureLigne::class);
    }
}
```

- [ ] **Step 2 : Créer l'entité FactureLigne avec tous ses champs**

Créer `src/Entity/FactureLigne.php` :

```php
<?php

namespace App\Entity;

use App\Repository\FactureLigneRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FactureLigneRepository::class)]
class FactureLigne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FacturationUtilisateur $facture = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(length: 255)]
    private string $chevalNom = '';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '100.00'])]
    private string $pourcentagePropriete = '100.00';

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $quantite = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, options: ['default' => '0.0000'])]
    private string $prixUnitaireHT = '0.0000';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '0.00'])]
    private string $tauxTVA = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $montantHT = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $montantTVA = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $montantTTC = '0.00';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ChevalProduit $origineChevalProduit = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?FactureLigne $ligneOrigine = null;

    public function getId(): ?int { return $this->id; }

    public function getFacture(): ?FacturationUtilisateur { return $this->facture; }
    public function setFacture(?FacturationUtilisateur $facture): static { $this->facture = $facture; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }

    public function getChevalNom(): string { return $this->chevalNom; }
    public function setChevalNom(string $chevalNom): static { $this->chevalNom = $chevalNom; return $this; }

    public function getPourcentagePropriete(): string { return $this->pourcentagePropriete; }
    public function setPourcentagePropriete(string $v): static { $this->pourcentagePropriete = $v; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getQuantite(): string { return $this->quantite; }
    public function setQuantite(string $quantite): static { $this->quantite = $quantite; $this->recomputeMontants(); return $this; }

    public function getPrixUnitaireHT(): string { return $this->prixUnitaireHT; }
    public function setPrixUnitaireHT(string $v): static { $this->prixUnitaireHT = $v; $this->recomputeMontants(); return $this; }

    public function getTauxTVA(): string { return $this->tauxTVA; }
    public function setTauxTVA(string $tauxTVA): static { $this->tauxTVA = $tauxTVA; $this->recomputeMontants(); return $this; }

    public function getMontantHT(): string { return $this->montantHT; }
    public function getMontantTVA(): string { return $this->montantTVA; }
    public function getMontantTTC(): string { return $this->montantTTC; }

    public function getOrigineChevalProduit(): ?ChevalProduit { return $this->origineChevalProduit; }
    public function setOrigineChevalProduit(?ChevalProduit $cp): static { $this->origineChevalProduit = $cp; return $this; }

    public function getLigneOrigine(): ?self { return $this->ligneOrigine; }
    public function setLigneOrigine(?self $ligneOrigine): static { $this->ligneOrigine = $ligneOrigine; return $this; }

    /**
     * Recalcule HT, TVA, TTC à partir de quantite, prixUnitaireHT, tauxTVA.
     * Appelé automatiquement par les setters concernés.
     */
    public function recomputeMontants(): void
    {
        $qty  = (float) $this->quantite;
        $pu   = (float) $this->prixUnitaireHT;
        $taux = (float) $this->tauxTVA;

        $ht  = round($qty * $pu, 2);
        $tva = round($ht * $taux / 100, 2);
        $ttc = round($ht + $tva, 2);

        $this->montantHT  = number_format($ht, 2, '.', '');
        $this->montantTVA = number_format($tva, 2, '.', '');
        $this->montantTTC = number_format($ttc, 2, '.', '');
    }
}
```

- [ ] **Step 3 : Vérifier la compilation PHP**

Run :
```bash
php bin/console lint:container
```
Expected : pas d'erreur (le repository est auto-enregistré via ServiceEntityRepository).

- [ ] **Step 4 : Commit**

```bash
git add src/Entity/FactureLigne.php src/Repository/FactureLigneRepository.php
git commit -m "feat: ajout entite FactureLigne pour lignes de facture intangibles"
```

---

### Task 2 : Étendre `FacturationUtilisateur` avec snapshot + brouillon

**Files:**
- Modify: `src/Entity/FacturationUtilisateur.php` (ajouter champs snapshot, relation `lignes`, nullable sur `numFacture` et `dateEmission`, totalHT/TVA/TTC)

- [ ] **Step 1 : Ajouter les use et imports en haut du fichier**

Dans `src/Entity/FacturationUtilisateur.php`, remplacer le bloc use :

```php
use App\Repository\FacturationUtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
```

- [ ] **Step 2 : Rendre nullable `numFacture` et `dateEmission`**

Remplacer les annotations existantes :

```php
// AVANT
#[ORM\Column(length: 255, unique: true)]
private ?string $numFacture = null;

#[ORM\Column(type: 'datetime_immutable')]
private ?\DateTimeImmutable $dateEmission = null;

// APRES
#[ORM\Column(length: 255, unique: true, nullable: true)]
private ?string $numFacture = null;

#[ORM\Column(type: 'datetime_immutable', nullable: true)]
private ?\DateTimeImmutable $dateEmission = null;
```

Le setter `setDateEmission` doit accepter `?\DateTimeImmutable` :

```php
public function setDateEmission(?\DateTimeImmutable $dateEmission): static
{
    $this->dateEmission = $dateEmission;
    return $this;
}
```

Le setter `setNumFacture` doit accepter `?string` :

```php
public function setNumFacture(?string $numFacture): static
{
    $this->numFacture = $numFacture;
    return $this;
}
```

- [ ] **Step 3 : Changer le défaut de `statut` vers `brouillon` et ajouter nouveaux champs**

Remplacer la définition du champ `statut` :

```php
#[ORM\Column(length: 255)]
private ?string $statut = 'brouillon';
```

Puis, juste avant la dernière accolade de la classe, ajouter les nouveaux champs :

```php
    /**
     * @var Collection<int, FactureLigne>
     */
    #[ORM\OneToMany(mappedBy: 'facture', targetEntity: FactureLigne::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $lignes;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $totalHT = '0.00';

    #[ORM\Column(type: Types::JSON, options: ['default' => '{}'])]
    private array $totalTVA = [];

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $totalTTC = '0.00';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $entrepriseNom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $entrepriseAdresse = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $entrepriseSiret = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $entrepriseTva = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $entrepriseFormeJuridique = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $entrepriseCapitalSocial = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $entrepriseRcs = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $clientNom = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $clientPrenom = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $clientEmail = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $clientAdresse = null;
```

- [ ] **Step 4 : Initialiser `$lignes` dans un constructeur**

Ajouter juste avant le premier getter :

```php
    public function __construct()
    {
        $this->lignes = new ArrayCollection();
    }
```

- [ ] **Step 5 : Ajouter les getters/setters des nouveaux champs**

Ajouter avant la dernière accolade de la classe :

```php
    /**
     * @return Collection<int, FactureLigne>
     */
    public function getLignes(): Collection { return $this->lignes; }

    public function addLigne(FactureLigne $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setFacture($this);
        }
        return $this;
    }

    public function removeLigne(FactureLigne $ligne): static
    {
        if ($this->lignes->removeElement($ligne)) {
            if ($ligne->getFacture() === $this) {
                $ligne->setFacture(null);
            }
        }
        return $this;
    }

    public function getTotalHT(): string { return $this->totalHT; }
    public function setTotalHT(string $v): static { $this->totalHT = $v; return $this; }

    public function getTotalTVA(): array { return $this->totalTVA; }
    public function setTotalTVA(array $v): static { $this->totalTVA = $v; return $this; }

    public function getTotalTTC(): string { return $this->totalTTC; }
    public function setTotalTTC(string $v): static { $this->totalTTC = $v; return $this; }

    public function getEntrepriseNom(): ?string { return $this->entrepriseNom; }
    public function setEntrepriseNom(?string $v): static { $this->entrepriseNom = $v; return $this; }

    public function getEntrepriseAdresse(): ?string { return $this->entrepriseAdresse; }
    public function setEntrepriseAdresse(?string $v): static { $this->entrepriseAdresse = $v; return $this; }

    public function getEntrepriseSiret(): ?string { return $this->entrepriseSiret; }
    public function setEntrepriseSiret(?string $v): static { $this->entrepriseSiret = $v; return $this; }

    public function getEntrepriseTva(): ?string { return $this->entrepriseTva; }
    public function setEntrepriseTva(?string $v): static { $this->entrepriseTva = $v; return $this; }

    public function getEntrepriseFormeJuridique(): ?string { return $this->entrepriseFormeJuridique; }
    public function setEntrepriseFormeJuridique(?string $v): static { $this->entrepriseFormeJuridique = $v; return $this; }

    public function getEntrepriseCapitalSocial(): ?string { return $this->entrepriseCapitalSocial; }
    public function setEntrepriseCapitalSocial(?string $v): static { $this->entrepriseCapitalSocial = $v; return $this; }

    public function getEntrepriseRcs(): ?string { return $this->entrepriseRcs; }
    public function setEntrepriseRcs(?string $v): static { $this->entrepriseRcs = $v; return $this; }

    public function getClientNom(): ?string { return $this->clientNom; }
    public function setClientNom(?string $v): static { $this->clientNom = $v; return $this; }

    public function getClientPrenom(): ?string { return $this->clientPrenom; }
    public function setClientPrenom(?string $v): static { $this->clientPrenom = $v; return $this; }

    public function getClientEmail(): ?string { return $this->clientEmail; }
    public function setClientEmail(?string $v): static { $this->clientEmail = $v; return $this; }

    public function getClientAdresse(): ?string { return $this->clientAdresse; }
    public function setClientAdresse(?string $v): static { $this->clientAdresse = $v; return $this; }

    /**
     * Recalcule les totaux depuis la collection de lignes.
     */
    public function recomputeTotals(): void
    {
        $ht = 0.0;
        $ttc = 0.0;
        $tva = [];
        foreach ($this->lignes as $ligne) {
            $ht  += (float) $ligne->getMontantHT();
            $ttc += (float) $ligne->getMontantTTC();
            $taux = $ligne->getTauxTVA();
            if ((float) $taux > 0) {
                $tva[$taux] = (float) ($tva[$taux] ?? 0) + (float) $ligne->getMontantTVA();
            }
        }
        foreach ($tva as $taux => $montant) {
            $tva[$taux] = round($montant, 2);
        }
        $this->totalHT  = number_format(round($ht, 2), 2, '.', '');
        $this->totalTTC = number_format(round($ttc, 2), 2, '.', '');
        $this->totalTVA = $tva;
    }
```

- [ ] **Step 6 : Vérifier le schéma Doctrine**

Run :
```bash
php bin/console doctrine:schema:validate --skip-sync
```
Expected : "Mapping files are correct". Le sync DB sera résolu à la Task 3.

- [ ] **Step 7 : Commit**

```bash
git add src/Entity/FacturationUtilisateur.php
git commit -m "feat: ajout champs snapshot + relation lignes sur FacturationUtilisateur"
```

---

### Task 3 : Migration Doctrine

**Files:**
- Create: `migrations/Version<timestamp>.php` (généré par Doctrine)

- [ ] **Step 1 : Générer la migration**

Run :
```bash
php bin/console make:migration
```

- [ ] **Step 2 : Relire la migration et vérifier qu'elle couvre :**
  - Création de la table `facture_ligne`
  - Ajout des 13 nouveaux champs sur `facturation_utilisateur`
  - `num_facture` rendu nullable
  - `date_emission` rendu nullable
  - Changement du default de `statut`

Si un point manque, éditer le fichier généré à la main.

- [ ] **Step 3 : Vérifier à vide (dry-run)**

Run :
```bash
php bin/console doctrine:migrations:migrate --dry-run -n
```
Expected : liste des requêtes SQL, pas d'erreur de syntaxe.

- [ ] **Step 4 : BACKUP BASE puis exécuter**

**Instruction à l'engineer :** vérifier qu'un dump MySQL récent existe. Puis :

```bash
php bin/console doctrine:migrations:migrate -n
```
Expected : `[OK] Successfully migrated`.

- [ ] **Step 5 : Vérifier la structure en DB**

Run :
```bash
php bin/console doctrine:schema:validate
```
Expected : "Mapping files are correct" ET "Database schema is in sync with the mapping files".

- [ ] **Step 6 : Commit**

```bash
git add migrations/
git commit -m "feat: migration facture_ligne + snapshot fields + nullable numFacture"
```

---

### Task 4 : Service `FactureLigneBuilder`

**Files:**
- Create: `src/Service/FactureLigneBuilder.php`

- [ ] **Step 1 : Créer le service**

```php
<?php

namespace App\Service;

use App\Entity\ChevalProduit;
use App\Entity\FactureLigne;

/**
 * Construit une FactureLigne à partir d'un ChevalProduit (consommation mensuelle),
 * en appliquant le pourcentage de propriété d'un copropriétaire.
 */
class FactureLigneBuilder
{
    public function fromChevalProduit(ChevalProduit $cp, float $pourcentage): FactureLigne
    {
        $cheval  = $cp->getCheval();
        $produit = $cp->getProduit();

        $description = $produit->getNom();
        if ($produit->getNom() === 'Déplacement' && $cp->getCommentaire()) {
            $description = $cp->getCommentaire();
        }

        $prixProrata = (float) $cp->getPrixUnitaire() * ($pourcentage / 100);
        $tauxTVA     = (float) ($produit->getTauxTVA() ?? 0);

        $ligne = new FactureLigne();
        $ligne
            ->setChevalNom($cheval->getNom() ?? '')
            ->setPourcentagePropriete(number_format($pourcentage, 2, '.', ''))
            ->setDescription($description)
            ->setTauxTVA(number_format($tauxTVA, 2, '.', ''))
            ->setPrixUnitaireHT(number_format($prixProrata, 4, '.', ''))
            ->setQuantite(number_format((float) $cp->getQuantite(), 2, '.', ''))
            ->setOrigineChevalProduit($cp);

        return $ligne;
    }
}
```

- [ ] **Step 2 : Vérifier l'auto-wiring**

Run :
```bash
php bin/console debug:container FactureLigneBuilder
```

- [ ] **Step 3 : Commit**

```bash
git add src/Service/FactureLigneBuilder.php
git commit -m "feat: service FactureLigneBuilder"
```

---

### Task 5 : Service `FactureEditionGuard`

**Files:**
- Create: `src/Service/FactureEditionGuard.php`

- [ ] **Step 1 : Créer le garde**

```php
<?php

namespace App\Service;

use App\Entity\FacturationUtilisateur;

/**
 * Garantit qu'une facture est éditable (statut brouillon).
 */
class FactureEditionGuard
{
    public function ensureEditable(FacturationUtilisateur $facture): void
    {
        if ($facture->getStatut() !== 'brouillon') {
            throw new \LogicException(sprintf(
                'La facture %s n\'est plus modifiable (statut: %s). Seuls les brouillons peuvent etre edites.',
                $facture->getNumFacture() ?? '#' . $facture->getId(),
                $facture->getStatut()
            ));
        }
    }

    public function isEditable(FacturationUtilisateur $facture): bool
    {
        return $facture->getStatut() === 'brouillon';
    }
}
```

- [ ] **Step 2 : lint:container**

```bash
php bin/console lint:container
```

- [ ] **Step 3 : Commit**

```bash
git add src/Service/FactureEditionGuard.php
git commit -m "feat: service FactureEditionGuard (verrou statut brouillon)"
```

---

### Task 6 : Service `FactureSnapshotService`

**Files:**
- Create: `src/Service/FactureSnapshotService.php`

- [ ] **Step 1 : Créer le service**

```php
<?php

namespace App\Service;

use App\Entity\FacturationUtilisateur;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Transition irréversible brouillon vers émise.
 * Assigne un numéro, fige la date d'émission, copie les données entreprise + client
 * en snapshot sur la facture elle-même.
 */
class FactureSnapshotService
{
    public function __construct(
        private EntityManagerInterface $em,
        private InvoiceNumberService $invoiceNumberService,
    ) {}

    public function emettre(FacturationUtilisateur $facture): void
    {
        if ($facture->getStatut() !== 'brouillon') {
            throw new \LogicException(sprintf(
                'La facture #%d est deja emise (statut: %s).',
                $facture->getId(),
                $facture->getStatut()
            ));
        }

        if ($facture->getLignes()->isEmpty()) {
            throw new \LogicException('Impossible d\'emettre une facture sans ligne.');
        }

        $hasLigneNonNulle = false;
        foreach ($facture->getLignes() as $ligne) {
            if ((float) $ligne->getQuantite() > 0 || (float) $ligne->getMontantTTC() !== 0.0) {
                $hasLigneNonNulle = true;
                break;
            }
        }
        if (!$hasLigneNonNulle) {
            throw new \LogicException('Impossible d\'emettre une facture dont toutes les lignes sont a 0.');
        }

        $mois = $facture->getMoisDeGestion();
        $numero = $this->invoiceNumberService->reserveNumbers(1);

        $prefix = $facture->getType() === 'avoir' ? 'AV-' : '';
        $numFacture = sprintf(
            '%s%d-%02d-%04d',
            $prefix,
            $mois->getAnnee(),
            $mois->getMois(),
            $numero
        );
        $facture->setNumFacture($numFacture);

        $now = new \DateTimeImmutable();
        $facture->setDateEmission($now);
        if ($facture->getCreatedAt() === null) {
            $facture->setCreatedAt($now);
        }

        $entreprise = $facture->getEntreprise();
        if ($entreprise) {
            $facture
                ->setEntrepriseNom($entreprise->getNom())
                ->setEntrepriseAdresse(sprintf(
                    "%s\n%s %s\n%s",
                    $entreprise->getRue() ?? '',
                    $entreprise->getCp() ?? '',
                    $entreprise->getVille() ?? '',
                    $entreprise->getPays() ?? ''
                ))
                ->setEntrepriseSiret($entreprise->getSiret())
                ->setEntrepriseTva($entreprise->getNumTVA())
                ->setEntrepriseFormeJuridique(
                    method_exists($entreprise, 'getFormeJuridique') ? $entreprise->getFormeJuridique() : null
                )
                ->setEntrepriseCapitalSocial(
                    method_exists($entreprise, 'getCapitalSocial') && $entreprise->getCapitalSocial() !== null
                        ? number_format((float) $entreprise->getCapitalSocial(), 2, '.', '')
                        : null
                )
                ->setEntrepriseRcs(
                    method_exists($entreprise, 'getRcs') ? $entreprise->getRcs() : null
                );
        }

        $user = $facture->getUtilisateur();
        if ($user) {
            $facture
                ->setClientNom($user->getNom())
                ->setClientPrenom($user->getPrenom())
                ->setClientEmail($user->getEmail())
                ->setClientAdresse(sprintf(
                    "%s\n%s %s\n%s",
                    $user->getRue() ?? '',
                    $user->getCp() ?? '',
                    $user->getVille() ?? '',
                    $user->getPays() ?? ''
                ));
        }

        $facture->recomputeTotals();
        $facture->setTotal((float) $facture->getTotalTTC());
        $facture->setStatut('impayee');

        $this->em->flush();
    }
}
```

- [ ] **Step 2 : lint:container**

```bash
php bin/console lint:container
```

- [ ] **Step 3 : Commit**

```bash
git add src/Service/FactureSnapshotService.php
git commit -m "feat: service FactureSnapshotService (brouillon vers emise)"
```

---

### Task 7 : Service `AvoirPartielService`

**Files:**
- Create: `src/Service/AvoirPartielService.php`

- [ ] **Step 1 : Créer le service**

```php
<?php

namespace App\Service;

use App\Entity\FacturationUtilisateur;
use App\Entity\FactureLigne;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Crée un avoir partiel à partir d'une sélection de lignes d'une facture émise.
 * L'avoir est émis immédiatement (pas de brouillon intermédiaire).
 */
class AvoirPartielService
{
    public function __construct(
        private EntityManagerInterface $em,
        private FactureSnapshotService $snapshotService,
    ) {}

    /**
     * @param array<int, float> $quantitesParLigneId  id_ligne => quantite_a_crediter (positive)
     */
    public function creer(FacturationUtilisateur $source, array $quantitesParLigneId): FacturationUtilisateur
    {
        if ($source->getType() !== 'facture') {
            throw new \LogicException('Seule une facture peut faire l\'objet d\'un avoir partiel.');
        }
        if ($source->getStatut() === 'brouillon') {
            throw new \LogicException('Un brouillon ne peut pas etre corrige par avoir (modifier directement les lignes).');
        }
        if ($source->getStatut() === 'annulee') {
            throw new \LogicException('Cette facture est deja annulee.');
        }
        if (empty($quantitesParLigneId)) {
            throw new \LogicException('Aucune ligne selectionnee pour l\'avoir.');
        }

        $avoir = new FacturationUtilisateur();
        $avoir
            ->setType('avoir')
            ->setStatut('brouillon')
            ->setUtilisateur($source->getUtilisateur())
            ->setMoisDeGestion($source->getMoisDeGestion())
            ->setEntreprise($source->getEntreprise())
            ->setFactureOrigine($source)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setMailEnvoye(false);

        $avoir->setTotal(0.0);
        $this->em->persist($avoir);

        $position = 0;
        foreach ($source->getLignes() as $ligneSource) {
            if (!isset($quantitesParLigneId[$ligneSource->getId()])) {
                continue;
            }
            $qteCreditee = (float) $quantitesParLigneId[$ligneSource->getId()];
            if ($qteCreditee <= 0) {
                continue;
            }
            $qteSource = (float) $ligneSource->getQuantite();
            if ($qteCreditee > $qteSource) {
                throw new \LogicException(sprintf(
                    'La quantite creditee (%s) depasse la quantite de la ligne source (%s).',
                    $qteCreditee, $qteSource
                ));
            }

            $ligneAvoir = new FactureLigne();
            $ligneAvoir
                ->setPosition($position++)
                ->setChevalNom($ligneSource->getChevalNom())
                ->setPourcentagePropriete($ligneSource->getPourcentagePropriete())
                ->setDescription('Avoir sur: ' . $ligneSource->getDescription())
                ->setTauxTVA($ligneSource->getTauxTVA())
                ->setPrixUnitaireHT($ligneSource->getPrixUnitaireHT())
                ->setQuantite(number_format(-$qteCreditee, 2, '.', ''))
                ->setLigneOrigine($ligneSource);

            $avoir->addLigne($ligneAvoir);
            $this->em->persist($ligneAvoir);
        }

        if ($avoir->getLignes()->isEmpty()) {
            throw new \LogicException('Aucune ligne valide pour l\'avoir (toutes les quantites etaient a 0).');
        }

        $avoir->recomputeTotals();
        $this->snapshotService->emettre($avoir);

        return $avoir;
    }
}
```

- [ ] **Step 2 : lint:container**

```bash
php bin/console lint:container
```

- [ ] **Step 3 : Commit**

```bash
git add src/Service/AvoirPartielService.php
git commit -m "feat: service AvoirPartielService"
```

---

### Task 8 : Commande `app:migrate:factures-snapshot`

**Files:**
- Create: `src/Command/MigrateFacturesSnapshotCommand.php`

- [ ] **Step 1 : Créer la commande**

```php
<?php

namespace App\Command;

use App\Entity\FacturationUtilisateur;
use App\Entity\FactureLigne;
use App\Repository\FacturationUtilisateurRepository;
use App\Service\FactureCalculator;
use App\Service\FactureLigneBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:migrate:factures-snapshot', description: 'Migre les factures existantes vers le modele snapshot (FactureLigne).')]
class MigrateFacturesSnapshotCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private FacturationUtilisateurRepository $repo,
        private FactureCalculator $calculator,
        private FactureLigneBuilder $ligneBuilder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'N ecrit rien en base')
            ->addOption('batch', null, InputOption::VALUE_REQUIRED, 'Taille du batch', 50);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io      = new SymfonyStyle($input, $output);
        $dryRun  = (bool) $input->getOption('dry-run');
        $batch   = (int) $input->getOption('batch');

        $factures = $this->repo->findAll();
        $io->title(sprintf('Migration snapshot - %d facture(s) trouvee(s)', count($factures)));

        $migrated = 0;
        $skipped  = 0;
        $warnings = 0;

        foreach ($factures as $i => $facture) {
            if (!$facture->getLignes()->isEmpty()) {
                $skipped++;
                continue;
            }

            if ($facture->getType() === 'avoir') {
                $this->migrateAvoir($facture);
            } else {
                $warnings += $this->migrateFacture($facture, $io);
            }

            $this->snapshotEntrepriseClient($facture);
            $migrated++;

            if (!$dryRun && ($i % $batch === 0)) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        if ($dryRun) {
            $io->warning('DRY RUN - aucun flush effectue.');
        } else {
            $this->em->flush();
        }

        $io->success(sprintf('Termine. Migrees: %d | Skipped: %d | Warnings: %d', $migrated, $skipped, $warnings));
        return Command::SUCCESS;
    }

    private function migrateFacture(FacturationUtilisateur $facture, SymfonyStyle $io): int
    {
        $user = $facture->getUtilisateur();
        $mois = $facture->getMoisDeGestion();
        if (!$user || !$mois) return 0;

        $data = $this->calculator->calculerFactureUtilisateur($user, $mois);
        $position = 0;
        foreach ($data['lignes'] as $ligneCalc) {
            $ligne = new FactureLigne();
            $ligne
                ->setPosition($position++)
                ->setChevalNom($ligneCalc['cheval'])
                ->setPourcentagePropriete(number_format((float) $ligneCalc['pourcentage'], 2, '.', ''))
                ->setDescription($ligneCalc['description'])
                ->setTauxTVA(number_format((float) $ligneCalc['tauxTVA'], 2, '.', ''))
                ->setPrixUnitaireHT(number_format((float) $ligneCalc['prixUnitaire'], 4, '.', ''))
                ->setQuantite(number_format((float) $ligneCalc['quantite'], 2, '.', ''));
            $facture->addLigne($ligne);
            $this->em->persist($ligne);
        }

        $facture->recomputeTotals();

        $oldTotal = (float) $facture->getTotal();
        $newTotal = (float) $facture->getTotalTTC();
        if (abs($oldTotal - $newTotal) > 0.01) {
            $io->warning(sprintf(
                'Facture %s: total historique=%.2f, total recalcule=%.2f (ecart %.2f) - total historique conserve.',
                $facture->getNumFacture() ?? ('#' . $facture->getId()),
                $oldTotal, $newTotal, $newTotal - $oldTotal
            ));
            return 1;
        }
        return 0;
    }

    private function migrateAvoir(FacturationUtilisateur $avoir): void
    {
        $origine = $avoir->getFactureOrigine();
        $ligne = new FactureLigne();
        $ligne
            ->setPosition(0)
            ->setChevalNom('')
            ->setPourcentagePropriete('100.00')
            ->setDescription(sprintf(
                'Avoir sur facture %s',
                $origine?->getNumFacture() ?? '(source inconnue)'
            ))
            ->setTauxTVA('0.00')
            ->setPrixUnitaireHT(number_format((float) $avoir->getTotal(), 4, '.', ''))
            ->setQuantite('1.00');
        $avoir->addLigne($ligne);
        $this->em->persist($ligne);
        $avoir->recomputeTotals();
    }

    private function snapshotEntrepriseClient(FacturationUtilisateur $facture): void
    {
        $e = $facture->getEntreprise();
        if ($e && !$facture->getEntrepriseNom()) {
            $facture
                ->setEntrepriseNom($e->getNom())
                ->setEntrepriseAdresse(sprintf("%s\n%s %s\n%s", $e->getRue() ?? '', $e->getCp() ?? '', $e->getVille() ?? '', $e->getPays() ?? ''))
                ->setEntrepriseSiret($e->getSiret())
                ->setEntrepriseTva($e->getNumTVA())
                ->setEntrepriseFormeJuridique(method_exists($e, 'getFormeJuridique') ? $e->getFormeJuridique() : null)
                ->setEntrepriseCapitalSocial(
                    method_exists($e, 'getCapitalSocial') && $e->getCapitalSocial() !== null
                        ? number_format((float) $e->getCapitalSocial(), 2, '.', '')
                        : null
                )
                ->setEntrepriseRcs(method_exists($e, 'getRcs') ? $e->getRcs() : null);
        }
        $u = $facture->getUtilisateur();
        if ($u && !$facture->getClientNom()) {
            $facture
                ->setClientNom($u->getNom())
                ->setClientPrenom($u->getPrenom())
                ->setClientEmail($u->getEmail())
                ->setClientAdresse(sprintf("%s\n%s %s\n%s", $u->getRue() ?? '', $u->getCp() ?? '', $u->getVille() ?? '', $u->getPays() ?? ''));
        }
    }
}
```

- [ ] **Step 2 : Dry-run**

Run :
```bash
php bin/console app:migrate:factures-snapshot --dry-run
```

- [ ] **Step 3 : Exécution réelle (après backup DB)**

Run :
```bash
php bin/console app:migrate:factures-snapshot
```

- [ ] **Step 4 : Vérifier en DB**

Run :
```bash
php bin/console dbal:run-sql "SELECT COUNT(*) FROM facture_ligne"
```

- [ ] **Step 5 : Commit**

```bash
git add src/Command/MigrateFacturesSnapshotCommand.php
git commit -m "feat: commande migrate:factures-snapshot (retroactive)"
```

---

### Task 9 : Refondre `genererUtilisateur()` pour créer des brouillons

**Files:**
- Modify: `src/Controller/Admin/FacturationUtilisateurController.php` (méthode `genererUtilisateur`)

- [ ] **Step 1 : Remplacer le corps de la méthode**

Localiser `genererUtilisateur()` et remplacer le contenu du `if ($form->isSubmitted() && $form->isValid())` :

```php
if ($form->isSubmitted() && $form->isValid()) {
    $mois       = $form->get('moisDeGestion')->getData();
    $entreprise = $form->get('entreprise')->getData();

    $deplacementService->genererPourMois($mois);
    if (!$mois) return $this->redirectToRoute('app_admin_facturation_utilisateur');

    $proprietaires = [];
    foreach ($mois->getChevalProduits() as $cp) {
        foreach ($cp->getCheval()->getChevalProprietaires() as $cprop) {
            $proprietaires[$cprop->getProprietaire()->getId()] = $cprop->getProprietaire();
        }
    }

    $created = 0;
    foreach ($proprietaires as $user) {
        $facture = new FacturationUtilisateur();
        $facture
            ->setUtilisateur($user)
            ->setMoisDeGestion($mois)
            ->setEntreprise($entreprise)
            ->setStatut('brouillon')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setMailEnvoye(false)
            ->setTotal(0.0);

        $position = 0;
        foreach ($mois->getChevalProduits() as $cp) {
            foreach ($cp->getCheval()->getChevalProprietaires() as $cprop) {
                if ($cprop->getProprietaire() !== $user) continue;
                $ligne = $ligneBuilder->fromChevalProduit($cp, (float) $cprop->getPourcentage());
                $ligne->setPosition($position++);
                $facture->addLigne($ligne);
                $this->em->persist($ligne);
            }
        }

        $facture->recomputeTotals();
        $facture->setTotal((float) $facture->getTotalTTC());

        $this->em->persist($facture);
        $created++;
    }

    $this->em->flush();
    $this->addFlash('success', sprintf('%d brouillon(s) genere(s). Verifiez-les puis cliquez sur "Emettre" pour les rendre officiels.', $created));
    return $this->redirectToRoute('app_admin_facturation_utilisateur');
}
```

- [ ] **Step 2 : Ajouter le type-hint `FactureLigneBuilder $ligneBuilder` à la signature de la méthode**

```php
public function genererUtilisateur(Request $request, MoisDeGestionRepository $moisRepo, DeplacementToChevalProduitService $deplacementService, FactureLigneBuilder $ligneBuilder): Response
```

Et ajouter l'import en haut du fichier :
```php
use App\Service\FactureLigneBuilder;
```

- [ ] **Step 3 : Vérifier la route**

```bash
php bin/console lint:container
```

- [ ] **Step 4 : Test fonctionnel**

Dans le navigateur :
1. Aller sur `/admin/facturation/generer-utilisateur`
2. Sélectionner un mois de gestion et une entreprise.
3. Soumettre.
4. Vérifier :
   ```bash
   php bin/console dbal:run-sql "SELECT id, num_facture, statut, total_ttc FROM facturation_utilisateur WHERE statut='brouillon'"
   ```

- [ ] **Step 5 : Commit**

```bash
git add src/Controller/Admin/FacturationUtilisateurController.php
git commit -m "feat: generation mensuelle cree des brouillons avec lignes snapshot"
```

---

### Task 10 : Form `FactureLigneType`

**Files:**
- Create: `src/Form/FactureLigneType.php`

- [ ] **Step 1 : Créer le form**

```php
<?php

namespace App\Form;

use App\Entity\FactureLigne;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FactureLigneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('chevalNom', TextType::class, ['label' => 'Cheval'])
            ->add('description', TextareaType::class, ['label' => 'Description', 'attr' => ['rows' => 2]])
            ->add('quantite', NumberType::class, ['label' => 'Qte', 'scale' => 2, 'html5' => true, 'attr' => ['step' => '0.01']])
            ->add('prixUnitaireHT', NumberType::class, ['label' => 'PU HT', 'scale' => 4, 'html5' => true, 'attr' => ['step' => '0.0001']])
            ->add('tauxTVA', NumberType::class, ['label' => 'Taux TVA', 'scale' => 2, 'html5' => true, 'attr' => ['step' => '0.01']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FactureLigne::class,
        ]);
    }
}
```

- [ ] **Step 2 : lint:container**

```bash
php bin/console lint:container
```

- [ ] **Step 3 : Commit**

```bash
git add src/Form/FactureLigneType.php
git commit -m "feat: form FactureLigneType"
```

---

### Task 11 : Form `FactureLignesEditType` (wrapper collection)

**Files:**
- Create: `src/Form/FactureLignesEditType.php`

- [ ] **Step 1 : Créer le form wrapper**

```php
<?php

namespace App\Form;

use App\Entity\FacturationUtilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FactureLignesEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('lignes', CollectionType::class, [
            'entry_type'    => FactureLigneType::class,
            'allow_add'     => true,
            'allow_delete'  => true,
            'by_reference'  => false,
            'prototype'     => true,
            'label'         => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FacturationUtilisateur::class,
        ]);
    }
}
```

- [ ] **Step 2 : lint:container**

```bash
php bin/console lint:container
```

- [ ] **Step 3 : Commit**

```bash
git add src/Form/FactureLignesEditType.php
git commit -m "feat: form FactureLignesEditType (CollectionType wrapper)"
```

---

### Task 12 : Route + template `edit_lignes`

**Files:**
- Modify: `src/Controller/Admin/FacturationUtilisateurController.php` (nouvelle action `editLignes`)
- Create: `templates/admin/facturation/edit_lignes.html.twig`

- [ ] **Step 1 : Ajouter la route dans le controller**

Dans `FacturationUtilisateurController.php`, ajouter avant la méthode `envoyerMail` :

```php
#[Route('/{id}/lignes', name: 'app_admin_facturation_edit_lignes', methods: ['GET', 'POST'])]
public function editLignes(
    FacturationUtilisateur $facture,
    Request $request,
    \App\Service\FactureEditionGuard $guard,
): Response {
    $this->requireAdminAccess();
    $guard->ensureEditable($facture);

    $form = $this->createForm(\App\Form\FactureLignesEditType::class, $facture);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $position = 0;
        foreach ($facture->getLignes() as $ligne) {
            $ligne->setPosition($position++);
            $ligne->recomputeMontants();
        }
        $facture->recomputeTotals();
        $facture->setTotal((float) $facture->getTotalTTC());
        $this->em->flush();

        $this->addFlash('success', 'Lignes enregistrees.');
        return $this->redirectToRoute('app_admin_facturation_edit_lignes', ['id' => $facture->getId()]);
    }

    return $this->render('admin/facturation/edit_lignes.html.twig', [
        'facture' => $facture,
        'form'    => $form,
    ]);
}
```

- [ ] **Step 2 : Créer le template**

Créer `templates/admin/facturation/edit_lignes.html.twig` :

```twig
{% extends 'base.admin.html.twig' %}

{% block title %}Editer les lignes - Brouillon #{{ facture.id }}{% endblock %}

{% block body %}
<div class="admin-page">
    <nav class="breadcrumbs" aria-label="Fil d'Ariane">
        <a href="{{ path('app_admin_dashboard') }}">Tableau de bord</a>
        <span>/</span>
        <a href="{{ path('app_admin_facturation_utilisateur') }}">Facturation</a>
        <span>/</span>
        <span>Editer brouillon #{{ facture.id }}</span>
    </nav>

    <h1>Editer les lignes - Brouillon #{{ facture.id }}</h1>

    <p class="muted">
        Utilisateur : <strong>{{ facture.utilisateur.nom }} {{ facture.utilisateur.prenom }}</strong> -
        Mois : <strong>{{ facture.moisDeGestion.mois }}/{{ facture.moisDeGestion.annee }}</strong> -
        Entreprise : <strong>{{ facture.entreprise.nom }}</strong>
    </p>

    {{ form_start(form) }}

    <table class="admin-table" id="lignes-collection" data-prototype="{{ form_widget(form.lignes.vars.prototype)|e('html_attr') }}">
        <thead>
            <tr>
                <th>Cheval</th>
                <th>Description</th>
                <th>Qte</th>
                <th>PU HT</th>
                <th>TVA</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        {% for ligne in form.lignes %}
            <tr class="ligne-row">
                <td>{{ form_widget(ligne.chevalNom) }}</td>
                <td>{{ form_widget(ligne.description) }}</td>
                <td>{{ form_widget(ligne.quantite) }}</td>
                <td>{{ form_widget(ligne.prixUnitaireHT) }}</td>
                <td>{{ form_widget(ligne.tauxTVA) }}</td>
                <td><button type="button" class="action-btn red" data-remove-row title="Supprimer"><i class="mdi mdi-delete"></i></button></td>
            </tr>
        {% endfor %}
        </tbody>
    </table>

    <div class="form-actions">
        <button type="button" class="nav-btn" id="btn-add-ligne"><i class="mdi mdi-plus"></i> Ajouter une ligne</button>
        <button type="submit" class="nav-btn primary"><i class="mdi mdi-content-save"></i> Enregistrer</button>
        <a href="{{ path('app_admin_facturation_utilisateur') }}" class="nav-btn">Annuler</a>
    </div>

    {{ form_end(form) }}
</div>

<script>
(function() {
    const table     = document.getElementById('lignes-collection');
    const container = table.querySelector('tbody');
    const addBtn    = document.getElementById('btn-add-ligne');
    const proto     = table.dataset.prototype;
    let index = container.querySelectorAll('tr').length;

    function wrapPrototype(html) {
        const tmp = document.createElement('tbody');
        tmp.innerHTML = html;
        const widgets = tmp.children[0] ? tmp.children[0].children : [];
        const tr = document.createElement('tr');
        tr.className = 'ligne-row';
        for (const w of widgets) {
            const td = document.createElement('td');
            td.appendChild(w.cloneNode(true));
            tr.appendChild(td);
        }
        const tdDel = document.createElement('td');
        tdDel.innerHTML = '<button type="button" class="action-btn red" data-remove-row title="Supprimer"><i class="mdi mdi-delete"></i></button>';
        tr.appendChild(tdDel);
        return tr;
    }

    addBtn.addEventListener('click', function() {
        const html = proto.replace(/__name__/g, index++);
        container.appendChild(wrapPrototype(html));
    });

    container.addEventListener('click', function(e) {
        const btn = e.target.closest('[data-remove-row]');
        if (btn) btn.closest('tr').remove();
    });
})();
</script>
{% endblock %}
```

- [ ] **Step 3 : Vérifier le lint Twig**

```bash
php bin/console lint:twig templates/admin/facturation/edit_lignes.html.twig
```

- [ ] **Step 4 : Test fonctionnel**

Dans le navigateur :
1. Aller sur `/admin/facturation` et trouver un brouillon.
2. Coller l'URL `/admin/facturation/<id>/lignes` (le lien sera ajouté en Task 18).
3. Modifier une quantité, ajouter une ligne libre, en supprimer une.
4. Soumettre → vérifier que les totaux sont recalculés en DB.

- [ ] **Step 5 : Commit**

```bash
git add src/Controller/Admin/FacturationUtilisateurController.php templates/admin/facturation/edit_lignes.html.twig
git commit -m "feat: route + template edit_lignes pour brouillons"
```

---

### Task 13 : Route `emettre`

**Files:**
- Modify: `src/Controller/Admin/FacturationUtilisateurController.php`

- [ ] **Step 1 : Ajouter la route**

Dans le controller, ajouter avant `envoyerMail` :

```php
#[Route('/{id}/emettre', name: 'app_admin_facturation_emettre', methods: ['POST'])]
public function emettre(
    FacturationUtilisateur $facture,
    Request $request,
    \App\Service\FactureSnapshotService $snapshotService,
): Response {
    $this->requireAdminAccess();

    $token = $request->request->get('_token');
    if (!$this->isCsrfTokenValid('emettre' . $facture->getId(), $token)) {
        $this->addFlash('danger', 'Token CSRF invalide.');
        return $this->redirectToRoute('app_admin_facturation_utilisateur');
    }

    try {
        $snapshotService->emettre($facture);
        $this->addFlash('success', sprintf('Facture %s emise avec succes.', $facture->getNumFacture()));
    } catch (\LogicException $e) {
        $this->addFlash('danger', $e->getMessage());
    }

    return $this->redirectToRoute('app_admin_facturation_utilisateur');
}
```

- [ ] **Step 2 : lint:container**

```bash
php bin/console lint:container
```

- [ ] **Step 3 : Commit**

```bash
git add src/Controller/Admin/FacturationUtilisateurController.php
git commit -m "feat: route emettre (brouillon vers facture officielle)"
```

---

### Task 14 : Refondre `pdf.html.twig` (snapshot + lignes)

**Files:**
- Modify: `templates/admin/facturation/pdf.html.twig`
- Modify: `src/Controller/Admin/FacturationUtilisateurController.php` (méthode `generatePdf` — ne plus appeler `FactureCalculator`)

- [ ] **Step 1 : Lire le template actuel**

```bash
cat templates/admin/facturation/pdf.html.twig
```

- [ ] **Step 2 : Remplacer les lectures depuis `facture.entreprise.xxx` par leurs équivalents snapshot**

Règles de remplacement dans le template :
- `facture.entreprise.nom` → `facture.entrepriseNom ?? facture.entreprise.nom`
- `facture.entreprise.rue / ville / cp / pays` → utiliser `facture.entrepriseAdresse|nl2br` si non null, sinon fallback sur les champs actuels
- `facture.entreprise.siret` → `facture.entrepriseSiret ?? facture.entreprise.siret`
- `facture.entreprise.numTVA` → `facture.entrepriseTva ?? facture.entreprise.numTVA`
- `facture.entreprise.formeJuridique` → `facture.entrepriseFormeJuridique ?? facture.entreprise.formeJuridique`
- `facture.entreprise.capitalSocial` → `facture.entrepriseCapitalSocial ?? facture.entreprise.capitalSocial`
- `facture.entreprise.rcs` → `facture.entrepriseRcs ?? facture.entreprise.rcs`
- `user.nom`, `user.prenom` → `facture.clientNom ?? user.nom`, `facture.clientPrenom ?? user.prenom`
- `user.rue`, etc. → `facture.clientAdresse|nl2br` si non null

- [ ] **Step 3 : Remplacer la boucle `lignesParCheval` par un regroupement depuis `facture.lignes`**

Dans le template :

```twig
{% set lignesParCheval = {} %}
{% for ligne in facture.lignes %}
    {% set cheval = ligne.chevalNom ?: 'Autres' %}
    {% set currentList = lignesParCheval[cheval] ?? [] %}
    {% set currentList = currentList|merge([ligne]) %}
    {% set lignesParCheval = lignesParCheval|merge({(cheval): currentList}) %}
{% endfor %}
```

Puis chaque ligne est un objet `FactureLigne` : `ligne.description`, `ligne.quantite`, `ligne.prixUnitaireHT`, `ligne.montantHT`, `ligne.tauxTVA`, `ligne.montantTVA`, `ligne.pourcentagePropriete`.

- [ ] **Step 4 : Remplacer les totaux**

- `totalHT` → `facture.totalHT`
- `totalTTC` → `facture.totalTTC`
- `totalTVA` → `facture.totalTVA`

- [ ] **Step 5 : Adapter le controller `generatePdf`**

Dans `FacturationUtilisateurController.php`, remplacer :

```php
#[Route('/pdf/{id}', name: 'app_admin_facturation_pdf_utilisateur')]
public function pdf(FacturationUtilisateur $facture): Response
{
    return $this->generatePdf($facture, 'attachment');
}

#[Route('/voir/{id}', name: 'app_admin_facturation_voir_utilisateur')]
public function voir(FacturationUtilisateur $facture): Response
{
    return $this->generatePdf($facture, 'inline');
}

private function generatePdf(FacturationUtilisateur $facture, string $disposition): Response
{
    $this->requireBackofficeAccess();

    $html = $this->renderView('admin/facturation/pdf.html.twig', [
        'user'    => $facture->getUtilisateur(),
        'mois'    => $facture->getMoisDeGestion(),
        'facture' => $facture,
    ]);

    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('chroot', $this->getParameter('kernel.project_dir') . '/public');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = sprintf('facture_%s.pdf', $facture->getNumFacture() ?? ('brouillon-' . $facture->getId()));

    return new Response($dompdf->output(), Response::HTTP_OK, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $filename),
    ]);
}
```

- [ ] **Step 6 : Adapter `envoyerMail` et `sendBulkMail` — retirer l'appel à `FactureCalculator`**

Dans les deux méthodes, remplacer le bloc de calcul par :

```php
$html = $this->renderView('admin/facturation/pdf.html.twig', [
    'user'    => $user,
    'mois'    => $mois,
    'facture' => $facture,
]);
```

Et retirer le paramètre `FactureCalculator $calculator` des signatures si plus utilisé.

- [ ] **Step 7 : lint:twig + lint:container**

```bash
php bin/console lint:twig templates/admin/facturation/pdf.html.twig
php bin/console lint:container
```

- [ ] **Step 8 : Test fonctionnel**

1. Ouvrir un PDF d'une facture migrée : `/admin/facturation/voir/<id>`
2. Vérifier que les lignes s'affichent, que les mentions légales sont présentes, que le total correspond.
3. Comparer visuellement avec l'ancien PDF (avant refonte).

- [ ] **Step 9 : Commit**

```bash
git add templates/admin/facturation/pdf.html.twig src/Controller/Admin/FacturationUtilisateurController.php
git commit -m "feat: pdf.html.twig lit depuis snapshot + lignes stockees"
```

---

### Task 15 : Refondre `FacturXService` pour lire depuis snapshot

**Files:**
- Modify: `src/Service/FacturXService.php`

- [ ] **Step 1 : Lire le service actuel**

```bash
cat src/Service/FacturXService.php
```

- [ ] **Step 2 : Remplacer les lectures vers `facture.entreprise` par les champs snapshot**

Règles :
- `$facture->getEntreprise()->getNom()` → `$facture->getEntrepriseNom() ?? $facture->getEntreprise()?->getNom() ?? ''`
- idem siret, TVA, adresse
- Client : `$facture->getUtilisateur()->getNom()` → `$facture->getClientNom() ?? ...`

- [ ] **Step 3 : Remplacer l'appel à `FactureCalculator` par la lecture des totaux**

Remplacer :
```php
$data = $this->factureCalculator->calculerFactureUtilisateur(...);
$totalHT  = $data['totalHT'];
$totalTTC = $data['totalTTC'];
$totalTVA = $data['totalTVA'];
```

par :
```php
$totalHT  = (float) $facture->getTotalHT();
$totalTTC = (float) $facture->getTotalTTC();
$totalTVA = $facture->getTotalTVA();
```

Si `FactureCalculator` était injecté dans le constructeur, le retirer.

- [ ] **Step 4 : lint:container**

```bash
php bin/console lint:container
```

- [ ] **Step 5 : Test fonctionnel**

1. Télécharger le XML d'une facture migrée : `/admin/facturation/facturx/<id>`
2. Ouvrir le XML dans un éditeur, vérifier que les balises `SellerTradeParty`, `BuyerTradeParty`, `ApplicableHeaderTradeSettlement` contiennent les bonnes valeurs (snapshot).

- [ ] **Step 6 : Commit**

```bash
git add src/Service/FacturXService.php
git commit -m "feat: FacturXService lit depuis snapshot (plus de recalcul)"
```

---

### Task 16 : Route + template `avoir_partiel`

**Files:**
- Modify: `src/Controller/Admin/FacturationUtilisateurController.php`
- Create: `templates/admin/facturation/avoir_partiel.html.twig`

- [ ] **Step 1 : Ajouter la route**

```php
#[Route('/{id}/avoir-partiel', name: 'app_admin_facturation_avoir_partiel', methods: ['GET', 'POST'])]
public function avoirPartiel(
    FacturationUtilisateur $facture,
    Request $request,
    \App\Service\AvoirPartielService $avoirService,
): Response {
    $this->requireAdminAccess();

    if ($facture->getType() !== 'facture' || $facture->getStatut() === 'brouillon' || $facture->getStatut() === 'annulee') {
        $this->addFlash('danger', 'Cette facture ne peut pas faire l\'objet d\'un avoir partiel.');
        return $this->redirectToRoute('app_admin_facturation_utilisateur');
    }

    if ($request->isMethod('POST')) {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('avoir' . $facture->getId(), $token)) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_facturation_utilisateur');
        }

        $quantites = [];
        $postLignes = $request->request->all('lignes');
        foreach ($postLignes as $ligneId => $data) {
            if (empty($data['selectionner'])) continue;
            $qte = (float) ($data['quantite'] ?? 0);
            if ($qte <= 0) continue;
            $quantites[(int) $ligneId] = $qte;
        }

        try {
            $avoir = $avoirService->creer($facture, $quantites);
            $this->addFlash('success', sprintf('Avoir %s cree avec succes.', $avoir->getNumFacture()));
            return $this->redirectToRoute('app_admin_facturation_utilisateur');
        } catch (\LogicException $e) {
            $this->addFlash('danger', $e->getMessage());
        }
    }

    return $this->render('admin/facturation/avoir_partiel.html.twig', [
        'facture' => $facture,
    ]);
}
```

- [ ] **Step 2 : Créer le template**

```twig
{% extends 'base.admin.html.twig' %}

{% block title %}Avoir partiel sur {{ facture.numFacture }}{% endblock %}

{% block body %}
<div class="admin-page">
    <nav class="breadcrumbs" aria-label="Fil d'Ariane">
        <a href="{{ path('app_admin_dashboard') }}">Tableau de bord</a>
        <span>/</span>
        <a href="{{ path('app_admin_facturation_utilisateur') }}">Facturation</a>
        <span>/</span>
        <span>Avoir sur {{ facture.numFacture }}</span>
    </nav>

    <h1>Avoir partiel - Facture {{ facture.numFacture }}</h1>
    <p class="muted">
        Selectionnez les lignes a crediter. Quantite par defaut = quantite totale de la ligne.
        La facture d'origine reste active - seul un avoir partiel sera cree.
    </p>

    <form method="post">
        <input type="hidden" name="_token" value="{{ csrf_token('avoir' ~ facture.id) }}">

        <table class="admin-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Cheval</th>
                    <th>Description</th>
                    <th>Qte origine</th>
                    <th>Qte a crediter</th>
                    <th>PU HT</th>
                    <th>Montant TTC</th>
                </tr>
            </thead>
            <tbody>
            {% for ligne in facture.lignes %}
                <tr>
                    <td><input type="checkbox" name="lignes[{{ ligne.id }}][selectionner]" value="1"></td>
                    <td>{{ ligne.chevalNom }}</td>
                    <td>{{ ligne.description }}</td>
                    <td>{{ ligne.quantite }}</td>
                    <td><input type="number" step="0.01" min="0" max="{{ ligne.quantite }}" name="lignes[{{ ligne.id }}][quantite]" value="{{ ligne.quantite }}" style="width:90px"></td>
                    <td>{{ ligne.prixUnitaireHT }} EUR</td>
                    <td>{{ ligne.montantTTC }} EUR</td>
                </tr>
            {% endfor %}
            </tbody>
        </table>

        <div class="form-actions">
            <button type="submit" class="nav-btn primary"><i class="mdi mdi-receipt-text-minus"></i> Creer l'avoir</button>
            <a href="{{ path('app_admin_facturation_utilisateur') }}" class="nav-btn">Annuler</a>
        </div>
    </form>
</div>
{% endblock %}
```

- [ ] **Step 3 : lint:twig + lint:container**

```bash
php bin/console lint:twig templates/admin/facturation/avoir_partiel.html.twig
php bin/console lint:container
```

- [ ] **Step 4 : Test fonctionnel**

1. Choisir une facture émise, coller `/admin/facturation/<id>/avoir-partiel`.
2. Cocher 1 ou 2 lignes, ajuster la quantité sur l'une.
3. Soumettre → flash success avec le numéro de l'avoir.
4. Vérifier en DB : avoir avec `type='avoir'`, lignes négatives, totaux négatifs.
5. La facture d'origine reste dans son statut.

- [ ] **Step 5 : Commit**

```bash
git add src/Controller/Admin/FacturationUtilisateurController.php templates/admin/facturation/avoir_partiel.html.twig
git commit -m "feat: route + template avoir partiel (selection de lignes)"
```

---

### Task 17 : Ajouter guards sur `envoyerMail`, `payer`, `sendBulkMail`

**Files:**
- Modify: `src/Controller/Admin/FacturationUtilisateurController.php`

- [ ] **Step 1 : Bloquer les actions sur brouillons**

Dans `envoyerMail`, ajouter après `$this->requireAdminAccess();` :

```php
if ($facture->getStatut() === 'brouillon') {
    $this->addFlash('danger', 'Impossible d\'envoyer un brouillon par mail. Emettez-le d\'abord.');
    return $this->redirectToRoute('app_admin_facturation_utilisateur');
}
```

Dans `payer` :

```php
if ($facture->getStatut() === 'brouillon') {
    $this->addFlash('danger', 'Impossible de marquer un brouillon comme paye.');
    return $this->redirectToRoute('app_admin_facturation_utilisateur');
}
```

Dans `sendBulkMail`, dans la boucle sur les ids (après le check `type !== 'facture'`) :

```php
if ($facture->getStatut() === 'brouillon') {
    $skipped++;
    continue;
}
```

- [ ] **Step 2 : lint:container**

```bash
php bin/console lint:container
```

- [ ] **Step 3 : Commit**

```bash
git add src/Controller/Admin/FacturationUtilisateurController.php
git commit -m "feat: garde statut brouillon sur envoyerMail/payer/sendBulkMail"
```

---

### Task 18 : Mettre à jour `liste.html.twig` (badges + boutons contextuels)

**Files:**
- Modify: `templates/admin/facturation/liste.html.twig`

- [ ] **Step 1 : Lire le template actuel**

```bash
cat templates/admin/facturation/liste.html.twig
```

- [ ] **Step 2 : Ajouter le badge brouillon**

Dans le bloc des badges statut, ajouter la branche brouillon au début :

```twig
{% if facture.statut == 'brouillon' %}
    <span class="pill pill-brouillon">Brouillon</span>
{% elseif facture.statut == 'annulee' %}
    <span class="pill pill-annulee">Annulee</span>
{% elseif facture.type == 'avoir' %}
    <span class="pill pill-avoir">Avoir</span>
{% elseif facture.statut == 'payee' %}
    <span class="pill pill-payee">Payee</span>
{% else %}
    <span class="pill pill-impayee">Impayee</span>
{% endif %}
```

- [ ] **Step 3 : Afficher "Brouillon" si `numFacture` null**

Remplacer `{{ facture.numFacture }}` par `{{ facture.numFacture ?? 'Brouillon' }}`.

- [ ] **Step 4 : Ajouter les boutons contextuels**

Dans la colonne actions, remplacer le bloc par :

```twig
{% if facture.statut == 'brouillon' %}
    <a href="{{ path('app_admin_facturation_edit_lignes', {id: facture.id}) }}" class="action-btn blue" title="Editer les lignes"><i class="mdi mdi-pencil"></i></a>
    <form method="post" action="{{ path('app_admin_facturation_emettre', {id: facture.id}) }}" style="display:inline" onsubmit="return confirm('Emettre cette facture ? Cette action est irreversible.')">
        <input type="hidden" name="_token" value="{{ csrf_token('emettre' ~ facture.id) }}">
        <button type="submit" class="action-btn green" title="Emettre la facture"><i class="mdi mdi-send"></i></button>
    </form>
{% else %}
    <a href="{{ path('app_admin_facturation_voir_utilisateur', {id: facture.id}) }}" class="action-btn" title="Voir PDF"><i class="mdi mdi-file-pdf-box"></i></a>
    <a href="{{ path('app_admin_facturation_pdf_utilisateur', {id: facture.id}) }}" class="action-btn" title="Telecharger PDF"><i class="mdi mdi-download"></i></a>
    {% if facture.type == 'facture' %}
        <a href="{{ path('app_admin_facturation_facturx', {id: facture.id}) }}" class="action-btn green" title="Telecharger XML Factur-X"><i class="mdi mdi-file-xml-box"></i></a>
    {% endif %}
    {% if not facture.mailEnvoye and facture.statut != 'annulee' %}
        <a href="{{ path('app_admin_facturation_envoyer_mail', {id: facture.id}) }}" class="action-btn blue" title="Envoyer par mail"><i class="mdi mdi-email-send"></i></a>
    {% endif %}
    {% if facture.statut == 'impayee' and facture.type == 'facture' %}
        <a href="{{ path('app_admin_facturation_payer', {id: facture.id}) }}" class="action-btn green" title="Marquer comme payee"><i class="mdi mdi-cash-check"></i></a>
    {% endif %}
    {% if facture.type == 'facture' and facture.statut != 'annulee' %}
        <a href="{{ path('app_admin_facturation_avoir_partiel', {id: facture.id}) }}" class="action-btn red" title="Avoir partiel"><i class="mdi mdi-receipt-text-minus"></i></a>
    {% endif %}
{% endif %}
```

- [ ] **Step 5 : lint:twig**

```bash
php bin/console lint:twig templates/admin/facturation/liste.html.twig
```

- [ ] **Step 6 : Test fonctionnel**

Recharger `/admin/facturation` :
- Les brouillons affichent "Brouillon" en badge gris + boutons Editer/Emettre.
- Les factures émises affichent tous les autres boutons, plus "Avoir partiel".

- [ ] **Step 7 : Commit**

```bash
git add templates/admin/facturation/liste.html.twig
git commit -m "feat: badge brouillon + boutons contextuels sur liste facturation"
```

---

### Task 19 : CSS `.pill-brouillon`

**Files:**
- Modify: `public/assets/css/admin.css`

- [ ] **Step 1 : Chercher où sont définis les autres pills**

```bash
grep -n "pill-annulee\|pill-avoir\|pill-payee\|pill-impayee" public/assets/css/admin.css
```

- [ ] **Step 2 : Ajouter la règle**

```css
.pill-brouillon {
    background: var(--surface-2);
    color: var(--ink-muted);
    border: 1px dashed var(--border);
}
```

- [ ] **Step 3 : Vérifier visuellement**

Recharger `/admin/facturation` et vérifier que le badge est lisible.

- [ ] **Step 4 : Commit**

```bash
git add public/assets/css/admin.css
git commit -m "feat: CSS pill-brouillon"
```

---

### Task 20 : Supprimer les anciennes routes `edit` et `corriger`

**Files:**
- Modify: `src/Controller/Admin/FacturationUtilisateurController.php`
- Delete: `templates/admin/facturation/facturation.edit.html.twig`
- Delete: `templates/admin/facturation/facturation.corriger.html.twig`

- [ ] **Step 1 : Supprimer la méthode `edit` et la méthode `corriger` du controller**

Localiser et supprimer :
- `public function edit(...)` avec `#[Route('/edit/{id}', ...)]`
- `public function corriger(...)` avec `#[Route('/corriger/{id}', ...)]`

Retirer aussi le use `FacturationUtilisateurType` si plus utilisé (vérifier par grep).

- [ ] **Step 2 : Supprimer les templates**

```bash
rm templates/admin/facturation/facturation.edit.html.twig
rm templates/admin/facturation/facturation.corriger.html.twig
```

- [ ] **Step 3 : Vérifier qu'aucune route ne référence plus ces chemins**

```bash
grep -rn "app_admin_facturation_edit\|app_admin_facturation_corriger" src/ templates/
```
Attendu : seul `app_admin_facturation_edit_lignes` apparaît.

- [ ] **Step 4 : lint:container + lint:twig global**

```bash
php bin/console lint:container
php bin/console lint:twig templates/
```

- [ ] **Step 5 : Commit**

```bash
git add -A
git commit -m "refactor: supprimer anciennes routes edit/corriger (remplacees par edit_lignes + avoir_partiel)"
```

---

### Task 21 : Q/A manuelle de bout en bout + mise à jour CLAUDE.md

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1 : Parcours complet**

Dans l'ordre :

1. **Générer un brouillon** : `/admin/facturation/generer-utilisateur`. Vérifier que N brouillons apparaissent sans numéro.
2. **Éditer un brouillon** : bouton "Éditer les lignes". Modifier une quantité, ajouter une ligne libre, en supprimer une. Soumettre.
3. **Émettre** : bouton vert "Émettre". Confirmer. Vérifier : numéro, date d'émission, snapshot entreprise + client en DB.
4. **Voir PDF** : mentions (entreprise, client, lignes, totaux, mentions légales Art. L441-10, indemnité 40€).
5. **Télécharger XML Factur-X** : balises vendeur/acheteur/totaux OK.
6. **Envoyer par mail** : mail envoyé avec PDF attaché. Re-cliquer → erreur.
7. **Marquer comme payée** : statut = `payee`.
8. **Avoir partiel** : cocher 1 ligne, réduire quantité, soumettre. Avoir créé avec `AV-...`, lignes négatives. Facture d'origine reste `payee`.
9. **Brouillon : actions bloquées** : tenter `/envoyer-mail/<id_brouillon>` → erreur. Idem `payer` et `avoir-partiel`.
10. **Comparaison PDF historique** : PDF d'une facture migrée visuellement identique au précédent.

- [ ] **Step 2 : Mettre à jour `CLAUDE.md`**

Ajouter une entrée dans "Travail realise" :

```markdown
### Refonte Facturation - Snapshot + Brouillon + Avoir partiel (session 2026-04-24)
- **`FactureLigne`** : nouvelle entite, OneToMany depuis FacturationUtilisateur, cascade persist/remove, orphanRemoval. Champs chevalNom, description, qte, PU HT, taux TVA, montants HT/TVA/TTC. `recomputeMontants()` appele par les setters.
- **`FacturationUtilisateur`** : 13 nouveaux champs snapshot + totalHT/totalTVA/totalTTC. `numFacture` et `dateEmission` nullable. Statut `brouillon` ajoute. `recomputeTotals()` depuis les lignes.
- **Services** : `FactureLigneBuilder`, `FactureEditionGuard`, `FactureSnapshotService::emettre()`, `AvoirPartielService::creer()`.
- **Cycle de vie** : brouillon (sans numero) -> edition libre -> emission (numero + snapshot + irreversible) -> envoi -> paiement ou avoir partiel.
- **Migration retroactive** : commande `app:migrate:factures-snapshot`.
- **PDF / Factur-X** : lecture depuis `facture.lignes` et champs snapshot (fallback Twig pour les brouillons). `FactureCalculator` n'est plus utilise que pour la generation initiale du brouillon.
- **Anciennes routes supprimees** : `edit` et `corriger` (remplacees par `edit_lignes` + `avoir_partiel`).
- **Migration DB** : `Version<timestamp>.php`
```

Ajouter aux "Conventions et pieges" :

```markdown
- **Brouillons** : statut `brouillon`, sans numero, sans date d'emission, modifiables librement. Emettre via `FactureSnapshotService::emettre()` jamais directement.
- **Intangibilite** : une fois emise, une facture ne peut plus etre modifiee. Seul un avoir partiel peut la corriger.
- **Snapshot entreprise + client** : toujours lire les champs snapshot de la facture dans les PDF/XML (pas `facture.entreprise.xxx` directement).
- **Avoirs partiels** : numero propre via `InvoiceNumberService` (prefix `AV-`), la facture d'origine garde son statut.
```

- [ ] **Step 3 : Commit final**

```bash
git add CLAUDE.md
git commit -m "docs: CLAUDE.md refonte facturation snapshot (session 2026-04-24)"
```

---

## Récapitulatif

- 21 tâches, environ 40-50 commits au total.
- Durée estimée : 1 à 2 journées de dev.
- Aucune période de downtime : le système reste utilisable après chaque commit.
- La migration rétroactive (Task 8) est idempotente — relançable sans dommage.

**Points de vigilance :**
- Task 3 (migration Doctrine) : **backup DB avant**.
- Task 8 : **backup DB avant**, lancer en dry-run d'abord.
- Task 14 : le refactor du PDF est la tâche la plus risquée — diff visuel avant/après.
- Les brouillons créés avant la Task 17 seront bloquants si un user essaie d'envoyer/payer — sensibiliser les utilisateurs.

---

## Self-review

**1. Spec coverage** :
- §4 décisions (hybride C, brouillon, avoir partiel sélection, migration A, entité dédiée) : toutes implémentées (Tasks 1-21).
- §5.1 entité FactureLigne : Task 1.
- §5.2 champs snapshot FacturationUtilisateur : Task 2.
- §5.3 services (FactureLigneBuilder, Guard, Snapshot, AvoirPartiel) : Tasks 4-7.
- §6.1 génération mensuelle brouillons : Task 9.
- §6.2 édition brouillon : Tasks 10-12.
- §6.3 émission formelle : Task 13.
- §6.4 envoi mail avec garde brouillon : Task 17.
- §6.5 marquer payée avec garde brouillon : Task 17.
- §6.6 avoir partiel : Task 16.
- §6.7 PDF + Factur-X lisent snapshot : Tasks 14-15.
- §7 migration rétroactive : Tasks 3 + 8.
- §8 invariants + guards : Tasks 5, 12, 17.
- §9 impact : couvert dans toutes les tasks.

**2. Placeholder scan** : aucun TODO, TBD, ou "handle appropriately" — chaque step a du code concret ou une commande concrète.

**3. Type consistency** :
- `FactureLigne::recomputeMontants()` défini Task 1, appelé Task 12 — cohérent.
- `FacturationUtilisateur::recomputeTotals()` défini Task 2, appelé Tasks 8, 9, 12 — cohérent.
- `FactureSnapshotService::emettre()` défini Task 6, appelé Tasks 7, 13 — cohérent.
- `AvoirPartielService::creer(FacturationUtilisateur, array)` défini Task 7, appelé Task 16 avec la bonne signature — cohérent.
- `FactureEditionGuard::ensureEditable()` défini Task 5, appelé Task 12 — cohérent.
- Préfixe `AV-` sur numéro d'avoir : consistant Task 6 (FactureSnapshotService) et spec §6.6.
