<?php
/**
 * mobiCMS (https://mobicms.org/)
 * This file is part of mobiCMS Content Management System.
 *
 * @license     https://opensource.org/licenses/GPL-3.0 GPL-3.0 (see the LICENSE.md file)
 * @link        http://mobicms.org mobiCMS Project
 * @copyright   Copyright (C) mobiCMS Community
 */

namespace Mobicms\System;

use Mobicms\Http\Request;
use Mobicms\System\Exception\IpBanException;
use Psr\Container\ContainerInterface;

class IpBan
{
    public function __construct(ContainerInterface $container)
    {
        /** @var \PDO $db */
        $db = $container->get(\PDO::class);

        /** @var Request $request */
        $request = $container->get(Request::class);

        $proxy = !empty($request->ipViaProxy()) ? ip2long($request->ipViaProxy()) : false;
        $ip = ip2long($request->ip());

        $req = $db->query("SELECT `ban_type`, `link` FROM `cms_ban_ip`
          WHERE '" . $ip . "' BETWEEN `ip1` AND `ip2`
          " . ($proxy ? " OR '" . $proxy . "' BETWEEN `ip1` AND `ip2`" : '') . "
          LIMIT 1
        ");

        if ($req->rowCount()) {
            $res = $req->fetch();

            switch ($res['ban_type']) {
                case 2:
                    throw new IpBanException('Location: ' . (!empty($res['link']) ? $res['link'] : 'http://example.com'));
                    break;
                case 3:
                    //TODO: реализовать запрет регистрации
                    //self::$deny_registration = true;
                    break;
                default :
                    throw new IpBanException('HTTP/1.0 404 Not Found');
            }
        }
    }
}
