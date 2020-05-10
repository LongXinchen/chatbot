<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Protocals\Intercom;

/**
 * Ghost 的输出消息.
 * @author thirdgerb <thirdgerb@gmail.com>
 */
interface GhostOutput extends GhostMsg
{
    public function getSessionId() : string;
}