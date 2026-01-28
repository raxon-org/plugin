<?php
/**
 * @author          Remco van der Velde
 * @since           2026-01-28
 * @copyright       (c) Remco van der Velde
 * @license         MIT
 * @version         1.0
 */
namespace Plugin;

trait Autoload {

    public function autoload(null|object $autoload = null): object | null {
        if($autoload !== null) {
            $this->autoload = $autoload;
        }
        return $this->autoload;
    }
}