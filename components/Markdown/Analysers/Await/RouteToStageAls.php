<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Components\Markdown\Analysers\Await;
use Commune\Blueprint\Ghost\Dialog;
use Commune\Blueprint\Ghost\Operate\Await;
use Commune\Blueprint\Ghost\Operate\Operator;
use Commune\Components\Markdown\Mindset\SectionStageDef;


/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class RouteToStageAls extends AbsAwaitAnalyser
{
    public function __invoke(
        Dialog $dialog,
        SectionStageDef $def,
        string $content,
        Await $await
    ): ? Operator
    {
        list($stageName, $index) = $this->separateRouteAndIndex($content);

        $ucl = $dialog->ucl->goStage($stageName);
        $cloner = $dialog->cloner;
        if (!$ucl->stageExists($cloner)) {
            $cloner->logger->error(
                static::class . '::'. __FUNCTION__
                . " stage $ucl not exists"
            );
            return $await;
        }

        $question = $await->getCurrentQuestion();

        list ($index, $suggestion) = $this->parseIndexAndDesc($ucl, $cloner, $index);
        $question->addSuggestion($suggestion, $index, $ucl);
        return $await;
    }



}