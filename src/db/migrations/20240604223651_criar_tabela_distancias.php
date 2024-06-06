<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CriarTabelaDistancias extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('distancias');
        $table->addColumn('cep_inicio', 'integer')
              ->addColumn('cep_fim', 'integer')
              ->addColumn('distancia', 'float', ['signed' => false])
              ->addColumn('criacao', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('atualizacao', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['cep_inicio', 'cep_fim'], ['unique' => true])
              ->create();
    }
}
