<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403123542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add legal compliance fields (entreprise, facturation) and unique constraints';
    }

    public function up(Schema $schema): void
    {
        // Entreprise — add columns if missing
        $cols = $this->connection->executeQuery(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'entreprise' AND COLUMN_NAME = 'forme_juridique'"
        )->fetchOne();

        if ($cols === false) {
            $this->addSql('ALTER TABLE entreprise ADD forme_juridique VARCHAR(50) DEFAULT NULL, ADD capital_social VARCHAR(50) DEFAULT NULL, ADD rcs VARCHAR(100) DEFAULT NULL');
        }

        // FacturationUtilisateur — add columns if missing
        $cols2 = $this->connection->executeQuery(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'facturation_utilisateur' AND COLUMN_NAME = 'date_emission'"
        )->fetchOne();

        if ($cols2 === false) {
            $this->addSql('ALTER TABLE facturation_utilisateur ADD date_emission DATETIME DEFAULT NULL, ADD date_paiement DATETIME DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL');
            $this->addSql('UPDATE facturation_utilisateur SET date_emission = NOW(), created_at = NOW() WHERE date_emission IS NULL');
            $this->addSql('ALTER TABLE facturation_utilisateur MODIFY date_emission DATETIME NOT NULL, MODIFY created_at DATETIME NOT NULL');
        }

        // Unique index on num_facture
        $idx1 = $this->connection->executeQuery(
            "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'facturation_utilisateur' AND INDEX_NAME = 'UNIQ_NUM_FACTURE'"
        )->fetchOne();

        if ($idx1 === false) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_NUM_FACTURE ON facturation_utilisateur (num_facture)');
        }

        // Unique index on (mois, annee)
        $idx2 = $this->connection->executeQuery(
            "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mois_de_gestion' AND INDEX_NAME = 'UNIQ_MOIS_ANNEE'"
        )->fetchOne();

        if ($idx2 === false) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_MOIS_ANNEE ON mois_de_gestion (mois, annee)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entreprise DROP forme_juridique, DROP capital_social, DROP rcs');
        $this->addSql('DROP INDEX UNIQ_NUM_FACTURE ON facturation_utilisateur');
        $this->addSql('ALTER TABLE facturation_utilisateur DROP date_emission, DROP date_paiement, DROP created_at');
        $this->addSql('DROP INDEX UNIQ_MOIS_ANNEE ON mois_de_gestion');
    }
}
