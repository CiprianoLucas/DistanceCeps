<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CriarTabelaDistancias extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('distancias');
        $table->addColumn('cep_inicio', 'integer')
              ->addColumn('cep_fim', 'integer')
              ->addColumn('distancia', 'float', ['signed' => false])
              ->addColumn('criacao', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('atualizacao', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['cep_inicio', 'cep_fim'], ['unique' => true])
              ->addValidator('distancia', 'greaterThan', ['value' => 0, 'message' => 'A distância deve ser maior que zero'])
              ->addValidator('cep_inicio', 'integer', ['message' => 'CEP de início deve ser um valor numérico'])
              ->addValidator('cep_fim', 'integer', ['message' => 'CEP de fim deve ser um valor numérico'])
              ->create();
    }
}
