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
use Module\File;

trait Config {

    public function config(null|Data $config = null): Data|null {
        if($config !== null) {
            $this->config = $config;
        }
        return $this->config;
    }

    /**
     * @throws ObjectException
     */
    public function config_extension(Data $config): Data {
        $url = $config->get('directory.data') . 'Extension.json';
        $data = new Data(Core::object(File::read($url)));
        if(!$data->has('Extension')){
            return $config;
        }
        $extension_list = [];
        $content_type_list = [];
        foreach($data->get('Extension') as $extension => $content_type){
            $extension_list[$extension] = $content_type;
            $content_type_list[$content_type] = $extension;
        }
        $config->set('extensions', $extension_list);
        $config->set('content.types', $content_type_list);
        return $config;
    }

    /**
     * @throws Exception
     */
    public function config_update($server, $files, $cookie): void
    {
        $config = $this->config();
        $config->set('server', $server);
        $config->set('files', $files);
        $config->set('cookie', $cookie);
        $config->set('time.current', microtime(true));
        $config->set('time.duration', $config->get('time.current') - $config->get('time.start'));
        $this->config($config);
    }
}