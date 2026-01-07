<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260107115258 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE distance_structure (id INT AUTO_INCREMENT NOT NULL, distance DOUBLE PRECISION DEFAULT NULL, structure_id INT DEFAULT NULL, entreprise_id INT DEFAULT NULL, INDEX IDX_B93A795E2534008B (structure_id), INDEX IDX_B93A795EA4AEAFEA (entreprise_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE distance_structure ADD CONSTRAINT FK_B93A795E2534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE distance_structure ADD CONSTRAINT FK_B93A795EA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE distance_structure DROP FOREIGN KEY FK_B93A795E2534008B');
        $this->addSql('ALTER TABLE distance_structure DROP FOREIGN KEY FK_B93A795EA4AEAFEA');
        $this->addSql('DROP TABLE distance_structure');
    }
}
