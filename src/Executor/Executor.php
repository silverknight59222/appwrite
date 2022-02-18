<?php

namespace Executor;

use Exception;
use Utopia\App;
use Utopia\CLI\Console;

class Executor 
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_HEAD = 'HEAD';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_CONNECT = 'CONNECT';
    const METHOD_TRACE = 'TRACE';

    private $endpoint;

    private $selfSigned = false;

    protected $headers = [
        'content-type' => '',
    ];

    public function __construct(string $endpoint = 'http://appwrite-executor/v1')
    { 
        $this->endpoint = $endpoint;
    }

    public function createRuntime(
        string $functionId, 
        string $deploymentId, 
        string $projectId, 
        string $source,
        string $runtime, 
        string $baseImage,
        bool $remove = false,
        string $entrypoint = '',
        string $workdir = '',
        string $destination = '',
        string $network = '',
        array $vars = [],
        array $commands = []
    ) {
        $route = "/runtimes";
        $headers = [
            'content-type' => 'application/json',
            'x-appwrite-executor-key' => App::getEnv('_APP_EXECUTOR_SECRET', '')
        ];
        $params = [
            'runtimeId' => "$projectId-$deploymentId",
            'source' => $source,
            'destination' => $destination,
            'runtime' => $runtime,
            'baseImage' => $baseImage,
            'entrypoint' => $entrypoint,
            'workdir' => $workdir,
            'network' => empty($network) ? App::getEnv('_APP_EXECUTOR_RUNTIME_NETWORK', 'openruntimes') : $network,
            'vars' => $vars,
            'remove' => $remove,
            'commands' => $commands
        ];

        $response = $this->call(self::METHOD_POST, $route, $headers, $params, true, 30);

        $status = $response['headers']['status-code'];
        if ($status >= 400) {
            throw new \Exception($response['body']['message'], $status);
        } 

        return $response['body'];
    }

    public function deleteRuntime(string $projectId, string $functionId, string $deploymentId)
    {
        $runtimeId = "$projectId-$deploymentId";
        $route = "/runtimes/$runtimeId";
        $headers = [
            'content-type' =>  'application/json',
            'x-appwrite-executor-key' => App::getEnv('_APP_EXECUTOR_SECRET', '')
        ];

        $params = [];

        $response = $this->call(self::METHOD_DELETE, $route, $headers, $params, true, 30);
        
        $status = $response['headers']['status-code'];
        if ($status >= 400) {
            throw new \Exception($response['body']['message'], $status);
        }

        return $response['body'];
    }

    public function createExecution(
        string $projectId,
        string $functionId,
        string $deploymentId,
        string $path,
        array $vars,
        string $entrypoint,
        string $data,
        string $runtime,
        string $baseImage,
        $timeout
    ) {

        $route = "/execution";
        $headers = [
            'content-type' =>  'application/json',
            'x-appwrite-executor-key' => App::getEnv('_APP_EXECUTOR_SECRET', '')
        ];
        $params = [
            'runtimeId' => "$projectId-$deploymentId",
            'path' => $path,
            'vars' => $vars, 
            'data' => $data,
            'runtime' => $runtime,
            'entrypoint' => $entrypoint,
            'timeout' => $timeout,
            'baseImage' => $baseImage,
        ];

        $response = $this->call(self::METHOD_POST, $route, $headers, $params, true, 30);
        $status = $response['headers']['status-code'];

        if ($status >= 400) {
            for ($attempts = 0; $attempts < 10; $attempts++) {
                switch ($status) {
                    case 404:
                        $response = $this->createRuntime(
                            functionId: $functionId,
                            deploymentId: $deploymentId,
                            projectId: $projectId,
                            source: $path,
                            runtime: $runtime,
                            baseImage: $baseImage,
                            vars: $vars,
                            entrypoint: $entrypoint,
                            commands: []
                        );
                        $response = $this->call(self::METHOD_POST, $route, $headers, $params, true, 30);
                        $status = $response['headers']['status-code'];
                        break;
                    case 406:
                        $response = $this->call(self::METHOD_POST, $route, $headers, $params, true, 30);
                        $status = $response['headers']['status-code'];
                        break;
                    default:
                        throw new \Exception($response['body']['message'], $status);
                }

                if ($status < 400) {
                    return $response['body'];
                }
                
                if ($status != 406) {
                    throw new \Exception($response['body']['message'], $status);
                }

                sleep(1);
            }
            throw new Exception($response['body']['message'], 503);
        }

        return $response['body'];
    }

    /**
     * Call
     *
     * Make an API call
     *
     * @param string $method
     * @param string $path
     * @param array $params
     * @param array $headers
     * @param bool $decode
     * @return array|string
     * @throws Exception
     */
    public function call(string $method, string $path = '', array $headers = [], array $params = [], bool $decode = true, int $timeout = 15)
    {
        $headers            = array_merge($this->headers, $headers);
        $ch                 = curl_init($this->endpoint . $path . (($method == self::METHOD_GET && !empty($params)) ? '?' . http_build_query($params) : ''));
        $responseHeaders    = [];
        $responseStatus     = -1;
        $responseType       = '';
        $responseBody       = '';

        switch ($headers['content-type']) {
            case 'application/json':
                $query = json_encode($params);
                break;

            case 'multipart/form-data':
                $query = $this->flatten($params);
                break;

            default:
                $query = http_build_query($params);
                break;
        }

        foreach ($headers as $i => $header) {
            $headers[] = $i . ':' . $header;
            unset($headers[$i]);
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); 
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$responseHeaders) {
            $len = strlen($header);
            $header = explode(':', $header, 2);

            if (count($header) < 2) { // ignore invalid headers
                return $len;
            }

            $responseHeaders[strtolower(trim($header[0]))] = trim($header[1]);

            return $len;
        });

        if ($method != self::METHOD_GET) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        }

        // Allow self signed certificates
        if ($this->selfSigned) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $responseBody   = curl_exec($ch);
        $responseType   = $responseHeaders['content-type'] ?? '';
        $responseStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($decode) {
            switch (substr($responseType, 0, strpos($responseType, ';'))) {
                case 'application/json':
                    $json = json_decode($responseBody, true);
    
                    if ($json === null) {
                        throw new Exception('Failed to parse response: '.$responseBody);
                    }
    
                    $responseBody = $json;
                    $json = null;
                break;
            }
        }

        if ((curl_errno($ch)/* || 200 != $responseStatus*/)) {
            throw new Exception(curl_error($ch) . ' with status code ' . $responseStatus, $responseStatus);
        }

        curl_close($ch);

        $responseHeaders['status-code'] = $responseStatus;

        return [
            'headers' => $responseHeaders,
            'body' => $responseBody
        ];
    }

    /**
     * Parse Cookie String
     *
     * @param string $cookie
     * @return array
     */
    public function parseCookie(string $cookie): array
    {
        $cookies = [];

        parse_str(strtr($cookie, array('&' => '%26', '+' => '%2B', ';' => '&')), $cookies);

        return $cookies;
    }

    /**
     * Flatten params array to PHP multiple format
     *
     * @param array $data
     * @param string $prefix
     * @return array
     */
    protected function flatten(array $data, string $prefix = ''): array
    {
        $output = [];

        foreach ($data as $key => $value) {
            $finalKey = $prefix ? "{$prefix}[{$key}]" : $key;

            if (is_array($value)) {
                $output += $this->flatten($value, $finalKey); // @todo: handle name collision here if needed
            } else {
                $output[$finalKey] = $value;
            }
        }

        return $output;
    }
}
