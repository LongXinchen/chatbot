
<?php

/**
 * 基于 Swoole 协程 Tcp 服务端的同步请求 Shell
 */

require __DIR__ . '/bootstrap/autoload.php';

// 运行平台.
$host->run('duplex_shell');

