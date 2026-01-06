<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251231134051 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit_entreprise_taxes ADD entreprise_id INT DEFAULT NULL, ADD produit_id INT DEFAULT NULL, ADD taxes_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE produit_entreprise_taxes ADD CONSTRAINT FK_9496F80CA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('ALTER TABLE produit_entreprise_taxes ADD CONSTRAINT FK_9496F80CF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE produit_entreprise_taxes ADD CONSTRAINT FK_9496F80C36D06393 FOREIGN KEY (taxes_id) REFERENCES taxes (id)');
        $this->addSql('CREATE INDEX IDX_9496F80CA4AEAFEA ON produit_entreprise_taxes (entreprise_id)');
        $this->addSql('CREATE INDEX IDX_9496F80CF347EFB ON produit_entreprise_taxes (produit_id)');
        $this->addSql('CREATE INDEX IDX_9496F80C36D06393 ON produit_entreprise_taxes (taxes_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit_entreprise_taxes DROP FOREIGN KEY FK_9496F80CA4AEAFEA');
        $this->addSql('ALTER TABLE produit_entreprise_taxes DROP FOREIGN KEY FK_9496F80CF347EFB');
        $this->addSql('ALTER TABLE produit_entreprise_taxes DROP FOREIGN KEY FK_9496F80C36D06393');
        $this->addSql('DROP INDEX IDX_9496F80CA4AEAFEA ON produit_entreprise_taxes');
        $this->addSql('DROP INDEX IDX_9496F80CF347EFB ON produit_entreprise_taxes');
        $this->addSql('DROP INDEX IDX_9496F80C36D06393 ON produit_entreprise_taxes');
        $this->addSql('ALTER TABLE produit_entreprise_taxes DROP entreprise_id, DROP produit_id, DROP taxes_id');
    }
}
