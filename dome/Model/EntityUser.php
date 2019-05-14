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
//         return (new \LSYS\Entity\Filter($this))->rules(array (
//             'nickname' => array (
//                 new \LSYS\Entity\FilterRule\Callback("trim"),
//                 new \LSYS\Entity\FilterRule\Callback("strip_tags"),
//             ),
//             'headimg' => array (
//                 new \LSYS\Entity\ValidRule\Callback(function($val){
//                     if(empty($val))return NULL;
//                     else $val;
//                 })
//                 ),
//                 ));
//     }
//     public function validationFactory() {
//         return (new \LSYS\Entity\Validation($this))->rules(array (
//             'nickname' => array (
//                 new \LSYS\Model\ValidRule\StrlenRange(1, 15, 0),
//             ),
//         ));
//     }
    
}
