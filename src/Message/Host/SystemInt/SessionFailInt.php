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
use Commune\Support\Struct\Struct;
use Commune\Blueprint\Kernel\Protocols\AppResponse;


/**
 * @author thirdgerb <thirdgerb@gmail.com>
 *
 * @property-read int $errcode
 * @property-read string $errmsg
 */
class SessionFailInt extends IIntentMsg
{
    const DEFAULT_LEVEL = HostMsg::ERROR;
    const INTENT_NAME = HostMsg\DefaultIntents::SYSTEM_SESSION_FAIL;

    public static function instance(string $errmsg = null) : self
    {
        $slots = isset($errmsg)
            ? ['errmsg' => $errmsg]
            : [];

        return new static($slots);
    }

    public static function intentStub(): array
    {
        return [
            'errcode' => AppResponse::HOST_SESSION_FAIL,
            'errmsg' => AppResponse::DEFAULT_ERROR_MESSAGES[AppResponse::HOST_SESSION_FAIL],
        ];
    }

    public function getText(): string
    {
        return $this->intentName;
    }
}