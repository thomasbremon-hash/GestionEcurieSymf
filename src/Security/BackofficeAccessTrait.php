<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Trait à utiliser dans tous les controllers admin.
 * Fournit une méthode pour vérifier l'accès backoffice (lecture seule ou admin complet).
 */
trait BackofficeAccessTrait
{
    /**
     * Accès lecture : ADMIN, GESTIONNAIRE, COMPTABILITE, CLIENT
     * Lance AccessDeniedException si aucun rôle ne correspond.
     */
    private function requireBackofficeAccess(): void
    {
        if (
            !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_GESTIONNAIRE')
            && !$this->isGranted('ROLE_COMPTABILITE')
            && !$this->isGranted('ROLE_CLIENT')
        ) {
            throw new AccessDeniedException('Accès réservé au backoffice.');
        }
    }

    /**
     * Accès écriture : ADMIN uniquement.
     * Lance AccessDeniedException si non admin.
     */
    private function requireAdminAccess(): void
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Action réservée aux administrateurs.');
        }
    }
}
