# identity-verify

Identity verify SDK.

## Installing

```shell
$ composer require jimchen/identity-verify -vvv
```

## Usage

```php
require __DIR__ . '/vendor/autoload.php';

use JimChen\Identity\Identity;
use JimChen\Identity\Exceptions\NoGatewayAvailableException;

$identity = new Identity([
	'default' => [
		'gateways' => [
			'juhe'
		]
	],

	'gateways' => [
		'juhe' => [
			'key'    => 'your app key',
			'openid' => 'your openid',
		]
	]
]);

try {
	var_dump($identity->verify('your name', 'chinese mainland identity card number'));
} catch (NoGatewayAvailableException $e) {
	var_dump($e->getResults());
}
```

## License

MIT