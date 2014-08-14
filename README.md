= merry\\Config

A merry configuration container.

Example:
```php
use merry\Config;

$config = new Config([
    "db" => [
        "dsn" => "user=mike", 
        "flags" => pq\Connection::PERSISTENT
    ],
    "cache" => [
        "pid" => "cluster1",
        "hosts" => ["10.0.1.1", "10.0.1.2", "10.0.1.3"]
    ]
]);

printf("Using database: '%s'\n", $config->db->dsn);
printf("Using cache cluster: '%s'\n", $config->cache->pid);

$config->apply([
    "db" => function($conf) {
        return new pq\Connection($conf->dsn, $conf->flags);
    },
    "cache" => function($conf) {
        $cache = new Memcached($conf->pid);
        foreach ($conf->{$conf->pid}->hosts as $host) {
            $cache->addServer($host);
        }
        return $cache;
    }
]);


extract($config->toArray());

if (!($q1 = $cache->get("q1"))) {
    $result = $db->exec("SELECT 1");
    $cache->set("q1", $q1 = $result->fetchAll());
}
```

Another example:
```php
use merry\Config;

$array = parse_ini_string('
[localhost]
db.dsn = "user=mike"
db.flags = 2 ;pq\Connection::PERSISTENT
cache.pid = "cluster1"
cache.cluster1.hosts[] = "10.0.1.1"
cache.cluster1.hosts[] = "10.0.1.2"
cache.cluster1.hosts[] = "10.0.1.3"
[production : localhost]
db.dsn = "user=app"
');

$config = new Config($array, getenv("APPLICATION_ENV"));
$flags = \RecursiveTreeIterator::BYPASS_CURRENT;
foreach (new \RecursiveTreeIterator($config, $flags) as $key => $val ) {
    printf("%s: %s\n", $key, ($val instanceof Config) ? "" : $val);
}
```

Output:
```
|-db: 
| |-dsn: user=app
| \-flags: 2
\-cache: 
  |-pid: cluster1
  \-cluster1: 
    \-hosts: 
      |-0: 10.0.1.1
      |-1: 10.0.1.2
      \-2: 10.0.1.3
```