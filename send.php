<?php
// ==========================================
// 1. 設定項目（必ずご自身の情報に書き換えてください）
// ==========================================

// ◆ 受信したいメールアドレス（このアドレスに通知が届きます）
$to_email = "masakikawano19@gmail.com"; 

// ◆ 受信メールの件名
$subject = "【AIセラピスト co-mii】資料請求・お問い合わせがありました";

// ◆ Google reCAPTCHAの「シークレットキー」
// ※現在入力されているのはGoogleが用意している「テスト用キー」です。
// ※本番公開時は、ご自身で取得したシークレットキーに必ず書き換えてください。
$recaptcha_secret = "6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe"; 

// ==========================================
// 2. メール送信プログラム（これ以降は変更不要です）
// ==========================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- 1. reCAPTCHAのスパム判定 ---
    if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
        $recaptcha_response = $_POST['g-recaptcha-response'];
        
        // Googleのサーバーに判定結果を問い合わせる
        $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = array(
            'secret' => $recaptcha_secret,
            'response' => $recaptcha_response
        );
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context  = stream_context_create($options);
        $verify_result = file_get_contents($verify_url, false, $context);
        $response_data = json_decode($verify_result);
        
        // ロボットと判定された場合
        if(!$response_data->success) {
            die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2 style='color:red;'>スパムチェックに失敗しました。</h2><p>前のページに戻って、再度「私はロボットではありません」にチェックを入れてください。</p><button onclick='history.back()' style='padding:10px 20px; cursor:pointer;'>戻る</button></div>");
        }
    } else {
        // チェックが入っていない場合
        die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2 style='color:red;'>エラーが発生しました。</h2><p>「私はロボットではありません」にチェックを入れてください。</p><button onclick='history.back()' style='padding:10px 20px; cursor:pointer;'>戻る</button></div>");
    }

    // --- 2. フォームに入力されたデータの受け取りと安全化 ---
    $company       = htmlspecialchars($_POST['company'] ?? '', ENT_QUOTES, 'UTF-8');
    $name          = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $tel           = htmlspecialchars($_POST['tel'] ?? '', ENT_QUOTES, 'UTF-8');
    $email         = htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8');
    $contact_time  = htmlspecialchars($_POST['contact-time'] ?? '', ENT_QUOTES, 'UTF-8');
    $address       = htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES, 'UTF-8');
    $inquiry_type  = htmlspecialchars($_POST['inquiry-type'] ?? '', ENT_QUOTES, 'UTF-8');
    $trigger       = htmlspecialchars($_POST['trigger'] ?? '', ENT_QUOTES, 'UTF-8');
    $trigger_other = htmlspecialchars($_POST['trigger-other'] ?? '', ENT_QUOTES, 'UTF-8');
    $memo          = htmlspecialchars($_POST['memo'] ?? '', ENT_QUOTES, 'UTF-8');

    // --- 3. メール本文の作成 ---
    $message = "Webサイトのフォームから新しいお問い合わせがありました。\n\n";
    $message .= "■ 会社名： {$company}\n";
    $message .= "■ お名前： {$name}\n";
    $message .= "■ 電話番号： {$tel}\n";
    $message .= "■ メール： {$email}\n";
    $message .= "■ 連絡の取れやすい時間帯： {$contact_time}\n";
    $message .= "■ ご住所： {$address}\n";
    $message .= "■ 問い合わせ内容： {$inquiry_type}\n";
    $message .= "■ 問い合わせのきっかけ： {$trigger}\n";
    if (!empty($trigger_other)) {
        $message .= "■ その他のきっかけ： {$trigger_other}\n";
    }
    $message .= "■ 備考欄：\n{$memo}\n";

    // --- 4. 送信元の設定（入力されたお客様のアドレスを送信元にする） ---
    $headers = "From: " . $email . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    
    // 文字化け防止のための日本語設定
    mb_language("Japanese");
    mb_internal_encoding("UTF-8");

    // --- 5. メール送信の実行と完了画面 ---
    if (mb_send_mail($to_email, $subject, $message, $headers)) {
        echo "<div style='text-align:center; padding: 50px; font-family: sans-serif; background-color: #f6f8e8; height: 100vh; margin: 0;'>";
        echo "<div style='background: #fff; max-width: 500px; margin: 0 auto; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>";
        echo "<h2 style='color: #0056b3; margin-top:0;'>送信が完了しました</h2>";
        echo "<p style='line-height: 1.6;'>お問い合わせありがとうございます。<br>担当者より順次ご連絡いたします。</p>";
        // 別タブで開いているので「閉じる」ボタンにする
        echo "<button onclick='window.close()' style='margin-top:20px; padding:12px 24px; background:#0088ff; color:#fff; border:none; border-radius:4px; font-weight:bold; cursor:pointer;'>この画面を閉じる</button>";
        echo "</div></div>";
    } else {
        echo "<div style='text-align:center; padding: 50px; font-family: sans-serif;'>";
        echo "<h2 style='color: red;'>送信に失敗しました</h2>";
        echo "<p>システムエラーが発生しました。時間を置いて再度お試しください。</p>";
        echo "<button onclick='history.back()' style='padding:10px 20px; cursor:pointer;'>戻る</button>";
        echo "</div>";
    }
} else {
    echo "不正なアクセスです。";
}
?>
