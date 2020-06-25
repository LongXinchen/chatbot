<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Blueprint;

use Commune\Blueprint\Exceptions\CommuneLogicException;


/**
 * 这里定义 Commune 项目的各种常量.
 * 允许通过全局来设置, 并且全局获取之.
 * 只能定义一次, 而且有默认值.
 *
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class CommuneEnv
{
    const DEBUG = 'COMMUNE_DEBUG';
    const RESET_REGISTRY = 'COMMUNE_RESET_REGISTRY';
    const RESOURCE_PATH = 'COMMUNE_RESOURCE_PATH';
    const LOG_PATH = 'COMMUNE_LOG_PATH';

    /*------- debug ------*/

    public static function isDebug() : bool
    {
        return self::get(self::DEBUG, false);
    }

    public static function defineDebug(bool $debug) : void
    {
        self::set(self::DEBUG, $debug);
    }

    /*------- log path ------*/


    public static function getLogPath() : string
    {
        return self::get(self::LOG_PATH, __DIR__ . '/../../demo/log');
    }

    public static function defineLogPath(string $path) : void
    {
        if (!is_dir($path)) {
            throw new CommuneLogicException("path $path is invalid dir");
        }
        self::set(self::LOG_PATH, $path);
    }

    /*------- path ------*/

    public static function getResourcePath() : string
    {
        return self::get(self::RESOURCE_PATH, __DIR__ . '/../../demo/resources');
    }

    public static function defineResourcePath(string $path) : void
    {
        if (!is_dir($path)) {
            throw new CommuneLogicException("path $path is invalid dir");
        }
        self::set(self::RESOURCE_PATH, $path);
    }

    /*------- mind ------*/

    public static function isResetRegistry() : bool
    {
        return self::get(self::RESET_REGISTRY, false);
    }

    public static function defineResetMind(bool $boolean) : void
    {
        self::set(self::RESET_REGISTRY, $boolean);
    }

    /*------- private ------*/

    private static function get(string $name, $default)
    {
        return defined($name)
            ? constant($name)
            : $default;
    }

    private static function set(string $name, $value) : void
    {
        if (defined($name)) {
            throw new CommuneLogicException("constant $name already defined!");
        }
        define($name, $value);
    }

}