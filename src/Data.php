<?php
/**
 * @author          Remco van der Velde
 * @since           2026-01-28
 * @copyright       (c) Remco van der Velde
 * @license         MIT
 * @version         1.0
 */
namespace Plugin;

use Module\Data as Module;

trait Data {

    public function data(null|Module $data=null): Module|null {
        if($data !== null) {
            $this->data = $data;
        }
        return $this->data;
    }
}