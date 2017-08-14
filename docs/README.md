[![4klift](https://raw.githubusercontent.com/deasilworks/4klift/master/assets/4KLIFT_Logo_Horizontal_thumb.png)][4klift]

Getting Started
===============

For this **getting started** tutorial we are going to write a log collector and reporting web site and service with 4klift.

The Apache Cassandra database is designed for very a high number
of simultaneous read and write operations.

> **This tutorial is in-progress and will be added to as 4klift is
built up from new development and our own existing refactored libraries.**

## 4klift Requires:

  - [Composer](https://getcomposer.org/ "Composer")
  - [VirtualBox](https://www.virtualbox.org/ "VirtualBox")
  - [Vagrant](https://www.vagrantup.com/ "Vagrant")
  
**The 4klift-se (Silex Edition)** project includes a configured virtual 
machine with everyting you need to develop a new project. 

>See the [VM.md] documentation for a current list of applications 
and services.  

## 1. Create Project

Open a terminal on your workstation and create a new directory 
called `collector`. In this directory use `composer` to create
a new **4klift-se** project. After 'composer' has downloaded the
project skeleton use `vagrant` to boot and provision the virtual
machine.

```
$ mkdir collector
$ cd collector
$ composer -n --no-install --ignore-platform-reqs create-project deasilworks/4klift-se . 1.0.x-dev
$ vagrant up
$ vagrant ssh
````

Once the virtual machine is booted it will use `composer` internally
to download dependencies and configure and autoloader. We do this
installation on the virtual machine to reduce the number on dependencies 
on a developer's individual workstation. This ensures everyone on the team
has a common environment that meets the minimum requirements.

Once the virtual machine has completed provisioning you will see:

```
==> default: ----------------------------------------------------
==> default:
==> default:  _  _   _    _ _  __ _
==> default: | || | | | _| (_)/ _| |_
==> default: | || |_| |/ / | | |_| __|
==> default: |__   _|   <| | |  _| |_
==> default:    |_| |_|\_\_|_|_|  \__|
==> default:
==> default:
==> default: Install complete.
==> default:
==> default: Browse to http://localhost:8080/
==> default:
==> default: Run: vagrant ssh to use the new vm.
==> default:
```


When running, browse to `http://localhost:8080`.

Or, add the following line (or replace with your own domain) to your 
workstation's *hosts* file. 

```
192.168.222.11 collector.vm.deasil.works
```

> On macOS you can use the included [vi] editor with the command: `vi /etc/hosts`

... and browse to `http://collector.vm.deasil.works`.

## 2. Login to the [virtual machine][VM.md]

From the command line, in the project directory use vagrant to **ssh into
the new virtual machine**:

```
$ vagrant ssh
```

You will get a screen similar to the following:

```
~/workspace/collector
$ vagrant ssh
Last login: Sat Aug 12 15:55:52 2017 from 10.0.2.2

 _  _   _    _ _  __ _
| || | | | _| (_)/ _| |_
| || |_| |/ / | | |_| __|
|__   _|   <| | |  _| |_
   |_| |_|\_\_|_|_|  \__|

MOTD.txt:
+-------------------------------------------------------------------------+
| - Web access ....... http://localhost:8080                              |
| - 4klift Source .... https://github.com/deasilworks/4klift              |
| - 4klift CLI ....... ./core/cli --ansi                                  |
| - Update Composer .. sudo /usr/local/bin/composer/composer self-update` |
| - Cassandra CLI .... cqlsh                                              |
+-------------------------------------------------------------------------+

vagrant@4klift.vm.deasil.works (192.168.222.11) ~/project
$ _
```

## 3. Create a [Keyspace] in Cassandra.

Next, create a Cassandra Keyspace called `collector`. The new Keyspace will hold 
our data **tables** (aka. Column Families).

> Keyspaces are similar what Oracle calls a `tablespace` or MySQL calls a `database`.

After logging in, you will need to connect to the Cassandra node running on the 
virtual machine using [cqlsh].

```
$ cqlsh
```

`cqlsh` will provide a prompt.

```
vagrant@4klift.vm.deasil.works (192.168.222.11) ~/project
$ cqlsh
Connected to Test Cluster at 127.0.0.1:9042.
[cqlsh 5.0.1 | Cassandra 3.9.0 | CQL spec 3.4.2 | Native protocol v4]
Use HELP for help.
cqlsh> _
```

Once connected to Cassandra, add the Keyspace `collector`:

```
cqlsh> CREATE KEYSPACE collector WITH replication = {'class': 'SimpleStrategy', 'replication_factor': '1'};
cqlsh> _
```

Use the new [Keyspace].

```
cqlsh> USE collector;
cqlsh:collector> _
```

## 3. Create a [Table] in the new collector keyspace.

Use a text editor to design a **log** table then cut-and-paste the new table create statement into [cqlsh]. Use
the following example if you are following along with this tutorial.

```
cqlsh:collector> CREATE TABLE log (
             ...     client text,
             ...     type text,
             ...     day int,
             ...     date timestamp,
             ...     event_uuid uuid,
             ...     log_uuid uuid,
             ...     level text,
             ...     context text,
             ...     payload text,
             ...     PRIMARY KEY ((client, type, day), date)
             ... );
cqlsh:collector>_
```

#### 4. Model the new table.

First you will need to create a directory for your PHP source code and update composer.json with a 
new [psr-4] namspace.

Create the directory structure of the root of the collector project:

```
src/
└── deasilworks/
    └── collector/
        └── tests/
        └── docs/
        └── src/
            └── CEF/
                └── Data/
                    └── Model/
                        └── LogDataModel.php
```

Since the project directory is mounted on the virtual machine, you can create this directory structure
on the virtual machine, in your IDE or directly on your workstation.

4klift does not require that you use this structure or even [psr-4], however we highly recommend this
or a similar level of organization. In this tutorial we will  be abstracting data and domain (business 
logic) in two separate sets of classes and exposing most or our domain logic to a web facing API.
 
**Update your composer.json*** with the following:

```
"autoload": {
    "psr-4": {
      "deasilworks\\Collector\\": "src/deasilworks/collector/src"
    }
},
```
To follow along with this tutorial, you are replacing the entire "autoload" key in the composer.json
with the code above.

You will need to run `composer install` again on the virtual machine in order to let the autoloader
know about your new class path. You can exit `cqlsh` by typing `exit`, or simply open a new terminal 
and run `vagrant ssh`.

On the virtual machine run `composer install`, you should see the following output:

```
vagrant@4klift.vm.deasil.works (192.168.222.11) ~/project
$ composer install
Loading composer repositories with package information
Installing dependencies (including require-dev) from lock file
Nothing to install or update
Generating autoload files
> cp ./vendor/deasilworks/4klift-core/index.php ./web/index.php
> cp ./vendor/deasilworks/4klift-core/4klift.php ./core/4klift.php
> cp ./vendor/deasilworks/4klift-core/cli.php ./core/cli.php
vagrant@4klift.vm.deasil.works (192.168.222.11) ~/project
$ _
```

## 5. Model the new table with the [LogDataModel] PHP class.

Using your preferred IDE (or text editor), create a PHP class called **LogDataModel** in the new
**LogDataModel.php** file. It should look like the following:

```php
<?php

namespace deasilworks\Collector\CEF\Data\Model;

use deasilworks\CEF\EntityDataModel;
use JMS\Serializer\Annotation\Exclude;

/**
 * Class LogDataModel
 * 
 * Responsible for modeling log data stored
 * in the log table of the database.
 */
class LogDataModel extends EntityDataModel
{
    /**
     * Overwritten to supply a Table Name.
     *
     * @Exclude()
     *
     * @var string
     */
    protected $tableName = 'log';

} 
```

With the new **LogDataModel** class you are extending the **[EntityDataModel]** class from
[deasilworks\CEF] and overwriting the `$tableName` parameter to indicate the physical table this 
Model is abstracting. 

The `@Exclude()` annotation is important in order to keep the `$tableName` property from being 
serialized, since this is not part of the physical model.

### Adding properties to the class:

Each column in the Cassandra `collector.log` table needs to be represented by a [property] in the
new **LogDataModel** class, along with a corresponding getter and setter. See the table below:

| Column     | [Type]      | Property   | [PHP-Type]   | Setter                 |  Getter          |
| ---------- | ----------- | ---------- | ------------ | ---------------------- | ---------------- |
| client     | text        | $client    | string       | setClient(...)         | getClient()      |
| type       | text        | $type      | string       | setType(...)           | getType()        |
| day        | int         | $day       | int          | setDay(...)            | getDay()         |
| date       | [timestamp] | $date      | \\[DateTime] | setDate([DateTime]...) | getDate()        |
| event_uuid | [uuid]      | $eventUuid | string       | setEventUuid(...)      | getetEventUuid() |
| log_uuid   | [uuid]      | $logUuid   | string       | setLogUuid(...)        | getLogUuid()     |
| level      | text        | $level     | string       | setLevel(...)          | getLevel()       |
| context    | text        | $context   | string       | setContext(...)        | getContext()     |
| payload    | text        | $payload   | string       | setPayload(...)        | getPayload()     |

See an example [Gist of the **[LogDataModel]**.

It is **important** to note that setters requiring specific type other than basic scalar need to be type
hinted. The setDate method requires a [DateTime] object and is properly type hinted in the example below:

```php
/**
 * @param \DateTime $date
 * @return LogDataModel
 */
public function setDate(\DateTime $date)
{
    $this->date = $date;
    return $this;
}
```

## 6. Model a collection of [LogDataModel]s.

Next we will create a very small class to model a collection. Since our `collector.log` table stores
a collection of [LogDataModel]s we will abstract just as we did the model, by
 creating a **[LogDataCollection]**.

Create a new directory called `Collection` under the `Data` directory and add the file
`LogDataCollection.php`. Your file structure should now look like this:

```
src/
└── deasilworks/
    └── collector/
        └── tests/
        └── docs/
        └── src/
            └── CEF/
                └── Data/
                    ├── Collection/
                    │   └── LogDataCollection.php
                    └── Model/
                        └── LogDataModel.php
``` 

Create a PHP class called [LogDataCollection] in the new [LogDataCollection].php file. 
It should look like the following:

```php
<?php

namespace deasilworks\Collector\CEF\Data\Collection;

use deasilworks\CEF\ResultContainer;
use deasilworks\Collector\CEF\Data\Model\LogDataModel;

/**
 * Class LogDataCollection.
 *
 * Responsible for providing an iterator object, sorting, 
 * math, filtering and aggregation functions specifically 
 * related to LogDataModels.
 */
class LogDataCollection extends ResultContainer
{
    /**
     * Overwritten to customize value Class.
     *
     * @var string
     */
    protected $valueClass = LogDataModel::class;
    
}
```

The **LogDataCollection** is coupled to the **LogDataModel** through the protected
property $valueClass.

>This is all the work that is required for the new collection class. All the needed functionality
is inherited from the **ResultContainer**. You may wish to further customize this class, to add
specific functionality like aggregation or math functions that opperate on the specific type
of data models this collection holds. Customizing the **[LogDataCollection]** will be covered later.

## 7. Managing Log Data.

We have modeled our `collector.log` keys with the [LogDataModel] and have a [LogDataCollection] to contain
them. We have abstration similar to our underlying database, where [LogDataModel] is a row of data and 
[LogDataCollection] is the table itself.

Our database support CRUD (Create, Read, Update, Delete) opperations which also need to be abstracted.
This will be done with a new **LogDataManager.php** class.

Create a new directory called `Manager` under the `Data` directory and add the file `LogDataManager.php`. 
Your file structure should now look like this:

```
src/
└── deasilworks/
    └── collector/
        └── tests/
        └── docs/
        └── src/
            └── CEF/
                └── Data/
                    ├── Collection/
                    │   └── LogDataCollection.php
                    ├── Manager/
                    │   └── LogDataManager.php
                    └── Model/
                        └── LogDataModel.php
``` 

Create a PHP class called **LogdDataManager** in the new **LogDataManager.php** file. 
It should look like the following:

```php
<?php

namespace deasilworks\Collector\CEF\Data\Manager;

use deasilworks\CEF\EntityDataManager;
use deasilworks\Collector\CEF\Data\Collection\LogDataCollection;

/**
 * Class PageDataManager.
 * 
 * Responsible for providing CRUD operations
 * on LogDataModels and providing LogDataCollections.
 */
class PageDataManager extends EntityDataManager
{
    /**
     * Overwritten to customize Collection Class.
     *
     * @var string
     */
    protected $collectionClass = LogDataCollection::class;

}
```

The **PageDataManager** is coupled to the **LogDataCollection** through the protected
property $collectionClass. Next, our **PageDataManager** needs to provide some simple CRUD methods in 
order to interact with the newly created model and collection.

### Create & Update Method (Setter)

Add a **setLog** method to the new **PageDataManager** like the following:

```php
/**
 * Create or Update a log entry.
 *
 * @param LogDataModel $logDataModel
 *
 * @return true
 */
public function setLog(LogDataModel $logDataModel)
{
    $logDataModel
        ->setEntityManager($this)
        ->save();

    return true;
}
```

>While there are more sophisticated was to set our data, this is the basic method. For large models, or models where
we only need to update a single column, it is more efficient to update the column directly. In Cassandra, some columns
may contain sets or maps up data that get added to updated on a deeper level. These will be handled by additional 
methods specific to those unique concerns. In this is collector tutorial we don't have a need to update a row in 
`collector.log` so a simple save of the model is all that is needed.

### Test

We now have a way of setting Log data and enough code to conduct a useful unit test. We can ensure the proper
operation our new setter and add a valuable unit test to our testing suite.

Add the file `LogDataTest.php` to the `tests` directory in the `Collector` library. The structure should
resemble the following:

```
src/
└── deasilworks/
    └── collector/
        └── tests/
            └── LogDataTest.php
``` 

Create a PHP class called **LogDataTest** in the new **LogDataTest.php** file. 
It should look like the following:

```php
<?php

use deasilworks\CEF\CEF;
use deasilworks\CEF\CEFConfig;
use deasilworks\CFG\CFG;
use deasilworks\Collector\CEF\Data\Manager\LogDataManager;
use deasilworks\Collector\CEF\Data\Model\LogDataModel;

/**
 * Class ModelTest.
 *
 * @SuppressWarnings(PHPMD)
 * We do bad things here and we like it.
 */
class LogDataTest extends \PHPUnit_Framework_TestCase
{
}
```

Add a property to store and instance of **CEF** and a **setUp()** method to create an instance
of CEF for subsequent tests.

```php
/**
 * @var CEF
 */
protected $cef;

/**
 * Set up
 */
protected function setUp()
{
    if (!$this->cef) {
        $cfg = new CFG;
        $cefConfig = new CEFConfig($cfg);
        $cefConfig
            ->setKeyspace('collector')
            ->setContactPoints(['127.0.0.1']);

        $this->cef = new CEF($cefConfig);
    }
}
```

> Note the **setKeyspace** and **setContactPoints** settings. These can be set per instance of CEF or 
configured in one of the configuration yamls. 

Next, add a **testSetLogData()** method. This will hold out first test. We can't test much but will know
if any exceptions occur. If the test run's without failing we should have a new `collector.log` record in
our local Cassandra node.

```php
/**
 * Set Log Data Test
 */
public function testSetLogData()
{
    /** @var LogDataManager $logDataMgr */
    $logDataMgr = $this->cef->getDataManager(LogDataManager::class);

    $logDataModel = new LogDataModel();
    $logDataModel
        ->setClient('0.0.0.0')
        ->setType('TEST_EVENT')
        ->setDay( (int)date('Ymd'))
        ->setDate(new \DateTime())
        ->setLogUuid(\deasilworks\API\UUID::getV4())
        ->setEventUuid(\deasilworks\API\UUID::getV4())
        ->setLevel('INFO')
        ->setPayload('{user} tested method {method} in class {class}.')
        ->setContext(json_encode([
            '{user}' => 'phpunit',
            '{method}' => 'setLog',
            '{class}' => 'logData'
        ]));

    $this->assertEquals('INFO', $logDataModel->getLevel());

    $logDataMgr->setLog($logDataModel);

}
```

Run `phpunit` from the `project` directory on the virtual machine. 

```
vagrant@4klift.vm.deasil.works (192.168.222.11) ~/project
$ phpunit
```

If an error occurs you will have an exception message and stack trace to help you find your bug.
Expect the following output:

```
PHPUnit 5.7.21 by Sebastian Bergmann and contributors.

..                                                                  2 / 2 (100%)

Time: 4.58 seconds, Memory: 5.75MB

OK (2 tests, 2 assertions)

Generating code coverage report in Clover XML format ... done

Generating code coverage report in HTML format ... done
vagrant@4klift.vm.deasil.works (192.168.222.11) ~/project
$ _
```

Use `cqlsh` to check Cassandra for the new record. 

```
vagrant@4klift.vm.deasil.works (192.168.222.11) ~/project
$ cqlsh
```

Turn expand on to make tables easier to read by pivoting the keys as the first column and values as the second:

```
Connected to Test Cluster at 127.0.0.1:9042.
[cqlsh 5.0.1 | Cassandra 3.9.0 | CQL spec 3.4.2 | Native protocol v4]
Use HELP for help.
cqlsh> expand on;
Now Expanded output is enabled
cqlsh>
```

Select all data from the the `collector.log` table:

```
cqlsh> select * from collector.log;

@ Row 1
------------+--------------------------------------------------------------
 client     | 0.0.0.0
 type       | TEST_EVENT
 day        | 20170814
 date       | 2017-08-14 06:57:24.000000+0000
 context    | {"{user}":"phpunit","{method}":"setLog","{class}":"logData"}
 event_uuid | 5097aa48-4c16-4ca5-8c3b-10b616cd380d
 level      | INFO
 log_uuid   | c882b862-893c-4ed7-ae56-c6d59000c76e
 payload    | {user} tested method {method} in class {class}.

(1 rows)
cqlsh>
```

### Create Getters

We have designed our table to satisfy two specific queries:
 1. Select all entries by client and type on a specific day.
 2. Select entries by client and type within a time range on a specific day.

These two requirements can by handled by one method. We will start with the first, let's take a look
at the `PRIMARY KEY` portion of our table. In the `cqlsh` terminal issue the command `DESC collector.log` and
note the following line:

`PRIMARY KEY ((client, type, day), date)`

>Cassandra tables are [designed to satisfy data retrial requirements][cas-modeling], in other words, they are not 
designed to [normalize] data. The goal is not a consideration of physical storage efficiencies but instead focusing 
purely on read and write performance. Because of this each table is organized to meet the needs or one or more queries 
against it. 

The [Partition Key][cas-keys] for our `collector.log` table, requires `client`, `type` and `day`, so this means our new
method will require these. The `date` portion of the Primary Key is considered a [Clustering Key][cas-clustering] and 
can be used to further filter the entry. 
 


##### This open-source project is brought to you by [Deasil Works, Inc.](http://deasil.works/) Copyright &copy; 2017 Deasil Works, Inc.

[psr-4]: http://www.php-fig.org/psr/psr-4/
[VM.md]: https://github.com/deasilworks/4klift/blob/master/skeleton-se/VM.md "4klift Virtual Machine"
[4klift]: https://github.com/deasilworks/4klift
[vi]: https://en.wikipedia.org/wiki/Vi
[Keyspace]: http://docs.datastax.com/en/cql/3.3/cql/cql_reference/cqlCreateKeyspace.html
[cqlsh]: http://docs.datastax.com/en/cql/3.3/cql/cql_reference/cqlsh.html
[deasilworks\CEF]: https://github.com/deasilworks/cef
[table]: https://docs.datastax.com/en/cql/3.3/cql/cql_reference/cqlCreateTable.html
[EntityDataModel]: https://github.com/deasilworks/4klift/blob/master/src/deasilworks/cef/docs/api/deasilworks-CEF-EntityDataModel.md
[property]: http://php.net/manual/en/language.oop5.properties.php
[DateTime]: http://php.net/manual/en/class.datetime.php
[timestamp]: http://docs.datastax.com/en/cql/3.3/cql/cql_reference/timestamp_type_r.html
[type]: http://docs.datastax.com/en/cql/3.3/cql/cql_reference/cql_data_types_c.html
[PHP-Type]: http://php.net/manual/en/language.types.php
[uuid]: http://docs.datastax.com/en/cql/3.3/cql/cql_reference/timeuuid_functions_r.html
[LogDataModel]: https://github.com/deasilworks/collector/blob/master/src/deasilworks/collector/src/CEF/Data/Model/LogDataModel.php
[LogDataCollection]: https://github.com/deasilworks/collector/blob/master/src/deasilworks/collector/src/CEF/Data/Collection/LogDataCollection.php
[normalize]: https://en.wikipedia.org/wiki/Database_normalization
[cas-modeling]: https://www.datastax.com/dev/blog/basic-rules-of-cassandra-data-modeling
[cas-keys]: https://www.datastax.com/dev/blog/the-most-important-thing-to-know-in-cassandra-data-modeling-the-primary-key
[cas-clustering]: https://stackoverflow.com/questions/24949676/difference-between-partition-key-composite-key-and-clustering-key-in-cassandra