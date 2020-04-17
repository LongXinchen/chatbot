<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Ghost\Prototype\Operators\Staging;

use Commune\Ghost\Blueprint\Convo\Conversation;
use Commune\Ghost\Blueprint\Definition\StageDef;
use Commune\Ghost\Blueprint\Operator\Operator;
use Commune\Ghost\Prototype\Operators\Current\FulfillCurrent;
use Commune\Ghost\Prototype\Operators\Events\ToActivateStage;

/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class NextStages implements Operator
{

    /**
     * @var StageDef
     */
    protected $stageDef;

    /**
     * @var string[]
     */
    protected $next;

    /**
     * @var bool
     */
    protected $flush;

    /**
     * NextStages constructor.
     * @param StageDef $stageDef
     * @param array $next
     * @param bool $flush
     */
    public function __construct(StageDef $stageDef, array $next, bool $flush)
    {
        $this->stageDef = $stageDef;
        $this->next = $next;
        $this->flush = $flush;
    }


    public function invoke(Conversation $conversation): ? Operator
    {
        $node = $conversation->runtime->getCurrentProcess()->aliveThread()->currentNode();

        if ($this->flush) {
            $node->flushStack();
        }

        // 入栈新的路径.
        if (!empty($this->next)) {
            $node->pushStack($this->next);
        }

        // 下一个节点存在.
        if ($node->next()) {
            $stageDef = $node->findStageDef($conversation);
            return new ToActivateStage(
                $stageDef,
                $node
            );
        }

        return new FulfillCurrent();
    }


}