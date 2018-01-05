<?php


namespace Library\Task;


use Curl\Curl;
use Kernel\AgileCore;

class Crawler
{
        public static function run()
        {
                $crawler = \Library\Crawler\Crawler::getCrawler($_POST);
                $crawler->runOne($_POST['target']);
                $again = 1;
                $index = 0;
                while (true) {
                        try {
                                if($again > 5) {
                                        break;
                                }
                                if($index > $_POST['number_count']) {
                                        break;
                                }
                                $url = $crawler->getUrl();
                                if (!empty($url)) {
                                        $crawler->runOne($url);
                                        $index ++;
                                }else{
                                        $again ++;
                                }
                        } catch (\Exception $exception) {
                                $again++;
                                continue;
                        }
                }
        }

        public static function after()
        {
                $internal = intval($_POST['interval']) ?? 0;

                $tableName = 'crawlerTable';
                /* @var \swoole_server */
                $server = AgileCore::getInstant()->get('http')->getServer();
                /* @var $table \swoole_table */
                $table = $server->{$tableName};
                $data = [
                        'processId'     =>      intval($_SERVER['process_id'] ?? -1),
                        'numberCount'   =>      intval($_POST['number_count'] ?? 0),
                        'interval'      =>      intval($_POST['interval'] ?? 0)
                ];

                if($internal > 0) {
                        if(isset($_SERVER['process_id']) and $_SERVER['process_id'] > -1) {
                                $pid = $_SERVER['process_id'];
                                $timeId = swoole_timer_after($internal*1000,function () use($pid){
                                        \swoole_process::kill($pid,0);
                                        $server = AgileCore::getInstant()->get('config')->get('server');
                                        $curl = new Curl();
                                        $curl->post($server['host'].':'.$server['port'].'/crawler', $_POST);
                                });
                                $data['timeId'] = $timeId;
                                $table->set($_POST['task_en_name'],$data);
                                return $data;
                        }
                }
                $data['timeId'] = -1;
                $table->set($_POST['task_en_name'],$data);
                return $data;
        }

        public static function stop()
        {
                $tableName = 'crawlerTable';
                /* @var \swoole_server */
                $server = AgileCore::getInstant()->get('http')->getServer();
                /* @var $table \swoole_table */
                $table = $server->{$tableName};
                $result = $table->get($_POST['task_en_name']);

                if(isset($result['timeId']) and $result['timeId'] > -1) {
                        swoole_timer_clear(intval($result['timeId']));
                }

                if(isset($result['processId']) and $result['processId'] != -1) {
                        \swoole_process::kill(intval($result['processId']),0);
                        $result['processId'] = -1;
                }
                return $result;
        }


        public static function check(array $data = [])
        {
                if (empty($data)) {
                        $data = $_POST;
                }
                $keys = ['target', 'action', 'task_en_name', 'number_count', 'interval', 'channel_rule'];
                $diff = array_diff($keys, array_keys($data));
                if (!empty($diff)) {
                        throw new \Exception('params lost ' . json_encode($diff));
                }
                return true;
        }
}