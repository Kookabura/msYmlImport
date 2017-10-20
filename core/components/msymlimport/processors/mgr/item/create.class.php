<?php

class msYmlImportItemCreateProcessor extends modObjectCreateProcessor
{
    public $objectType = 'msYmlImportItem';
    public $classKey = 'msYmlImportItem';
    public $languageTopics = array('msymlimport');
    //public $permission = 'create';


    /**
     * @return bool
     */
    public function beforeSet()
    {
        $name = trim($this->getProperty('name'));
        if (empty($name)) {
            $this->modx->error->addField('name', $this->modx->lexicon('msymlimport_item_err_name'));
        } elseif ($this->modx->getCount($this->classKey, array('name' => $name))) {
            $this->modx->error->addField('name', $this->modx->lexicon('msymlimport_item_err_ae'));
        }

        return parent::beforeSet();
    }

}

return 'msYmlImportItemCreateProcessor';