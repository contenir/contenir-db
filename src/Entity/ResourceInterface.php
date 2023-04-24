<?php

namespace Contenir\Db\Model\Entity;

interface ResourceInterface
{
    /**
     * Returns the string identifier of the Resource
     *
     * @return string
     */
    public function getResourceId();

    /**
     * Returns an array of the row primary keys/values
     *
     * @return array
     */
    public function getPrimaryKeys();
}
