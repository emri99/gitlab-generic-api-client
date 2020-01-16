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
use Unirest\Response;

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
            if (null === $param) {
                continue;
            }

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
        $response = $this->request(Method::GET, $this->flushPath($parameters), $headers);

        return $this->returnOrThrow($response);
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
        $body = null;
        if (!empty($parameters) && empty($files)) {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            $body = Request\Body::Form($parameters);
        } elseif (!empty($files)) {
            $headers['Content-Type'] = 'multipart/form-data';
            $body = Request\Body::Multipart($parameters, $files);
        }

        $response = $this->request(Method::POST, $this->flushPath(), $headers, $body);

        return $this->returnOrThrow($response);
    }

    /**
     * @param array $parameters
     * @param array $headers
     * @param array $files
     *
     * @return mixed
     */
    public function put(array $parameters = array(), $headers = array(), array $files = array())
    {
        $body = null;
        if (!empty($parameters) && empty($files)) {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            $body = Request\Body::Form($parameters);
        } elseif (!empty($files)) {
            $headers['Content-Type'] = 'multipart/form-data';
            $body = Request\Body::Multipart($parameters, $files);
        }

        $response = $this->request(Method::PUT, $this->flushPath(), $headers, $body);

        return $this->returnOrThrow($response);
    }

    /**
     * @param array $parameters
     * @param array $headers
     *
     * @return mixed
     */
    public function delete(array $parameters = array(), $headers = array())
    {
        $response = $this->request('DELETE', $this->flushPath($parameters), $headers);

        return $this->returnOrThrow($response);
    }

    /**
     * @param Response $response
     *
     * @throws \RuntimeException
     *
     * @return array|\stdClass depending on option 'json_decode_to_array'
     */
    protected function returnOrThrow($response)
    {
        if (!$response) {
            throw new \RuntimeException('No response');
        }

        if (null === $response->raw_body) {
            return null;
        }

        if (is_string($response->body) && $response->headers["Content-Type"] === "application/json") {
            throw new \RuntimeException('Unable to decode json response');
        }

        $error = !empty($response->body->error) ? $response->body->error : null;
        if ($error && !is_string($error)) {
            $error = trim(json_encode($error), '"');
        }

        if ($response->code >= 400) {
            $error = $error ?: '';
            if (404 === $response->code && $this->lastpath) {
                $error .= $error.' (url : '.$this->lastpath.')';
                $this->lastpath = null;
            }
        }

        if ($error !== null) {
            throw new \RuntimeException(sprintf('%s - %s', $response->code, $error), $response->code);
        }

        if (!$this->options['json_decode_to_array']) {
            return $response->body;
        }

        return json_decode(json_encode($response->body), true);
    }

    protected function request($httpMethod = 'GET', $path, array $headers = array(), $body = null)
    {
        $httpMethod = strtoupper($httpMethod);
        $path = trim($this->baseUrl.$path, '/');

        $httpOptions = array(
            'verifyPeer' => array($this->options['verify_peer']),
            'verifyHost' => array($this->options['verify_host']),
            'timeout' => array($this->options['timeout']),
            'jsonOpts' => array(false, 512, $this->options['json_decode_options']),
        );

        $allHeaders = array_merge($this->headers, $this->authenticationHeaders, $headers);

        return $this->performRequest(
            $httpMethod,
            $path,
            $body,
            $allHeaders,
            $httpOptions
        );
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
     * @throws \Unirest\Exception
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
     * @param null|mixed $parameters
     *
     * @return string
     */
    protected function flushPath($parameters = null)
    {
        $path = $this->path;

        if ($parameters) {
            $separator = false === strpos($path, '?') ? '?' : '&';
            $path .= $separator.http_build_query($parameters);
        }

        // remember full url to display path in 404 message
        $this->lastpath = $this->baseUrl.$path;
        $this->path = '';

        return $path;
    }
}
