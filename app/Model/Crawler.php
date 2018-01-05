<?php

namespace Model;


use Component\Orm\Model\Model;

class Crawler extends Model
{
        public function __construct()
        {
                $this->configName = 'crawlerModel';
                parent::__construct();
        }
}