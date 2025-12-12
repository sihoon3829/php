<?php
if(isset($_GET['resetdb'])) {
    $link = new mysqli("localhost", "root", "", "");
    $link->query("DROP DATABASE IF EXISTS debate_on");
    $link->query("CREATE DATABASE debate_on CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $link->select_db("debate_on");
    
    $sql = "
    CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, user_id VARCHAR(50) UNIQUE, password VARCHAR(255), nickname VARCHAR(50), is_admin TINYINT DEFAULT 0);
    CREATE TABLE posts (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), content TEXT, author VARCHAR(50), category VARCHAR(20) DEFAULT 'free', views INT DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE comments (id INT AUTO_INCREMENT PRIMARY KEY, post_id INT, content TEXT, author VARCHAR(50), created_at DATETIME DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE topic_votes (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(200), proposed_by VARCHAR(50), yes INT DEFAULT 0, no INT DEFAULT 0, end_time DATETIME, created_at DATETIME DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE chat_rooms (id INT AUTO_INCREMENT PRIMARY KEY, topic VARCHAR(200), created_at DATETIME DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE chat_messages (id INT AUTO_INCREMENT PRIMARY KEY, room_id INT, user_id VARCHAR(50), message TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE visitors (visit_date DATE PRIMARY KEY, count INT DEFAULT 0);
    
    INSERT IGNORE INTO visitors (visit_date,count) VALUES (CURDATE(),0);
    INSERT INTO users (user_id,password,nickname,is_admin) VALUES ('admin','1234','관리자',1);
    
    INSERT INTO topic_votes (title, proposed_by, yes, no, end_time) VALUES ('아이폰 vs 갤럭시, 너의 선택은?', 'admin', 22, 8, DATE_ADD(NOW(), INTERVAL 10 MINUTE));
    INSERT INTO chat_rooms (topic) VALUES ('아이폰 vs 갤럭시, 너의 선택은?');
    INSERT INTO posts (title, content, author) VALUES 
        ('안녕하세요! 첫 번째 글입니다', '환영합니다! 자유롭게 토론해주세요', 'admin'),
        ('오늘 저녁 뭐 먹지?', '치킨 vs 피자 vs 떡볶이', 'admin');
    ";
    
    foreach(explode(';', $sql) as $q) if(trim($q)) $link->query($q);
    echo "<script>alert('DB 완전 초기화 완료!\\n이제 진짜 완벽합니다!'); location.href='index.php';</script>";
    exit;
}



session_start();
date_default_timezone_set('Asia/Seoul');
$mysqli = new mysqli("localhost", "root", "", "debate_on");
if ($mysqli->connect_error) die("DB 연결 실패");
$mysqli->set_charset("utf8mb4");

function e($str) {
    global $mysqli;
    return htmlspecialchars(trim($mysqli->real_escape_string($str)), ENT_QUOTES, 'UTF-8');
}

$_SESSION['last_msg_time'] = $_SESSION['last_msg_time'] ?? 0;


// 회원가입 처리
if(isset($_POST['register'])) {
    $uid = e($_POST['uid']);
    $pw = $_POST['pw'];  
    $nick = e($_POST['nick']);
    
    // 간단한 유효성 검사
    if(strlen($uid) < 4 || strlen($uid) > 12) $reg_error = "아이디는 4~12자";
    elseif(strlen($pw) < 6) $reg_error = "비밀번호는 6자 이상";
    elseif(strlen($nick) < 2 || strlen($nick) > 10) $reg_error = "닉네임은 2~10자";
    elseif($mysqli->query("SELECT id FROM users WHERE user_id='$uid'")->num_rows > 0) {
        $reg_error = "이미 사용 중인 아이디입니다";
    } else {
        $mysqli->query("INSERT INTO users(user_id,password,nickname) VALUES('$uid','$pw','$nick')");
        $_SESSION['user_id'] = $uid;
        $_SESSION['nickname'] = $nick;
        echo "<script>alert('회원가입 완료! 환영합니다 $nick 님!'); location.href='index.php';</script>";
        exit;
    }
}
?>