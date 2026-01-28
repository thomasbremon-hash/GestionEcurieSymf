<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260127084659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cheval_proprietaire (id INT AUTO_INCREMENT NOT NULL, pourcentage DOUBLE PRECISION NOT NULL, cheval_id INT DEFAULT NULL, proprietaire_id INT DEFAULT NULL, INDEX IDX_88D5EF2BC8BE953B (cheval_id), INDEX IDX_88D5EF2B76C50E4A (proprietaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cheval_proprietaire ADD CONSTRAINT FK_88D5EF2BC8BE953B FOREIGN KEY (cheval_id) REFERENCES cheval (id)');
        $this->addSql('ALTER TABLE cheval_proprietaire ADD CONSTRAINT FK_88D5EF2B76C50E4A FOREIGN KEY (proprietaire_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE cheval_user DROP FOREIGN KEY `FK_58298092A76ED395`');
        $this->addSql('ALTER TABLE cheval_user DROP FOREIGN KEY `FK_58298092C8BE953B`');
        $this->addSql('DROP TABLE cheval_user');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cheval_user (cheval_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_58298092C8BE953B (cheval_id), INDEX IDX_58298092A76ED395 (user_id), PRIMARY KEY (cheval_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE cheval_user ADD CONSTRAINT `FK_58298092A76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cheval_user ADD CONSTRAINT `FK_58298092C8BE953B` FOREIGN KEY (cheval_id) REFERENCES cheval (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cheval_proprietaire DROP FOREIGN KEY FK_88D5EF2BC8BE953B');
        $this->addSql('ALTER TABLE cheval_proprietaire DROP FOREIGN KEY FK_88D5EF2B76C50E4A');
        $this->addSql('DROP TABLE cheval_proprietaire');
    }
}
