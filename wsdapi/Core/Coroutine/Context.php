<?php
/**
 * author
 * Date: 2019/8/23 0023
 * Time: 下午 1:59
 */

namespace Core\Coroutine;

class Context
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Response
     */
    private $response;
    /**
     * @var array 一个array，容器
     */
    private $map = [];
    public function __construct(\swoole_http_request $request, \swoole_http_response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
    /**
     * @param $key
     * @param $val
     */
    public function set($key, $val)
    {
        $this->map[$key] = $val;
    }
    /**
     * @param $key
     * @return mixed|null
     * @DEMO
            [fd] => 1
            [streamId] => 0
            [header] => Array
            (
            [content-type] => multipart/form-data; boundary=--------------------------734576435617339789787436
            [user-agent] => PostmanRuntime/7.15.2
            [accept] =>*    /    *
            [cache-control] => no-cache
            [postman-token] => 9f714304-dc8c-49f1-ba09-86dcdcfdbeaa
            [host] => 10.254.1.76:9502
            [accept-encoding] => gzip, deflate
            [content-length] => 536
            [connection] => keep-alive
            )

            [server] => Array
            (
            [query_string] => a=1
            [request_method] => POST
            [request_uri] => /
            [path_info] => /
            [request_time] => 1566615774
            [request_time_float] => 1566615774.4368
            [server_protocol] => HTTP/1.1
            [server_port] => 9502
            [remote_port] => 50366
            [remote_addr] => 10.254.1.188
            [master_time] => 1566615773
            )

            [request] =>
            [cookie] =>
            [get] => Array
            (
            [a] => 1
            )

            [files] =>
            [post] => Array
            (
            [platformno] => CQ_0002
            [parameter] => U9ZMCtuKdqI7iUcLfdneHQlHbNtB+hebsg2QyNC9Sc8Jwf9AWrB0gHrv8ibGz3nFgIYzll0e4npU6KRNjaBH68mzj6Xo7GhvwMaGoSKWIYobBzTnJ6dsG1eKpM9Tdr5HQ/DuOxUfUrr+RUs4sPYHhlzpxUW34AHSwGyHwLUKFZmx8MaW6Z1KkaSUDEcEszXtfgxhU+9aN+iXxP6BExiugk38s8DPSQm5JNeVTIvosMy11xZZcZJPyV8KyzzK6Ju2
            )

            [tmpfiles] =>
            )
     *
     */
    public function get($key)
    {
        if (isset($this->map[$key])) {
            return $this->map[$key];
        }
        return null;
    }
}