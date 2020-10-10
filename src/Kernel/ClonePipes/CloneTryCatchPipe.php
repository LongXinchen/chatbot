<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Kernel\ClonePipes;

use Closure;
use Commune\Blueprint\CommuneEnv;
use Commune\Blueprint\Exceptions\CommuneRuntimeException;
use Commune\Blueprint\Kernel\Protocols\GhostRequest;
use Commune\Blueprint\Kernel\Protocols\GhostResponse;
use Commune\Contracts\Log\ExceptionReporter;
use Commune\Message\Host\SystemInt\RequestFailInt;
use Commune\Message\Host\SystemInt\SessionFailInt;
use Commune\Message\Host\SystemInt\SessionQuitInt;
use Commune\Blueprint\Exceptions\Runtime\BrokenRequestException;
use Commune\Blueprint\Exceptions\Runtime\BrokenSessionException;

/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class CloneTryCatchPipe extends AClonePipe
{
    const FAIL_TIME_KEY = '_requestFailTimes';

    /**
     * @var string[]
     */
    protected $unsupportedMessages = [];

    protected function doHandle(GhostRequest $request, Closure $next) : GhostResponse
    {
        $message = $request->getInput()->getMessage();

        if (CommuneEnv::isDebug()) {
            $text = $message->getText();
            $type = get_class($message);
            $this->cloner->logger->debug("receive message $type: \"$text\"");
        }

        try {

            $response = $next($request);
            $this->resetFailureCount();

            return $response;

        } catch (BrokenSessionException $e) {
            $this->report($e);
            $this->cloner->noState();
            return $this->quitSession($request, $e);

        } catch (BrokenRequestException $e) {
            $this->report($e);
            $this->cloner->noState();
            return $this->requestFail($request, $e);
        }
    }

    protected function report(\Throwable $e) : void
    {
        /**
         * @var ExceptionReporter $expHandler
         */
        $expHandler = $this->cloner->container->get(ExceptionReporter::class);
        $expHandler->report($e);
    }

    protected function requestFail(
        GhostRequest $request,
        CommuneRuntimeException $e
    ) : GhostResponse
    {

        $storage = $this->cloner->storage;
        $times = $storage[self::FAIL_TIME_KEY] ?? 0;
        $times ++;
        if ($times >= $this->cloner->config->maxRequestFailTimes) {
            return $this->quitSession($request, $e);
        }

        $storage[self::FAIL_TIME_KEY] = $times;

        return $request->output(
            $this->cloner->avatar->getId(),
            $this->cloner->avatar->getName(),
            RequestFailInt::instance($e->getMessage())
        );
    }

    protected function quitSession(
        GhostRequest $request,
        CommuneRuntimeException $e
    ) : GhostResponse
    {
        $messages = [
            SessionFailInt::instance($e->getMessage()),
            SessionQuitInt::instance(),
        ];

        $this->cloner->endConversation();
        $this->resetFailureCount();

        return $request->output(
            $this->cloner->avatar->getId(),
            $this->cloner->avatar->getName(),
            ...$messages
        );
    }

    protected function resetFailureCount() : void
    {
        $storage = $this->cloner->storage;
        $storage[self::FAIL_TIME_KEY] = 0;
    }
}