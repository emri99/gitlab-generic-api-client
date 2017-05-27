<?php

/*
 * This file is part of emri99/gitlab-generic-api-client.
 *
 * (c) 2017 Cyril MERY <mery.cyril@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Emri99\Gitlab;

use Unirest\Method;
use Unirest\Request;

class GitlabApiClient
{
    /**
     * Authenticate mode : HTTP Header
     * Use an personal/impersonate access token.
     */
    const AUTH_HTTP_TOKEN = 'HTTP';

    /**
     * Authenticate mode : OAUTH token
     * Use the key supplied by Gitlab's OAuth provider.
     */
    const AUTH_OAUTH_TOKEN = 'OAUTH';

    /**
     * Dynamic part of url.
     *
     * @var string
     */
    protected $path;

    /**
     * Default headers (supplied).
     *
     * @var array
     */
    protected $headers = array();

    /**
     * Authentication headers (computed internally).
     *
     * @var array
     */
    protected $authenticationHeaders = array();

    /**
     * @var null|array
     */
    protected $auth = null;

    /**
     * @var array
     */
    private $options = array();

    private $baseUrl;

    public function __construct($baseUrl, array $options = array())
    {
        if ('/' === substr($baseUrl, -1, 1)) {
            $baseUrl = substr($baseUrl, 0, -1);
        }
        $this->baseUrl = $baseUrl;
        $this->options = array_merge(array(
            'user_agent' => 'php-gitlab-generic-api-client',
            'timeout' => 60,
            'json_decode_to_array' => false,
            'json_decode_options' => JSON_NUMERIC_CHECK & JSON_FORCE_OBJECT & JSON_UNESCAPED_SLASHES & JSON_UNESCAPED_UNICODE,
            'verify_peer' => true,
            'verify_host' => true,
        ), $options);
    }

    /**
     * Add url segment to endpoint url.
     *
     * @param string $method
     * @param array  $args
     *
     * @return $this
     */
    public function __call($method, array $args)
    {
        $this->path .= '/'.$method;

        if (0 === count($args)) {
            return $this;
        }

        foreach ($args as $param) {
            if (!is_scalar($param)) {
                throw new \InvalidArgumentException(sprintf('Method "%s" : Invalid argument type. scalar expected.', $method));
            }
            $this->__call($this->encodePath($param), array());
        }

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public function getOption($name)
    {
        if (!array_key_exists($name, $this->options)) {
            throw new \InvalidArgumentException(sprintf('Undefined option called: "%s"', $name));
        }

        return $this->options[$name];
    }

    /**
     * Authenticate a user for all next requests.
     *
     * @param string $token      Gitlab private token
     * @param string $authMethod One of the AUTH_* class constants
     * @param string $sudo
     *
     * @return $this
     */
    public function authenticate($token, $authMethod = self::AUTH_HTTP_TOKEN, $sudo = null)
    {
        $this->authenticationHeaders = array();

        if (!in_array($authMethod, array(self::AUTH_HTTP_TOKEN, self::AUTH_OAUTH_TOKEN))) {
            return $this;
        }

        if ($authMethod === self::AUTH_HTTP_TOKEN) {
            $this->authenticationHeaders['PRIVATE-TOKEN'] = $token;
        }

        if ($authMethod === self::AUTH_OAUTH_TOKEN) {
            $this->authenticationHeaders['Authorization'] = sprintf('Bearer %s', $token);
        }

        if (!is_null($sudo)) {
            $this->authenticationHeaders['SUDO'] = $sudo;
        }

        return $this;
    }

    /**
     * @param array $parameters
     * @param array $headers
     *
     * @return mixed
     */
    public function get(array $parameters = array(), $headers = array())
    {
        $response = $this->request(Method::GET, $this->flushPath(), $parameters, $headers);

        return $response->body;
    }

    /**
     * @param array $parameters
     * @param array $headers
     * @param array $files
     *
     * @return mixed
     */
    public function post(array $parameters = array(), $headers = array(), array $files = array())
    {
        $response = $this->request(Method::POST, $this->flushPath(), $parameters, $headers, $files);

        return $response->body;
    }

    /**
     * @param array $parameters
     * @param array $headers
     *
     * @return mixed
     */
    public function patch(array $parameters = array(), $headers = array())
    {
        $response = $this->request(Method::PATCH, $this->flushPath(), $parameters, $headers);

        return $response->body;
    }

    /**
     * @param array $parameters
     * @param array $headers
     *
     * @return mixed
     */
    public function put(array $parameters = array(), $headers = array())
    {
        $response = $this->request(Method::PUT, $this->flushPath(), $parameters, $headers);

        return $response->body;
    }

    /**
     * @param array $parameters
     * @param array $headers
     *
     * @return mixed
     */
    public function delete(array $parameters = array(), $headers = array())
    {
        $response = $this->request('DELETE', $this->flushPath(), $parameters, $headers);

        return $response->body;
    }

    protected function request($httpMethod = 'GET', $path, array $parameters = array(), array $headers = array(), array $files = array())
    {
        $httpMethod = strtoupper($httpMethod);
        $path = trim($this->baseUrl.$path, '/');
        $body = empty($files) ? $parameters : Request\Body::Multipart($parameters, $files);

        $httpOptions = array(
            'verifyPeer' => array($this->options['verify_peer']),
            'verifyHost' => array($this->options['verify_host']),
            'timeout' => array($this->options['timeout']),
            'jsonOpts' => array($this->options['json_decode_to_array'], 512, $this->options['json_decode_options']),
        );

        $allHeaders = array_merge($this->headers, $this->authenticationHeaders, $headers);

        $response = $this->performRequest(
            $httpMethod,
            $path,
            $body,
            $allHeaders,
            $httpOptions
        );

        if ($response->code === 404) {
            $message = @$response->body->message ?: '';
            $message .= sprintf(' (url : %s)', $path);
            if (is_array($response->body)) {
                $response->body['message'] = $message;
            } elseif (is_object($response->body)) {
                $response->body->message = $message;
            } else {
                $response->body = json_encode(array('message' => $message), JSON_UNESCAPED_SLASHES);
            }
        }

        return $response;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $httpMethod
     * @param string $path
     * @param string $body
     * @param array  $allHeaders
     * @param array  $httpOptions
     *
     * @return \Unirest\Response
     */
    protected function performRequest($httpMethod, $path, $body, $allHeaders, $httpOptions)
    {
        foreach ($httpOptions as $method => $args) {
            call_user_func_array(array('Unirest\Request', $method), $args);
        }

        return Request::send($httpMethod, $path, $body, $allHeaders);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function encodePath($path)
    {
        $path = rawurlencode($path);

        return str_replace('.', '%2E', $path);
    }

    /**
     * Retrieve current url fragment & flush it from next requests.
     *
     * @return string
     */
    protected function flushPath()
    {
        $path = $this->path;
        $this->path = '';

        return $path;
    }
}
