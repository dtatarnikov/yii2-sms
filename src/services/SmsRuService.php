<?php
namespace strong2much\sms\services;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Object;

/**
 * SmsRuService represents the sms service for sms.ru
 *
 * @author   Denis Tatarnikov <tatarnikovda@gmail.com>
 */
class SmsRuService extends Object implements IService
{
    const HOST  = 'http://sms.ru/';
    const SEND = 'sms/send?';
    const STATUS = 'sms/status?';
    const COST = 'sms/cost?';
    const BALANCE = 'my/balance?';
    const LIMIT = 'my/limit?';
    const SENDERS = 'my/senders?';
    const GET_TOKEN = 'auth/get_token';
    const CHECK = 'auth/check?';
    const ADD = 'stoplist/add?';
    const DEL = 'stoplist/del?';
    const GET = 'stoplist/get?';
    const UCS = 'sms/ucs?';

    /**
     * @var string api id
     */
    public $apiId;
    /**
     * @var string use login
     */
    public $login;
    /**
     * @var string user password
     */
    public $password;
    /**
     * @var string default sender (from)
     */
    public $sender;

    private $_token;
    private $_sha512;
    private $_params;

    /**
     * @var array response codes for all methods
     */
    private $_responseCodes = [
        'send' => [
            '100' => 'Сообщение принято к отправке. На следующих строчках вы найдете идентификаторы отправленных сообщений в том же порядке, в котором вы указали номера, на которых совершалась отправка.',
            '200' => 'Неправильный api_id',
            '201' => 'Не хватает средств на лицевом счету',
            '202' => 'Неправильно указан получатель',
            '203' => 'Нет текста сообщения',
            '204' => 'Имя отправителя не согласовано с администрацией',
            '205' => 'Сообщение слишком длинное (превышает 8 СМС)',
            '206' => 'Будет превышен или уже превышен дневной лимит на отправку сообщений',
            '207' => 'На этот номер (или один из номеров) нельзя отправлять сообщения, либо указано более 100 номеров в списке получателей',
            '208' => 'Параметр time указан неправильно',
            '209' => 'Вы добавили этот номер (или один из номеров) в стоп-лист',
            '210' => 'Используется GET, где необходимо использовать POST',
            '211' => 'Метод не найден',
            '220' => 'Сервис временно недоступен, попробуйте чуть позже.',
            '300' => 'Неправильный token (возможно истек срок действия, либо ваш IP изменился)',
            '301' => 'Неправильный пароль, либо пользователь не найден',
            '302' => 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс)'
        ],
        'status' => [
            '-1' => 'Сообщение не найдено.',
            '100' => 'Сообщение находится в нашей очереди',
            '101' => 'Сообщение передается оператору',
            '102' => 'Сообщение отправлено (в пути)',
            '103' => 'Сообщение доставлено',
            '104' => 'Не может быть доставлено: время жизни истекло',
            '105' => 'Не может быть доставлено: удалено оператором',
            '106' => 'Не может быть доставлено: сбой в телефоне',
            '107' => 'Не может быть доставлено: неизвестная причина',
            '108' => 'Не может быть доставлено: отклонено',
            '200' => 'Неправильный api_id',
            '210' => 'Используется GET, где необходимо использовать POST',
            '211' => 'Метод не найден',
            '220' => 'Сервис временно недоступен, попробуйте чуть позже.',
            '300' => 'Неправильный token (возможно истек срок действия, либо ваш IP изменился)',
            '301' => 'Неправильный пароль, либо пользователь не найден',
            '302' => 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс)'
        ],
        'cost' => [
            '100' => 'Запрос выполнен. На второй строчке будет указана стоимость сообщения. На третьей строчке будет указана его длина.',
            '200' => 'Неправильный api_id',
            '202' => 'Неправильно указан получатель',
            '207' => 'На этот номер нельзя отправлять сообщения',
            '210' => 'Используется GET, где необходимо использовать POST',
            '211' => 'Метод не найден',
            '220' => 'Сервис временно недоступен, попробуйте чуть позже.',
            '300' => 'Неправильный token (возможно истек срок действия, либо ваш IP изменился)',
            '301' => 'Неправильный пароль, либо пользователь не найден',
            '302' => 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс)'
        ],
        'balance' => [
            '100' => 'Запрос выполнен. На второй строчке вы найдете ваше текущее состояние баланса.',
            '200' => 'Неправильный api_id',
            '210' => 'Используется GET, где необходимо использовать POST',
            '211' => 'Метод не найден',
            '220' => 'Сервис временно недоступен, попробуйте чуть позже.',
            '300' => 'Неправильный token (возможно истек срок действия, либо ваш IP изменился)',
            '301' => 'Неправильный пароль, либо пользователь не найден',
            '302' => 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс)'
        ],
        'limit' => [
            '100' => 'Запрос выполнен. На второй строчке вы найдете ваше текущее дневное ограничение. На третьей строчке количество сообщений, отправленных вами в текущий день.',
            '200' => 'Неправильный api_id',
            '210' => 'Используется GET, где необходимо использовать POST',
            '211' => 'Метод не найден',
            '220' => 'Сервис временно недоступен, попробуйте чуть позже.',
            '300' => 'Неправильный token (возможно истек срок действия, либо ваш IP изменился)',
            '301' => 'Неправильный пароль, либо пользователь не найден',
            '302' => 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс)'
        ],
        'senders' => [
            '100' => 'Запрос выполнен. На второй и последующих строчках вы найдете ваших одобренных отправителей, которые можно использовать в параметре &from= метода sms/send.',
            '200' => 'Неправильный api_id',
            '210' => 'Используется GET, где необходимо использовать POST',
            '211' => 'Метод не найден',
            '220' => 'Сервис временно недоступен, попробуйте чуть позже.',
            '300' => 'Неправильный token (возможно истек срок действия, либо ваш IP изменился)',
            '301' => 'Неправильный пароль, либо пользователь не найден',
            '302' => 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс)'
        ],
        'check' => [
            '100' => 'ОК, номер телефона и пароль совпадают.',
            '300' => 'Неправильный token (возможно истек срок действия, либо ваш IP изменился)',
            '301' => 'Неправильный пароль, либо пользователь не найден',
            '302' => 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс)'
        ],
        'add' => [
            '100' => 'Номер добавлен в стоплист.',
            '202' => 'Номер телефона в неправильном формате'
        ],
        'del' => [
            '100' => 'Номер удален из стоплиста.',
            '202' => 'Номер телефона в неправильном формате'
        ],
        'get' => [
            '100' => 'Запрос обработан. На последующих строчках будут идти номера телефонов, указанных в стоплисте в формате номер;примечание.'
        ]
    ];

    /**
     * Base initiation of queue service
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!function_exists ('curl_init')) {
            throw new InvalidConfigException(Yii::t('sms', 'cURL extension required'));
        }

        if((!isset($this->apiId)) && (!isset($this->login) && !isset($this->password))) {
            throw new InvalidConfigException(Yii::t('sms', '"api_id", or "login" and "password" must be provided'));
        }

        $this->_params = $this->getDefaultParams();

        parent::init();
    }

    /**
     * Sends sms
     * @param string $to phone number
     * @param string $text sms text
     * @param array $config additional config for sms
     * @return array response
     */
    public function send($to, $text, $config = [])
    {
        $url = self::HOST . self::SEND;
        $params = $this->_params;

        $params['to'] = $this->optimizePhone($to);
        $params['text'] = $text;

        if($this->sender) {
            $params['from'] = $this->sender;
        }

        if (isset($config['time']) && $config['time'] < ( time() + 7 * 60 * 60 * 24)) {
            $params['time'] = $config['time'];
        }

        if (isset($config['translit'])) {
            $params['translit'] = 1;
        }

        if (isset($config['test'])) {
            $params['test'] = 1;
        }

        $result = $this->request($url, $params);
        $result = explode("\n", $result);

        $response = [];
        $response['code'] = $result[0];
        $response['description'] = $this->getAnswer('send', $response['code']);
        unset($result[0]);

        foreach ($result as $id) {
            if (!preg_match('/=/', $id)) {
                $response['ids'][] = $id;
            } else {
                $result = explode('=', $id);
                $response[$result[0]] = $result[1];
            }
        }
        return $response;
    }

    /**
     * Check message status
     * @param integer $id sms message id
     * @return array response
     */
    public function status($id)
    {
        $url = self::HOST.self::STATUS;
        $params = $this->_params;

        $params['id'] = $id;
        $result = $this->request($url, $params);

        $response = [];
        $response['code'] = $result;
        $response['description'] = $this->getAnswer('status', $response['code']);
        return $response;
    }

    /**
     * Check user balance
     * @return array answer
     */
    public function balance()
    {
        $url = self::HOST . self::BALANCE;
        $params = $this->_params;

        $result = $this->request($url, $params);
        $result = explode("\n", $result);

        return [
            'code' => $result[0],
            'description' => $this->getAnswer('balance', $result[0]),
            'balance' => $result[1]
        ];
    }

    /**
     * Check day limit
     * @return array answer
     */
    public function limit()
    {
        $url = self::HOST . self::LIMIT;
        $params = $this->_params;

        $result = $this->request($url, $params);
        $result = explode("\n", $result);

        return [
            'code' => $result[0],
            'description' => $this->getAnswer('limit', $result[0]),
            'total' => $result[1],
            'current' => $result[2]
        ];
    }

    /**
     * Get message cost
     * @param string $to
     * @param string $text
     * @return array answer
     */
    public function cost($to, $text)
    {
        $url = self::HOST.self::COST;
        $params = $this->_params;

        $params['to'] = $this->optimizePhone($to);
        $params['text'] = $text;

        $result = $this->request($url, $params);
        $result = explode("\n", $result);

        return [
            'code' => $result[0],
            'description' => $this->getAnswer('cost', $result[0]),
            'price' => $result[1],
            'number' => $result[2]
        ];
    }

    /**
     * Get my senders list
     * @return array answer
     */
    public function senders()
    {
        $url = self::HOST . self::SENDERS;
        $params = $this->_params;
        $result = $this->request( $url, $params );
        $result = explode("\n", rtrim($result));

        $response = [
            'code' => $result[0],
            'description' => $this->getAnswer('senders', $result[0]),
            'senders' => $result
        ];
        unset($response['senders'][0]);
        $response['senders'] = array_values($response['senders']);

        return $response;
    }

    /**
     * Check user auth
     * @return array answer
     */
    public function check()
    {
        $url = self::HOST . self::CHECK;
        $params = $this->_params;

        $result = $this->request($url, $params);

        $response = [];
        $response['code'] = $result;
        $response['description'] = $this->getAnswer('check', $response['code']);

        return $response;
    }

    /**
     * Add phone to stoplist
     * @param $stoplist_phone
     * @param $stoplist_text
     * @return array response
     */
    public function stoplistAdd($stoplist_phone, $stoplist_text)
    {
        $url = self::HOST . self::ADD;
        $params = $this->_params;

        $params['stoplist_phone'] = $stoplist_phone;
        $params['stoplist_text'] = $stoplist_text;
        $result = $this->request($url, $params);

        $response = [];
        $response['code'] = $result;
        $response['description'] = $this->getAnswer('add', $response['code']);

        return $response;
    }

    /**
     * Delete phone from stoplist
     * @param $stoplist_phone
     * @return array response
     */
    public function stoplistDel($stoplist_phone)
    {
        $url = self::HOST . self::DEL;
        $params = $this->_params;

        $params['stoplist_phone'] = $stoplist_phone;
        $result = $this->request($url, $params);

        $response = [];
        $response['code'] = $result;
        $response['description'] = $this->getAnswer('del', $response['code']);

        return $response;
    }

    /**
     * Get current stoplist
     * @return array response
     */
    public function stoplistGet()
    {
        $url = self::HOST . self::GET;
        $params = $this->_params;
        $result = $this->request($url, $params);

        $result = explode("\n", rtrim($result));
        $response = [
            'code' => $result[0],
            'description' => $this->getAnswer('get', $result[0]),
            'stoplist' => $result
        ];

        $stoplist = [];
        $count = count($response['stoplist']);
        for ( $i = 1; $i < $count; $i++ ) {
            $result = explode(';', $response['stoplist'][$i]);
            $stoplist[$i - 1]['number'] = $result[0];
            $stoplist[$i - 1]['note'] = $result[1];
        }
        $response['stoplist'] = $stoplist;

        return $response;
    }

    /**
     * @return mixed response
     */
    public function smsUcs()
    {
        $url = self::HOST . self::UCS;
        $params = $this->_params;

        $result = $this->request($url, $params);

        return $result;
    }

    /**
     * @return array default params
     */
    private function getDefaultParams()
    {
        if (!empty($this->login) && !empty($this->password)) {
            $this->_token = $this->authGetToken();
            $this->_sha512 = $this->getSha512();

            $params['login'] = $this->login;
            $params['token'] = $this->_token;
            $params['sha512'] = $this->_sha512;
        } else {
            $params['api_id'] = $this->apiId;
        }
        return $params;
    }

    /**
     * @return string auth token
     */
    private function authGetToken()
    {
        $url = self::HOST . self::GET_TOKEN;
        return $this->request($url);
    }

    /**
     * @return string sha512 string for auth
     */
    private function getSha512()
    {
        if (!$this->apiId || empty($this->apiId) ) {
            return hash('sha512', $this->password . $this->_token);
        } else {
            return hash('sha512', $this->password . $this->_token . $this->apiId);
        }
    }

    /**
     * Gets answer description by code
     * @param string $key answer key
     * @param integer $code error code
     * @return string answer description
     */
    private function getAnswer($key, $code)
    {
        if (isset($this->_responseCodes[$key][$code])) {
            return $this->_responseCodes[$key][$code];
        }

        return $code;
    }

    /**
     * Make request to server
     * @param string $url url to request
     * @param array $params request params
     * @return mixed answer
     */
    protected function request($url, $params = [])
    {
        $ch = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => $params
        ];
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Optimize phone number for sending sms
     * @param string $phone phone number
     * @return string optimized phone number
     */
    protected function optimizePhone($phone)
    {
        $return = str_replace('+','',$phone);
        $phones = explode(',', $return);
        for($i=0;$i<count($phones);$i++) {
            if(substr($phones[$i],0,1)=='8') {
                $phones[$i] = substr($phones[$i], 1);
            }
        }
        $return = implode(',', $phones);

        return $return;
    }
} 