<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930160512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        $this->addSql('ALTER TABLE message ADD command VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE message ADD reply_to_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FFFDF7169 FOREIGN KEY (reply_to_id) REFERENCES message (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_B6BD307FFFDF7169 ON message (reply_to_id)');
    }

    public function down(Schema $schema): void
    {

        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307FFFDF7169');
        $this->addSql('DROP INDEX IDX_B6BD307FFFDF7169');
        $this->addSql('ALTER TABLE message DROP command');
        $this->addSql('ALTER TABLE message DROP reply_to_id');
    }
}
