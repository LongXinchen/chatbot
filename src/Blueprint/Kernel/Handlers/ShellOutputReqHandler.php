<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Blueprint\Kernel\Handlers;

use Commune\Blueprint\Kernel\Protocols\ShellOutputRequest;
use Commune\Blueprint\Kernel\Protocols\ShellOutputResponse;

/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
interface ShellOutputReqHandler
{

    public function __invoke(ShellOutputRequest $request) : ShellOutputResponse;

}