<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260205082518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facturation_utilisateur ADD entreprise_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE facturation_utilisateur ADD CONSTRAINT FK_32CD4F2A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('CREATE INDEX IDX_32CD4F2A4AEAFEA ON facturation_utilisateur (entreprise_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facturation_utilisateur DROP FOREIGN KEY FK_32CD4F2A4AEAFEA');
        $this->addSql('DROP INDEX IDX_32CD4F2A4AEAFEA ON facturation_utilisateur');
        $this->addSql('ALTER TABLE facturation_utilisateur DROP entreprise_id');
    }
}
