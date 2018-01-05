# swooleCrawler


### 基于swoole框架agileSwoole开发的一个爬虫框架

#### 方便进行二次开发

#### 目前基础功能有:

	1. 远程发起爬虫任务
	2. 远程关闭爬虫任务

### demo
	
	route中定义

```
		'post'	=>	[
	 		[
                                'path'          =>      '/crawler',
                                'dispatch'      =>      [\Controller\Crawler::class, 'run'],
                                'before'        =>      [\Controller\Crawler::class,  'before'],
                                'after'         =>      [\Controller\Crawler::class, 'after'],
                                'type'          =>      \Component\Producer\Producer::PRODUCER_PROCESS
                        ],
                ],
                'put'   =>      [
                        //put
                ],
                'delete'=>      [
                        [
                                'path'          =>      '/crawler',
                                'dispatch'      =>      [\Controller\Crawler::class, 'stop'],
                                'type'          =>      \Component\Producer\Producer::PRODUCER_SYNC
                        ]
                ]
```

cd public 

php index.php

打开 http://localhost:9550 即可看到宣传首页

使用curl 请求即可
提交数据示例：
	{"action":"crawler","interval":"500","task_en_name":"csdn","target":"http:\/\/blog.csdn.net\/","number_count":2250,"channel_rule":"http:\/\/blog.csdn.net\/*\/article\/details\/*"}

```
<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_PORT => "9550",
  CURLOPT_URL => "http://localhost:9550/crawler",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => "{\"action\":\"crawler\",\"interval\":\"500\",\"task_en_name\":\"csdn\",\"target\":\"http:\\/\\/blog.csdn.net\\/\",\"number_count\":2250,\"channel_rule\":\"http:\\/\\/blog.csdn.net\\/*\\/article\\/details\\/*\"}",
  CURLOPT_HTTPHEADER => array(
    "cache-control: no-cache",
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}
```
