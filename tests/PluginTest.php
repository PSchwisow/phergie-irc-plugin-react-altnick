<?php
/**
 * Phergie plugin for switching to alternate nicks when primary is not available (https://github.com/PSchwisow/phergie-irc-plugin-react-altnick)
 *
 * @link https://github.com/PSchwisow/phergie-irc-plugin-react-altnick for the canonical source repository
 * @copyright Copyright (c) 2015 Patrick Schwisow (https://github.com/PSchwisow/phergie-irc-plugin-react-altnick)
 * @license http://phergie.org/license Simplified BSD License
 * @package PSchwisow\Phergie\Plugin\AltNick
 */

namespace PSchwisow\Phergie\Tests\Plugin\AltNick;

use Phake;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Event\EventInterface as Event;
use PSchwisow\Phergie\Plugin\AltNick\Plugin;

/**
 * Tests for the Plugin class.
 *
 * @category PSchwisow
 * @package PSchwisow\Phergie\Plugin\AltNick
 */
class PluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that an exception is thrown if required constructor argument is missing.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Missing required configuration key 'nicks'
     */
    public function testConstructMissingArgument()
    {
        $plugin = new Plugin();
    }

    /**
     * Tests that an exception is thrown if 'nicks' is not an array.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage setNicks method expects an array
     */
    public function testSetNicksNotArray()
    {
        $plugin = new Plugin(['nicks' => 'Foo']);
    }

    /**
     * Tests that the list of nicks is properly filtered and set.
     */
    public function testSetNicks()
    {
        $nicks = [
            // valid
            'Foo',
            'Foo_Bar',
            'a1235',
            'x-[]\`_^{|}',

            // NOT valid
            null,
            '',
            '1235',
            12345,
            $this,
        ];
        $plugin = new Plugin(['nicks' => $nicks]);

        $reflectionClass = new \ReflectionClass(get_class($plugin));
        $reflectionProperty = $reflectionClass->getProperty('nicks');
        $reflectionProperty->setAccessible(true);
        $nicksValue = $reflectionProperty->getValue($plugin);

        $this->assertInternalType('array', $nicksValue);
        $this->assertCount(4, $nicksValue); // adjust number to match the number of valid nicks defined above
    }

    /**
     * Tests that an exception is thrown if 'nicks' contains no valid values.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage setNicks method did not receive any valid nicks
     */
    public function testSetNicksNoValidValues()
    {
        $plugin = new Plugin(['nicks' => ['']]);
    }

    /**
     * Tests that getSubscribedEvents() returns an array.
     */
    public function testGetSubscribedEvents()
    {
        $plugin = new Plugin(['nicks' => ['Foo']]);
        $this->assertInternalType('array', $plugin->getSubscribedEvents());
    }

    /**
     * Tests handleEvent().
     */
    public function testHandleEvent()
    {
        $event1 = $this->getMockEvent();
        $connection1 = $this->getMockConnection();
        Phake::when($event1)->getConnection()->thenReturn($connection1);
        $queue1 = $this->getMockEventQueue();

        $event2 = $this->getMockEvent();
        $connection2 = $this->getMockConnection();
        Phake::when($event2)->getConnection()->thenReturn($connection2);
        $queue2 = $this->getMockEventQueue();

        $logger = $this->getMockLogger();

        $plugin = new Plugin(['nicks' => ['Foo', 'Foo_', 'FooBar']]);
        $plugin->setLogger($logger);
        $plugin->handleEvent($event1, $queue1);
        $plugin->handleEvent($event1, $queue1);
        $plugin->handleEvent($event2, $queue2);
        $plugin->handleEvent($event1, $queue1);
        $plugin->handleEvent($event2, $queue2);
        $plugin->handleEvent($event1, $queue1);
        $plugin->handleEvent($event2, $queue2);
        $plugin->handleEvent($event2, $queue2);

        Phake::verify($logger, Phake::times(2))->debug("[AltNick] Switching nick to 'Foo'");
        Phake::verify($logger, Phake::times(2))->debug("[AltNick] Switching nick to 'Foo_'");
        Phake::verify($logger, Phake::times(2))->debug("[AltNick] Switching nick to 'FooBar'");

        Phake::inOrder(
            Phake::verify($queue1)->ircNick('Foo'),
            Phake::verify($connection1)->setNickname('Foo'),

            Phake::verify($queue1)->ircNick('Foo_'),
            Phake::verify($connection1)->setNickname('Foo_'),

            Phake::verify($queue2)->ircNick('Foo'),
            Phake::verify($connection2)->setNickname('Foo'),

            Phake::verify($queue1)->ircNick('FooBar'),
            Phake::verify($connection1)->setNickname('FooBar'),

            Phake::verify($queue2)->ircNick('Foo_'),
            Phake::verify($connection2)->setNickname('Foo_'),

            Phake::verify($queue1)->ircQuit('All specified alternate nicks are in use'),

            Phake::verify($queue2)->ircNick('FooBar'),
            Phake::verify($connection2)->setNickname('FooBar'),

            Phake::verify($queue2)->ircQuit('All specified alternate nicks are in use')
        );
    }

    /**
     * Returns a mock event.
     *
     * @return \Phergie\Irc\Event\EventInterface
     */
    protected function getMockEvent()
    {
        return Phake::mock('Phergie\Irc\Event\EventInterface');
    }

    /**
     * Returns a mock connect.
     *
     * @return \Phergie\Irc\ConnectionInterface
     */
    protected function getMockConnection()
    {
        return Phake::mock('Phergie\Irc\ConnectionInterface');
    }

    /**
     * Returns a mock event queue.
     *
     * @return \Phergie\Irc\Bot\React\EventQueueInterface
     */
    protected function getMockEventQueue()
    {
        return Phake::mock('Phergie\Irc\Bot\React\EventQueueInterface');
    }

    /**
     * Returns a mock logger.
     *
     * @return \Monolog\Logger
     */
    protected function getMockLogger()
    {
        return Phake::mock('Monolog\Logger');
    }
}
