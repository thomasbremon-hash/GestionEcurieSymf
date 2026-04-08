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
            $document->addDocumentSellerTaxRegistration('VA', $entreprise->getNumTVA());
        }

        $document->setDocumentBuyer(
            trim($user->getNom() . ' ' . $user->getPrenom())
        );

        // setDocumentSummation($grandTotalAmount, $duePayableAmount, $lineTotalAmount,
        //   $chargeTotalAmount, $allowanceTotalAmount, $taxBasisTotalAmount, $taxTotalAmount, ...)
        $document->setDocumentSummation(
            (float) $data['totalTTC'],  // grandTotalAmount
            (float) $data['totalTTC'],  // duePayableAmount
            (float) $data['totalHT'],   // lineTotalAmount
            0.0,                        // chargeTotalAmount
            0.0,                        // allowanceTotalAmount
            (float) $data['totalHT'],   // taxBasisTotalAmount
            (float) $data['totalTVA']   // taxTotalAmount
        );

        return $document->getContent();
    }
}
