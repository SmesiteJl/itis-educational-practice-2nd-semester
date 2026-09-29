<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001165730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Настройки бота (системный промпт)';
    }

    public function up(Schema $schema): void
    {

        $this->addSql('CREATE TABLE setting (name VARCHAR(100) NOT NULL, value TEXT NOT NULL, PRIMARY KEY (name))');

        $this->addSql(
            'INSERT INTO setting (name, value) VALUES (?, ?)',
            [
                'system_prompt',
                'Ты бот в групповом чате. Отвечай на русском языке, если в задании не сказано другое. '
                . 'Пиши коротко и по делу, без вступлений вроде «Конечно!» и без Markdown-разметки.',
            ]
        );
    }

    public function down(Schema $schema): void
    {

        $this->addSql('DROP TABLE setting');
    }
}
