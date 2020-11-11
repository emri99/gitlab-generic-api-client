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

use Emri99\Gitlab\GitlabApiClient as SUT;
use Unirest\Response;

/**
 * @covers \Emri99\Gitlab\GitlabApiClient
 */
class GitlabApiClientTest extends \PHPUnit_Framework_TestCase
{
    const BASE_PATH = 'http://example.com';

    public function testConstants()
    {
        $this->assertEquals('HTTP', SUT::AUTH_HTTP_TOKEN);
        $this->assertEquals('OAUTH', SUT::AUTH_OAUTH_TOKEN);
    }

    public function testBuildComplexPath()
    {
        $sut = $this->getMockedSUT();
        $sut
            ->expects($this->once())
            ->method('performRequest')
                ->with(
                    'GET',
                    self::BASE_PATH.'/path/1/to/data%20one/for/test?param2=value2',
                    null,
                    $this->isType('array'),
                    $this->isType('array')
                )
            ->willReturn(new Response(200, '{}', '', array()));

        $sut->path(1)->to('data one')->for('test')->get(array(
            'param2' => 'value2',
        ));
    }

    public function testAuthenticateMissingMode()
    {
        $sut = $this->getMockedSUT();
        $sut->expects($this->once())
            ->method('performRequest')
            ->with(
                'PUT',
                self::BASE_PATH.'/path',
                $this->anything(),
                $this->isEmpty(),
                $this->anything()
            )
            ->willReturn(new Response(200, '{}', '', array()));

        $sut
            ->authenticate('wont-be-passed', 'inexistent-mode')
            ->path()
            ->put();
    }

    public function testAuthenticateHttpToken()
    {
        $token = 'my-precious';
        $sudo = 'special sudo string';
        $self = $this; // for closure

        $sut = $this->getMockedSUT(self::BASE_PATH.'/');
        $sut->expects($this->once())
            ->method('performRequest')
            ->with(
                'PUT',
                self::BASE_PATH.'/path/to/put',
                $this->anything(),
                $this->callback(function ($datas) use ($self) {
                    $self->assertEquals(array('PRIVATE-TOKEN' => 'my-precious',
                    ), $datas);

                    return true;
                }),
                $this->anything()
            )
            ->willReturn(new Response(200, '{}', '', array()));

        $sut
            ->authenticate($token, SUT::AUTH_HTTP_TOKEN)
            ->path('to', 'put')
            ->put();

        $sut = $this->getMockedSUT();
        $sut->expects($this->once())
            ->method('performRequest')
            ->with(
                'POST',
                self::BASE_PATH.'/path/2/to/post',
                $this->anything(),
                $this->callback(function ($datas) use ($self) {
                    $self->assertEquals(array(
                        'PRIVATE-TOKEN' => 'my-precious',
                        'SUDO' => 'special sudo string',
                    ), $datas);

                    return true;
                }),
                $this->anything()
            )
            ->willReturn(new Response(200, '{}', '', array()));

        $sut
            ->authenticate($token, SUT::AUTH_HTTP_TOKEN, $sudo)
            ->path(2)
            ->to('post')
            ->post();
    }

    public function testAuthenticateOauthToken()
    {
        $token = 'my-precious';
        $sudo = 'special sudo string';
        $self = $this;

        $sut = $this->getMockedSUT();
        $sut->expects($this->once())
            ->method('performRequest')
            ->with(
                'PUT',
                self::BASE_PATH.'/path/to/put',
                $this->anything(),
                $this->callback(function ($datas) use ($self) {
                    $self->assertEquals(array('Authorization' => 'Bearer my-precious',
                    ), $datas);

                    return true;
                }),
                $this->anything()
            )
            ->willReturn(new Response(200, '{}', '', array()));

        $sut
            ->authenticate($token, SUT::AUTH_OAUTH_TOKEN)
            ->path('to', 'put')
            ->put();

        $sut = $this->getMockedSUT();
        $sut->expects($this->once())
            ->method('performRequest')
            ->with(
                'DELETE',
                self::BASE_PATH.'/path/2/to/delete',
                $this->anything(),
                $this->callback(function ($datas) use ($self) {
                    $self->assertEquals(array(
                        'Authorization' => 'Bearer my-precious',
                        'SUDO' => 'special sudo string',
                    ), $datas);

                    return true;
                }),
                $this->anything()
            )
            ->willReturn(new Response(200, '{}', '', array()));

        $sut
            ->authenticate($token, SUT::AUTH_OAUTH_TOKEN, $sudo)
            ->path(2)
            ->to('delete')
            ->delete();
    }

    /**
     * @expectedExceptionMessage Page not found : http://example.com/path
     * @expectedException \Emri99\Gitlab\Exception\NotFoundException
     */
    public function testDisplayUrlOn404()
    {
        $sut = $this->getMockedSUT();
        $sut
            ->expects($this->once())
            ->method('performRequest')
            ->with(
                'GET',
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            )
            ->willReturn(new Response(404, '{}', '', array()));

        $sut->path()->get();
    }

    /**
     * @expectedExceptionMessage No response
     * @expectedException \Emri99\Gitlab\Exception\GitlabApiClientException
     */
    public function testNoResponse()
    {
        $sut = $this->getMockedSUT();
        $sut
            ->expects($this->once())
            ->method('performRequest')
            ->with(
                'GET',
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            )
            ->willReturn(null);

        $sut->path()->get();
    }

    /**
     * @expectedExceptionMessage Unable to decode json response
     * @expectedException \Emri99\Gitlab\Exception\GitlabApiClientException
     */
    public function testBadResponse()
    {
        $sut = $this->getMockedSUT();
        $sut
            ->expects($this->once())
            ->method('performRequest')
            ->with(
                'GET',
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            )
            ->willReturn(new Response(200, '{malformed json}', 'Content-Type: application/json', array()));

        $sut->path()->get();
    }

    public function testOptions()
    {
        $client = new SUT(self::BASE_PATH, array(
            'new_option' => 'value',
            'timeout' => 666,
        ));
        $this->assertEquals('value', $client->getOption('new_option'));
        $this->assertEquals(666, $client->getOption('timeout'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidOptions()
    {
        $client = new SUT(self::BASE_PATH);
        $client->getOption('missing-option');
    }

    public function testHeaders()
    {
        $self = $this;

        $sut = $this->getMockedSUT();
        $sut->expects($this->exactly(2))
            ->method('performRequest')
            ->withConsecutive(
                // first call have all headers
                // * globally defined ones
                // * current request ones
                array(
                    'GET',
                    $this->anything(),
                    $this->anything(),
                    $this->callback(function ($datas) use ($self) {
                        $self->assertEquals(array(
                            'HEADER1' => 'VALUE1',
                            'HEADER2' => 'VALUE2',
                            'HEADER3' => 'VALUE3',
                            'HEADER4' => 'VALUE4',
                        ), $datas);

                        return true;
                    }),
                    $this->anything(),
                ),
                // next call must have only global ones
                array(
                    'GET',
                    $this->anything(),
                    $this->anything(),
                    $this->callback(function ($datas) use ($self) {
                        $self->assertEquals(array(
                            'HEADER1' => 'VALUE1',
                            'HEADER2' => 'VALUE2',
                        ), $datas);

                        return true;
                    }),
                    $this->anything(),
                )
            )
            ->willReturn(new Response(200, '{}', '', array()));

        $sut
            ->setHeaders(array(
                'HEADER1' => 'VALUE1',
                'HEADER2' => 'VALUE2',
            ))
            ->path(2)
            ->get(array(), array(
                'HEADER3' => 'VALUE3',
                'HEADER4' => 'VALUE4',
            ));
        $sut->path(2)->get();

        // custom headers must not replace authentication header with same name
        $sut = $this->getMockedSUT();
        $sut->expects($this->once())
            ->method('performRequest')
            ->with(
                'GET',
                $this->anything(),
                $this->anything(),
                $this->callback(function ($datas) use ($self) {
                    $self->assertEquals(array(
                        'HEADER2' => 'VALUE2',
                        'HEADER3' => 'VALUE3',
                        'HEADER4' => 'VALUE4',
                        'Authorization' => 'Bearer my-precious-token',
                        'SUDO' => 'im sudoers',
                    ), $datas);

                    return true;
                }),
                $this->anything()
            )
            ->willReturn(new Response(200, '{}', '', array()));

        $sut
            ->authenticate('my-precious-token', SUT::AUTH_OAUTH_TOKEN, 'im sudoers')
            ->setHeaders(array(
                'Authorization' => 'VALUE1',
                'HEADER2' => 'VALUE2',
            ))
            ->path(2)
            ->get(array(), array(
                'HEADER3' => 'VALUE3',
                'HEADER4' => 'VALUE4',
            ));
    }

    /**
     * @expectedExceptionMessage scalar expected
     * @expectedException \InvalidArgumentException
     */
    public function testIncorrectPath()
    {
        $this->getMockedSUT()->path(array('array' => 'not accepted here'));
    }

    protected function getMockedSUT($baseUrl = self::BASE_PATH, $methods = array('performRequest'))
    {
        return $this
            ->getMockBuilder('Emri99\\Gitlab\\GitlabApiClient')
            ->setConstructorArgs(array($baseUrl))
            ->enableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}
