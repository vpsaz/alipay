<?php
// 开启严格错误报告，方便调试
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// 敏感信息，建议从配置文件或环境变量获取
$checkApiKey = '4394e5376aa28b696b717f7aa9a95834';
$qq = 8242718;
// 支付平台 IP 白名单，可根据实际情况添加
$allowedIPs = [
    '192.168.1.1', // 示例 IP，替换为实际支付平台 IP
    '192.168.1.2'
];

// IP 白名单验证
$clientIP = $_SERVER['REMOTE_ADDR'];
if (!in_array($clientIP, $allowedIPs)) {
    header('HTTP/1.1 403 Forbidden');
    echo '非法 IP 访问';
    exit;
}

// 假设支付平台通过 POST 方式传递回调数据
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取回调数据，这里根据实际支付平台返回的数据结构调整
    $outTradeNo = $_POST['out_trade_no'] ?? '';
    $tradeStatus = $_POST['trade_status'] ?? '';
    $sign = $_POST['sign'] ?? '';

    if (!$outTradeNo || !$tradeStatus || !$sign) {
        echo 'fail: 缺少必要参数';
        exit;
    }

    // 验证签名，实际要根据支付平台的签名规则实现
    $params = [
        'po' => $outTradeNo,
        'qq' => $qq,
        'apikey' => $checkApiKey
    ];
    // 按字典序排序数据
    ksort($params);
    // 拼接数据和密钥
    $signStr = http_build_query($params);
    // 生成签名，示例使用 HMAC - SHA256 算法，更安全
    $generatedSign = hash_hmac('sha256', $signStr, $checkApiKey); 

    if ($sign === $generatedSign && $tradeStatus === '支付成功') {
        // 验证通过，处理业务逻辑，比如更新订单状态
        try {
            // 数据库配置，建议从配置文件或环境变量获取
            $dbHost = 'localhost';
            $dbName = 'your_database';
            $dbUser = 'username';
            $dbPass = 'password';

            // 创建 PDO 连接
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // 检查订单是否已处理
            $checkStmt = $pdo->prepare("SELECT status FROM orders WHERE order_number = :outTradeNo");
            $checkStmt->bindParam(':outTradeNo', $outTradeNo, PDO::PARAM_STR);
            $checkStmt->execute();
            $order = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($order && $order['status'] === 'paid') {
                echo 'success: 订单已处理';
                exit;
            }

            // 开启事务
            $pdo->beginTransaction();

            // 更新订单状态
            $updateStmt = $pdo->prepare("UPDATE orders SET status = 'paid' WHERE order_number = :outTradeNo");
            $updateStmt->bindParam(':outTradeNo', $outTradeNo, PDO::PARAM_STR);
            $updateStmt->execute();

            // 可以添加其他业务逻辑，如记录日志等

            // 提交事务
            $pdo->commit();

            // 返回成功响应给支付平台
            echo 'success';
        } catch (PDOException $e) {
            // 回滚事务
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // 记录错误日志
            error_log("Database error: " . $e->getMessage());
            echo 'fail: 数据库操作失败';
        }
    } else {
        // 验证失败，返回失败响应
        echo 'fail: 签名验证失败或支付状态不符';
    }
} else {
    // 非 POST 请求，返回失败响应
    echo 'fail: 仅支持 POST 请求';
}
?>