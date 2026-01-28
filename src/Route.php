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
use Module\Destination;
use Module\Dir;
use Module\File;
use Module\Sort;


trait Route {

    const METHOD_CLI = 'CLI';
    const DELETE = 'DELETE';
    const GET = 'GET';
    const PATCH = 'PATCH';
    const POST = 'POST';
    const PUT = 'PUT';
    const HEAD = 'HEAD';
    const CONNECT = 'CONNECT';
    const OPTIONS = 'OPTIONS';
    const TRACE = 'TRACE';

    const METHODS = [
        self::DELETE,
        self::GET,
        self::PATCH,
        self::POST,
        self::PUT,
        self::HEAD,
        self::CONNECT,
        self::OPTIONS,
        self::TRACE
    ];

    /**
     * @throws Exception
     */
    public function route_configure(): void
    {
        $config = $this->config();
        //route_find $config->get('request.request')
        $config = $this->route_load($config);
        if (substr($config->get('request.request'), -1) != '/') {
            $config->set('request.request', $config->get('request.request') . '/');
        }
        $select = (object)[
            'input' => $config->get('request.request'),
        ];
        $test = $this->route_request_explode(urldecode($select->input));
        $test_count = count($test);
        if ($test_count > 1) {
            $select->attribute = explode('/', $test[0]);
            if (end($select->attribute) === '') {
                array_pop($select->attribute);
            }
            $array = [];
            for ($i = 1; $i < $test_count; $i++) {
                $array[] = $test[$i];
            }
            $select->attribute = array_merge($select->attribute, $array);
            $select->deep = count($select->attribute);
        } else {
            $string_count = $select->input;
            $select->deep = substr_count($string_count, '/');
            $select->attribute = explode('/', $select->input);
            if (end($select->attribute) === '') {
                array_pop($select->attribute);
            }
        }
        while (end($select->attribute) === '') {
            array_pop($select->attribute);
        }
        $select->method = $this->route_method();
        $this->config($this->route_select($config, $select));
    }

    /**
     * @throws Exception
     */
    public static function route_method(): string
    {
        if(array_key_exists('REQUEST_METHOD', $_SERVER)){
            if(
                in_array(
                    $_SERVER['REQUEST_METHOD'],
                    self::METHODS,
                    true
                )
            ){
                return $_SERVER['REQUEST_METHOD'];
            }
        }
        elseif(defined('IS_CLI')){
            return self::METHOD_CLI;
        }
        throw new Exception('Method undefined');
    }

    private static function route_request_explode($input=''): array
    {
        $split = mb_str_split($input);
        $is_quote_double = false;
        $collection = '';
        $explode = [];
        $previous_char = false;
        foreach($split as $nr => $char){
            if(
                $previous_char === '/' &&
                $char === '{' &&
                $is_quote_double === false
            ){
                if(!empty($collection)){
                    $value = substr($collection, 0,-1);
                    if(!empty($value)){
                        $explode[] = $value;
                    }
                }
                $collection = $char;
                continue;
            }
            elseif(
                $previous_char === '/' &&
                $char == '[' &&
                $is_quote_double === false
            ){
                if(!empty($collection)){
                    $value = substr($collection, 0,-1);
                    if(!empty($value)){
                        $explode[] = $value;
                    }
                }
                $collection = $char;
                continue;
            }
            elseif(
                $char === '"' &&
                $previous_char !== '\\'
            ){
                $is_quote_double = !$is_quote_double;
            }
            $collection .= $char;
            $previous_char = $char;
        }
        if(!empty($collection)){
            if($previous_char === '/'){
                $value = substr($collection, 0,-1);
                if(!empty($value)){
                    $explode[] = $value;
                }
            } else {
                $explode[] = $collection;
            }
        }
        return $explode;
    }

    /**
     * @throws ObjectException
     * @throws Exception
     */
    public function route_load(Data $config): Data
    {
        $list = $config->data('route.list');
        if(empty($list)){
            $dir_route_temp = $config->get('directory.temp') . 'Data' . DIRECTORY_SEPARATOR;
            Dir::create($dir_route_temp, Dir::CHMOD);
            $url_route_temp =  $dir_route_temp . 'Route.json';
            if(!File::exist($url_route_temp)){
                $url_route = $config->get('directory.data') . 'Route.json';
                if($url_route === $url_route_temp){
                    throw new Exception('Route.json not found');
                }
                File::copy($url_route, $url_route_temp);
            }
            $read = File::read($url_route_temp);
            $data = new Data(Core::object($read));
            $list = $data->get('Route');
        }
        if(is_array($list)){
            foreach($list as $nr => $item){
                $list[$nr] = $this->route_item_path($item);
                $list[$nr] = $this->route_item_deep($list[$nr]);
            }
        } elseif(is_object($list)){
            //already done
            return $config;
        }

        //add filter (no cli)
        $data = Sort::list($list)->with([
            'priority' => 'asc',
            'name' => 'asc'
        ], []);
        $data = $this->route_decorator($data);
        $config->data('route.list', $data);
        return $config;
    }

    public function route_decorator(array $response): object
    {
        $result = [];
        if(
            !empty($response) &&
            is_array($response)
        ){
            foreach($response as $nr => $record){
                if(
                    is_array($record) &&
                    array_key_exists('name', $record)
                ){
                    $name = str_replace('.', '-', strtolower($record['name']));
                    $result[$name] = $record;
                }
                elseif(
                    is_object($record) &&
                    property_exists($record, 'name')
                ){
                    $name = str_replace('.', '-', strtolower($record->name));
                    $result[strtolower($name)] = $record;
                }
                if(
                    is_array($record) &&
                    array_key_exists('uuid', $record)
                ){
                    unset($record['uuid']);
                }
                elseif(
                    is_object($record) &&
                    property_exists($record, 'uuid')
                ){
                    unset($record->uuid);
                }
            }
        }
        return (object) $result;
    }


    /**
     * @throws Exception
     */
    public function route_parse(Data $config, string $resource): string
    {
        $resource = str_replace([
            '{{',
            '}}'
        ], [
            '{',
            '}'
        ], $resource);
        $explode = explode('}', $resource, 2);
        if(!isset($explode[1])){
            return $resource;
        }
        $temp = explode('{', $explode[0], 2);
        if(isset($temp[1])){
            $attribute = substr($temp[1], 1);
            $value = $config->get($attribute);
            $resource = str_replace('{$' . $attribute . '}', $value, $resource);
            return $this->route_parse($config, $resource);
        } else {
            return $resource;
        }
    }

    public function route_item_path(object $item): object
    {
        if(!property_exists($item, 'path')){
            return $item;
        }
        if(substr($item->path, 0, 1) == '/'){
            $item->path = substr($item->path, 1);
        }
        if(substr($item->path, -1) !== '/'){
            $item->path .= '/';
        }
        return $item;
    }

    public function route_item_deep(object $item): object
    {
        if(!property_exists($item, 'path')){
            $item->deep = 0;
            return $item;
        }
        $item->deep = substr_count($item->path, '/');
        return $item;
    }

    /**
     * @throws Exception
     */
    private function route_select(Data $config, object $select): Data
    {
        $data =  $config->get('route.list');
        $match = false;
        if(empty($data)){
            return $config;
        }
        $current = false;
        foreach($data as $name => $record){
            if(!is_object($record)){
                continue;
            }
            if(property_exists($record, 'resource')){
                continue;
            }
            if(!property_exists($record, 'deep')){
                continue;
            }
            $match = $this->route_is_match($config, $record, $select);
            if($match === true){
                $current = $record;
                break;
            }
        }
        if($match === false){
            foreach($data as $nr => $record){
                if(property_exists($record, 'resource')){
                    continue;
                }
                if(!property_exists($record, 'deep')){
                    continue;
                }
                $match = $this->route_is_match_has_slash_in_attribute($config, $record, $select);
                if($match === true){
                    $current = $record;
                    break;
                }
            }
        }
        if($current !== false){
            $current = $this->route_prepare($config, $current, $select);
            if($current){
                $config->set('route.current', new Destination($current));
                foreach($config->get('route.current')->get('request')->data() as $key => $value){
                    $config->set('request.' . $key, $value);
                }
                return $config;
            }
        } else {
            $current = $this->route_wildcard($config);
            if($current){
                $config->set('route.current', new Destination($current));
                foreach($config->get('route.current')->get('request')->data() as $key => $value){
                    $config->set('request.' . $key, $value);
                }
                return $config;
            }
        }
        $config->set('route.current', false);
        return $config;
    }

    /**
     * @throws ObjectException
     * @throws Exception
     */
    public function route_wildcard(Data $config): bool | object
    {
        if(defined('IS_CLI')){

        } else {
            $route = $config->get('route.list');
            $request = $route->get('*');
            if($request !== null){
                if(property_exists($request, 'controller')){
                    $request = $this->route_controller($request);
                }
                return $request;
            }

        }
        return false;
    }

    private function route_is_match_by_method(Data $config, object $route, object $select): bool
    {
        if(!property_exists($route, 'method')){
            return true;
        }
        if(!is_array($route->method)){
            return false;
        }
        foreach($route->method as $method){
            if(strtoupper($method) == strtoupper($select->method)){
                return true;
            }
        }
        return false;
    }

    private function route_is_match_by_deep(Data $config, object $route, object $select): bool
    {
        if(!property_exists($route, 'deep')){
            return false;
        }
        if(!property_exists($select, 'deep')){
            return false;
        }
        if($route->deep != $select->deep){
            return false;
        }
        return true;
    }

    private function route_is_match_by_attribute(Data $config, object $route, object $select): bool
    {
        if(!property_exists($route, 'path')){
            return false;
        }
        $explode = explode('/', $route->path);
        array_pop($explode);
        $attribute = $select->attribute;
        if(empty($attribute) && $route->path === '/'){
            return true;
        }
        elseif(empty($attribute)){
            if(!empty($explode)){
                return false;
            }
            return true;
        }
        $path_attribute = [];
        foreach($explode as $nr => $part){
            if($this->route_is_variable($part)){
                $variable = $this->route_get_variable($part);
                if($variable){
                    $temp = explode(':', $variable, 2);
                    if(array_key_exists(1, $temp)){
                        $path_attribute[$nr] = $temp[0];
                    }
                }
                continue;
            }
            if(array_key_exists($nr, $attribute) === false){
                return false;
            }
            if(strtolower($part) != strtolower($attribute[$nr])){
                return false;
            }
        }
        if(!empty($path_attribute)){
            foreach($explode as $nr => $part){
                if($this->route_is_variable($part)){
                    $variable = $this->route_get_variable($part);
                    if($variable){
                        $temp = explode(':', $variable, 2);
                        if(count($temp) === 2){
                            $attribute = $temp[0];
                            $type = ucfirst($temp[1]);
                            $className = '\\Router\\Type' . $type;
                            $exist = class_exists($className);
                            if($exist){
                                $value = null;
                                foreach($path_attribute as $path_nr => $path_value){
                                    if(
                                        $path_value == $attribute &&
                                        array_key_exists($path_nr, $select->attribute)
                                    ){
                                        $value = $select->attribute[$path_nr];
                                        break;
                                    }
                                }
                                if($value){
                                    $validate = $className::validate($value);
                                    if(!$validate){
                                        return false;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    private function route_is_match_has_slash_in_attribute(Data $config, object $route, object $select): bool
    {
        $is_match = $this->route_is_match_by_method($config, $route, $select);
        if($is_match === false){
            return $is_match;
        }
        $is_match = $this->route_is_match_by_attribute($config, $route, $select);
        if($is_match === false){
            return $is_match;
        }
        $is_match = $this->route_is_match_by_condition($config, $route, $select);
        if($is_match === false){
            return $is_match;
        }
        return $is_match;
    }

    private function route_is_match(Data $config, object $route, object $select): bool
    {
        $is_match = $this->route_is_match_by_method($config, $route, $select);
        if ($is_match === false) {
            return $is_match;
        }
        $is_match = $this->route_is_match_by_deep($config, $route, $select);
        if ($is_match === false) {
            return $is_match;
        }
        $is_match = $this->route_is_match_by_attribute($config, $route, $select);
        if ($is_match === false) {
            return $is_match;
        }
        $is_match = $this->route_is_match_by_condition($config, $route, $select);
        if ($is_match === false) {
            return $is_match;
        }
        return $is_match;
    }

    private function route_is_variable($string): bool
    {
        $string = trim($string);
        $string = str_replace([
            '{{',
            '}}'
        ], [
            '{',
            '}'
        ], $string);
        if(
            substr($string, 0, 2) == '{$' &&
            substr($string, -1) == '}'
        ){
            return true;
        }
        return false;
    }

    private function route_get_variable($string): ?string
    {
        $string = trim($string);
        $string = str_replace([
            '{{',
            '}}'
        ], [
            '{',
            '}'
        ], $string);
        if(
            substr($string, 0, 2) == '{$' &&
            substr($string, -1) == '}'
        ){
            return substr($string, 2, -1);
        }
        return null;
    }

    private function route_is_match_by_condition(Data $config, object $route, object $select): bool
    {
        if(!property_exists($route, 'path')){
            return false;
        }
        $explode = explode('/', $route->path);
        array_pop($explode);
        $attribute = $select->attribute;
        if(empty($attribute)){
            return true;
        }
        foreach($explode as $nr => $part){
            if($this->route_is_variable($part)){
                if(
                    property_exists($route, 'condition') &&
                    is_array($route->condition)
                ){
                    foreach($route->condition as $condition_nr => $value){
                        if(substr($value, 0, 1) == '!'){
                            //invalid conditions
                            if(strtolower(substr($value, 1)) == strtolower($attribute[$nr])){
                                return false;
                            }
                        } else {
                            //valid conditions
                            if(strtolower($value) == strtolower($attribute[$nr])){
                                return true;
                            }
                        }
                    }
                }
                continue;
            }
            if(array_key_exists($nr, $attribute) === false){
                return false;
            }
            if(strtolower($part) != strtolower($attribute[$nr])){
                return false;
            }
        }
        return true;
    }

    /**
     * @throws Exception
     */
    private function route_prepare(Data $config, object $route, object $select): object
    {
        $route = clone $route;
        if(property_exists($route, 'request') && get_Class($route->request) !== 'Data'){
            $route->request = new Data(clone $route->request);
        }
        elseif(!property_exists($route, 'request')){
            $route->request = new Data();
        }
        $route->path = str_replace([
            '{{',
            '}}'
        ], [
            '{',
            '}'
        ], $route->path);
        $explode = explode('/', $route->path);
        array_pop($explode);
        $attribute = $select->attribute;
        $nr = 0;
        foreach($explode as $nr => $part){
            if($this->route_is_variable($part)){
                $get_attribute = $this->route_get_variable($part);
                $temp = explode(':', $get_attribute, 2);
                if(array_key_exists(1, $temp)){
                    $variable = $temp[0];
                    if(property_exists($route->request, $variable)){
                        continue;
                    }
                    if(array_key_exists($nr, $attribute)){
                        $type = ucfirst($temp[1]);
                        $className = '\\Router\\Type' . $type;
                        $exist = class_exists($className);
                        if(
                            $exist &&
                            in_array('cast', get_class_methods($className), true)
                        ){
                            $value = $className::cast(urldecode($attribute[$nr]));
                        } else {
                            $value = urldecode($attribute[$nr]);
                        }
                        $route->request->data($variable, $value);
                    }
                } else {
                    $variable = $temp[0];
                    if(property_exists($route->request, $variable)){
                        continue;
                    }
                    if(array_key_exists($nr, $attribute)){
                        $value = urldecode($attribute[$nr]);
                        $route->request->data($variable, $value);
                    }
                }
            }
        }
        if(
            !empty($variable) &&
            count($attribute) > count($explode)
        ){
            $request = '';
            for($i = $nr; $i < count($attribute); $i++){
                $request .= $attribute[$i] . '/';
            }
            $request = substr($request, 0, -1);
            $request = urldecode($request);
            $route->request->data($variable, $request);
        }

        foreach($config->data('request') as $key => $record){
            if($key == 'request'){
                continue;
            }
            $route->request->data($key, $record);
        }
        if(property_exists($route, 'controller')){
            $route = $this->route_controller($route);
        }
        return $route;
    }

    public static function route_controller(object $route): object
    {
        if(property_exists($route, 'controller')){
            $is_double_colon = str_contains($route->controller, ':');
            if($is_double_colon){
                $controller = explode(':', $route->controller);
                if(array_key_exists(1, $controller)) {
                    $function = array_pop($controller);
                    $route->controller = str_replace('.', '_', implode('\\', $controller));
                    $function = str_replace('.', '_', $function);
                    $route->function = $function;
                }
            } else {
                $controller = explode('.', $route->controller);
                if(array_key_exists(1, $controller)) {
                    $function = array_pop($controller);
                    $route->controller = implode('\\', $controller);
                    $route->function = $function;
                }
            }
        }
        return $route;
    }

}