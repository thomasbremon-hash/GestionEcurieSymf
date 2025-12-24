<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224132825 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE structure (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, rue VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, cp VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cheval_proprietaire DROP FOREIGN KEY `FK_88D5EF2B710ED0A5`');
        $this->addSql('ALTER TABLE cheval_proprietaire DROP FOREIGN KEY `FK_88D5EF2BA76ED395`');
        $this->addSql('ALTER TABLE cheval_proprietaire DROP FOREIGN KEY `FK_88D5EF2BC8BE953B`');
        $this->addSql('ALTER TABLE cheval_proprietaire DROP FOREIGN KEY `FK_88D5EF2BEC439866`');
        $this->addSql('DROP TABLE cheval_proprietaire');
        $this->addSql('ALTER TABLE cheval ADD deplacement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cheval ADD CONSTRAINT FK_F286849D355B84A FOREIGN KEY (deplacement_id) REFERENCES deplacement (id)');
        $this->addSql('CREATE INDEX IDX_F286849D355B84A ON cheval (deplacement_id)');
        $this->addSql('ALTER TABLE deplacement ADD structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE deplacement ADD CONSTRAINT FK_1296FAC22534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('CREATE INDEX IDX_1296FAC22534008B ON deplacement (structure_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cheval_proprietaire (id INT AUTO_INCREMENT NOT NULL, pourcentage DOUBLE PRECISION NOT NULL, proprietaires_id INT DEFAULT NULL, chevaux_id INT DEFAULT NULL, cheval_id INT DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_88D5EF2B710ED0A5 (proprietaires_id), INDEX IDX_88D5EF2BA76ED395 (user_id), INDEX IDX_88D5EF2BC8BE953B (cheval_id), INDEX IDX_88D5EF2BEC439866 (chevaux_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE cheval_proprietaire ADD CONSTRAINT `FK_88D5EF2B710ED0A5` FOREIGN KEY (proprietaires_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE cheval_proprietaire ADD CONSTRAINT `FK_88D5EF2BA76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE cheval_proprietaire ADD CONSTRAINT `FK_88D5EF2BC8BE953B` FOREIGN KEY (cheval_id) REFERENCES cheval (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE cheval_proprietaire ADD CONSTRAINT `FK_88D5EF2BEC439866` FOREIGN KEY (chevaux_id) REFERENCES cheval (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE structure');
        $this->addSql('ALTER TABLE cheval DROP FOREIGN KEY FK_F286849D355B84A');
        $this->addSql('DROP INDEX IDX_F286849D355B84A ON cheval');
        $this->addSql('ALTER TABLE cheval DROP deplacement_id');
        $this->addSql('ALTER TABLE deplacement DROP FOREIGN KEY FK_1296FAC22534008B');
        $this->addSql('DROP INDEX IDX_1296FAC22534008B ON deplacement');
        $this->addSql('ALTER TABLE deplacement DROP structure_id');
    }
}
