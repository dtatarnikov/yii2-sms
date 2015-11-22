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
            '100' => '��������� ������� � ��������. �� ��������� �������� �� ������� �������������� ������������ ��������� � ��� �� �������, � ������� �� ������� ������, �� ������� ����������� ��������.',
            '200' => '������������ api_id',
            '201' => '�� ������� ������� �� ������� �����',
            '202' => '����������� ������ ����������',
            '203' => '��� ������ ���������',
            '204' => '��� ����������� �� ����������� � ��������������',
            '205' => '��������� ������� ������� (��������� 8 ���)',
            '206' => '����� �������� ��� ��� �������� ������� ����� �� �������� ���������',
            '207' => '�� ���� ����� (��� ���� �� �������) ������ ���������� ���������, ���� ������� ����� 100 ������� � ������ �����������',
            '208' => '�������� time ������ �����������',
            '209' => '�� �������� ���� ����� (��� ���� �� �������) � ����-����',
            '210' => '������������ GET, ��� ���������� ������������ POST',
            '211' => '����� �� ������',
            '220' => '������ �������� ����������, ���������� ���� �����.',
            '300' => '������������ token (�������� ����� ���� ��������, ���� ��� IP ���������)',
            '301' => '������������ ������, ���� ������������ �� ������',
            '302' => '������������ �����������, �� ������� �� ����������� (������������ �� ���� ���, ���������� � ��������������� ���)'
        ],
        'status' => [
            '-1' => '��������� �� �������.',
            '100' => '��������� ��������� � ����� �������',
            '101' => '��������� ���������� ���������',
            '102' => '��������� ���������� (� ����)',
            '103' => '��������� ����������',
            '104' => '�� ����� ���� ����������: ����� ����� �������',
            '105' => '�� ����� ���� ����������: ������� ����������',
            '106' => '�� ����� ���� ����������: ���� � ��������',
            '107' => '�� ����� ���� ����������: ����������� �������',
            '108' => '�� ����� ���� ����������: ���������',
            '200' => '������������ api_id',
            '210' => '������������ GET, ��� ���������� ������������ POST',
            '211' => '����� �� ������',
            '220' => '������ �������� ����������, ���������� ���� �����.',
            '300' => '������������ token (�������� ����� ���� ��������, ���� ��� IP ���������)',
            '301' => '������������ ������, ���� ������������ �� ������',
            '302' => '������������ �����������, �� ������� �� ����������� (������������ �� ���� ���, ���������� � ��������������� ���)'
        ],
        'cost' => [
            '100' => '������ ��������. �� ������ ������� ����� ������� ��������� ���������. �� ������� ������� ����� ������� ��� �����.',
            '200' => '������������ api_id',
            '202' => '����������� ������ ����������',
            '207' => '�� ���� ����� ������ ���������� ���������',
            '210' => '������������ GET, ��� ���������� ������������ POST',
            '211' => '����� �� ������',
            '220' => '������ �������� ����������, ���������� ���� �����.',
            '300' => '������������ token (�������� ����� ���� ��������, ���� ��� IP ���������)',
            '301' => '������������ ������, ���� ������������ �� ������',
            '302' => '������������ �����������, �� ������� �� ����������� (������������ �� ���� ���, ���������� � ��������������� ���)'
        ],
        'balance' => [
            '100' => '������ ��������. �� ������ ������� �� ������� ���� ������� ��������� �������.',
            '200' => '������������ api_id',
            '210' => '������������ GET, ��� ���������� ������������ POST',
            '211' => '����� �� ������',
            '220' => '������ �������� ����������, ���������� ���� �����.',
            '300' => '������������ token (�������� ����� ���� ��������, ���� ��� IP ���������)',
            '301' => '������������ ������, ���� ������������ �� ������',
            '302' => '������������ �����������, �� ������� �� ����������� (������������ �� ���� ���, ���������� � ��������������� ���)'
        ],
        'limit' => [
            '100' => '������ ��������. �� ������ ������� �� ������� ���� ������� ������� �����������. �� ������� ������� ���������� ���������, ������������ ���� � ������� ����.',
            '200' => '������������ api_id',
            '210' => '������������ GET, ��� ���������� ������������ POST',
            '211' => '����� �� ������',
            '220' => '������ �������� ����������, ���������� ���� �����.',
            '300' => '������������ token (�������� ����� ���� ��������, ���� ��� IP ���������)',
            '301' => '������������ ������, ���� ������������ �� ������',
            '302' => '������������ �����������, �� ������� �� ����������� (������������ �� ���� ���, ���������� � ��������������� ���)'
        ],
        'senders' => [
            '100' => '������ ��������. �� ������ � ����������� �������� �� ������� ����� ���������� ������������, ������� ����� ������������ � ��������� &from= ������ sms/send.',
            '200' => '������������ api_id',
            '210' => '������������ GET, ��� ���������� ������������ POST',
            '211' => '����� �� ������',
            '220' => '������ �������� ����������, ���������� ���� �����.',
            '300' => '������������ token (�������� ����� ���� ��������, ���� ��� IP ���������)',
            '301' => '������������ ������, ���� ������������ �� ������',
            '302' => '������������ �����������, �� ������� �� ����������� (������������ �� ���� ���, ���������� � ��������������� ���)'
        ],
        'check' => [
            '100' => '��, ����� �������� � ������ ���������.',
            '300' => '������������ token (�������� ����� ���� ��������, ���� ��� IP ���������)',
            '301' => '������������ ������, ���� ������������ �� ������',
            '302' => '������������ �����������, �� ������� �� ����������� (������������ �� ���� ���, ���������� � ��������������� ���)'
        ],
        'add' => [
            '100' => '����� �������� � ��������.',
            '202' => '����� �������� � ������������ �������'
        ],
        'del' => [
            '100' => '����� ������ �� ���������.',
            '202' => '����� �������� � ������������ �������'
        ],
        'get' => [
            '100' => '������ ���������. �� ����������� �������� ����� ���� ������ ���������, ��������� � ��������� � ������� �����;����������.'
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