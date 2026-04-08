# Conformité légale — Numérotation séquentielle & Factur-X Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Garantir la numérotation séquentielle sans trou via un verrou DB atomique, et générer un XML Factur-X MINIMUM téléchargeable depuis la liste des factures.

**Architecture:** `InvoiceCounter` (entité ligne unique + verrou pessimiste) remplace le preg_match fragile dans le controller. `FacturXService` génère le XML via `horstoeko/zugferd`. Nouvelle route `facturx/{id}` + bouton XML dans la liste.

**Tech Stack:** Symfony 7.4, PHP 8.3, Doctrine ORM 3.6 (LockMode::PESSIMISTIC_WRITE), MySQL 8, horstoeko/zugferd v1

---

## Fichiers impactés

| Fichier | Action |
|---|---|
| `src/Entity/InvoiceCounter.php` | Créé |
| `src/Service/InvoiceNumberService.php` | Créé |
| `src/Service/FacturXService.php` | Créé |
| `src/Controller/Admin/FacturationUtilisateurController.php` | Modifié — injection service + remplacement preg_match + nouvelle route facturx |
| `templates/admin/facturation/liste.html.twig` | Modifié — bouton XML |
| Migration Doctrine | Créé — table invoice_counter seedée |
| `composer.json` / `composer.lock` | Modifié — horstoeko/zugferd |

---

### Task 1 : Entité `InvoiceCounter` + migration

**Files:**
- Create: `src/Entity/InvoiceCounter.php`
- Create: migration (via `doctrine:migrations:diff`)

- [ ] **Step 1 : Créer l'entité**

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class InvoiceCounter
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id;

    #[ORM\Column]
    private int $counter;

    public function __construct(int $id, int $counter)
    {
        $this->id      = $id;
        $this->counter = $counter;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCounter(): int
    {
        return $this->counter;
    }

    public function setCounter(int $counter): void
    {
        $this->counter = $counter;
    }
}
```

- [ ] **Step 2 : Générer la migration**

```bash
php bin/console doctrine:migrations:diff
```

Attendu : nouveau fichier `migrations/Version<timestamp>.php` avec `CREATE TABLE invoice_counter`.

- [ ] **Step 3 : Ajouter le seed dans la migration générée**

Ouvrir le fichier de migration généré. Dans la méthode `up()`, APRÈS le `CREATE TABLE invoice_counter`, ajouter :

```php
// Seed with current max sequential number from existing factures
$maxNum = $this->connection->fetchOne(
    "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(num_facture, '-', -1) AS UNSIGNED)), 0) FROM facturation_utilisateur WHERE type = 'facture'"
);
$this->addSql('INSERT INTO invoice_counter (id, counter) VALUES (1, ' . (int)$maxNum . ')');
```

Et dans `down()`, ajouter avant ou après le `DROP TABLE invoice_counter` (s'il n'y a pas déjà un DROP) :

```php
$this->addSql('DROP TABLE IF EXISTS invoice_counter');
```

- [ ] **Step 4 : Vérifier le contenu de la migration**

La migration doit contenir :
- `CREATE TABLE invoice_counter (id INT NOT NULL, counter INT NOT NULL, PRIMARY KEY(id))`
- L'insertion seed avec le MAX actuel

- [ ] **Step 5 : Exécuter la migration**

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

Attendu : `[notice] Migrating up to Version<timestamp>`

- [ ] **Step 6 : Vérifier**

```bash
php bin/console doctrine:query:sql "SELECT * FROM invoice_counter"
```

Attendu : une ligne `id=1, counter=N` où N est la valeur MAX des séquences existantes (ou 0 si aucune facture).

- [ ] **Step 7 : Commit**

```bash
git add src/Entity/InvoiceCounter.php migrations/
git commit -m "feat: add InvoiceCounter entity and seeded migration"
```

---

### Task 2 : Service `InvoiceNumberService`

**Files:**
- Create: `src/Service/InvoiceNumberService.php`

- [ ] **Step 1 : Créer le service**

```php
<?php

namespace App\Service;

use App\Entity\InvoiceCounter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\LockMode;

class InvoiceNumberService
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * Atomically reserves $count consecutive invoice numbers.
     * Returns the first reserved number.
     *
     * Uses a pessimistic write lock (SELECT ... FOR UPDATE) to prevent
     * race conditions when multiple invoices are generated concurrently.
     *
     * Example: reserveNumbers(3) when counter=10 → returns 11, sets counter to 13.
     */
    public function reserveNumbers(int $count = 1): int
    {
        return $this->em->wrapInTransaction(function () use ($count): int {
            /** @var InvoiceCounter $counter */
            $counter = $this->em->find(InvoiceCounter::class, 1, LockMode::PESSIMISTIC_WRITE);

            if (!$counter) {
                throw new \RuntimeException('InvoiceCounter not initialized. Run doctrine:migrations:migrate.');
            }

            $firstNumber = $counter->getCounter() + 1;
            $counter->setCounter($counter->getCounter() + $count);
            $this->em->flush();

            return $firstNumber;
        });
    }
}
```

- [ ] **Step 2 : Vérifier l'autowiring**

```bash
php bin/console debug:autowiring InvoiceNumber
```

Attendu : `App\Service\InvoiceNumberService` listé.

- [ ] **Step 3 : Commit**

```bash
git add src/Service/InvoiceNumberService.php
git commit -m "feat: add InvoiceNumberService with pessimistic lock"
```

---

### Task 3 : Mettre à jour `FacturationUtilisateurController`

**Files:**
- Modify: `src/Controller/Admin/FacturationUtilisateurController.php`

**Context :** Remplacer les deux blocs `preg_match` (dans `genererUtilisateur` et `corriger`) par des appels à `InvoiceNumberService::reserveNumbers()`. Injecter le service dans le constructeur.

- [ ] **Step 1 : Ajouter l'import**

Dans le bloc `use` du controller, ajouter :

```php
use App\Service\InvoiceNumberService;
```

- [ ] **Step 2 : Injecter dans le constructeur**

Le constructeur actuel est :
```php
public function __construct(private EntityManagerInterface $em) {}
```

Le remplacer par :
```php
public function __construct(
    private EntityManagerInterface $em,
    private InvoiceNumberService $invoiceNumberService,
) {}
```

- [ ] **Step 3 : Remplacer la logique dans `corriger`**

Dans la méthode `corriger()`, chercher le bloc (environ lignes 195-209) :

```php
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
```

Le remplacer par :

```php
// 3. Créer la nouvelle facture corrigée
$nouveauNumero = $this->invoiceNumberService->reserveNumbers(1);
```

- [ ] **Step 4 : Supprimer `FacturationUtilisateurRepository` de la signature de `corriger`**

La signature actuelle de `corriger` est :
```php
public function corriger(
    FacturationUtilisateur $facture,
    Request $request,
    FactureCalculator $calculator,
    FacturationUtilisateurRepository $factureRepo
): Response
```

La remplacer par :
```php
public function corriger(
    FacturationUtilisateur $facture,
    Request $request,
    FactureCalculator $calculator,
): Response
```

- [ ] **Step 5 : Remplacer la logique dans `genererUtilisateur`**

Dans la méthode `genererUtilisateur()`, chercher le bloc (environ lignes 268-275) :

```php
$dernierFacture = $factureRepo->createQueryBuilder('f')->select('f.numFacture')->orderBy('f.id', 'DESC')->setMaxResults(1)->getQuery()->getOneOrNullResult();
$dernierNumero = 0;
if ($dernierFacture && isset($dernierFacture['numFacture'])) {
    preg_match('/\d{4}$/', $dernierFacture['numFacture'], $matches);
    if (!empty($matches[0])) $dernierNumero = (int)$matches[0];
}

$compteur = $dernierNumero;
foreach ($proprietaires as $user) {
    $compteur++;
```

Le remplacer par :

```php
$count     = count($proprietaires);
$compteur  = $count > 0 ? $this->invoiceNumberService->reserveNumbers($count) - 1 : 0;
foreach ($proprietaires as $user) {
    $compteur++;
```

- [ ] **Step 6 : Supprimer `FacturationUtilisateurRepository` de la signature de `genererUtilisateur`**

La signature actuelle est :
```php
public function genererUtilisateur(Request $request, MoisDeGestionRepository $moisRepo, DeplacementToChevalProduitService $deplacementService, FacturationUtilisateurRepository $factureRepo): Response
```

La remplacer par :
```php
public function genererUtilisateur(Request $request, MoisDeGestionRepository $moisRepo, DeplacementToChevalProduitService $deplacementService): Response
```

- [ ] **Step 7 : Vérifier les routes**

```bash
php bin/console debug:router | grep facturation
```

Attendu : toutes les routes facturation sont présentes, aucune erreur PHP.

- [ ] **Step 8 : Commit**

```bash
git add src/Controller/Admin/FacturationUtilisateurController.php
git commit -m "feat: replace preg_match numbering with InvoiceNumberService atomic lock"
```

---

### Task 4 : Installer `horstoeko/zugferd`

**Files:**
- Modify: `composer.json`, `composer.lock`

- [ ] **Step 1 : Installer la librairie**

```bash
composer require horstoeko/zugferd
```

Attendu : 9 nouveaux packages installés dont `horstoeko/zugferd`, `setasign/fpdi`, `jms/serializer`.

- [ ] **Step 2 : Vérifier l'installation**

```bash
php -r "require 'vendor/autoload.php'; echo horstoeko\zugferd\ZugferdProfiles::PROFILE_MINIMUM . PHP_EOL;"
```

Attendu : `0` (valeur de la constante MINIMUM).

- [ ] **Step 3 : Commit**

```bash
git add composer.json composer.lock
git commit -m "feat: install horstoeko/zugferd for Factur-X XML generation"
```

---

### Task 5 : Service `FacturXService`

**Files:**
- Create: `src/Service/FacturXService.php`

- [ ] **Step 1 : Créer le service**

```php
<?php

namespace App\Service;

use App\Entity\FacturationUtilisateur;
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdProfiles;

class FacturXService
{
    public function __construct(private FactureCalculator $calculator) {}

    /**
     * Generates a Factur-X MINIMUM profile XML for a given invoice.
     * Only supports type='facture' (not avoirs).
     */
    public function generateXml(FacturationUtilisateur $facture): string
    {
        $entreprise = $facture->getEntreprise();
        $user       = $facture->getUtilisateur();
        $mois       = $facture->getMoisDeGestion();
        $data       = $this->calculator->calculerFactureUtilisateur($user, $mois);

        $document = ZugferdDocumentBuilder::createNew(ZugferdProfiles::PROFILE_MINIMUM);

        $document->setDocumentInformation(
            $facture->getNumFacture(),
            '380',
            \DateTime::createFromImmutable(
                $facture->getDateEmission() ?? new \DateTimeImmutable()
            ),
            'EUR'
        );

        $document->setDocumentSeller($entreprise->getNom() ?? '');
        $document->setDocumentSellerAddress(
            $entreprise->getRue()   ?? '',
            '',
            '',
            $entreprise->getCp()    ?? '',
            $entreprise->getVille() ?? '',
            $entreprise->getPays()  ?? 'FR'
        );

        if ($entreprise->getNumTVA()) {
            $document->setDocumentSellerTaxRegistration('VA', $entreprise->getNumTVA());
        }

        $document->setDocumentBuyer(
            trim($user->getNom() . ' ' . $user->getPrenom())
        );

        $document->setDocumentSummation(
            (float) $data['totalTTC'],
            (float) $data['totalTTC'],
            (float) $data['totalHT'],
            (float) $data['totalHT'],
            (float) $data['totalTVA']
        );

        return $document->getContent();
    }
}
```

- [ ] **Step 2 : Vérifier l'autowiring**

```bash
php bin/console debug:autowiring FacturX
```

Attendu : `App\Service\FacturXService` listé.

- [ ] **Step 3 : Vérifier la syntaxe PHP**

```bash
php -l src/Service/FacturXService.php
```

Attendu : `No syntax errors detected`

- [ ] **Step 4 : Commit**

```bash
git add src/Service/FacturXService.php
git commit -m "feat: add FacturXService for Factur-X MINIMUM profile XML generation"
```

---

### Task 6 : Route `facturx` + bouton dans la liste

**Files:**
- Modify: `src/Controller/Admin/FacturationUtilisateurController.php`
- Modify: `templates/admin/facturation/liste.html.twig`

- [ ] **Step 1 : Ajouter l'import `FacturXService` dans le controller**

Dans le bloc `use`, ajouter :

```php
use App\Service\FacturXService;
```

- [ ] **Step 2 : Ajouter la route `facturx` dans le controller**

Après la méthode `voir()` (environ ligne 68), ajouter :

```php
#[Route('/facturx/{id}', name: 'app_admin_facturation_facturx')]
public function facturx(FacturationUtilisateur $facture, FacturXService $facturXService): Response
{
    $this->requireBackofficeAccess();

    if ($facture->getType() !== 'facture') {
        $this->addFlash('danger', 'Le format Factur-X n\'est disponible que pour les factures (pas les avoirs).');
        return $this->redirectToRoute('app_admin_facturation_utilisateur');
    }

    $xml      = $facturXService->generateXml($facture);
    $filename = sprintf('facturx_%s.xml', $facture->getNumFacture());

    return new Response($xml, Response::HTTP_OK, [
        'Content-Type'        => 'application/xml; charset=UTF-8',
        'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
    ]);
}
```

- [ ] **Step 3 : Vérifier la route**

```bash
php bin/console debug:router | grep facturx
```

Attendu : `app_admin_facturation_facturx` présente au path `/admin/facturation/facturx/{id}`.

- [ ] **Step 4 : Ajouter le bouton XML dans `liste.html.twig`**

Trouver la cellule des boutons d'action dans `templates/admin/facturation/liste.html.twig`. Après le bouton PDF téléchargeable (l'`<a>` avec `mdi-file-pdf-box`), ajouter :

```twig
{% if facture.type == 'facture' %}
    <a href="{{ path('app_admin_facturation_facturx', {id: facture.id}) }}" class="action-btn" style="color:#16a34a;border-color:rgba(22,163,74,0.3);background:rgba(22,163,74,0.08);" title="Télécharger Factur-X (XML)">
        <i class="mdi mdi-xml"></i>
    </a>
{% endif %}
```

- [ ] **Step 5 : Lint Twig**

```bash
php bin/console lint:twig templates/admin/facturation/liste.html.twig
```

Attendu : `OK`

- [ ] **Step 6 : Commit**

```bash
git add src/Controller/Admin/FacturationUtilisateurController.php templates/admin/facturation/liste.html.twig
git commit -m "feat: add Factur-X XML download route and button in facturation list"
```

---

## Self-Review Checklist

- [ ] `InvoiceCounter` est seedé avec le MAX existant (pas de reset à zéro)
- [ ] `LockMode::PESSIMISTIC_WRITE` présent dans `InvoiceNumberService`
- [ ] Les deux blocs `preg_match` sont supprimés du controller
- [ ] `reserveNumbers(count($proprietaires))` dans `genererUtilisateur` — si 0 propriétaires, le compteur n'est pas incrémenté
- [ ] `FacturXService::generateXml()` n'est jamais appelé pour `type='avoir'` (guard dans le controller)
- [ ] `ZugferdDocumentBuilder::createNew(ZugferdProfiles::PROFILE_MINIMUM)` vérifié avec la version installée
- [ ] Bouton XML visible uniquement pour `type='facture'` dans la liste
