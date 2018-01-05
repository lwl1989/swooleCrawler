<?php


namespace Library\Crawler\Download;


use Curl\Curl;
use Library\Exception\DataException;

trait DownloadTrait
{
        protected $url;
        protected $urlInfo;

        public function setUrl(string $url)
        {
                $this->url = $url;
                return $this;
        }

        public function getUrl() : string
        {
                if(empty($this->url)) {
                        throw new DataException('set Url before this action');
                }

                return $this->url;
        }

        public function getUrlInfo(string $name = '')
        {
                if(empty($this->url)) {
                        throw new DataException('set Url before this action');
                }

                if(empty($this->urlInfo)) {
                        $this->urlInfo = parse_url($this->url);
                }

                if('' != $name) {
                        return $this->urlInfo[$name] ?? '';
                }
                return $this->urlInfo;
        }


        public function  download(\Closure $callback = null)
        {
                try {
                        $curl = new Curl();
                        $curl->get($this->url);
                        $curl->setUserAgent('Chrome/49.0.2587.3');
                        $curl->setConnectTimeout(1);
                        $curl->setHeader('Accept','text/html,application/xhtml+xml,application/xml');
                        $curl->setHeader('Accept-Encoding','gzip');
                        $curl->setHeader('upgrade-insecure-requests','1');
                        $content = $curl->response;
                        $error = $curl->error;
                        unset($curl);
                        if ($error) {
                                throw new \Exception('error');
                        } else {
                                call_user_func_array($callback, [$this->url, $content]);
                        }
                }catch (\Exception $exception) {
                        call_user_func_array($callback, [$this->url, '']);
                }
        }
}