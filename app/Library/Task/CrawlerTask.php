<?php


namespace Library\Task;

use Kernel\Core;
use Kernel\Core\Cache\Type\Hash;
use Library\Crawler\Crawler;

class CrawlerTask implements ITask
{
        protected $server;
        protected $redis;
        const ACTION_CRAWLER = 'crawler';
        const ACTION_START = 'start';
        const ACTION_KILL = 'kill';
        const ACTION_RELOAD = 'reload';
        const ACTION_STOP = 'stop';
        const KEY = 'crawler:list:';
        const DAY_SECOND = 86400;
        const MIN_INTERVAL = 3600;
        const MAX_INTERVAL = self::DAY_SECOND;
        protected $data = [];

        public function __construct(\swoole_server $server)
        {
                $this->server = $server;
        }

        public function setData(array $data)
        {
                /**
                 * target=>url
                 * action=>action
                 * task_en_name=>flag
                 * number_count=>count
                 * interval=>interval
                 * channel_rule=>rule
                 */
                if ($data['action'] == self::ACTION_START or $data['action'] == self::ACTION_CRAWLER) {
                        $keys = ['target', 'action', 'task_en_name', 'number_count', 'interval', 'channel_rule'];
                } else {
                        $keys = ['task_en_name', 'action'];
                }
                $diff = array_diff($keys, array_keys($data));
                if (!empty($diff)) {
                        throw new \Exception('params lost ' . json_encode($diff));
                }
                $this->data = [
                        'action' => $data['action'],
                        'flag' => $data['task_en_name'],
                        'count' => $data['number_count']??'',
                        'url' => $data['target']??'',
                        'interval' => $data['interval']??'',
                        'rule' => $data['channel_rule']??'',
                ];
        }

        public function run()
        {
                return $this->_check();
        }

        public function _check()
        {
                $data = $this->data;
                if (isset($data['interval'])) {
                        if (self::MIN_INTERVAL > $data['interval'] or self::MAX_INTERVAL < $data['interval']) {
                                return ['code' => 1, 'response' => 'interval value must be gt ' . self::MIN_INTERVAL . ' and lt ' . self::MAX_INTERVAL];
                        }
                }
                if (isset($data['action']) and isset($data['flag'])) {
                        switch ($data['action']) {
                                case self::ACTION_START:
                                case self::ACTION_CRAWLER:
                                        return $this->_crawler($data);
                                case self::ACTION_KILL:
                                case self::ACTION_STOP:
                                        return $this->_stop($data);
                                case self::ACTION_RELOAD:
                                        return $this->_reload($data);
                                default:
                                        return ['code' => 1];
                        }
                }
                return ['code' => 1];
        }

        private function getHash(string $key): Hash
        {
                $config = Core::getInstant()->get('config');
                $redis = new Core\Cache\Redis($config, false);
                $class = new Hash($redis);
                $class->setKey($key);
                return $class;
        }

        private function _crawler(array $data)
        {
                $hash = $this->getHash(self::KEY . $data['flag']);
                if ($hash->hasKey()) {
                        $cache = $hash->getAll();
                        if ($cache['stop'] == 1) {
                                return $this->_start($data);
                        }
                        return $hash->getAll();
                }
                unset($hash);
                return $this->_start($data);
        }

        private function _stop(array $data, bool $reload = false)
        {
                $processId = -1;
                $hash = $this->getHash(self::KEY . $data['flag']);
                $cache = $hash->getAll();
                if(!$reload) {
                        $hash->setField('stop', 1);
                }

                if (isset($cache['processId']) and $cache['stop'] == '0') {
                        if (!empty($cache['processId'])) {
                                $this->_delProcess($cache['processId']);
                        }
                }
                unset($hash);
                return ['processId' => $processId];
        }

        private function _reload(array $data)
        {
                $this->_stop($data);
                return $this->_start($data);
        }

        private function _doCrawler(array $data)
        {
                $process = new \swoole_process(function () use ($data) {
                        $task = Crawler::getCrawler($data);
                        while (true) {
                                try {
                                        if ($data['count'] > 0) {
                                                if ($task->getGot() >= $data['count']) {
                                                        break;
                                                }
                                        }
                                        $url = $task->getUrl();
                                        echo "url:" . $url . "\r\n";
                                        if ($url == '') {
                                                break;
                                        }
                                        $task->runOne($url);
                                } catch (\Exception $exception) {
                                        file_put_contents('exception', date('Y-m-d H:i:s') . ":\r\n" . $exception->getTraceAsString() . "\r\n\r\n", FILE_APPEND);
                                }
                                //不同workerId定时器共用一个问题  包括swoole_timer_tick swoole_time_afer都有此问题
                                //需要在while(true)里面加入休眠释放CPU控制权
                                //还有一个可能，我测试的机器是虚拟机，单核
                                //可能由于CPU控制权限问题造成错乱暂时无法定位
                                usleep(20000);
                        }
                        $hash = $hash = $this->getHash(self::KEY . $data['flag']);
                        $hash->setField('stop', 1);
                        unset($hash);
                }, false, false);
                $processId = $process->start();
                $process->name($data['flag']);
                return $processId;
        }

        private function _start(array $data)
        {
                $hash = $this->getHash(self::KEY . $data['flag']);
                $cache = $hash->getAll();

                if (isset($cache['stop'])) {
                        if (self::ACTION_RELOAD == $data['action']) {
                                $processId = $this->_doCrawler($data);
                                $hash->setField('processId', $processId);
                                $hash->setField('stop', 0);
                        } else {
                                if ($cache['stop'] == 0) {
                                        $processId = $this->_doCrawler($data);
                                        $hash->setField('processId', $processId);
                                } else {
                                        $processId = '-1';
                                }
                        }
                } else {
                        $processId = $this->_doCrawler($data);
                        $hash->setField('processId', $processId);
                        $hash->setField('stop', 0);
                }

                if (isset($data['interval']) and self::MIN_INTERVAL > $data['interval']) {
                        $this->server->after($data['interval'] * 1000, function () use ($data) {
                                $hash = $this->getHash(self::KEY . $data['flag']);
                                $cache = $hash->getAll();
                                if(isset($cache['processId'])) {
                                        if(isset($cache['stop'])){
                                                if('0' == $cache['stop']) {
                                                        $this->_start($data);
                                                }else{
                                                        $hash->delKey();
                                                }
                                        }
                                        $this->_delProcess($cache['processId']);
                                }else{
                                        $hash->delKey();
                                }
                                unset($hash);
                        });
                }
                unset($hash);
                return ['processId' => $processId];
        }

        private function _delProcess($processId)
        {
                $this->server->tick(20, function ($id) use ($processId) {
                        echo "kill processId" . $processId . PHP_EOL;
                        \swoole_process::kill($processId);
                        \swoole_process::wait(true);
                        \swoole_timer_clear($id);
                });
        }
}