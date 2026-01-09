<?php

namespace App\EventSubscriber;

use App\Entity\DistanceStructure;
use App\Service\DistanceCalculator;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class DistanceStructureSubscriber implements EventSubscriber
{
    public function __construct(
        private DistanceCalculator $calculator
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->handle($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->handle($args);
    }

    private function handle(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof DistanceStructure) {
            return;
        }

        if (!$entity->getEntreprise() || !$entity->getStructure()) {
            return;
        }

        $distance = $this->calculator->calculate(
            $entity->getEntreprise()->getAdresseComplete(),
            $entity->getStructure()->getAdresseComplete()
        );

        if ($distance !== null) {
            $entity->setDistance(round($distance, 2));
        }
    }
}
