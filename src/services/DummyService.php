<?php
namespace strong2much\sms\services;

use yii\base\Object;

/**
 * DummyService represents the sms service for tests
 *
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
class DummyService extends Object implements IService
{
    /**
     * Sends sms
     * @param string $to phone number
     * @param string $text sms text
     * @param array $config additional config for sms
     * @return array response
     */
    public function send($to, $text, $config = [])
    {
        return [
            'code' => 100,
            'description' => 'OK',
        ];
    }

    /**
     * Check message status
     * @param integer $id sms message id
     * @return array response
     */
    public function status($id)
    {
        return [
            'code' => 100,
            'description' => 'OK',
        ];
    }

    /**
     * Check user balance
     * @return array answer
     */
    public function balance()
    {
        return [
            'code' => 100,
            'description' => 'OK',
            'balance' => 0.0,
        ];
    }
} 