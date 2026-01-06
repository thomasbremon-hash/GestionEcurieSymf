<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251231133723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entreprise DROP FOREIGN KEY `FK_D19FA60AA526104`');
        $this->addSql('DROP INDEX IDX_D19FA60AA526104 ON entreprise');
        $this->addSql('ALTER TABLE entreprise DROP produit_entreprise_taxes_id');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY `FK_29A5EC27AA526104`');
        $this->addSql('DROP INDEX IDX_29A5EC27AA526104 ON produit');
        $this->addSql('ALTER TABLE produit DROP produit_entreprise_taxes_id');
        $this->addSql('ALTER TABLE taxes DROP FOREIGN KEY `FK_C28EA7F8AA526104`');
        $this->addSql('DROP INDEX IDX_C28EA7F8AA526104 ON taxes');
        $this->addSql('ALTER TABLE taxes DROP produit_entreprise_taxes_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entreprise ADD produit_entreprise_taxes_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE entreprise ADD CONSTRAINT `FK_D19FA60AA526104` FOREIGN KEY (produit_entreprise_taxes_id) REFERENCES produit_entreprise_taxes (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_D19FA60AA526104 ON entreprise (produit_entreprise_taxes_id)');
        $this->addSql('ALTER TABLE produit ADD produit_entreprise_taxes_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT `FK_29A5EC27AA526104` FOREIGN KEY (produit_entreprise_taxes_id) REFERENCES produit_entreprise_taxes (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_29A5EC27AA526104 ON produit (produit_entreprise_taxes_id)');
        $this->addSql('ALTER TABLE taxes ADD produit_entreprise_taxes_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE taxes ADD CONSTRAINT `FK_C28EA7F8AA526104` FOREIGN KEY (produit_entreprise_taxes_id) REFERENCES produit_entreprise_taxes (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_C28EA7F8AA526104 ON taxes (produit_entreprise_taxes_id)');
    }
}
