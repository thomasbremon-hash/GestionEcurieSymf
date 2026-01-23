<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260121094648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cheval_deplacement (cheval_id INT NOT NULL, deplacement_id INT NOT NULL, INDEX IDX_2A781F89C8BE953B (cheval_id), INDEX IDX_2A781F89355B84A (deplacement_id), PRIMARY KEY (cheval_id, deplacement_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cheval_produit (id INT AUTO_INCREMENT NOT NULL, quantite DOUBLE PRECISION NOT NULL, prix_unitaire DOUBLE PRECISION NOT NULL, total DOUBLE PRECISION NOT NULL, commentaire LONGTEXT DEFAULT NULL, cheval_id INT DEFAULT NULL, produit_id INT DEFAULT NULL, mois_de_gestion_id INT DEFAULT NULL, INDEX IDX_6F2C9663C8BE953B (cheval_id), INDEX IDX_6F2C9663F347EFB (produit_id), INDEX IDX_6F2C9663B832F94D (mois_de_gestion_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mois_de_gestion (id INT AUTO_INCREMENT NOT NULL, mois INT NOT NULL, annee INT NOT NULL, entreprise_id INT DEFAULT NULL, INDEX IDX_83CEAEC2A4AEAFEA (entreprise_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cheval_deplacement ADD CONSTRAINT FK_2A781F89C8BE953B FOREIGN KEY (cheval_id) REFERENCES cheval (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cheval_deplacement ADD CONSTRAINT FK_2A781F89355B84A FOREIGN KEY (deplacement_id) REFERENCES deplacement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cheval_produit ADD CONSTRAINT FK_6F2C9663C8BE953B FOREIGN KEY (cheval_id) REFERENCES cheval (id)');
        $this->addSql('ALTER TABLE cheval_produit ADD CONSTRAINT FK_6F2C9663F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE cheval_produit ADD CONSTRAINT FK_6F2C9663B832F94D FOREIGN KEY (mois_de_gestion_id) REFERENCES mois_de_gestion (id)');
        $this->addSql('ALTER TABLE mois_de_gestion ADD CONSTRAINT FK_83CEAEC2A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cheval_deplacement DROP FOREIGN KEY FK_2A781F89C8BE953B');
        $this->addSql('ALTER TABLE cheval_deplacement DROP FOREIGN KEY FK_2A781F89355B84A');
        $this->addSql('ALTER TABLE cheval_produit DROP FOREIGN KEY FK_6F2C9663C8BE953B');
        $this->addSql('ALTER TABLE cheval_produit DROP FOREIGN KEY FK_6F2C9663F347EFB');
        $this->addSql('ALTER TABLE cheval_produit DROP FOREIGN KEY FK_6F2C9663B832F94D');
        $this->addSql('ALTER TABLE mois_de_gestion DROP FOREIGN KEY FK_83CEAEC2A4AEAFEA');
        $this->addSql('DROP TABLE cheval_deplacement');
        $this->addSql('DROP TABLE cheval_produit');
        $this->addSql('DROP TABLE mois_de_gestion');
    }
}
