<?php
/**
 * Phergie plugin for switching to alternate nicks when primary is not available (https://github.com/PSchwisow/phergie-irc-plugin-react-altnick)
 *
 * @link https://github.com/PSchwisow/phergie-irc-plugin-react-altnick for the canonical source repository
 * @copyright Copyright (c) 2015 Patrick Schwisow (https://github.com/PSchwisow/phergie-irc-plugin-react-altnick)
 * @license http://phergie.org/license Simplified BSD License
 * @package PSchwisow\Phergie\Plugin\AltNick
 */

namespace PSchwisow\Phergie\Plugin\AltNick;

use ArrayIterator;
use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\ConnectionInterface;
use Phergie\Irc\Event\EventInterface as Event;
use SplObjectStorage;

/**
 * Plugin class.
 *
 * @category PSchwisow
 * @package PSchwisow\Phergie\Plugin\AltNick
 */
class Plugin extends AbstractPlugin
{
    /**
     * Array of alternate nicks to try
     *
     * @var array
     */
    private $nicks;

    /**
     * One iterator per connection
     *
     * @var SplObjectStorage (collection of ArrayIterator)
     */
    private $iterators;

    /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     * nicks - an array of alternate nicks (at least one is required)
     *
     * @param array $config
     * @throws \InvalidArgumentException
     */
    public function __construct(array $config = [])
    {
        if (!array_key_exists('nicks', $config)) {
            throw new \InvalidArgumentException("Missing required configuration key 'nicks'");
        }
        $this->setNicks($config['nicks']);
    }

    /**
     * Set adapter
     *
     * @param array $nicks
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setNicks($nicks)
    {
        if (!is_array($nicks)) {
            throw new \InvalidArgumentException('setNicks method expects an array');
        }

        $special = preg_quote('[]\`_^{|}');
        $nicks = array_filter(
            array_map(
                function ($nick) use ($special) {
                    if (is_string($nick)) {
                        $nick = trim($nick);
                        return (preg_match("/^[a-z$special][a-z0-9$special-]*$/i", $nick) ? $nick : null);
                    }
                    return null;
                },
                $nicks
            )
        );

        if (empty($nicks)) {
            throw new \InvalidArgumentException('setNicks method did not receive any valid nicks');
        }

        $this->nicks = $nicks;
    }

    /**
     * Subscribe to events
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'irc.received.err_nicknameinuse' => 'handleEvent',
        ];
    }

    /**
     * Nick is in use, pick another.
     *
     * @param \Phergie\Irc\Event\EventInterface $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function handleEvent(Event $event, Queue $queue)
    {
        $iterator = $this->getIterator($event->getConnection());

        if (!$iterator->valid()) {
            $queue->ircQuit('All specified alternate nicks are in use');
            return;
        }

        $nick = $iterator->current();
        $iterator->next();

        $this->logger->debug("[AltNick] Switching nick to '$nick'");
        $queue->ircNick($nick);
        $event->getConnection()->setNickname($nick);
    }

    protected function getIterator(ConnectionInterface $connection)
    {
        if ($this->iterators === null) {
            $this->iterators = new SplObjectStorage();
        }

        if (!$this->iterators->offsetExists($connection)) {
            $this->iterators[$connection] = new ArrayIterator($this->nicks);
        }

        return $this->iterators[$connection];
    }
}
