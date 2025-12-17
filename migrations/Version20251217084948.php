<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251217084948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE course (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, lieu VARCHAR(255) NOT NULL, date_course VARCHAR(255) NOT NULL, distance INT NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, position INT DEFAULT NULL, temps DOUBLE PRECISION DEFAULT NULL, cheval_id INT NOT NULL, course_id INT NOT NULL, INDEX IDX_AB55E24FC8BE953B (cheval_id), INDEX IDX_AB55E24F591CC992 (course_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FC8BE953B FOREIGN KEY (cheval_id) REFERENCES cheval (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FC8BE953B');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F591CC992');
        $this->addSql('DROP TABLE course');
        $this->addSql('DROP TABLE participation');
    }
}
