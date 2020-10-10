<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Protocols\Intercom;

use Commune\Protocols\HostMsg;
use Commune\Protocols\IntercomMsg;

/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
interface OutputMsg extends IntercomMsg
{

    /**
     * @param HostMsg[] $messages
     * @return static[]
     */
    public function derive(HostMsg ...$messages) : array;


}