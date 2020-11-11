<?php

/*
 * This file is part of emri99/gitlab-generic-api-client.
 *
 * (c) 2017 Cyril MERY <mery.cyril@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Emri99\Gitlab\Exception;

/**
 * Class NotFoundException
 *
 * @package Emri99\Gitlab
 * @author  Cyril MERY <cmery@coffreo.com>
 */
class NotFoundException extends GitlabApiClientException
{
    static function create($url, $message = null)
    {
        return new self('Page not found : '.$url. (!empty($message) ? " / $message" : '') , 404);
    }
}
