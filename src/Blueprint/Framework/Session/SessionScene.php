<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Blueprint\Framework\Session;

use Commune\Blueprint\Ghost\Ucl;


/**
 * 当前请求的场景信息.
 *
 * @author thirdgerb <thirdgerb@gmail.com>
 *
 * @property-read Ucl $entry                入口路径, 同时也作为根路径
 * @property-read array $env                环境变量
 *
 *
 * # 其它环境变量.
 *
 * @property-read string|null $lang         对话所使用的语言.
 *
 * @property-read int $userLevel            用户等级信息
 * @property-read array $userInfo           用户更多信息. 视客户端决定是否存在.
 */
interface SessionScene
{
    // 预定义的场景变量名. 表示这些参数可以通过 Env 数组传递给 Ghost

    // 语言类型.
    const ENV_LANG = 'lang';
    // 用户级别.
    const ENV_USER_LEVEL = 'userLevel';
    // 用户的附加属性.
    const ENV_USER_INFO = 'userInfo';
}