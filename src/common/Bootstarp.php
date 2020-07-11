<?php
/**
 * lsys cache
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Model{
    function __(?string $string, array $values = NULL, string $domain = "default"):?string
	{
		$i18n=\LSYS\I18n\DI::get()->i18n(__DIR__."/I18n/");
		return $i18n->__($string,  $values , $domain );
	}
	/**
	 * 页码转偏移
	 * @param int $page
	 * @param int $limit
	 * @return number
	 */
	function pageOffset($page,$limit) {
        $limit=intval($limit);
        if ($limit<=0)return 0;
	    $page=$page<=1?1:$page;
	    $page-=1;
	    return $limit*$page;
	}
	/**
	 * 页码数组转偏移数组
	 * @param int $page
	 * @param int $limit
	 * @return [int,int]
	 */
	function pageParam($page,$limit) {
	    return [pageOffset($page, $limit),$limit];
	}
	
}