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

trait Request {

    /**
     * @throws Exception
     */
    public function request($attribute=null, $value=null): mixed
    {
        $prefix = __FUNCTION__;
        $config = $this->config();
        if($attribute!== null && $value !== null){
            $config->set($prefix . '.' . $attribute, $value);
        }
        elseif($attribute !== null){
            return $config->get($prefix . '.' . $attribute);
        } else {
            return $config->get($prefix);
        }
        return null;
    }

    /**
     * @throws Exception
     */
    public function query($attribute=null, $value=null): mixed
    {
        $prefix = __FUNCTION__;
        $config = $this->config();
        if($attribute!== null && $value !== null){
            $config->set($prefix . '.' . $attribute, $value);
        }
        elseif($attribute !== null){
            return $config->get($prefix . '.' . $attribute);
        } else {
            return $config->get($prefix);
        }
        return null;
    }

    /**
     * @throws Exception
     */
    public function request_configure(): void
    {
        $this->config($this->request_query_init($this->config()));
    }


    /**
     * @throws Exception
     */
    private function request_query_init(Data $config): Data
    {
        $request = $this->request_input();
        foreach($request->data() as $attribute => $value){
            $config->set($attribute, $value);
        }
        return $config;
    }

    /**
     * @throws ObjectException
     */
    private static function request_key_group(array|object $data): object
    {
        $result = (object) [];
        foreach($data as $key => $value){
            $explode = explode('.', $key, 4);
            if(!isset($explode[1])){
                $result->{$key} = $value;
                continue;
            }
            $temp = Core::object_horizontal($explode, $value);
            $result = Core::object_merge($result, $temp);
        }
        return $result;
    }

    /**
     * @throws ObjectException
     * @throws Exception
     */
    private function request_input(): Data
    {
        $data = new Data();
        $query = [];
        if(defined('IS_CLI')){
            global $argc, $argv;
            $temp = $argv;
            array_shift($temp);
            $request = $temp;
            $request = Core::array_object($request);
            $data->set('request', '/');
            foreach($request as $key => $value){
                $key = str_replace(['-', '_'], ['.', '.'], $key);
                $data->set($key, trim($value));
                if($key === "0"){
                    $data->set('request', trim($value));
                    $data->set('path', trim($value));
                }
            }
        } else {
            $request = $this->request_key_group($_REQUEST);
            if(!property_exists($request, 'request')){
                $uri = ltrim($_SERVER['REQUEST_URI'], '/');
                $uri = explode('?', $uri, 2);
                $request->request = $uri[0];
                $query_string = $uri[1] ?? '';
                $query = $this->request_query_string($query_string);
                if(empty($request->request)){
                    $request->request = '/';
                }
            } else {
                $uri = ltrim($_SERVER['REQUEST_URI'], '/');
                $uri = explode('?', $uri, 2);
                $request->request = $uri[0];
                $query_string = $uri[1] ?? '';
                $query = $this->request_query_string($query_string);
                if(empty($request->request)){
                    $request->request = '/';
                }
            }
            foreach($request as $attribute => $value){
                if(is_numeric($value)){
                    $value = $value + 0;
                } else {
                    switch($value){
                        case 'true':
                            $value = true;
                            break;
                        case 'false':
                            $value = false;
                            break;
                        case 'null':
                            $value = null;
                            break;
                    }
                }
                $data->set($attribute, $value);
            }
            foreach($query as $attribute => $value){
                $data->set($attribute, $value);
            }
            /* --backend-disabled
            $input =
                htmlspecialchars(
                    htmlspecialchars_decode(
                        implode(
                            '',
                            file('php://input')
                        ),
                        ENT_NOQUOTES
                    ),
                    ENT_NOQUOTES,
                    'UTF-8'
                );
            */
            $input = implode('', file('php://input'));
            if(!empty($input)){
                $input = json_decode($input);
            }
            if(!empty($input)){
                if(is_object($input) || is_array($input)){
                    foreach($input as $key => $record){
                        if(
                            is_object($record) &&
                            property_exists($record, 'name') &&
                            property_exists($record, 'value') &&
                            $record->name != 'request'
                        ){
                            if($record->value !== null){
                                if(is_numeric($record->value)){
                                    $record->value = $record->value + 0;
                                } else {
                                    switch($record->value){
                                        case 'true':
                                            $record->value = true;
                                            break;
                                        case 'false':
                                            $record->value = false;
                                            break;
                                        case 'null':
                                            $record->value = null;
                                            break;
                                    }
                                }
                                //$record->name = str_replace(['-', '_'], ['.', '.'], $record->name);
                                $data->set($record->name, $record->value);
                            }
                        } else {
                            if($record !== null){
                                if(is_numeric($record)){
                                    $record = $record + 0;
                                } else {
                                    switch($record){
                                        case 'true':
                                            $record = true;
                                            break;
                                        case 'false':
                                            $record = false;
                                            break;
                                        case 'null':
                                            $record = null;
                                            break;
                                    }
                                }
                                //$key = str_replace(['-', '_'],  ['.', '.'], $key);
                                $data->set($key, $record);
                            }
                        }
                    }
                }
            }
        }
        $response = new Data();
        $response->set('query', $query);
        $response->set('request', $data->data());
        return $response;
    }


    private function request_query_result(mixed $result=null): mixed
    {
        if(is_array($result)){
            foreach($result as $key => $value){
                $value = $this->request_query_result($value);
                $key_original =  $key;
                if(
                    in_array(
                        substr($key, 0, 1),
                        [
                            '\'',
                            '"'
                        ],
                        true
                    )
                ){
                    $key = substr($key, 1);
                }
                if(
                    in_array(
                        substr($key, -1, 1),
                        [
                            '\'',
                            '"'
                        ],
                        true
                    )
                ){
                    $key = substr($key, 0, -1);
                }
                unset($result[$key_original]);
                $result[$key] = $value;
            }
        }
        elseif(is_string($result)){
            switch($result){
                case 'null':
                    $result = null;
                    break;
                case 'true':
                    $result = true;
                    break;
                case 'false':
                    $result = false;
                    break;
                default:
                    if(is_numeric($result)){
                        $result += 0;
                    }
            }
        }
        return $result;

    }

    /**
     * @throws ObjectException
     */
    private function request_query_string($query=''): object
    {
        parse_str($query, $result);
        $result = $this->request_query_result($result);
        return (object) $result;
    }

}