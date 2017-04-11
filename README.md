# Jejik/MT940

An MT940 bank statement parser for PHP 5.3

## Installation

You can install shaikun/jejik-mt940 using Composer. You can read more about packaging a repository  
[https://knpuniversity.com/screencast/question-answer-day/create-composer-package](https://knpuniversity.com/screencast/question-answer-day/create-composer-package "Here"). First install Composer for your project using the instructions on the
Packagist home page, then define your dependency on shaikun/jejik-mt940 in your `composer.json` file.

```json
    {
        "require": {
            "shaikun/jejik-mt940": "0.4"
        },
        "repositories": [{
            "type": "package",
            "package": {
                "name": "shaikun/jejik-mt940",
                "version": "0.4",
                "source": {
                    "url": "https://github.com/shaikun/jejik-mt940.git",
                    "type": "git",
                    "reference": "0.4"
                },
                "autoload": {
                    "psr-0" : {
                        "Jejik\\MT940" : "lib"
                    }
                }
            }
        }]
    }
```

This library follows the [PSR-0 standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md). You will need
a PSR-0 compliant autoloader to load the Jejik/MT940 classes. Composer provides one for you in your
`vendor/.composer/autoload.php`.

## Usage

```php
<?php

use Jejik\MT940\Reader;

$reader = new Reader();
$statements = $reader->getStatements(file_get_contents('mt940.txt'));

foreach ($statements as $statement) {
    echo $statement->getOpeningBalance()->getAmount() . "\n";

    foreach ($statement->getTransactions() as $transaction) {
        echo $transaction->getAmount() . "\n";
    }

    echo $statement->getClosingBalance()->getAmount() . "\n";
}
```

## Statement structure

The returned statements have the following properties. Not all banks supply
all properties (e.g. only few provide a transaction book date separately).
Properties that are not supplied will be `null`.

*   `Jejik\MT940\StatementInterface`
    *   `getNumber()` Statement sequence number
    *   `getAccount()` An object implementing `Jejik\MT940\AccountInterface`
    *   `getOpeningBalance()` An object implementing `Jejik\MT940\BalanceInterface`
    *   `getClosingBalance()` An object implementing `Jejik\MT940\BalanceInterface`
    *   `getTransactions()` An array of objects implementing `Jejik\MT940\TransactionInterface`
*   `Jejik\MT940\AccountInterface`
    *   `getNumber()` The account number
    *   `getName()` The account holder name
*   `Jejik\MT940\BalanceInterface`
    *   `getCurrency()` 3-letter ISO 4217 currency code
    *   `getAmount()` Balance amount
    *   `getDate()` Balance date as a `\DateTime` object
*   `Jejik\MT940\TransactionInterface`
    *   `getContraAccount()` An object implementing `Jejik\MT940\AccountInterface`
    *   `getAmount()` Transaction amount
    *   `getDescription()` Description text
    *   `getValueDate()` Date of the transaction as a `\DateTime`
    *   `getBookDate()` Date the transaction was booked as a `\DateTime`

## Supported banks

Currencly there are statement parsers for the following banks:

*   ABN-AMRO
*   ING
*   Rabobank
*   SNS
*   Knab
*   Triodos bank

## Adding bank parsers

You can easily add your own parser to the statement reader.

```php
<?php

use Jejik\MT940\Reader;

$reader = new Reader();
$reader->addParser('My bank', 'My\Bank');
```

When you add your own parser, the default list of parsers is cleared. You must
add them back if you want the reader to support them as well.

```php
<?php

$reader->addParsers($reader->getDefaultParsers());
```

You can also add your parser at a specific place in the parser chain. For
example, this is how you add your parser before the ING parser.

```php
<?php

$reader->addParsers($reader->getDefaultParsers());
$reader->addParser('My bank', 'My\Bank', 'ING');
```

Custom parsers should extend the `Jejik\MT940\Parser\AbstractParser` class.
Have a look at the parsers already implemented to see how to support your
bank. At the very minimum, you should implement the `accept()` method.

```php
<?php

namespace My;

use Jejik\MT940\Parser\AbstractParser;

class Bank extends AbstractParser
{
    public function accept($text)
    {
        return strpos($text, 'MYBANK') !== false);
    }
}
```

## Injecting classes

You can easily extend the build-in objects and
inject them into the MT940 reader. This allows for easily integrating MT940 into
your application. For example, by storing the statements in your database. You can
inject them using the following methods:

* `setStatementClass($className)` defaults to `Jejik\MT940\Statement`
* `setAccountClass($className)` defaults to `Jejik\MT940\Account`
* `setContraAccountClass($className)` defaults to `Jejik\MT940\Account`
* `setTransactionClass($className)` defaults to `Jejik\MT940\Transaction`
* `setOpeningBalanceClass($className)` defaults to `Jejik\MT940\Balance`
* `setClosingBalanceClass($className)` defaults to `Jejik\MT940\Balance`

You can either specify the classname as a string, or provide a PHP callable that
returns an object. Your classes do not have to extend the built-in classes but
they must implement the proper interfaces.

The callable for the `Statement` class is passed an `AccountInterface` and the statement
sequence number as parameters. The callable for the `Account` class and `ContraAccount`
class are passed the account number as parameter. The other callables are not passed
any variables.

If the callable for the `Account` class or `Statement` class returns `null` then that
statement will be skipped by the parser.

An example, integrating MT940 with your ORM:

```php
<?php

use Jejik\MT940\AccountInterface;
use Jejik\MT940\Reader;

$db = new ORM(); // Whatever your flavour is...
$reader = new Reader();

$reader->setAccountClass(function ($accountNumber) use ($db) {
    $account = $db::factory('My\Account')->findBy(array(
        'number' => $accountNumber,
    ));

    return $account ?: new My\Account();
});

$reader->setStatementClass(function (AccountInterface $account, $number) use ($db) {
    $statement = $db::factory('My\Statement')->findBy(array(
        'account' => $account->getNumber(),
        'number'  => $number,
    ));

    return $statement ?: new My\Statement();
});

$reader->setTransactionClass('My\Transaction')
       ->setContraAccountClass('My\ContraAccount')
       ->setOpeningBalanceClass('My\OpeningBalance')
       ->setClosingBalanceClass('My\ClosingBalance');

foreach ($reader->getStatements(file_get_contents('mt940.txt'))) {
    $statement->save();
}
```
