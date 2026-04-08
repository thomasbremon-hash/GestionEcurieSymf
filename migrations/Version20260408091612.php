<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260408091612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invoice_counter (id INT NOT NULL, counter INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        // Seed with current max sequential number from existing factures
        $maxNum = $this->connection->fetchOne(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(num_facture, '-', -1) AS UNSIGNED)), 0) FROM facturation_utilisateur WHERE type = 'facture'"
        );
        $this->addSql('INSERT INTO invoice_counter (id, counter) VALUES (1, ' . (int)$maxNum . ')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE invoice_counter');
    }
}
