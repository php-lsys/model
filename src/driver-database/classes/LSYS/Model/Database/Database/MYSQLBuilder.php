<?php
namespace LSYS\Model\Database;
class Builder extends \LSYS\Entity\Database\Builder{
    
    /**
     * {@inheritDoc}
     * @see \LSYS\Entity\Database\Builder::insert()
     */
    public function insert(array $records,$unique_replace=false){
        $sql=parent::insert($records,$unique_replace);
        if($unique_replace){
            $sql.="";
        }
        return $sql;
    }
}
