<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001151012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        $this->addSql('ALTER TABLE admin ADD api_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE admin ADD token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_880E0D767BA2F5EB ON admin (api_token)');
    }

    public function down(Schema $schema): void
    {

        $this->addSql('DROP INDEX UNIQ_880E0D767BA2F5EB');
        $this->addSql('ALTER TABLE admin DROP api_token');
        $this->addSql('ALTER TABLE admin DROP token_expires_at');
    }
}
