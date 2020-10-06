<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Ghost\IMindReg;

use Commune\Blueprint\Ghost\MindDef\MemoryDef;
use Commune\Blueprint\Ghost\MindReg\MemoryReg;
use Commune\Blueprint\Ghost\MindMeta\MemoryMeta;
use Commune\Ghost\Support\ContextUtils;

/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class IMemoryReg extends AbsDefRegistry implements MemoryReg
{
    protected function normalizeDefName(string $name): string
    {
        return ContextUtils::normalizeMemoryName($name);
    }

    protected function getDefType(): string
    {
        return MemoryDef::class;
    }

    public function getMetaId(): string
    {
        return MemoryMeta::class;
    }

//  懒加载现在已无好处.
//    protected function hasRegisteredMeta(string $defName): bool
//    {
//        if (parent::hasRegisteredMeta($defName)) {
//            return true;
//        }
//
//        $contextReg = $this->mindset->contextReg();
//        if ($contextReg->hasDef($defName)) {
//            $def = $contextReg->getDef($defName)->asMemoryDef();
//            $this->registerDef($def);
//            return true;
//        }
//
//        return false;
//    }


}