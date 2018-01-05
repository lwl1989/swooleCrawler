<?php

namespace Controller;


class Crawler
{
        public function before()
        {
                \Library\Task\Crawler::check();
        }

        public function run()
        {
                \Library\Task\Crawler::run();
        }

        public function after()
        {
                \Library\Task\Crawler::after();
        }

        public function stop()
        {
                if(!isset($_POST['task_en_name'])) {
                        return ['code'=>1,'response'=>'task_en_name lost'];
                }
                return \Library\Task\Crawler::stop();
        }

}