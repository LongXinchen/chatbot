<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Ghost\Dialog;

use Commune\Ghost\ITools;
use Commune\Blueprint\Ghost;
use Commune\Blueprint\Ghost\Ucl;
use Commune\Blueprint\Ghost\Dialog;
use Commune\Blueprint\Ghost\Tools;
use Commune\Protocols\HostMsg;
use Commune\Protocols\Intercom\InputMsg;
use Commune\Support\DI\Injectable;
use Commune\Support\DI\TInjectable;
use Commune\Blueprint\Ghost\Cloner;
use Commune\Support\Utils\TypeUtils;
use Commune\Blueprint\Ghost\Context;
use Commune\Framework\Spy\SpyAgency;
use Commune\Blueprint\Ghost\Runtime\Task;
use Commune\Blueprint\Ghost\Runtime\Process;
use Commune\Blueprint\Ghost\Operate\Operator;
use Commune\Blueprint\Exceptions\Logic\InvalidArgumentException;
use Commune\Blueprint\Exceptions\Runtime\BrokenRequestException;


/**
 * 抽象的 Dialog 实现.
 *
 * @author thirdgerb <thirdgerb@gmail.com>
 *
 * @property-read Cloner $cloner        当前对话机器人的分身.
 * @property-read Context $context      当前的上下文语境.
 * @property-read Ucl $ucl              当前语境的地址.
 * @property-read Task $task            当前语境的任务状态.
 * @property-read Process $process      当前多轮对话运行中的进程.
 * @property-read Dialog|null $prev     上一个 Dialog 对象.
 */
abstract class AbsBaseDialog implements
    Injectable,
    Dialog,
    Ghost\Tools\DialogContainer
{
    use TInjectable;

    /*------ params -------*/

    /**
     * @var Cloner
     */
    protected $_cloner;

    /**
     * @var Ucl
     */
    protected $_ucl;

    /**
     * @var InputMsg
     */
    protected $_input;


    /**
     * @var HostMsg
     */
    protected $_message;

    /**
     * @var Dialog|null
     */
    protected $_prev;


    /*------ cached -------*/

    /**
     * @var Process|null
     */
    protected $process;

    /**
     * AbsBaseDialog constructor.
     * @param Cloner $cloner
     * @param Ucl $ucl
     * @param Dialog|null $prev
     */
    public function __construct(
        Cloner $cloner,
        Ucl $ucl,
        Dialog $prev = null
    )
    {
        $this->_cloner = $cloner;
        $this->_ucl = $ucl->toInstance($cloner);
        $this->_prev = $prev;
        SpyAgency::incr(static::class);
    }

    /*-------- dialog tool --------*/

    public function send(bool $immediately = true): Tools\Deliver
    {
        return new ITools\IDeliver($this, $immediately);
    }

    public function chainCallable(callable $callable, callable ...$callableList): callable
    {
        array_unshift($callableList, $callable);
        return new ITools\CallableChain($this->container(), $callableList);
    }


    /*-------- operators --------*/

    public function hearing(): Tools\Hearing
    {
        return new ITools\IHearing($this);
    }

    /*-------- caller --------*/

    public function container(): Tools\DialogContainer
    {
        return $this;
    }

    public function make(string $abstract, array $parameters = [])
    {
        $parameters = $this->getDialogicInjections($parameters);

        try {
            return $this->_cloner->container->make($abstract, $parameters);
        } catch (\Exception $e) {
            throw new BrokenRequestException('', $e);
        }
    }


    public function call($caller, array $parameters = [])
    {
        $parameters = $this->getDialogicInjections($parameters);

        try {
            return $this->_cloner->container->call($caller, $parameters);
        } catch (\Exception $e) {
            $type = TypeUtils::getType($caller);
            throw new BrokenRequestException("fail to call $type", $e);
        }
    }

    public function predict(callable $caller, array $parameters = []): bool
    {
        $result = $this->call($caller, $parameters);
        if (!is_bool($result)) {
            throw new InvalidArgumentException('caller is not predict which return with bool');
        }

        return $result;
    }

    public function action($caller, array $parameters = []): ? Operator
    {
        $result = $this->call($caller, $parameters);
        if (is_null($result) || $result instanceof Operator) {
            return $result;
        }

        throw new InvalidArgumentException('caller should return operator or null, ' . TypeUtils::getType($result) . ' given');
    }

    protected function getDialogicInjections(array $parameters) : array
    {
        $injections = [
            'context' => $this->context,
            'dialog' => $this,
        ];

        $injections = $parameters + $injections;

        foreach ($injections as $key => $value) {

            $parameters[$key] = $value;

            if (!$value instanceof Injectable) {
                continue;
            }

            foreach ($value->getInterfaces() as $interface) {
                $parameters[$interface] = $value;
            }
        }

        $parameters['dependencies'] = array_keys($parameters);
        return $parameters;
    }


    /*-------- operator --------*/

    /**
     * @return Process
     */
    protected function getProcess() : Process
    {
        return $this->process
            ?? $this->process = $this->_cloner->runtime->getCurrentProcess();
    }

    public function getDialog(): Dialog
    {
        return $this;
    }


    /*-------- status --------*/

    public function isEvent(string $statusType): bool
    {
        return is_a($this, $statusType, TRUE);
    }

    /*-------- recall --------*/

    public function recall(string $name): Ghost\Memory\Recollection
    {
        $recollection = $this
            ->cloner
            ->mind
            ->memoryReg()
            ->getDef($name)
            ->recall($this->_cloner);
        
        return $recollection;
    }


    /*-------- magic --------*/

    public function __isset($name)
    {
        if ($name === 'prev') {
            return isset($this->_prev);
        }

        return in_array($name, ['cloner', 'ucl', 'context', 'task', 'process', 'input', 'message']);
    }

    public function __get($name)
    {
        switch ($name) {
            case 'cloner' :
                return $this->_cloner;

            case 'input' :
                return $this->_input
                    ?? $this->_input = $this->_cloner->input;
            case 'message' :
                return $this->_message
                    ?? $this->_message = $this->_cloner->input->getMessage();

            case 'ucl' :
                return $this->_ucl;

            case 'prev' :
                return $this->_prev;

            case 'context' :
                return $this->_ucl->findContext($this->_cloner);

            case 'process' :
                return $this->_cloner
                    ->runtime
                    ->getCurrentProcess();

            case 'task' :
                return $this->_cloner
                    ->runtime
                    ->getCurrentProcess()
                    ->getTask($this->_ucl);

            default:
                return null;
        }

    }

    protected function depth(): int
    {
        $current = $this;
        $depth = 1;

        while(isset($current)) {
            $current = $current->prev;
            $depth++;
        }

        return $depth;
    }

    public function getInterfaces(): array
    {
        return static::getInterfacesOf(
            Ghost\Dialog::class,
            true
        );
    }


    public function __destruct()
    {
        unset($this->_prev);
        unset($this->_cloner);
        unset($this->_ucl);
        unset($this->_message);
        unset($this->_input);
        SpyAgency::decr(static::class);
    }

}