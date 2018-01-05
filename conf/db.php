<?php

/**
 * db connections
 */
return [
//        'mongodb'       =>      [
//                'uri'                   =>      'mongodb://13.125.90.42:56789/',
//                'uriOptions'            =>      [],
//                'driverOptions'         =>      [
////                        'replicaSet'            => 'rs',
////                        'readPreference'        => 'primary'
//                ],
//                'pool'  =>      [
//                        'max'   =>      10,
//                        'init'  =>      3
//                ]
//        ],
        'redis'         =>      [
                'host'  =>      '127.0.0.1',
                'port'  =>      '6379',
                'db'    =>      9
        ],
//        'mysql'         =>      [
//                'dns'           =>      '127.0.0.1',
//                'user'          =>      'root',
//                'password'      =>      'sa'
//        ],
];
