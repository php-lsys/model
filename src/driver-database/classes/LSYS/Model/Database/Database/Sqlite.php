<?php
namespace LSYS\Model\Database\Database;
use LSYS\Entity\Column;
use LSYS\Entity\ColumnSet;
class Sqlite extends  \LSYS\Model\Database\Database {
    /**
     * {@inheritDoc}
     * @see \LSYS\Model\Database::listColumns()
     */
    public function listColumns(string  $table)
    {
        $columns=[];
        $pk=[];
        $sql='PRAGMA table_info(' . $table . ')';
        $result = $this->_db->getMasterConnect()->query($sql);
        foreach ($result as $row)
        {
            $column=new Column($row['name']);
            if($row['pk']??0)$pk[]=$row['name'];
            if($row['notnull'] == '0')$column->setAllowNull(1);
            $column->setDefault($row['dflt_value']);
            $column->setType($row['type']);
            $column->setComment($row['name']);
            $columns[]=$column;
        }
        return new \LSYS\Model\Database\ColumnSet(new ColumnSet($columns), empty($pk)?null:(count($pk)==1?array_shift($pk):$pk));
    }
}
