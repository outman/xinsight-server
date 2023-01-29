# XInsight backend server
- [Web frontend ui](https://github.com/outman/XInsight)
# Config

## .env
```ini
cp .env.example .env
composer run start
```

## Admin user
```ini
.env SYSTEM_USERS
```

# xhprof extension data
```php

// start
xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);


// register shutdown function to record xhprof result
register_shutdown_function(function() use ($request, $mongoHost, $mongoOptions) {
    try {
        $profile = xhprof_disable();
        $value = [
            'url'          => $request->getUri()->getPath(),
            'server_name'  => gethostname(),
            'request_time' => $_SERVER['REQUEST_TIME'],
            'profile'      => json_encode(['profile' => $profile]),
            'mu'           => $profile['main()']['mu'],
            'pmu'          => $profile['main()']['pmu'],
            'ct'           => $profile['main()']['ct'],
            'cpu'          => $profile['main()']['cpu'],
            'wt'           => $profile['main()']['wt'],
            'create_at'    => new \MongoDate(),
        ];
        
        // save data
        $mongo = new \MongoClient($mongoHost, $mongoOptions);
        $collection = $mongo->selectDB("xhprof")->selectCollection("xhprof");
        $collection->insert($value);
    } catch (\Throwable $e) {
        \error_log('xhprof error:' . $e->getMessage());
    }
});
```
