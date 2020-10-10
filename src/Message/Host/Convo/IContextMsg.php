<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Message\Host\Convo;

use Commune\Blueprint\Ghost\Cloner;
use Commune\Blueprint\Ghost\Context;
use Commune\Blueprint\Ghost\Ucl;
use Commune\Protocols\HostMsg;
use Commune\Support\Message\AbsMessage;
use Commune\Protocols\HostMsg\Convo\ContextMsg;


/**
 * @author thirdgerb <thirdgerb@gmail.com>
 *
 * @property string $contextName        语境名称
 * @property string $contextId          语境Id
 * @property string $stageName
 * @property-read array $query
 * @property array $data                语境的数据.
 * @property int $mode
 */
class IContextMsg extends AbsMessage implements ContextMsg
{
    public static function stub(): array
    {
        return [
            'contextName' => '',
            'contextId' => '',
            'stageName' => '',
            'query' => [],
            'data' => [],
            'mode' => ContextMsg::MODE_REDIRECT,
        ];
    }

    public static function relations(): array
    {
        return [];
    }

    public function getProtocolId(): string
    {
        return $this->contextName;
    }

    public function toContext(Cloner $cloner): Context
    {
        $ucl = Ucl::make($this->contextName, $this->query);
        $context = $ucl->findContext($cloner);
        $context->merge($this->data);
        return $context;
    }

    public function getContextId(): string
    {
        return $this->contextId;
    }

    public function getContextName(): string
    {
        return $this->contextName;
    }

    public function getStageName(): string
    {
        return $this->stageName;
    }


    public function getQuery(): array
    {
        return $this->query;
    }

    public function getMemorableData(): array
    {
        return $this->data;
    }

    public function getLevel(): string
    {
        return HostMsg::INFO;
    }


    public function getText(): string
    {
        return $this->toJson();
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function withMode(int $mode): ContextMsg
    {
        $this->mode = $mode;
        return $this;
    }


}