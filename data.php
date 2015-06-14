<?php
    echo '===== start progress =====' . "\n";

    $config = parse_ini_file('./config.ini');

    //�ѥ�᡼���򥻥å�
    $params = array( 
        "loginid"  => $config['id'], 
        "password" => $config['password'], 
        "login"    => "login"
    ); 
    
    //���å�����¸�ե���������
    $cookie_path = $config['cookie_path'];
    touch($cookie_path);
    echo 'made cookie file ...' . "\n";
    
    //������ڡ����ذ�ư
    $URL1 = "https://www.watashi-move.jp/pc/login.php";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $URL1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_path);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_path);
    $put = curl_exec($ch) or die('error ' . curl_error($ch)); 
    curl_close($ch);
    echo 'request login ...' . "\n";
    
    //������ID�ȥѥ���ɤ�ž������ǧ�ڤ����Хȥåץڡ����򳫤�
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $URL1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_path);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_path);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $put = curl_exec($ch) or die('error ' . curl_error($ch)); 
    curl_close($ch);
    echo 'login successed ...' . "\n";

    // ¬���̥ڡ���������
    $URL2 = "https://www.watashi-move.jp/wl/mydata/body_scale.php?targetDate=2015%2F06%2F13";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $URL2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_path);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_path);
    $output = curl_exec($ch) or die('error ' . curl_error($ch)); 
    curl_close($ch);
    echo 'get data ...' . "\n";

    //mb_language("Japanese");
    $output = str_replace(array("\r\n", "\r"), "\n", $output);
    //$html_source = $output;
    $html_source = mb_convert_encoding($output, "UTF-8", "auto");
    file_put_contents($config['output_path'], $html_source); 
    echo 'write file ...' . "\n";
    
    //Cookie���
    //unlink($cookie_path);
    echo '===== finish progress =====' . "\n";
?>

