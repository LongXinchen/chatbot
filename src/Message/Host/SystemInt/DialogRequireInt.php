<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Message\Host\SystemInt;

use Commune\Message\Host\IIntentMsg;
use Commune\Protocols\HostMsg;

/**
 * @author thirdgerb <thirdgerb@gmail.com>
 * 
 * @property-read string $attrName
 */
class DialogRequireInt extends IIntentMsg
{
    const DEFAULT_LEVEL = HostMsg::INFO;
    const INTENT_NAME = HostMsg\DefaultIntents::SYSTEM_DIALOG_REQUIRE;


    public static function instance(string $attrName = '') : self
    {
        return new static(get_defined_vars());
    }

    public static function intentStub(): array
    {
        return [
            'attrName' => '',
        ];
    }

}