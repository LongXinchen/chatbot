<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Message\Intercom;

use Commune\Message\Host\Convo\IText;
use Commune\Protocals\HostMsg;
use Commune\Protocals\IntercomMsg;
use Commune\Support\Message\AbsMessage;
use Commune\Support\Utils\StringUtils;
use Commune\Support\Uuid\HasIdGenerator;
use Commune\Support\Uuid\IdGeneratorHelper;

/**
 * @author thirdgerb <thirdgerb@gmail.com>
 *
 * @property string $batchId            batchId: 消息的批次ID
 * @property string $messageId          messageId: 为空则自动生成.
 * @property string $sessionId          sessionId: 会话Id, 为空则是 guestId
 * @property string $convoId            convoId: 多轮会话的 ID. 允许为空. 除非客户端有指定的 conversation.
 *
 * @property string $creatorId          creatorId: 用户的ID.
 * @property string $creatorName        creatorName: 用户的姓名.
 * @property HostMsg $message           message: 输入消息. 不可以为空.
 * @property string $scene              场景.
 * @property int $createdAt             createdAt: 创建时间.
 * @property int $deliverAt             deliverAt: 发送时间. 默认为0.
 * @property bool $fromBot
 *
 */
abstract class AIntercomMsg extends AbsMessage implements IntercomMsg, HasIdGenerator
{
    use IdGeneratorHelper;

    /**
     * @var string
     */
    protected $_normalizedText;

    /**
     * 由于定义的 HostMsg 是 interface, 所以任何时候都要保留该对象的结构.
     * @var bool
     */
    protected $transferNoEmptyRelations = false;


    public static function stub(): array
    {
        return [
            'messageId' => '',

            // 不可为空
            'sessionId' => '',

            // 不可为空
            'batchId' => '',

            // 会话 id
            'convoId' => '',

            // 创建者. 为空表示为是机器人.
            'creatorId' => '',

            // 创建者名称.
            'creatorName' => '',

            // 调用场景. 是和客户端形成一致的关键.
            'scene' => '',

            // 消息体
            'message' => new IText(),

            // 发布时间
            'deliverAt' => $now = intval(microtime(true) * 1000),

            // 创建时间
            'createdAt' => $now,

            // 是否来自机器人
            'fromBot' => false,
        ];
    }

    public static function relations(): array
    {
        return [
            'message' => HostMsg::class
        ];
    }

    public function __set_messageId(string $name, string $value) : void
    {
        $this->_data[$name] = empty($value)
            ? $this->createUuId()
            : $value;
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function isFromBot(): bool
    {
        return $this->fromBot;
    }



    /*------- clone -------*/

    /**
     * 将当前消息分裂出不同的版本.
     * 通常只需要指定不一样的值就可以了, 其它的值都是相同的.
     * 至少 Message 是不同的.
     *
     * @param HostMsg $message
     * @param string $sessionId
     * @param string|null $convoId
     * @param string|null $creatorId
     * @param string|null $creatorName
     * @param int|null $deliverAt
     * @param string|null $scene
     * @return IntercomMsg
     */
    public function divide(
        HostMsg $message,
        string $sessionId,
        string $convoId = null,
        string $creatorId = null,
        string $creatorName = null,
        int $deliverAt = null,
        string $scene = null
    ): IntercomMsg
    {
        $deliverAt = $deliverAt ?? intval(microtime(true) * 1000);
        $messageId = $this->createUuId();
        $vars = get_defined_vars();

        $data = $this->_data;
        foreach ($vars as $name => $val) {
            $data[$name] = $val ?? $data[$name] ?? null;
        }

        return new static($data);
    }


    /*------- properties -------*/

    public function getProtocalId(): string
    {
        return get_class($this->getMessage());
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function getConvoId(): string
    {
        return $this->convoId;
    }

    public function getCreatorId(): string
    {
        return $this->creatorId;
    }

    public function getCreatorName(): string
    {
        return $this->creatorName;
    }

    public function getMessage(): HostMsg
    {
        return $this->message;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getDeliverAt(): int
    {
        return $this->deliverAt;
    }

    public function getScene(): string
    {
        return $this->scene;
    }

    public function setMessage(HostMsg $message): void
    {
        $this->message = $message;
    }

    public function isMsgType(string $hostMessageType): bool
    {
        return is_a($this->message, $hostMessageType, TRUE);
    }

    public function getMsgText(): string
    {
        return $this->message->getText();
    }

    public function getNormalizedText(): string
    {
        return $this->_normalizedText
            ?? $this->_normalizedText = StringUtils::normalizeString($this->getMsgText());
    }

    public function setConvoId(string $convoId): void
    {
        $this->convoId = $convoId;
    }

}