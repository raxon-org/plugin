<?php
/**
 * @author          Remco van der Velde
 * @since           2026-01-28
 * @copyright       (c) Remco van der Velde
 * @license         MIT
 * @version         1.0
 */
namespace Plugin;

use Module\File;
use Module\Dir;

trait Cron {

    /**
     * @throws DirectoryCreateException
     * @throws FileWriteException
     * @throws ObjectException
     * @throws Exception
     */
    public function cron_backup($flags, $options): void
    {
        $config = $this->config();
        $environment = $config->get('environment');
        $url = '/etc/cron.d/raxon';
        if(File::exists($url)){
            $target = $config->get('directory.data') .
                'Cron/' . 'Cron' . '.' . $environment;
            File::write($target, File::read($url));
        } else {
            //create cron file for each environment.
            $environments = [
                'development',
                'test',
                'staging',
                'replica',
                'production'
            ];

            $dir = $config->get('directory.data') . 'Cron' . DIRECTORY_SEPARATOR;
            $source = $config->get('directory.data') . 'Cron' . DIRECTORY_SEPARATOR . 'Cron.' . $environment;
            foreach($environments as $record){
                $url = $dir . 'Cron' . '.' . $record;
                if(!File::exists($url)){
                    Dir::create($dir, Dir::CHMOD);
                    File::write($url, File::read($source));
                }
            }
        }
    }

    /**
     * @throws FileWriteException
     * @throws ObjectException
     * @throws Exception
     */
    public function cron_restore($flags=null, $options=null): void
    {
        $config = $this->config();
        $url = '/etc/cron.d/raxon';
        $environment = $config->get('environment');
        $source = $config->get('directory.data') .
            'Cron' .
            DIRECTORY_SEPARATOR .
            'Cron' .
            '.' .
            $environment
        ;

        if(File::exists($source)){
            File::write($url, File::read($source));
            $this->cron_init();
        } else {
            echo 'Please create a cron file for your environment: ' . $source . PHP_EOL;
        }
    }

    /**
     * @throws ObjectException
     * @throws Exception
     */
    public function cron_restart($flags, $options): void
    {
        $command = 'service cron restart';
        exec($command, $output, $code);
        if($output){
            echo implode(PHP_EOL, $output);
        }
    }

    /**
     * @throws ObjectException
     * @throws Exception
     */
    public function cron_start($flags, $options): void
    {
        $command = 'service cron start';
        exec($command, $output, $code);
        if($output){
            echo implode(PHP_EOL, $output);
        }
    }

    /**
     * @throws ObjectException
     * @throws Exception
     */
    public function cron_stop($flags, $options): void
    {
        $command = 'service cron stop';
        exec($command, $output, $code);
        if($output){
            echo implode(PHP_EOL, $output);
        }
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public function cron_init(): void
    {
        $url = '/etc/crontab';
        $read = File::read($url);

        if($read){
            $read = explode(PHP_EOL, $read);
            $has_cron_d = false;
            foreach($read as $nr => $line){
                if(
                    strpos($line, 'run-parts') !== false &&
                    strpos($line, '/etc/cron.d') !== false &&
                    strpos($line, '/etc/cron.daily') === false
                ){
                    $has_cron_d = true;
                    break;
                }
            }
            $url_cron_d = '/etc/cron.d/raxon';
            if(!File::exists($url_cron_d)){
                $this->cron_restore();
            }
            if($has_cron_d === false) {
                $read[] = '*/1 *   * * *   root    cd / && run-parts --report /etc/cron.d';
                $read = implode(PHP_EOL, $read);
                File::write($url, $read);
                $command = 'service cron restart';
                exec($command, $output, $code);
                if($output){
                    echo implode(PHP_EOL, $output);
                }
            }
        }
    }
}