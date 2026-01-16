<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260115133720 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cheval_deplacement (cheval_id INT NOT NULL, deplacement_id INT NOT NULL, INDEX IDX_2A781F89C8BE953B (cheval_id), INDEX IDX_2A781F89355B84A (deplacement_id), PRIMARY KEY (cheval_id, deplacement_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cheval_deplacement ADD CONSTRAINT FK_2A781F89C8BE953B FOREIGN KEY (cheval_id) REFERENCES cheval (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cheval_deplacement ADD CONSTRAINT FK_2A781F89355B84A FOREIGN KEY (deplacement_id) REFERENCES deplacement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE deplacement DROP FOREIGN KEY `FK_1296FAC2C8BE953B`');
        $this->addSql('DROP INDEX IDX_1296FAC2C8BE953B ON deplacement');
        $this->addSql('ALTER TABLE deplacement DROP cheval_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cheval_deplacement DROP FOREIGN KEY FK_2A781F89C8BE953B');
        $this->addSql('ALTER TABLE cheval_deplacement DROP FOREIGN KEY FK_2A781F89355B84A');
        $this->addSql('DROP TABLE cheval_deplacement');
        $this->addSql('ALTER TABLE deplacement ADD cheval_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE deplacement ADD CONSTRAINT `FK_1296FAC2C8BE953B` FOREIGN KEY (cheval_id) REFERENCES cheval (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_1296FAC2C8BE953B ON deplacement (cheval_id)');
    }
}
