<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Test;

use Spork\EventDispatcher\EventDispatcher;
use Spork\ProcessManager;

class ProcessManagerTest extends \PHPUnit_Framework_TestCase
{
    private $manager;

    protected function setUp()
    {
        $this->manager = new ProcessManager();
    }

    protected function tearDown()
    {
        unset($this->manager);
    }

    public function testSuccess()
    {
        $fork = $this->manager->fork(function() {
            echo 'Forked ' . getmypid() . ' at ' . strftime('%F %T');
        });

        $this->manager->wait();

        $this->assertTrue($fork->isSuccessful());
    }

    public function testFail()
    {
        $fork = $this->manager->fork(function() {
            throw new \Exception('Hey, it\'s an error');
        });

        $this->manager->wait();

        $this->assertFalse($fork->isSuccessful());
    }
}
