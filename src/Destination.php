<?php
/**
 * @author          Remco van der Velde
 * @since           2026-01-28
 * @copyright       (c) Remco van der Velde
 * @license         MIT
 * @version         1.0
 */
namespace Plugin;

use Module\Destination as Destiny;

trait Destination {

    public function destination(): false|Destiny
    {
        $config = $this->config();
        if($config){
            if(empty($config->get('route.current'))){
                return false;
            }
            return $this->config()->get('route.current');
        }
        return false;
    }
}