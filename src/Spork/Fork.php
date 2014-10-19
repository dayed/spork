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

use Spork\Exception\ProcessControlException;

class Fork
{
    private $pid;
    private $status;

    public function __construct($pid)
    {
        $this->pid   = $pid;
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
            $this->status = $status;
        }

        return $this;
    }


    public function kill($signal = SIGINT)
    {
        if (false === posix_kill($this->pid, $signal)) {
            throw new ProcessControlException('Unable to send signal');
        }

        return $this;
    }
    
    public function isRunning()
    {
        return false !== posix_kill($this->pid, SIG_DFL);
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
