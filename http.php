#!/usr/bin/php
<?php
use UI\UI;

define('W_DIR', __DIR__);
define('LIB_UI', dirname(W_DIR) . '/php-libui');
include LIB_UI . '/src/UI.php';
class HTTP
{
    public static $ui;
    public static $table;
    public $config = [];
    public static $requestList = [];
    public static $currentRequest = -1;
    const HTTP_METHOD = ['GET', 'POST'];

    const REQUEST_CONFIG = W_DIR . '/config/request.php';
    public function __construct($argv, $argc)
    {
        if ($argc < 2 || array_search('--nodaemon', $argv) === false) {
            $this->daemon();
        }
        self::$ui = new UI(LIB_UI . '/shared/libui.so');
        $this->config = include W_DIR . '/config/ui.php';

        $this->config['close'] = self::$ui->event(function ($ui) {
            $ui->quit();
            return 1;
        });
        $build = self::$ui->build($this->config);
        $this->loadRequest();
        $build->show();
    }
    public function loadRequest()
    {
        if (file_exists(self::REQUEST_CONFIG)) {
            self::$requestList = include self::REQUEST_CONFIG;
        }
        self::$table = self::getControl('requestList');
        foreach (self::$requestList as $config) {
            self::$table->addRow([$config['name'], 0]);
        }
    }
    public static function getControl($id)
    {
        return self::$ui->build->getControlById($id);
    }
    public static function onChangeRequestName($mh, $tm, $row, $col, $val)
    {
        self::$requestList[$row]['name'] = $val;
        self::$table->updateRowColumValue($row, $col, $val);
        self::saveRequestList();
    }
    protected static function saveRequestList()
    {
        $code = '<?php return ' . var_export(self::$requestList, true) . ';';
        file_put_contents(self::REQUEST_CONFIG, $code);
    }
    public static function onSelectRequest($mh, $tm, $row, $col, $val)
    {
        if ($val == 1) {
            self::$table->setColumAllValue($col, 0);
            self::fillFormData(self::$requestList[$row]);
        }
        self::$currentRequest = $row;
        self::$table->updateRowColumValue($row, $col, $val);
    }
    public static function fillFormData($request)
    {
        $inputIds = ['method', 'url', 'header', 'body'];
        foreach ($inputIds as $id) {
            self::getControl($id)->setValue($request[$id]);
        }
    }
    public static function getFormData()
    {
        $inputIds = ['method', 'url', 'header', 'body'];
        $res = [];
        foreach ($inputIds as $id) {
            $res[$id] = self::getControl($id)->getValue();
        }
        return $res;
    }
    public static function onSave()
    {
        if (self::$currentRequest < 0) {
            return self::onSaveAs();
        }
        $ret = self::getFormData();
        self::$requestList[self::$currentRequest] = $ret;
        self::saveRequestList();
    }
    public static function onSaveAs()
    {
        $ret = self::getFormData();
        $ret['name'] = 'No-Name';
        self::$currentRequest = count(self::$requestList);
        self::$requestList[self::$currentRequest] = $ret;
        self::saveRequestList();
        self::$table->setColumAllValue(1, 0);
        self::$table->addRow([$ret['name'], 1]);
    }
    public static function onRequest()
    {
        $params = self::getFormData();
        $op = [];
        $hst = explode("\n", $params['header']);
        $hs = [];
        foreach ($hst as $v) {
            $hs[] = trim($v);
        }
        $url = $params['url'];
        if (self::HTTP_METHOD[$params['method']] == 'POST') {
            $op[CURLOPT_POST] = 1;
        }
        $op[CURLOPT_HTTPHEADER] = $hs;

        $op[CURLOPT_RETURNTRANSFER] = 1;
        if (!empty($params['body'])) {
            $op[CURLOPT_POSTFIELDS] = $params['body'];
        }
        $isJson = false;
        $op[CURLOPT_HEADERFUNCTION] = function ($ch, $h) use (&$isJson) {
            if (self::checkType($h, ['text/json', 'application/json'])) {
                $isJson = true;
            }
            return strlen($h);
        };

        $ch = curl_init(trim($url));
        curl_setopt_array($ch, $op);
        $ret = curl_exec($ch);
        if ($isJson) {
            $json = json_decode($ret, true);
            if ($json) {
                $c = var_export($json, true);
                self::getControl('outputText')->setValue($c);
            } else {
                self::getControl('outputText')->setValue($ret);
            }
        } else {
            $str = 'save to ' . W_DIR . '/output.html';
            self::getControl('outputText')->setValue($str);
            file_put_contents(W_DIR . '/output.html', $ret);
        }

        curl_close($ch);
    }
    public static function checkType($h, $type)
    {
        if (stripos($h, 'Content-Type:') === 0) {
            if (is_array($type)) {
                foreach ($type as $v) {
                    return stripos($h, $v) > 13;
                }
            } else {
                return stripos($h, $type) > 13;
            }
            return true;
        }
        return false;
    }

    public static function daemon()
    {
        $pid = pcntl_fork();
        if ($pid > 0) {
            exit;
        } else if ($pid < 0) {
            throw new \RuntimeException('process fork fail');
        }

        chdir('/');
        umask('0');
        posix_setsid();
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
        $pid = pcntl_fork();
        if ($pid > 0) {
            exit;
        } else if ($pid < 0) {
            throw new \RuntimeException('process fork fail');
        }
    }
}

new HTTP($argv, $argc);
