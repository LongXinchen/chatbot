<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Protocols\HostMsg\Convo;

use Commune\Protocols\HostMsg\ConvoMsg;


/**
 * 客户端或服务端不支持的消息.
 *
 * @author thirdgerb <thirdgerb@gmail.com>
 */
interface UnsupportedMsg extends ConvoMsg
{
    public function getMsgType() : string;
}