<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260122152827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE facturation_entreprise (id INT AUTO_INCREMENT NOT NULL, total DOUBLE PRECISION NOT NULL, entreprise_id INT DEFAULT NULL, mois_de_gestion_id INT DEFAULT NULL, INDEX IDX_3C5CD3F2A4AEAFEA (entreprise_id), INDEX IDX_3C5CD3F2B832F94D (mois_de_gestion_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE facturation_entreprise ADD CONSTRAINT FK_3C5CD3F2A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('ALTER TABLE facturation_entreprise ADD CONSTRAINT FK_3C5CD3F2B832F94D FOREIGN KEY (mois_de_gestion_id) REFERENCES mois_de_gestion (id)');
        $this->addSql('ALTER TABLE mois_de_gestion DROP FOREIGN KEY `FK_83CEAEC2A4AEAFEA`');
        $this->addSql('DROP INDEX IDX_83CEAEC2A4AEAFEA ON mois_de_gestion');
        $this->addSql('ALTER TABLE mois_de_gestion DROP entreprise_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facturation_entreprise DROP FOREIGN KEY FK_3C5CD3F2A4AEAFEA');
        $this->addSql('ALTER TABLE facturation_entreprise DROP FOREIGN KEY FK_3C5CD3F2B832F94D');
        $this->addSql('DROP TABLE facturation_entreprise');
        $this->addSql('ALTER TABLE mois_de_gestion ADD entreprise_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE mois_de_gestion ADD CONSTRAINT `FK_83CEAEC2A4AEAFEA` FOREIGN KEY (entreprise_id) REFERENCES entreprise (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_83CEAEC2A4AEAFEA ON mois_de_gestion (entreprise_id)');
    }
}
