<?php
// 开启严格错误报告，方便调试
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// 敏感信息，建议从配置文件或环境变量获取
$apiKey = 'd8dbb5d5ae6524503add3d0ac91a60a3';
$qq = 8242718;
// 可修改此金额
$amount = 0.1; 
// 回调地址，根据实际部署情况修改
$notifyUrl = 'https://yourdomain.com/payment_callback.php'; 

// 构建支付请求参数
$payParams = [
    'qq' => $qq,
    'apikey' => $apiKey,
    'amt' => $amount,
    'notify_url' => $notifyUrl // 添加回调地址参数
];

// 构建支付请求 URL
$payUrl = 'https://baiapi.cn/api/pay/?' . http_build_query($payParams);

// 初始化 cURL
$ch = curl_init($payUrl);
// 设置 cURL 选项
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// 支持 HTTPS，生产环境建议配置正确证书
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
// 设置超时时间
curl_setopt($ch, CURLOPT_TIMEOUT, 30); 

// 执行 cURL 请求
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// 获取 cURL 错误信息
$curlError = curl_error($ch); 
curl_close($ch);

if ($httpCode === 200) {
    $paymentData = json_decode($response, true);
    if ($paymentData === null || !isset($paymentData['success'])) {
        $paymentData = [
            'success' => false,
            'msg' => '支付请求返回数据解析失败或格式错误'
        ];
    }
} else {
    $paymentData = [
        'success' => false,
        'msg' => "支付请求失败，状态码: $httpCode，cURL 错误: $curlError"
    ];
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>请使用支付宝付款 - BaiPay支付</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 引入 jQuery 库 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            background-image: url(), url(https://vpsaz.cn/qita/tupian/bj.svg);
            background-position: right bottom, left top;
            background-repeat: no-repeat, repeat;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 400px;
            text-align: center;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        .amount {
            font-size: 28px;
            color: #d9534f;
            font-weight: bold;
        }

        .qrcode-wrapper {
            margin: 20px 0;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            color: #ffffff;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }

        .button:hover {
            background-color: #0056b3;
        }

        .footer {
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .tip {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tip-text {
            margin-left: 10px;
        }

        .detail {
            margin-top: 20px;
            text-align: left;
        }

        .detail dt {
            font-weight: bold;
            color: #333;
        }

        .detail dd {
            margin: 0 0 10px 0;
            color: #555;
        }

        .arrow {
            display: block;
            text-align: center;
            margin-top: 10px;
            cursor: pointer;
            color: #007bff;
            font-size: 14px;
        }

        .arrow:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">请支付 <span class="amount"><?php echo $amount; ?></span> 元</div>

        <div class="qrcode-wrapper">
            <div id="qrcode">
                <?php
                if ($paymentData['success'] && isset($paymentData['payment_url'])) {
                    $qrCodeApi = 'https://baiapi.cn/api/qrcode/';
                    $qrCodeParams = [
                        'text' => $paymentData['payment_url'],
                        'size' => 200
                    ];
                    $qrCodeUrl = $qrCodeApi . '?' . http_build_query($qrCodeParams);
                    echo '<img src="' . $qrCodeUrl . '" alt="支付二维码">';
                } else {
                    echo '<p style="color: red;">' . $paymentData['msg'] . '</p>';
                }
                ?>
            </div>
        </div>

        <div>
            <?php
            if ($paymentData['success'] && isset($paymentData['payment_url'])) {
                echo '<a href="' . $paymentData['payment_url'] . '" class="button" id="alipayLink">点我跳转支付宝进行支付</a>';
            }
            ?>
        </div>

        <div class="footer">
            <div class="detail" id="orderDetail">
                <dl id="desc" style="display: none;">
                    <dt>金额</dt>
                    <dd><?php echo $amount; ?></dd>
                    <dt>商户订单</dt>
                    <dd id="ordernum"><?php echo $paymentData['out_trade_no'] ?? ''; ?></dd>
                    <dt>状态</dt>
                    <dd id="orderStatus">未支付</dd>
                </dl>
                <div class="arrow" id="toggleDetail">展开订单详情</div>
            </div>
        </div>
    </div>

    <script>
        const checkApiKey = '4394e5376aa28b696b717f7aa9a95834';
        const qq = 8242718;
        const outTradeNo = '<?php echo $paymentData['out_trade_no'] ?? ''; ?>';
        // 定义支付成功后跳转的页面 URL，需替换为实际地址
        const successRedirectUrl = 'https://yourdomain.com/payment_success.html'; 

        // 开始支付状态检查
        function startPaymentCheck() {
            if (!outTradeNo) return;

            const intervalId = setInterval(async () => {
                const checkParams = {
                    po: outTradeNo,
                    qq: qq,
                    apikey: checkApiKey
                };
                const checkUrl = 'https://baiapi.cn/api/pay_y/?' + new URLSearchParams(checkParams).toString();

                try {
                    const response = await fetch(checkUrl);
                    if (!response.ok) {
                        throw new Error(`查询失败，状态码: ${response.status}`);
                    }

                    const data = await response.json();
                    if (data.success && data.trade_status === '支付成功') {
                        clearInterval(intervalId);
                        document.getElementById('orderStatus').textContent = '支付成功';
                        alert('支付成功！即将跳转到成功页面。');
                        // 支付成功后跳转到指定页面
                        window.location.href = successRedirectUrl; 
                    }
                } catch (error) {
                    console.error('支付状态查询错误:', error);
                    if (typeof window.checkErrorCount === 'undefined') {
                        window.checkErrorCount = 1;
                    } else {
                        window.checkErrorCount++;
                    }
                    if (window.checkErrorCount >= 5) {
                        clearInterval(intervalId);
                        alert('支付状态查询多次失败，请联系客服');
                    }
                }
            }, 5000);
        }

        // 页面加载完成后开始支付状态检查
        window.addEventListener('load', startPaymentCheck);

        $(document).ready(function() {
            $('#toggleDetail').click(function() {
                $('#desc').slideToggle(500);
                if ($(this).text() === '展开订单详情') {
                    $(this).text('收起订单详情');
                } else {
                    $(this).text('展开订单详情');
                }
            });
        });
    </script>
</body>
</html>
