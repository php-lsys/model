<?php
namespace LSYS\Model\Database\Database;
use LSYS\Entity\Column;
use LSYS\Entity\ColumnSet;
class Postgresql extends  \LSYS\Model\Database\Database {
    public function listColumns($table)
    {
        $sql="SELECT
		pg_attribute.attname as attname,pg_attribute.typname as typname,
        pg_attribute.adsrc as adsrc,pg_constraint.contype as contype,
        pg_attribute.attnotnull as attnotnull
		FROM
		pg_attribute
		INNER JOIN pg_constraint  ON pg_constraint.conrelid = pg_attribute.attrelid
		INNER JOIN pg_class  ON pg_attribute.attrelid = pg_class.oid
		INNER JOIN pg_type   ON pg_attribute.atttypid = pg_type.oid
		LEFT OUTER JOIN pg_attrdef ON pg_attrdef.adrelid = pg_class.oid AND pg_attrdef.adnum = pg_attribute.attnum
		LEFT OUTER JOIN pg_description ON pg_description.objoid = pg_class.oid AND pg_description.objsubid = pg_attribute.attnum
		WHERE
		pg_attribute.attnum > 0
		AND attisdropped <> 't'
		ORDER BY pg_attribute.attnum ;";
	    $result = $this->_db->query($sql);
        $columns=[];
        $pk=[];
        foreach ($result as $row)
        {
            $column=new Column($row['attname']);
            if($row['contype']=='p')$pk[]=$row['attname'];
            if(empty($row['attnotnull']))$column->setAllowNull(1);
            $column->setType($row['typname']);
            $column->setComment(trim($row['adsrc'],"\r\n\t"));
            $columns[]=$column;
        }
        return new \LSYS\Model\Database\ColumnSet(new ColumnSet($columns), count($pk)==1?array_shift($pk):$pk);
    }
}
