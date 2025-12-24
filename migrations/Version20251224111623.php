<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224111623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cheval_proprietaire (id INT AUTO_INCREMENT NOT NULL, pourcentage DOUBLE PRECISION NOT NULL, proprietaires_id INT DEFAULT NULL, chevaux_id INT DEFAULT NULL, cheval_id INT DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_88D5EF2B710ED0A5 (proprietaires_id), INDEX IDX_88D5EF2BEC439866 (chevaux_id), INDEX IDX_88D5EF2BC8BE953B (cheval_id), INDEX IDX_88D5EF2BA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cheval_proprietaire ADD CONSTRAINT FK_88D5EF2B710ED0A5 FOREIGN KEY (proprietaires_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE cheval_proprietaire ADD CONSTRAINT FK_88D5EF2BEC439866 FOREIGN KEY (chevaux_id) REFERENCES cheval (id)');
        $this->addSql('ALTER TABLE cheval_proprietaire ADD CONSTRAINT FK_88D5EF2BC8BE953B FOREIGN KEY (cheval_id) REFERENCES cheval (id)');
        $this->addSql('ALTER TABLE cheval_proprietaire ADD CONSTRAINT FK_88D5EF2BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cheval_proprietaire DROP FOREIGN KEY FK_88D5EF2B710ED0A5');
        $this->addSql('ALTER TABLE cheval_proprietaire DROP FOREIGN KEY FK_88D5EF2BEC439866');
        $this->addSql('ALTER TABLE cheval_proprietaire DROP FOREIGN KEY FK_88D5EF2BC8BE953B');
        $this->addSql('ALTER TABLE cheval_proprietaire DROP FOREIGN KEY FK_88D5EF2BA76ED395');
        $this->addSql('DROP TABLE cheval_proprietaire');
    }
}
