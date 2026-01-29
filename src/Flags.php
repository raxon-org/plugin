<?php
/**
 * @author          Remco van der Velde
 * @since           2026-01-28
 * @copyright       (c) Remco van der Velde
 * @license         MIT
 * @version         1.0
 */
namespace Plugin;

use Exception;
use Exception\ObjectException;
use Module\Core;
use Module\Data;

trait Flags {

    /**
     * @throws Exception
     */
    public function flags($attribute=null, $value=null): mixed
    {
        $prefix = __FUNCTION__;
        $config = $this->config();
        if($attribute!== null && $value !== null){
            $config->set($prefix . '.' . $attribute, $value);
        }
        elseif($attribute !== null){
            return $config->get($prefix . '.' . $attribute);
        } elseif($config !== null) {
            return $config->get($prefix);
        }
        return null;
    }
}