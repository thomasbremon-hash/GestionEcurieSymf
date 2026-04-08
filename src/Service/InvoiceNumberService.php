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
