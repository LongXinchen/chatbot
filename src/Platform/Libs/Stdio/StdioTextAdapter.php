<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Platform\Libs\Stdio;

use Commune\Blueprint\Framework\Auth\Supervise;
use Commune\Blueprint\Ghost\Cloner\ClonerScene;
use Commune\Blueprint\Kernel\Protocals\AppRequest;
use Commune\Blueprint\Kernel\Protocals\AppResponse;
use Commune\Blueprint\Kernel\Protocals\ShellInputRequest;
use Commune\Blueprint\Kernel\Protocals\ShellOutputResponse;
use Commune\Blueprint\Platform\Adapter;
use Commune\Framework\Log\IConsoleLogger;
use Commune\Kernel\Protocals\IShellInputRequest;
use Commune\Message\Host\Convo\IEventMsg;
use Commune\Message\Host\Convo\IText;
use Commune\Message\Intercom\IInputMsg;
use Commune\Protocals\HostMsg\Convo\ContextMsg;
use Commune\Protocals\HostMsg\DefaultEvents;
use Commune\Protocals\HostMsg\DefaultIntents;
use Commune\Protocals\Intercom\InputMsg;
use Commune\Support\Utils\TypeUtils;


/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class StdioTextAdapter implements Adapter
{

    /**
     * @var StdioPacker
     */
    protected $packer;

    /**
     * @var string
     */
    protected $appId;

    /**
     * @var ShellInputRequest
     */
    protected $request;


    /**
     * StdioTextAdapter constructor.
     * @param StdioPacker $packer
     * @param string $appId
     */
    public function __construct(StdioPacker $packer, string $appId)
    {
        $this->packer = $packer;
        $this->appId = $appId;
    }

    public function isInvalidRequest(): ? string
    {
        return null;
    }

    /**
     * @return ShellInputRequest
     */
    public function getRequest(): AppRequest
    {
        if (isset($this->request)) {
            return $this->request;
        }

        $line = trim($this->packer->line);
        if ($line === '') {
            $message = IEventMsg::instance(DefaultEvents::EVENT_CLIENT_ACKNOWLEDGE);

        } elseif ($line === '#connect') {
            $message = IEventMsg::instance(DefaultEvents::EVENT_CLIENT_CONNECTION);
        } else {
            $message = IText::instance($line);
        }

        $inputMsg = IInputMsg::instance(
            $message,
            $this->packer->sessionId,
            $this->packer->creatorId,
            $this->packer->creatorName
        );

        $request = IShellInputRequest::instance(
            false,
            $inputMsg,
            '',
            [
                ClonerScene::ENV_USER_LEVEL => Supervise::SUPERVISOR
            ]
        );

        return $this->request = $request;
    }

    public function sendResponse(AppResponse $response): void
    {
        if (!$response instanceof ShellOutputResponse) {
            $type = TypeUtils::getType($response);
            $this->packer
                ->platform
                ->getLogger()
                ->error(
                    static::class . '::'. __FUNCTION__
                    . " only accept ShellOutputResponse, $type given"
                );
            return;
        }

        if (!$response->isForward()) {
            $code = $response->getErrcode();
            $error = $response->getErrmsg();
            $this->packer->fail("errcode: $code, errmsg: $error");
            return;
        }

        $this->renderOutputs($response);
        $this->handleIntents($response);

    }

    protected function renderOutputs(ShellOutputResponse $response) : void
    {
        $outputs = $response->getOutputs();
        $outputStr = '';

        foreach ($outputs as $output) {
            $message = $output->getMessage();

            if ($output instanceof InputMsg && $message instanceof ContextMsg) {
                continue;
            }

            $text = $message->getText();
            $level = $message->getLevel();

            $outputStr .= IConsoleLogger::wrapMessage($level, $text);
            $outputStr .= "\n\n";
        }

        $this->packer->stdio->write($outputStr);
    }

    protected function handleIntents(ShellOutputResponse $response) : void
    {
        $intents = $response->getIntents();

        foreach ($intents as $message) {
            if ($message->getProtocalId() === DefaultIntents::SYSTEM_SESSION_QUIT) {
                $this->packer->quit = true;
            }
        }
    }

    public function destroy(): void
    {
        unset(
            $this->packer,
            $this->request
        );
    }


}