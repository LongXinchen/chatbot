<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Test\Message;

use Commune\Message\Abstracted\IComprehension;
use Commune\Protocols\Comprehension;
use Commune\Support\Babel\Babel;
use PHPUnit\Framework\TestCase;

/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class ComprehensionTest extends TestCase
{

    public function testIComprehension()
    {
        $com = new IComprehension();
        $this->checkBabelSerialize($com);

        // choice
        // selection
        // query
        // intention
        // replies

        // vector
        $vector = [0.11, 0.22, 0.33];

        // emotions
        $emotions = ['positive', 'agreed'];
        $com->emotion->addEmotions(...$emotions);
        $this->assertEquals($emotions, $com->emotion->getEmotions());
        $this->assertTrue($com->emotion->isEmotion('agreed'));

        // cmd
        $this->assertFalse($com->command->hasCmdStr());
        $com->command->setCmdStr($command = 'hello world');
        $this->assertEquals($command, $com->command->getCmdStr());
        $this->assertEquals('hello', $com->command->getCmdName());

        // token
        $this->assertFalse($com->tokenize->hasTokens());
        $com->tokens->addTokens($tokens = ['a', 'b', 'c']);
        $this->assertTrue($com->tokenize->hasTokens());
        $this->assertEquals($tokens, $com->tokenize->getTokens());
        $this->checkBabelSerialize($com);
    }



    protected function checkBabelSerialize(Comprehension $comprehension)
    {
        $str = Babel::serialize($comprehension);
        $un = Babel::unserialize($str);
        $str2 = Babel::serialize($un);
        $this->assertEquals($str, $str2);
    }
}