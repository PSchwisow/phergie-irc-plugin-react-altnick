# pschwisow/phergie-irc-plugin-react-altnick

[Phergie](http://github.com/phergie/phergie-irc-bot-react/) plugin for switching to alternate nicks when primary is not available.

[![Build Status](https://secure.travis-ci.org/PSchwisow/phergie-irc-plugin-react-altnick.png?branch=master)](http://travis-ci.org/PSchwisow/phergie-irc-plugin-react-altnick) [![Code Climate](https://codeclimate.com/github/PSchwisow/phergie-irc-plugin-react-altnick/badges/gpa.svg)](https://codeclimate.com/github/PSchwisow/phergie-irc-plugin-react-altnick) [![Test Coverage](https://codeclimate.com/github/PSchwisow/phergie-irc-plugin-react-altnick/badges/coverage.svg)](https://codeclimate.com/github/PSchwisow/phergie-irc-plugin-react-altnick)

## Install

The recommended method of installation is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "pschwisow/phergie-irc-plugin-react-altnick": "dev-master"
    }
}
```

See Phergie documentation for more information on
[installing and enabling plugins](https://github.com/phergie/phergie-irc-bot-react/wiki/Usage#plugins).

## Configuration

```php
return [
    'plugins' => [
        // configuration
        new \PSchwisow\Phergie\Plugin\AltNick\Plugin([
            // At least one alternate nick
            'nicks' => [
                'Foo',
                'Foo_',
                'FooBar'
            ]
        ])
    ]
];
```

## Tests

To run the unit test suite:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
./vendor/bin/phpunit
```

## License

Released under the BSD License. See `LICENSE`.
