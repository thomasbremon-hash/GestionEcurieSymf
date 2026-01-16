<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260115135203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE deplacement_cheval (deplacement_id INT NOT NULL, cheval_id INT NOT NULL, INDEX IDX_40689A67355B84A (deplacement_id), INDEX IDX_40689A67C8BE953B (cheval_id), PRIMARY KEY (deplacement_id, cheval_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE deplacement_cheval ADD CONSTRAINT FK_40689A67355B84A FOREIGN KEY (deplacement_id) REFERENCES deplacement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE deplacement_cheval ADD CONSTRAINT FK_40689A67C8BE953B FOREIGN KEY (cheval_id) REFERENCES cheval (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deplacement_cheval DROP FOREIGN KEY FK_40689A67355B84A');
        $this->addSql('ALTER TABLE deplacement_cheval DROP FOREIGN KEY FK_40689A67C8BE953B');
        $this->addSql('DROP TABLE deplacement_cheval');
    }
}
