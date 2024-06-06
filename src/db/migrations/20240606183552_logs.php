<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Logs extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('logs');
        $table->addColumn('level', 'string', ['limit' => 50])
              ->addColumn('message', 'text')
              ->addColumn('context', 'text', ['null' => true])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->create();
    }
}
