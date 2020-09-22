<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Shell\Bootstrap;

use Commune\Blueprint\Framework\App;
use Commune\Blueprint\Shell;
use Commune\Framework\Bootstrap\LoadConfigOption;
use Commune\Support\Utils\TypeUtils;


/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class ShellLoadConfigOption extends LoadConfigOption
{
    /**
     * @param Shell $app
     * @return array
     */
    protected function getConfigOptions(App $app): array
    {
        TypeUtils::validateInstance($app, Shell::class, static::class . '::'. __FUNCTION__);
        return $app->getConfig()->options;
    }


}