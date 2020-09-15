<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Ghost\ITools;

use Commune\Blueprint\Framework\Command\CommandDef;
use Commune\Blueprint\Framework\Command\CommandMsg;
use Commune\Blueprint\Ghost\Callables\Prediction;
use Commune\Blueprint\Ghost\Cloner;
use Commune\Blueprint\Ghost\MindDef\EmotionDef;
use Commune\Blueprint\Ghost\MindReg\StageReg;
use Commune\Blueprint\Ghost\Tools\Matcher;
use Commune\Framework\Command\ICommandDef;
use Commune\Framework\Spy\SpyAgency;
use Commune\Message\Host\IIntentMsg;
use Commune\Protocals\Comprehension;
use Commune\Protocals\HostMsg\Convo\EventMsg;
use Commune\Protocals\HostMsg\Convo\VerbalMsg;
use Commune\Protocals\HostMsg\IntentMsg;
use Commune\Protocals\Intercom\InputMsg;
use Commune\Support\Protocal\Protocal;
use Commune\Support\SoundLike\SoundLikeInterface;
use Commune\Support\Utils\ArrayUtils;
use Commune\Ghost\Support\ContextUtils;
use Commune\Support\Utils\StringUtils;

/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class IMatcher implements Matcher
{
    /**
     * @var InputMsg
     */
    protected $input;

    /**
     * @var Cloner
     */
    protected $cloner;

    /**
     * @var Comprehension
     */
    protected $comprehension;

    /**
     * @var array
     */
    protected $injectionContext;

    /**
     * @var array
     */
    protected $matchedParams = [];

    /**
     * @var bool
     */
    protected $matched = false;

    /**
     * IMatcher constructor.
     * @param Cloner $cloner
     * @param array $injectionContext
     */
    public function __construct(Cloner $cloner, array $injectionContext)
    {
        $this->cloner = $cloner;
        $this->input = $cloner->input;
        $this->comprehension = $cloner->comprehension;
        $this->injectionContext = $injectionContext;
        SpyAgency::incr(static::class);
    }


    /**
     * @param $caller
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    protected function call($caller)
    {
        $injection = $this->matchedParams + $this->injectionContext;
        return $this->cloner->container->call($caller, $injection);
    }

    public function getMatchedParams(): array
    {
        return $this->matchedParams;
    }

    public function truly(): bool
    {
        return $this->matched;
    }

    /**
     * @return static
     */
    public function refresh(): Matcher
    {
        $this->matched = false;
        $this->matchedParams = [];
        return $this;
    }

    public function isEvent(string $eventName): Matcher
    {
        $message = $this->input->getMessage();
        if (
            $message instanceof EventMsg
            && $message->getEventName() === $eventName
        ) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $eventName;
        }

        return $this;
    }

    public function isEventIn(array $eventNames): Matcher
    {
        $message = $this->input->getMessage();
        if (!$message instanceof EventMsg) {
            return $this;
        }
        $actual = $message->getEventName();

        foreach ($eventNames as $eventName) {
            if ($eventName == $actual) {
                $this->matched = true;
                $this->matchedParams[__FUNCTION__] = $eventName;
                return $this;
            }
        }

        return $this;
    }

    /**
     * @param callable|Prediction|string $prediction
     * @return Matcher
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function expect($prediction): Matcher
    {
        $bool = $this->call($prediction);
        if ($bool) {
            $this->matched = true;
        }

        return $this;
    }

    public function is(string $text): Matcher
    {
        $expect = StringUtils::normalizeString($text);
        $actual = $this->input->getNormalizedText();
        if ($expect === $actual) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $text;
        }

        return $this;
    }

    public function isEmpty() : Matcher
    {
        if ($this->input->getMessage()->isEmpty()) {
            $this->matched = true;
        }

        return $this;
    }

    public function pregMatch(string $pattern): Matcher
    {
        $text = $this->input->getMsgText();
        $matches = [];
        $matched = preg_match($pattern, $text, $matches);
        if ($matched) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $matches;
        }

        return $this;
    }

    public function isVerbal() : Matcher
    {
        $message = $this->input->getMessage();
        if ($message instanceof VerbalMsg) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $message;
        }

        return $this;
    }

    public function isInstanceOf(string $messageClazz): Matcher
    {
        $message = $this->input->getMessage();
        if (is_a($message, $messageClazz, TRUE)) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $message;
        }

        return $this;
    }

    public function isProtocal(string $protocalName): Matcher
    {
        $message = $this->input->getMessage();
        if (
            $message instanceof Protocal
            && is_a($message, $protocalName, TRUE)
        ) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $message;
        }

        return $this;
    }

    public function soundLike(
        string $text,
        string $lang = SoundLikeInterface::ZH
    ): Matcher
    {
        return $this->soundLikePart(
            $text,
            $lang,
            SoundLikeInterface::COMPARE_EXACTLY
        );
    }

    public function soundLikePart(
        string $text,
        string $lang = SoundLikeInterface::ZH,
        int $type = SoundLikeInterface::COMPARE_ANY_PART
    ): Matcher
    {
        $message = $this->input->getMessage();
        /**
         * @var SoundLikeInterface $soundLike
         */
        $soundLike = $this
            ->cloner
            ->container
            ->make(SoundLikeInterface::class);

        if (!$message instanceof VerbalMsg) {
            $this->matched = true;
        }

        $matched = $soundLike->soundLike(
            $message->getText(),
            $text,
            $lang,
            $type
        );

        if ($matched) {
            $this->matched = true;
        }

        return $this;
    }

    public function isAnswered(): Matcher
    {
        $answer = $this->comprehension->answer->getAnswer();
        if (isset($answer)) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $answer;
        }
        return $this;
    }

    public function isAnswerOf(string $answerInterface): Matcher
    {
        $answer = $this->comprehension->answer->getAnswer();
        if (isset($answer) && is_a($answer, $answerInterface, true)) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $answer;
        }
        return $this;
    }


    public function isChoice($suggestionIndex): Matcher
    {
        $answer = $this->comprehension->answer->getAnswer();
        if (isset($answer) && $answer->getChoice() === $suggestionIndex) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $answer;
        }

        return $this;
    }

    public function isAnswer(string $answerText): Matcher
    {
        $answerText = StringUtils::normalizeString($answerText);
        $answer = $this->comprehension->answer->getAnswer();
        if (isset($answer) && $answer->getAnswer() == $answerText) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $answer;
        }

        return $this;
    }


    public function isCommand(string $signature, bool $correct = false) : Matcher
    {
        $cmd = $this
            ->comprehension
            ->command;

        $cmdStr = $cmd->getCmdStr();
        if (!isset($cmdStr)) {
            return $this;
        }

        $cmdName = $cmd->getCmdName();
        $def = ICommandDef::makeBySignature($signature);

        $matched = $this->doMatchCommandDef($cmdName, $cmdStr, $def);
        if (isset($matched) && ($correct && $matched->isCorrect())) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $matched;
        }

        return $this;
    }

    public function matchCommandDef(CommandDef $def, bool $correct = false): Matcher
    {
        $cmd = $this
            ->comprehension
            ->command;

        $cmdStr = $cmd->getCmdStr();

        if (!isset($cmdStr)) {
            return $this;
        }

        $cmdName = $cmd->getCmdName();
        $matched = $this->doMatchCommandDef($cmdName, $cmdStr, $def);

        if (isset($matched) && ($correct && $matched->isCorrect())) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $matched;
        }

        return $this;
    }

    protected function doMatchCommandDef(
        string $cmdName,
        string $cmdStr,
        CommandDef $def
    ) : ? CommandMsg
    {
        return $cmdName === $def->getCommandName()
            ? $def->parseCommandMessage($cmdStr)
            : null;
    }



    public function hasKeywords(array $keyWords, array $blacklist = [], bool $normalize = false): Matcher
    {
        if (empty($keyWords)) {
            return $this;
        }

        if (!$this->input->isMsgType(VerbalMsg::class)) {
            return $this;
        }

        if ($normalize) {
            $keyWords = ArrayUtils::recursiveArrayParse(
                $keyWords,
                $caller = [StringUtils::class, 'normalizeString']
            );
            $blacklist = ArrayUtils::recursiveArrayParse(
                $blacklist,
                $caller
            );
        }

        // 先尝试用分词来做
        $tokenize = $this->comprehension->tokens;
        if ($tokenize->hasTokens()) {
            $tokens = $tokenize->getTokens();
            if (empty($tokens)) {
                return $this;
            }

            if ($normalize) {
                $tokens = array_map(function($token){
                    return StringUtils::normalizeString($token);
                }, $tokens);
            }

            if (
                ArrayUtils::expectTokens($tokens, $keyWords, true)
                && !ArrayUtils::expectTokens($tokens, $blacklist, false)
            ) {
                $this->matched = true;
            }

            return $this;
        }

        // 然后用字符串来做
        $text = $this->input->getMsgText();
        if (
            StringUtils::expectKeywords($text, $keyWords, true)
            && !StringUtils::expectKeywords($text, $blacklist, false)
        ) {
            $this->matched = true;
        }

        return $this;
    }

    public function feels(string $emotionName) : Matcher
    {
        if ($this->doFeels($emotionName)) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $emotionName;
        }

        return $this;
    }

    protected function doFeels(string $emotionName) : bool
    {
        $reg = $this->cloner->mind->emotionReg();
        if (!$reg->hasDef($emotionName)) {
            return false;
        }

        $def = $reg->getDef($emotionName);
        return $def->feels($this->cloner, $this->injectionContext) ?? false;
    }

    public function isPositive(): Matcher
    {
        if ($this->doFeels($name = EmotionDef::EMO_POSITIVE)) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $name;
        }
        return $this;
    }

    public function isNegative(): Matcher
    {
        if ($this->doFeels($name = EmotionDef::EMO_NEGATIVE)) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $name;
        }
        return $this;
    }


    public function isIntent(string $intentName): Matcher
    {
        $matched = $this->singleIntentMatch($intentName);
        if (isset($matched)) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $intentName;
        }

        return $this;
    }

    protected function wrapIntentMsg(string $intentName) : IntentMsg
    {
        $reg = $this->cloner->mind->intentReg();
        if ($reg->hasDef($intentName)) {
            return $reg->getDef($intentName)->toIntentMessage($this->cloner);
        }

        $entities = $this->cloner
            ->comprehension
            ->intention
            ->getIntentEntities($intentName);

        $entities = array_map(
            function($entityValues) {
                return is_array($entityValues) ? current($entityValues) : $entityValues;
            },
            $entities
        );

        return IIntentMsg::newIntent(
            $intentName,
            $entities
        );
    }

    protected function singleIntentMatch(string $intentName) : ? string
    {
        return ContextUtils::isWildcardIntentPattern($intentName)
            ? $this->singleWildcardIntentMatch($intentName)
            : $this->singleExactlyIntentMatch($intentName);
    }

    protected function singleExactlyIntentMatch(string $intent) : ? string
    {
        $reg = $this->cloner->mind->intentReg();
        $intention = $this->cloner->comprehension->intention;
        if ($intention->hasPossibleIntent($intent)) {
            return $intent;
        }

        if (!$reg->hasDef($intent)) {
            return null;
        }

        $def = $reg->getDef($intent);
        if ($def->match($this->cloner)) {
            return $intent;
        }
        return null;
    }

    protected function singleWildcardIntentMatch(string $intent) : ? string
    {
        $intention = $this->cloner->comprehension->intention;
        $possibles = $intention->getPossibleIntentNames(true);
        foreach ($possibles as $possible) {
            $matched = ContextUtils::wildcardIntentMatch($intent, $possible);
            if (isset($matched)) {
                return $possible;
            }
        }
        return null;
    }


    public function isIntentIn(array $intentNames): Matcher
    {
        $matched = $this->multiIntentsMatch($intentNames);

        if (!empty($matched)) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $matched;
        }

        return $this;
    }

    protected function multiIntentsMatch(array $intents) : array
    {
        if (empty($intents)) {
            return [];
        }

        $possibleIntents = $this
            ->comprehension
            ->intention
            ->getPossibleIntentNames(true);

        $matched = [];

        // 进行批量匹配.
        foreach ($intents as $intentName) {
            if (ContextUtils::isWildcardIntentPattern($intentName)) {
                $matched = array_merge(
                    $matched,
                    ContextUtils::wildcardIntentSearch($intentName, $possibleIntents)
                );
            } elseif ($this->singleExactlyIntentMatch($intentName)) {
                $matched[] = $intentName;
            }

        }
        return $matched;
    }

    public function isAnyIntent(): Matcher
    {
        $intent = $this
            ->comprehension
            ->intention
            ->getMatchedIntent();

        if (isset($intent)) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $intent;
        }

        return $this;
    }

    public function isIntentMsg(string ...$intentNames): Matcher
    {
        $intentName = array_shift($intentNames);

        // 无参数情况.
        if (empty($intentName)) {
            $matched = $this->cloner->comprehension->intention->getMatchedIntent();

        // 只有一个参数
        } elseif (empty($intentNames)) {
            $matched = $this->singleIntentMatch($intentName);

        // 有 n 个参数
        } else {
            array_unshift($intentNames, $intentName);
            $matched = $this->multiIntentsMatch($intentNames)[0] ?? null;
        }

        if (isset($matched)) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $this->wrapIntentMsg($matched);
        }

        return $this;
    }


    public function hasPossibleIntent(string $intentName): Matcher
    {
        $has = $this->comprehension->intention->hasPossibleIntent($intentName);
        if ($has) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $intentName;
        }

        return $this;
    }

    public function hasEntity(string $entityName, bool $defExtractor = false): Matcher
    {
        $matched = $this->doCheckEntity($entityName);
        if ($defExtractor) {
            $matched = $matched ?? $this->doMatchEntity($entityName);
        }

        if (!empty($matched)) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $matched;
        }

        return $this;
    }

    protected function doCheckEntity(string $entityName) : ? array
    {
        $intention = $this->comprehension->intention;

        $entities = $intention->getMatchedEntities();
        return empty($entities[$entityName]) ? null : $entities[$entityName];
    }

    protected function doMatchEntity(string $entityName) : ? array
    {
        if (!$this->input->isMsgType(VerbalMsg::class)) {
            return null;
        }

        $mind = $this->cloner->mind;
        $entityReg = $mind->entityReg();

        if (!$entityReg->hasDef($entityName)) {
            return null;
        }

        $def = $entityReg->getDef($entityName);
        $text = $this->input->getNormalizedText();

        $synonymReg = $mind->synonymReg();
        $entities = $def->match($text, $synonymReg);
        return empty($entities)
            ? null
            : $entities;
    }

    public function matchEntity(string $entityName): Matcher
    {
        $entities = $this->doMatchEntity($entityName);
        if (!empty($entities)) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $entities;
        }

        return $this;
    }

    public function hasEntityValue(string $entityName, string $expect, bool $defExtractor = false): Matcher
    {
        $matched = $this->doCheckEntity($entityName);
        if ($defExtractor) {
            $matched = $matched ?? $this->doMatchEntity($entityName);
        }

        if (!empty($matched) && in_array($expect, $matched)) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $expect;
        }

        return $this;
    }

    public function matchStage(string $stageFullname): Matcher
    {
        $stageReg = $this->cloner->mind->stageReg();

        $matched = null;

        $possibles = $this->comprehension
            ->intention
            ->getPossibleIntentNames(true);

        $matched = $this->matchAnyStage($stageFullname, $possibles, $stageReg)
            ?? $this->matchWildcardStage($stageFullname, $possibles, $stageReg)
            ?? $this->singleExactlyIntentMatch($stageFullname);

        if ($matched && $stageReg->hasDef($matched)) {
            $this->matched = true;
            $this->matchedParams[__FUNCTION__] = $stageReg->getDef($matched);
        }

        return $this;
    }

    protected function matchAnyStage(
        string $stageFullname,
        array $possibles,
        StageReg $stageReg
    ) : ? string
    {
        // 通配符号专门处理以提速.
        if ($stageFullname === '*') {
            foreach ($possibles as $possible) {
                if ($stageReg->hasDef($possible)) {
                    return $possible;
                }
            }
        }

        return null;
    }

    protected function matchWildcardStage(
        string $stageFullname,
        array $possibles,
        StageReg $stageReg
    ) : ? string
    {
        // 如果查找的是通配符...
        if (StringUtils::isWildcardPattern($stageFullname)) {
            foreach ($possibles as $possible) {
                $wildcardMatched = StringUtils::wildcardMatch($stageFullname, $possible);
                if ($wildcardMatched && $stageReg->hasDef($possible)) {
                    return $possible;
                }
            }
        }
        return null;

    }

    public function matchStageIn(array $intents): Matcher
    {
        $matched = $this->multiIntentsMatch($intents);

        $stageReg = $this->cloner->mind->stageReg();
        foreach ($matched as $intentName) {

            if ($stageReg->hasDef($intentName)) {
                $this->matched = true;
                $this->matchedParams[__FUNCTION__] = $stageReg->getDef($intentName);
                return $this;
            }
        }

        return $this;
    }

    /*-------- getter --------*/


    public function __destruct()
    {
        unset(
            $this->cloner,
            $this->input,
            $this->matched,
            $this->matchedParams,
            $this->injectionContext
        );
        SpyAgency::decr(static::class);
    }
}