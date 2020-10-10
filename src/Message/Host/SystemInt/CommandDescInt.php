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
 *
 * @property-read string $command
 * @property-read string $desc
 * @property-read string $arguments
 * @property-read string $options
 */
class CommandDescInt extends IIntentMsg
{
    const DEFAULT_LEVEL = HostMsg::INFO;
    const INTENT_NAME = HostMsg\DefaultIntents::SYSTEM_COMMAND_DESC;

    public static function instance(
        string $command,
        string $desc,
        string $arguments,
        string $options
    ) : self
    {
        return new static(get_defined_vars());
    }

    public static function intentStub(): array
    {
        return [
            'command' => '',
            'desc' => '',
            'arguments' => '',
            'options' => '',
        ];
    }

    public function getText(): string
    {
        $command = $this->command;
        $desc = $this->desc;
        $args = $this->arguments;
        $opts = $this->options;
        return "$command: $desc\n$args\n$opts";
    }
}