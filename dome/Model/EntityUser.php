<?php
namespace Model;
use Model\Traits\EntityUserTrait;
use LSYS\Model\Entity;
class EntityUser extends Entity{
    use EntityUserTrait;
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
