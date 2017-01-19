# Event Store

Radvance let's you easily store domain events.

## Usage:

Create a new Event class for your events:

```php
<?php

namespace MyApp\Domain\Blog;

use Radvance\Event\BaseStoredEvent;

class BlogPostedEvent extends BaseStoredEvent
{
    protected $username;
    protected $title;
}
```

Extending from `BaseStoreEvent` is not required, but it's helpful because:

1. BaseStoreEvent implements `Radvance\Event\StoredEventInterface`, which means it will automatically be stored.
2. BaseStoreEvent automatically creates a constructor, getters and setters based on your protected properties.

Now in your application code (a controller, command handler, or other domain logic code), dispatch the event:

```php
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
public class BlogController
{
    public function postAction(EventDispatcherInterface $dispatcher, $blog)
    {
        // persist the blog here
        $event = new BlogPostedEvent('joe', $blog->getTitle());
        $dispatcher->dispatch(BlogPostedEvent::class, $event);
        $this->redirect('/my-blogs');
    }
}
```
Note that the dispatcher implements the standard Symfony EventDispatcherInterface.

So you can register listeners and subscribers that will be triggered for your events.

## Enable storing events

To store your events, open your `app/config/config.yml` and add the following:

```yml
event_store:
    table_name: event_store
```
This configuration will tell Radvance that you want to store your events, and
in which database table.

Make sure to add a table in your schema.xml file to store these events:
    
```xml
<table name="event_store">
    <column name="id" type="integer" unsigned="true" autoincrement="true"/>
    <column name="stamp" type="integer" />
    <column name="space_id" type="integer" />
    <column name="name" type="string" length="128" />
    <column name="data" type="text" notnull="false" />
    <column name="meta_data" type="text" notnull="false" />

    <index name="primary" primary="true" columns="id" />
</table>
```

## Naming convention

A small class is created for each event that your app will trigger.

1. Make sure the class name/event name is written as a past-tense action.
2. Make sure it follows SubjectVerb format.
3. Use "Business" terminology for your events. Avoid implementation-details like "delete", "insert", etc. Instead use "Revoke", "Post", etc.
4. Post-fix every event with `Event`
5. Put your Event classes in the `Domain/` directory of your app/module, in a sub-directory for each domain model. For example `MyApp/Domain/Blog/BlogPostedEvent.php`

* Good: PostCreatedEvent, PermissionGrantedEvent, BlogArchivedEvent
* Bad: PostCreateEvent (present-tense, should be past-tense)
* Bad: CreatedPostEvent (first subject, then verb)
* Bad: PostCreated (should end in `Event`)
* Bad: InsertUserEvent (Use business terminology, for example: UserSignedUp)
