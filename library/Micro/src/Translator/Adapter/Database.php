<?php

namespace Micro\Translator\Adapter;

use Micro\Database\Table\Table;

class Database extends AdapterAbstract
{
    /**
     * @var Table
     */
    protected $table;

    public function __construct()
    {
        $name = \config('translator.options.table', 'Translations');

        $this->table = new Table(array(
            'name' => $name
        ));
    }

    protected function loadTranslations($code)
    {
        $this->translations[$code] = [];

        $cols  = $this->table->info('cols');
        $value = 'value_' . $code;

        if (in_array($value, $cols)) {
            $this->translations[$code] = $this->table->getAdapter()->fetchPairs(
                $this->table->select(\true)->where('active = 1')->reset('columns')->columns(['key', $value])
            );
        }

        return $this->translations;
    }
}