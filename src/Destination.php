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
use Module\Destination as Destiny;
use Module\File;

trait Destination {

    /**
     * @throws Exception
     */
    public function destination($type='page'): false|Destiny
    {
        $config = $this->config();
        switch($type){
            case 'file-request':
            case 'file_request': {
                $request = $this->request('request');
                $extension = File::extension($request);
                if($extension === ''){
                    return false;
                }
                $current = (object) [
                    'name' => '...',
                    'path' => $request,
                    'priority' => 0,
                    'controller' => 'Microstorm\\Controller\\FileRequest',
                    'method' => [
                        'GET'
                    ]
                ];
                return new Destiny($current);
            }
            break;
            default: {
                if($config){
                    if(empty($config->get('route.current'))){
                        return false;
                    }
                    return $this->config()->get('route.current');
                }
            }
        }
        return false;
    }
}