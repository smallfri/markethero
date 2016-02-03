# EmailONE sdk

The EmailOne SDK for php enables developers to easily work with EmailOne.

## Getting Started

1. To get started, download this SDK and upload it to your server.
2. Open /examples/setup.php.
3. In the following code, insert your public and private api key, available [here](http://www.emailone.net/customers/api-keys/index)

```
$config = new EmailOneApi_Config(array(
    'apiUrl'        => 'http://www.emailone.net/api',
    'publicKey'     => 'YOUR_PUBLIC_KEY',
    'privateKey'    => 'YOUR_PRIVATE_KEY',
    'components' => array(
        'cache' => array(
            'class'     => 'EmailOneApi_Cache_File',
            'filesPath' => dirname(__FILE__) . '/../EmailOneApi/Cache/data/cache', // make sure it is writable by webserver
        )
    ),
));
```
See the examples in the [wiki](https://github.com/smallfri/EmailOne-SDK/wiki) for more information.

