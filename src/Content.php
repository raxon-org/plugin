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
use Module\Data;

trait Content {

    /**
     * @throws Exception
     */
    public function content_type(Data $config): string
    {
        $content_type = $config->get('content.type');
        if(empty($content_type)) {
            $content_type = 'text/html';
            if (array_key_exists('CONTENT_TYPE', $_SERVER) && !empty($_SERVER['CONTENT_TYPE'])) {
                $content_type = $_SERVER['CONTENT_TYPE'];
            }
            $config->set('content.type', $content_type);
        }
        return $content_type;

    }
}