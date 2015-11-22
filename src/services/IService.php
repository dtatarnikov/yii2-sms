<?php
namespace strong2much\sms\services;

/**
 * Interface for all sms services
 *
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
interface IService
{
    /**
     * Sends sms
     * @param string $to phone number
     * @param string $text sms text
     * @param array $config additional config for sms
     * @return array response
     */
    function send($to, $text, $config = []);

    /**
     * Check message status
     * @param integer $id sms message id
     * @return array response
     */
    function status($id);

    /**
     * Check user balance
     * @return array answer
     */
    function balance();
} 