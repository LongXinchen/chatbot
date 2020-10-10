<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Platform\Shell\Tcp;

use Swoole;
use Swoole\Coroutine;
use Commune\Blueprint\Configs\PlatformConfig;
use Commune\Blueprint\Host;
use Psr\Log\LoggerInterface;
use Commune\Blueprint\Shell;
use Commune\Blueprint\Platform;
use Commune\Platform\AbsPlatform;
use Commune\Blueprint\Kernel\Protocols\AppRequest;
use Commune\Blueprint\Kernel\Handlers\ShellInputReqHandler;
use Commune\Blueprint\Kernel\Protocols\ShellOutputResponse;
use Commune\Platform\Libs\SwlCo\TcpPlatformOption;
use Commune\Platform\Libs\SwlCo\TcpPlatformServeTrait;
use Commune\Platform\Libs\SwlCo\ProcPoolFactory;


/**
 * Swoole Coroutine Sync Shell
 *
 * 基于 Swoole Coroutine 实现的, Tcp 同步 Shell 客户端.
 * 同步是指和 Ghost 端的通讯, 只能接受同步的请求, 无法去响应广播.
 *
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class SwlCoShellPlatform extends AbsPlatform
{
    use TcpPlatformServeTrait;

    /**
     * @var SwlCoShellOption
     */
    protected $option;

    /**
     * @var Shell
     */
    protected $shell;

    /**
     * @var ProcPoolFactory
     */
    protected $poolFactory;

    public function __construct(
        Host $host,
        PlatformConfig $config,
        Shell $shell,
        SwlCoShellOption $option,
        LoggerInterface $logger
    )
    {
        $this->shell = $shell;
        $this->option = $option;
        $this->poolFactory = new ProcPoolFactory($option->poolOption);

        parent::__construct($host, $config, $logger);
    }

    public function getAppId(): string
    {
        return $this->shell->getId();
    }


    public function shutdown(): void
    {
        $this->poolFactory->shutdown();
    }

    public function sleep(float $seconds): void
    {
        Coroutine::sleep($seconds);
    }

    public function serve(): void
    {
        Swoole\Runtime::enableCoroutine();

        $pool = $this->poolFactory->getPool();

        $pool->on('workerStart', function ($pool, $id) {

            $server = $this->poolFactory->createServer();

            //接收到新的连接请求 并自动创建一个协程
            $server->handle([$this, 'receive']);

            //开始监听端口
            $server->start();
        });

        $pool->start();
    }

    public function getAdapterOption(): TcpPlatformOption
    {
        return $this->option->adapterOption;
    }


    protected function handleRequest(Platform\Adapter $adapter, AppRequest $request, string $interface = null): void
    {
        $interface = $interface ?? ShellInputReqHandler::class;
        /**
         * @var ShellOutputResponse
         */
        $response = $this->shell->handleRequest(
            $request,
            $interface
        );

        // 发送响应.
        $adapter->sendResponse($response);
    }
}