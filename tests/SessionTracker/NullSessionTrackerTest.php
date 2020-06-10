<?php

namespace Bugsnag\Tests\SessionTracker;

use Bugsnag\SessionTracker\NullSessionTracker;
use Bugsnag\SessionTracker\SessionTrackerInterface;
use Bugsnag\Tests\TestCase;

class NullSessionTrackerTest extends TestCase
{
    public function testItImplementsTheSessionTrackerInterface()
    {
        $sessionTracker = new NullSessionTracker();

        $this->assertInstanceOf(SessionTrackerInterface::class, $sessionTracker);
    }

    public function testItLogsWhenStartSessionIsCalled()
    {
        $errorLog = $this->getFunctionMock('Bugsnag\SessionTracker', 'error_log');
        $errorLog->expects($this->once())
            ->with("Bugsnag: 'startSession' cannot be called when session tracking is disabled");

        $sessionTracker = new NullSessionTracker();
        $sessionTracker->startSession();
    }

    public function testItLogsWhenSendSessionsIsCalled()
    {
        $errorLog = $this->getFunctionMock('Bugsnag\SessionTracker', 'error_log');
        $errorLog->expects($this->once())
            ->with("Bugsnag: 'sendSessions' cannot be called when session tracking is disabled");

        $sessionTracker = new NullSessionTracker();
        $sessionTracker->sendSessions();
    }

    public function testSetCurrentSessionHasNoObservableEffects()
    {
        $errorLog = $this->getFunctionMock('Bugsnag\SessionTracker', 'error_log');
        $errorLog->expects($this->never());

        $sessionTracker = new NullSessionTracker();
        $sessionTracker->setCurrentSession([]);
    }

    public function testGetCurrentSessionHasNoObservableEffects()
    {
        $errorLog = $this->getFunctionMock('Bugsnag\SessionTracker', 'error_log');
        $errorLog->expects($this->never());

        $sessionTracker = new NullSessionTracker();
        $sessionTracker->getCurrentSession(null);
    }

    public function testSetLockFunctionsHasNoObservableEffects()
    {
        $errorLog = $this->getFunctionMock('Bugsnag\SessionTracker', 'error_log');
        $errorLog->expects($this->never());

        $sessionTracker = new NullSessionTracker();
        $sessionTracker->setLockFunctions(null, null);
    }

    public function testSetRetryFunctionHasNoObservableEffects()
    {
        $errorLog = $this->getFunctionMock('Bugsnag\SessionTracker', 'error_log');
        $errorLog->expects($this->never());

        $sessionTracker = new NullSessionTracker();
        $sessionTracker->setRetryFunction(null);
    }

    public function testSetStorageFunctionHasNoObservableEffects()
    {
        $errorLog = $this->getFunctionMock('Bugsnag\SessionTracker', 'error_log');
        $errorLog->expects($this->never());

        $sessionTracker = new NullSessionTracker();
        $sessionTracker->setStorageFunction(null);
    }

    public function testSetSessionFunctionHasNoObservableEffects()
    {
        $errorLog = $this->getFunctionMock('Bugsnag\SessionTracker', 'error_log');
        $errorLog->expects($this->never());

        $sessionTracker = new NullSessionTracker();
        $sessionTracker->setSessionFunction(null);
    }
}
