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

trait Config {

    public function config(null|Data $config = null): Data|null {
        if($config !== null) {
            $this->config = $config;
        }
        return $this->config;
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