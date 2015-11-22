# yii2-sms

This extension is using for works with different sms services.

Installation
------------

Install package by composer
```composer
{
    "require": {
       "strong2much/yii2-sms": "dev-master"
    }
}

Or

$ composer require strong2much/yii2-sms "dev-master"
```

Use the following code in your configuration file. You can use different services
```php
'sms' => [
    'class' => 'strong2much\sms\SmsManager'
    'serviceConfig' => [
        'class' => 'strong2much\sms\services\SmsRuService',
        'apiId' => '',
    ]
]
```

Now you can simple use it like so:
```php
$sms = Yii::$app->sms->getService();
$response = $sms->send('+79990005555', 'Hello, world!');
```
