<?php

namespace Radvance\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use PDO;
use ReflectionObject;
use RuntimeException;
use Sinergi\BrowserDetector\Browser;
use Sinergi\BrowserDetector\Os;
use Sinergi\BrowserDetector\Device;

class PdoEventStoreDispatcher implements EventDispatcherInterface
{
    protected $dispatcher;
    protected $pdo;
    protected $tableName;
    protected $request;
    protected $app;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        PDO $pdo,
        $tableName = 'event_store'
    ) {
        $this->dispatcher = $dispatcher;
        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }

    public function setRequest($request = [])
    {
        $this->request = $request;
    }

    public function setApp($app)
    {
        $this->app = $app;
    }

   /**
     * {@inheritDoc}
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        $this->dispatcher->addListener($eventName, $listener, $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->dispatcher->addSubscriber($subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function removeListener($eventName, $listener)
    {
        return $this->dispatcher->removeListener($eventName, $listener);
    }

    /**
     * {@inheritdoc}
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->dispatcher->removeSubscriber($subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function getListeners($eventName = null)
    {
        return $this->dispatcher->getListeners($eventName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasListeners($eventName = null)
    {
        return $this->dispatcher->hasListeners($eventName);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($eventName, Event $event = null)
    {
        if (null === $event) {
            $event = new Event();
        }

        if ($event instanceof StoredEventInterface) {
            //echo $eventName . "<br />\n";

            $data = [];
            $reflectionObject = new ReflectionObject($event);

            foreach ($reflectionObject->getProperties() as $p) {
                $p->setAccessible(true);
                $value = (string)$p->getValue($event);
                if (is_object($value) || is_array($value)) {
                    throw new RuntimeException(
                        "StoredEvents can not contain object/array properties: " . $p->getName()
                    );
                } else {
                    $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', (string)$p->getName()));
                    $data[$key] = $value;
                }
            }

            $metaData = [];
            if ($this->app) {
                if (isset($this->app['current_user']) && $this->app['current_user']) {
                    $metaData['username'] = $this->app['current_user']->getName();
                }
                if (isset($this->app['session'])) {
                    $metaData['session_id'] = $this->app['session']->getId();
                }
                if (isset($this->app['accountName'])) {
                    $metaData['space_account'] = $this->app['accountName'];
                }
                if (isset($this->app['spaceName'])) {
                    $metaData['space_name'] = $this->app['spaceName'];
                }
                if (isset($this->app['space'])) {
                    $metaData['space_id'] = $this->app['space']->getId();
                }
            }
            if ($this->request) {
                if ($this->request->headers->has('X-Request-Id')) {
                    $metaData['request_id'] = $this->request->headers->get('X-Request-Id');
                }
                $metaData['ip'] = $this->request->getClientIp();
                $metaData['host'] = $this->request->getHost();
                $metaData['route'] = $this->request->get('_route');
                $metaData['uri'] = $this->request->getRequestUri();
                $browser = new Browser();
                $metaData['browser_name'] = $browser->getName();
                $metaData['browser_version'] = $browser->getVersion();
                $os = new Os();
                $metaData['os_name'] = $os->getName();
                $metaData['os_version'] = $os->getVersion();
                $device = new Device();
                $metaData['device_name'] = $device->getName();
            }
            //print_r($data); print_r($metaData);

            $stmt = $this->pdo->prepare(
                'INSERT INTO ' . $this->tableName .
                ' (stamp, space_id, name, data, meta_data)' .
                ' values(:stamp, :space_id, :name, :data, :meta_data)'
            );
            $spaceId = 0;
            if (isset($metaData['space_id'])) {
                $spaceId = $metaData['space_id'];
            }
            $stmt->execute(
                [
                    'stamp' => time(),
                    'space_id' => $spaceId,
                    'name' => $eventName,
                    'data' => json_encode($data),
                    'meta_data' => json_encode($metaData)
                ]
            );
        }
        $this->dispatcher->dispatch($eventName, $event);

        /*
        // reset the id as another event might have been dispatched during the dispatching of this event
        $this->id = spl_object_hash($event);

        unset($this->firstCalledEvent[$eventName]);

        $e->stop();

        $this->postDispatch($eventName, $event);
        */

        return $event;
    }

    /**
     * {@inheritDoc}
     */
    public function getCalledListeners()
    {
        return $this->called;
    }

    /**
     * {@inheritDoc}
     */
    public function getNotCalledListeners()
    {
        $notCalled = array();

        foreach ($this->getListeners() as $name => $listeners) {
            foreach ($listeners as $listener) {
                $info = $this->getListenerInfo($listener, $name);
                if (!isset($this->called[$name.'.'.$info['pretty']])) {
                    $notCalled[$name.'.'.$info['pretty']] = $info;
                }
            }
        }

        return $notCalled;
    }


    /**
     * Proxies all method calls to the original event dispatcher.
     *
     * @param string $method    The method name
     * @param array  $arguments The method arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array(array($this->dispatcher, $method), $arguments);
    }

    public function getListenerPriority($eventName, $listener)
    {
        return 0;
    }
}
