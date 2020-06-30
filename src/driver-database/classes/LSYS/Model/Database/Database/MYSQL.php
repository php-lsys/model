<?php
namespace LSYS\Model\Database\Database;
use LSYS\Entity\Column;
use LSYS\Entity\ColumnSet;
class MYSQL extends  \LSYS\Model\Database\Database {
    /**
     * {@inheritDoc}
     * @see \LSYS\Model\Database::listColumns()
     */
    public function listColumns(string $table)
    {
        $columns=[];
        $pk=[];
        $sql='SHOW FULL COLUMNS FROM '.$table;
        foreach ($this->_db->getMasterConnect()->query($sql)->setFetchFree() as $row) {
            $column=new Column($row['Field']);
            if($row['Key']=='PRI')$pk[]=$row['Field'];
            if($row['Null'] == 'YES')$column->setAllowNull(1);
            if ($row['Default']!='CURRENT_TIMESTAMP'){
                $column->setDefault($row['Default']);
            }
            $column->setType($row['Type']);
            $column->setComment(trim($row['Comment'],"\t\r\n"));
            $columns[]=$column;
        }
        return new \LSYS\Model\Database\ColumnSet(new ColumnSet($columns), empty($pk)?null:(count($pk)==1?array_shift($pk):$pk));
    }
}
