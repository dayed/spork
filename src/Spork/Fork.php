<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork;

use Spork\Exception\ForkException;
use Spork\Exception\ProcessControlException;
use Spork\Util\ExitMessage;

class Fork implements DeferredInterface
{
    private $pid;
    private $fifo;
    private $debug;
    private $name;
    private $status;
    private $message;

    public function __construct($pid, Fifo $fifo, $debug = false)
    {
        $this->pid   = $pid;
        $this->fifo  = $fifo;
        $this->debug = $debug;
        $this->name  = '<anonymous>';
    }

    /**
     * Assign a name to the current fork (useful for debugging).
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function wait($hang = true)
    {
        if ($this->isExited()) {
            return $this;
        }

        if (-1 === $pid = pcntl_waitpid($this->pid, $status, ($hang ? 0 : WNOHANG) | WUNTRACED)) {
            throw new ProcessControlException('Error while waiting for process '.$this->pid);
        }

        if ($this->pid === $pid) {
            $this->processWaitStatus($status);
        }

        return $this;
    }

    /**
     * Processes a status value retrieved while waiting for this fork to exit.
     */
    public function processWaitStatus($status)
    {
        if ($this->isExited()) {
            throw new \LogicException('Cannot set status on an exited fork');
        }

        $this->status = $status;

        if ($this->isExited()) {
            $this->receiveMany();

            $this->fifo->close();
            $this->fifo->cleanup();

            $this->isSuccessful() ? $this->resolve() : $this->reject();

            if ($this->debug && (!$this->isSuccessful() || $this->getError())) {
                throw new ForkException($this->name, $this->pid, $this->getError());
            }
        }
    }

    public function receiveOne()
    {
        $message = $this->fifo->receiveOne($success);

        if (!$success) {
            return;
        } elseif ($message instanceof ExitMessage) {
            $this->message = $message;
        } else {
            return $message;
        }
    }

    public function receiveMany()
    {
        $messages = array();

        foreach ($this->fifo->receiveMany() as $message) {
            if ($message instanceof ExitMessage) {
                $this->message = $message;
            } else {
                $messages[] = $message;
            }
        }

        return $messages;
    }

    public function kill($signal = SIGINT)
    {
        if (false === $this->fifo->signal($signal)) {
            throw new ProcessControlException('Unable to send signal');
        }

        return $this;
    }

    public function getResult()
    {
        if ($this->message) {
            return $this->message->getResult();
        }
    }

    public function getOutput()
    {
        if ($this->message) {
            return $this->message->getOutput();
        }
    }

    public function getError()
    {
        if ($this->message) {
            return $this->message->getError();
        }
    }

    public function isSuccessful()
    {
        return 0 === $this->getExitStatus();
    }

    public function isExited()
    {
        return null !== $this->status && pcntl_wifexited($this->status);
    }

    public function isStopped()
    {
        return null !== $this->status && pcntl_wifstopped($this->status);
    }

    public function isSignaled()
    {
        return null !== $this->status && pcntl_wifsignaled($this->status);
    }

    public function getExitStatus()
    {
        if (null !== $this->status) {
            return pcntl_wexitstatus($this->status);
        }
    }

    public function getTermSignal()
    {
        if (null !== $this->status) {
            return pcntl_wtermsig($this->status);
        }
    }

    public function getStopSignal()
    {
        if (null !== $this->status) {
            return pcntl_wstopsig($this->status);
        }
    }
}
