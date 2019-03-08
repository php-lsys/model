<?php
namespace Model\Traits;
/**
 * @property int $id ID
 * @property int $is_post	是否发货
 * @property int $post_time	发货时间
 * @property int $is_post_confirm	是否确认收货
 * @method orm1 table()
 */
trait EntityUser {
    public function tableClass()
    {
        return \Model\ModelUser::class;
    }
}