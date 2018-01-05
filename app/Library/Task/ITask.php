<?php


namespace Library\Task;


interface ITask
{
        public function run();
        public function setData(array $data);
}