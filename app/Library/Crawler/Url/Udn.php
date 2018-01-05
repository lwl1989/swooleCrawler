<?php


namespace Library\Crawler\Url;

use Kernel\AgileCore as Core;
use Kernel\Core\Cache\Redis;
use Kernel\Core\Cache\Type\Hash;
use Kernel\Core\Cache\Type\Set;
//use Model\Crawler;

class Udn
{
        /* @var $urls Set */
        protected $urls;
        /* @var $got Set */
        protected $got;
        protected $host;
        protected $dbName;
        protected $dbPrefix;
        public function __construct(string $host, string $dbName = '')
        {
                $this->setHost($host);
                if(!empty($dbName)) {
                        $this->setDbName($dbName);
                }else{
                        $dbName = $host;
                        $this->setDbName($dbName);
                }
                $this->urls = $this->getSet($dbName.':'.date('ymd').':urls');
                $this->got = $this->getSet($dbName.':'.date('ymd').':got');
                $this->clear();
        }

        public function setHost(string $host)
        {
                $this->host = $host;
                $domain = explode('.', $host);

                if(count($domain)>2) {
                        unset($domain[0]);
                }
                $this->dbName = implode('_', $domain);
                if(!empty($this->dbPrefix)) {
                        $this->dbName = $this->dbPrefix.'_'.$this->dbName;
                }

                $this->_fixDbName();
        }

        private function _fixDbName()
        {
                $this->dbName = str_replace('.','_', $this->dbName);
        }

        public function setDbName(string $name)
        {
                $this->dbName = $name;
        }

        public function addUrls(array $urls)
        {
              $got = $this->got->getAll();
              if(!is_array($got)) {
                      $diff = array_diff($urls, $got);
                      if (!empty($diff)) {
                              $this->urls->addValues($diff);
                      }
              }else{
                      $this->urls->addValues($urls);
              }
        }

        public function getOne()
        {
                if($this->urls->getLength() < 1) {
                        return '';
                }
                $get = $this->urls->get();
                $this->got->addValue($get);
                return $get;
        }

        public function setContent(string $url, array $content)
        {
                $content = array_merge(['url'=>$url], $content);
                $hash = new Hash(new Redis(Core::getInstant()->get('config'), false));
                $hash->setKey('hash:'.$this->dbName);
                $hash->setField($url,json_encode($content));
                unset($hash);
        }

        public function getGotLen()
        {
                return $this->got->getLength();
        }

        public function clear()
        {
                $this->got->del();
                $this->urls->del();
        }

        private function getSet(string $key) : Set
        {
                $class = new Set(new Redis(Core::getInstant()->get('config'), false));
                $class->setKey($key);
                return $class;
        }



        public function __destruct()
        {
                $this->got = null;
                $this->urls = null;
        }

}