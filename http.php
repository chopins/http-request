#!/usr/bin/php
<?php

use UI\Control\AttributeString;
use UI\Control\Box;
use UI\Control\Button;
use UI\Control\DrawText;
use UI\Control\Input;
use UI\Event;
use UI\Struct\DrawTextAlign;
use UI\Struct\FontDescriptor;
use UI\Struct\TextLayoutParams;
use UI\Struct\UIAlign;
use UI\UI;

define('W_DIR', __DIR__);

include W_DIR . '/vendor/autoload.php';

class HTTP
{
    public static UI $ui;
    public static $table;
    public static $requestList = [];
    public static $currentRequest = -1;
    public static $logsFp = null;
    protected static $draw;
    protected static $drawHeight;
    protected static $inputIds;

    const HTTP_METHOD = ['GET', 'POST'];

    const REQUEST_CONFIG = W_DIR . '/config/request.php';
    public function __construct($argv, $argc)
    {
        if (array_search('-d', $argv)) {
            $this->daemon();
            self::logs();
        }

        self::$ui = new UI(W_DIR . '/shared/libui.so');
        //$this->config = include W_DIR . '/config/ui.php';

        $build = self::$ui->build(W_DIR . '/config/ui.xml');
        $this->loadRequest();
        $build->show();
    }
    public static function quit($e)
    {
        $e->ui()->quit();
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

    public static function onSearch(Event $e)
    {
        $txt = self::getControl('outputText')->getValue();
        $stxt =  $e->getTarget()->getValue();
        if (empty($stxt)) {
            return self::getControl('search-text')->setTitle("");
        }
        if (($idx = substr_count($txt, $stxt))) {
            self::getControl('search-text')->setTitle("Found $idx");
        } else {
            self::getControl('search-text')->setTitle('Not Found');
        }
    }
    public static function onDeleteFormInput(Event $e)
    {
        $name =  $e->getTarget()->name;
        $idx = array_search($name, self::$inputIds, true);
        $f = self::getControl('edit-body-form');
        $f->delete($idx);
        unset(self::$inputIds[$idx]);
        self::$inputIds = array_values(self::$inputIds);
        $f->hide();
        $f->show();
    }
    public static function addFormInput($e)
    {
        static $add = 0;
        self::$inputIds[] = $add;
        $f = self::getControl('edit-body-form');
        $boxAttr = ['dir' => 'h', 'child_fit' => 0, 'label' => '添加' . $add, 'stretchy' => 0];
        $box = new Box($e->build(), $boxAttr);

        $kopt = ['type' => 'text', 'id' => 'add-key' . $add];
        $key = new Input($e->build(), $kopt);
        $box->appendChild($key, $kopt);
        $vopt = ['type' => 'text', 'id' => 'add-value' . $add];
        $value = new Input($e->build(), $vopt);
        $box->appendChild($value, $vopt);

        $delopt = [
            'type' => 'text', 'title' => '删除',
            'name' => $add,
            'click' => $e->ui()->event([self::class, 'onDeleteFormInput'])
        ];
        $del = new Button($e->build(), $delopt);
        $box->appendChild($del, $delopt);

        $f->appendChild($box, $boxAttr);
        $add++;
    }
    public static function onEditBody(Event $e)
    {
        $body = self::getControl('body')->getValue();
        parse_str($body, $params);
        $childs = [];
        self::$inputIds = [];
        foreach ($params as $k => $v) {
            $box = [
                'widget' => 'grid', 'padded' => 0, 'child_fit' => 0, 'label' => $k,
                'child_left' => 0, 'child_top' => 2,
                'child_hexpand' => 0,
                'child_halign' => UIAlign::ALIGN_FILL,
                'child_vexpand' => 0,
                'child_valign' => UIAlign::ALIGN_CENTER,
                'child_height' => 50,
                'childs' => [
                    [
                        'widget' => 'input', 'type' => 'text', 'label' => $k, 'value' => $v, 'id' => $k,
                        'child_width' => 200, 'child_hexpand' => 1, 'child_vexpand' => 1,
                    ],
                    [
                        'widget' => 'button', 'name' => $k, 'child_left' => 200, 'type' => 'text', 'title' => '删除', 'child_width' => 20,
                        'click' => $e->ui()->event([self::class, 'onDeleteFormInput'])
                    ],
                ]
            ];
            $childs[] = $box;
            self::$inputIds[] = $k;
        }
        $formOp = [
            'widget' => 'box', 'dir' => 'h', 'child_fit' => 0, 'label' => '操作', 'child_height' => 50, 'child_top' => 550,
            'childs' => [
                ['widget' => 'button', 'type' => 'text', 'title' => '确定', 'click' =>
                $e->ui()->event(function ($e) {
                    $newParams = [];
                    foreach (self::$inputIds as $id) {
                        if ($id === null) {
                            continue;
                        } else if (is_int($id)) {
                            $key = self::getControl('add-key' . $id)->getValue();
                            $value = self::getControl('add-value' . $id)->getValue();
                            $newParams[$key] = $value;
                        } else {
                            $value =  self::getControl($id)->getValue();
                            $newParams[$id] = $value;
                        }
                    }

                    $newBody = http_build_query($newParams);
                    self::getControl('body')->setValue($newBody);
                    $w = self::getControl('edit-body-win');
                    $w->hide();
                    $w->destroy();
                })],
                ['widget' => 'button', 'type' => 'text', 'title' => '取消', 'click' => $e->ui()->event(function ($e) {
                    $w = self::getControl('edit-body-win');
                    $w->hide();
                    $w->destroy();
                })],
                [
                    'widget' => 'button', 'type' => 'text', 'title' => '增加一项',
                    'click' => $e->ui()->event([self::class, 'addFormInput'])
                ],
            ], 'stretchy' => 0, 'label' => '操作'
        ];

        $winGrid = [[
            'widget' => 'grid',
            'padded' => 0, 'child_fit' => 0,
            'child_width' => '900',
            'child_left' => 0, 'child_top' => 2,
            'child_hexpand' => 0,
            'child_halign' => UIAlign::ALIGN_FILL,
            'child_vexpand' => 0,
            'child_valign' => UIAlign::ALIGN_FILL,
            'childs' => [
                [
                    'widget' => 'form',
                    'id' => 'edit-body-form',
                    'padded' => 0,
                    'childs' => $childs,
                    'child_height' => 500,
                    'child_vexpand' => 1,
                    'child_hexpand' => 1,
                ],
                $formOp
            ]
        ]];
        $winConf = ['widget' => 'window', 'id' => 'edit-body-win', 'width' => 800, 'childs' => $winGrid, 'height' => 600, 'title' => 'Edit Boyd Item', 'close' => $e->ui()->event(function (Event $e) {
            $e->getTarget()->hide();
            $e->getTarget()->destroy();
        })];
        $win = $e->build()->window($winConf, false);
        $win->show();
    }
    public static function onChangeRequestName($e)
    {
        $table = $e->getTarget();
        self::$requestList[$e->row]['name'] = $e->value;
        $table->updateRowColumValue($e->row, $e->col, $e->value);
        self::saveRequestList();
    }
    protected static function saveRequestList()
    {
        $code = '<?php return ' . var_export(self::$requestList, true) . ';';
        file_put_contents(self::REQUEST_CONFIG, $code);
    }
    public static function onSelectRequest($e)
    {
        if ($e->value == 1) {
            $e->getTarget()->setColumAllValue($e->col, 0);
            self::fillFormData(self::$requestList[$e->row]);
        }
        self::$currentRequest = $e->row;
        $e->getTarget()->updateRowColumValue($e->row, $e->col, $e->value);
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

    public static function setAreaString($e)
    {
        if (self::$draw) {
            self::$draw->free();
        }
        $build = $e->build();
        $font = new FontDescriptor($build);
        $font->fill('Sans', 14);
        //$color = $build->getControlById('set-color')->getValue();

        $txt = self::getControl('outputText')->getValue();
        $string = $build->createItem(['widget' => 'string', 'string' => $txt, 'color' => 'rgba(33,33,33,0.8)']);
        $mt = $font->queryFontMetrics();
        $line = substr_count($txt, PHP_EOL);
        self::$drawHeight = ($mt['textHeight'] + $mt['maxHorizontalAdvance']) * $line;

        self::addTextColor($string);

        $textPrams = new TextLayoutParams($build, $string, $font, 1000, DrawTextAlign::DRAW_TEXT_ALIGN_LEFT);
        self::$draw = DrawText::newFromParams($build, $textPrams);
    }

    public static function onDraw(Event $e)
    {
        static $execTime = 0;
        if (!self::$draw) {
            return;
        }
        $e->getTarget()->drawText(self::$draw, 0, 0);
        $e->getTarget()->setSize(100, self::$drawHeight);
        $now = time();
        if ($now - $execTime > 3) {
            echo date('Y-m-d H:i:s') . '|Mem Peak Usage:' . memory_get_peak_usage();
            $execTime = $now;
        }
    }

    public static function addTextColor(AttributeString $str)
    {
        $str->addAttr('color', '#E71D1D', 1, 100);
    }

    public static function onmouseEvent($e)
    {
        return;
        $event = $e->mouseEvent;
        if ($event['down'] == 1 && $event['count'] == 1) {
            var_dump('mp down');
        } else if ($event['down'] == 1 && $event['count'] == 2) {
            var_dump('double');
        } else if ($event['up'] == 1) {
            var_dump('mp up');
        } else if ($event['drag'] == 1) {
            var_dump('mouse hold');
        } else if ($event['up'] == 3 && $event['count'] == 1) {
        }
    }
    public static function onmouseCrossed($e)
    {
        //var_dump(__METHOD__);
    }
    public static function onkeyEvent($e)
    {
        //var_dump(__METHOD__);
    }
    public static function onDragbroken($e)
    {
        //var_dump(__METHOD__);
    }
    public static function onSave()
    {
        if (self::$currentRequest < 0) {
            return self::onSaveAs();
        }
        $ret = self::getFormData();
        $ret['name'] = self::$requestList[self::$currentRequest]['name'];
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
    public static function onRequest($e)
    {
        $params = self::getFormData();
        if (empty($params['url'])) {
            $e->build()->getWin()->msgBoxError('API提示', '未选中API或URL');
            return;
        }
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
        if ($isJson || strlen($ret) < 1000) {
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
        self::setAreaString($e);
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

    public static function logs()
    {
        self::$logsFp = fopen(W_DIR . '/http.log', 'ab');
        ob_start(function ($buff, $phase) {
            fwrite(self::$logsFp, $buff);
            return true;
        }, 1024);
    }
}

new HTTP($argv, $argc);
