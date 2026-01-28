<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260127153018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE facturation_utilisateur (id INT AUTO_INCREMENT NOT NULL, total DOUBLE PRECISION NOT NULL, utilisateur_id INT DEFAULT NULL, mois_de_gestion_id INT DEFAULT NULL, INDEX IDX_32CD4F2FB88E14F (utilisateur_id), INDEX IDX_32CD4F2B832F94D (mois_de_gestion_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE facturation_utilisateur ADD CONSTRAINT FK_32CD4F2FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE facturation_utilisateur ADD CONSTRAINT FK_32CD4F2B832F94D FOREIGN KEY (mois_de_gestion_id) REFERENCES mois_de_gestion (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facturation_utilisateur DROP FOREIGN KEY FK_32CD4F2FB88E14F');
        $this->addSql('ALTER TABLE facturation_utilisateur DROP FOREIGN KEY FK_32CD4F2B832F94D');
        $this->addSql('DROP TABLE facturation_utilisateur');
    }
}
