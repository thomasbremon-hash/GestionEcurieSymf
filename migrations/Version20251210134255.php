<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210134255 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cheval (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, race VARCHAR(255) NOT NULL, sexe VARCHAR(255) NOT NULL, date_naissance DATE NOT NULL, proprietaire_id INT DEFAULT NULL, INDEX IDX_F286849D76C50E4A (proprietaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cheval ADD CONSTRAINT FK_F286849D76C50E4A FOREIGN KEY (proprietaire_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cheval DROP FOREIGN KEY FK_F286849D76C50E4A');
        $this->addSql('DROP TABLE cheval');
    }
}
