<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Components\Demo\Contexts;

use Commune\Blueprint\Ghost\Context\CodeContextOption;
use Commune\Blueprint\Ghost\Context\Depending;
use Commune\Blueprint\Ghost\Context\StageBuilder as Stage;
use Commune\Blueprint\Ghost\Context\StageBuilder;
use Commune\Blueprint\Ghost\Dialog;
use Commune\Blueprint\Ghost\Dialog\Activate;
use Commune\Blueprint\Ghost\Ucl;
use Commune\Components\Demo\Git\GitContext;
use Commune\Components\Demo\Maze\Maze;
use Commune\Components\HeedFallback\Context\TeachTasks;
use Commune\Components\HeedFallback\HeedFallbackComponent;
use Commune\Components\Markdown\MarkdownComponent;
use Commune\Components\Tree\Demo\TreeDemoContext;
use Commune\Components\Tree\TreeComponent;
use Commune\Ghost\Context\ACodeContext;
use Commune\Ghost\Predefined\Manager\NLUManagerContext;
use Commune\Support\Registry\OptRegistry;


/**
 * @author thirdgerb <thirdgerb@gmail.com>
 *
 * @desc Demo的入口
 */
class DemoHome extends ACodeContext
{
    public static function __depending(Depending $depending): Depending
    {
        return $depending;
    }

    public static function __option(): CodeContextOption
    {
        return new CodeContextOption([
            'strategy' => [
                'onQuit' => 'quit',
                'onCancel' => 'cancel',
            ],
        ]);
    }

    public function __on_start(Stage $stage): Stage
    {
        return $stage
            ->onActivate(function(Activate $dialog){
                return $dialog->next('menu');
            });
    }


    public function __on_quit(Stage $stage) : Stage
    {
        return $stage->always(function(Dialog $dialog){
            return $dialog
                ->send()
                ->notice('quiting pass by quit stage')
                ->over()
                ->quit();
        });
    }

    public function __on_cancel(Stage $stage) : Stage
    {
        return $stage->always(function(Dialog $dialog){
            return $dialog
                ->send()
                ->notice('canceling pass by cancel stage')
                ->over()
                ->cancel();
        });
    }

    /**
     * @param StageBuilder $stage
     * @return StageBuilder
     *
     * @title 选项菜单
     * @desc 选择测试功能
     */
    public function __on_menu(Stage $stage) : Stage
    {
        /**
         * @var OptRegistry $a
         */
        $ab = $stage->dialog->cloner->mind;
        $ab->stageReg()->hasDef(static::__name());

        return $stage
            ->onActivate(function(Activate $dialog){

                $menu = [
                    FeatureTest::genUcl(),
                    $this->getStage('maze'),
                    GitContext::genUcl(),
                    NLUManagerContext::genUcl(),
                ];

                $container = $dialog->cloner->container;

                if ($container->has(TreeComponent::class)) {
                    $menu[] = Ucl::make(TreeDemoContext::NAME);
                }

                if ($container->has(MarkdownComponent::class)) {
                    $menu[] = Ucl::make('md.demo.commune_v2_intro');
                }

                if ($container->has(HeedFallbackComponent::class)) {
                    $menu[] = TeachTasks::genUcl();
                }

                return $dialog
                    ->await()
                    ->askChoose(
                        '请您选择',
                        $menu
                    );

            });

    }

    /**
     * @param StageBuilder $stage
     * @return StageBuilder
     *
     * @desc 测试迷宫小游戏
     */
    public function __on_maze(StageBuilder $stage) : StageBuilder
    {
        return $stage
            ->onActivate($stage->dialog->dependOn(Maze::genUcl()))
            ->onEvent(
                Dialog::CALLBACK,
                function(Dialog $dialog) {
                    return $dialog
                        ->send()
                        ->info('完成测试')
                        ->over()
                        ->goStage('menu');
                }
            );
    }


}