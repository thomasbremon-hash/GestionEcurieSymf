<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407123636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE facturation_utilisateur ADD type VARCHAR(20) NOT NULL DEFAULT 'facture', ADD facture_origine_id INT DEFAULT NULL");
        $this->addSql('ALTER TABLE facturation_utilisateur ADD CONSTRAINT FK_32CD4F2EA98768A FOREIGN KEY (facture_origine_id) REFERENCES facturation_utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_32CD4F2EA98768A ON facturation_utilisateur (facture_origine_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facturation_utilisateur DROP FOREIGN KEY FK_32CD4F2EA98768A');
        $this->addSql('DROP INDEX IDX_32CD4F2EA98768A ON facturation_utilisateur');
        $this->addSql('ALTER TABLE facturation_utilisateur DROP type, DROP facture_origine_id');
    }
}
