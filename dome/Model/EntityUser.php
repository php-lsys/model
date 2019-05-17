<?php
namespace Model;
use LSYS\Model\Traits\EntityRelatedMethods;
use Model\Traits\EntityUserTrait;
use LSYS\Model\Entity;
/**
 * @method EntityUser orm1(); 在 hasOne 定义
 * @method EntityUser orm2();　在 belongsTo 定义
 * @method ModelUser orm3();　在 hasMany 定义
 * @method ModelUser orm4();　在 hasMany 定义
 */
class EntityUser extends Entity{
    use EntityUserTrait;
    use EntityRelatedMethods;
    //自动校验 自动过滤数据 实现 示例如下
//     public function filterFactory() {
//         return (new \LSYS\Entity\Filter($this))
//             ->rules(array (
//                 new \LSYS\Model\FilterRule\FilterCallback("trim"),
//                 new \LSYS\Model\FilterRule\FilterCallback("strip_tags"),
//             ),'nickname')
//             ->rules(array (
//                 new \LSYS\Model\ValidRule\ValidCallback(function($val){
//                     if(empty($val))return NULL;
//                     else $val;
//                 })
//             ),'headimg')
//             ;
//     }
//     public function validationFactory() {
//         return (new \LSYS\Entity\Validation($this))
//             ->rules(array (
//                 new \LSYS\Model\ValidRule\ValidStrlen(1, 15, 0),
//             ),'nickname')
//             ->rules(array (
//                 new \LSYS\Model\ValidRule\ValidNum(1, 200, 1),
//             ),'age')
//         ;
//     }
    
}
