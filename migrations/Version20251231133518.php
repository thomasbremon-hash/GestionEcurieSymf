<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251231133518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prix DOUBLE PRECISION NOT NULL, description LONGTEXT NOT NULL, produit_entreprise_taxes_id INT DEFAULT NULL, INDEX IDX_29A5EC27AA526104 (produit_entreprise_taxes_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE produit_entreprise_taxes (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE taxes (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, pourcentage INT NOT NULL, produit_entreprise_taxes_id INT DEFAULT NULL, INDEX IDX_C28EA7F8AA526104 (produit_entreprise_taxes_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27AA526104 FOREIGN KEY (produit_entreprise_taxes_id) REFERENCES produit_entreprise_taxes (id)');
        $this->addSql('ALTER TABLE taxes ADD CONSTRAINT FK_C28EA7F8AA526104 FOREIGN KEY (produit_entreprise_taxes_id) REFERENCES produit_entreprise_taxes (id)');
        $this->addSql('ALTER TABLE entreprise ADD produit_entreprise_taxes_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE entreprise ADD CONSTRAINT FK_D19FA60AA526104 FOREIGN KEY (produit_entreprise_taxes_id) REFERENCES produit_entreprise_taxes (id)');
        $this->addSql('CREATE INDEX IDX_D19FA60AA526104 ON entreprise (produit_entreprise_taxes_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27AA526104');
        $this->addSql('ALTER TABLE taxes DROP FOREIGN KEY FK_C28EA7F8AA526104');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE produit_entreprise_taxes');
        $this->addSql('DROP TABLE taxes');
        $this->addSql('ALTER TABLE entreprise DROP FOREIGN KEY FK_D19FA60AA526104');
        $this->addSql('DROP INDEX IDX_D19FA60AA526104 ON entreprise');
        $this->addSql('ALTER TABLE entreprise DROP produit_entreprise_taxes_id');
    }
}
