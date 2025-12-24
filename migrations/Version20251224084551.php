<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224084551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cheval_user (cheval_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_58298092C8BE953B (cheval_id), INDEX IDX_58298092A76ED395 (user_id), PRIMARY KEY (cheval_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cheval_user ADD CONSTRAINT FK_58298092C8BE953B FOREIGN KEY (cheval_id) REFERENCES cheval (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cheval_user ADD CONSTRAINT FK_58298092A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cheval ADD CONSTRAINT FK_F286849DA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('CREATE INDEX IDX_F286849DA4AEAFEA ON cheval (entreprise_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cheval_user DROP FOREIGN KEY FK_58298092C8BE953B');
        $this->addSql('ALTER TABLE cheval_user DROP FOREIGN KEY FK_58298092A76ED395');
        $this->addSql('DROP TABLE cheval_user');
        $this->addSql('ALTER TABLE cheval DROP FOREIGN KEY FK_F286849DA4AEAFEA');
        $this->addSql('DROP INDEX IDX_F286849DA4AEAFEA ON cheval');
    }
}
