<?php require_once 'config.php';

// 로그인/회원가입/로그아웃
if(isset($_GET['logout'])) { session_destroy(); header("Location: ."); exit; }
if(isset($_POST['login'])) {
    $uid = e($_POST['uid']); $pw = $_POST['pw'];
    $r = $mysqli->query("SELECT * FROM users WHERE user_id='$uid' AND password='$pw'");
    if($row = $r->fetch_assoc()) {
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['nickname'] = $row['nickname'];
        $_SESSION['is_admin'] = $row['is_admin'];
    } else $login_error = "틀림";
}

// 새 주제 제안
if(isset($_POST['new_topic']) && isset($_SESSION['user_id'])) {
    $title = e($_POST['topic_title']);
    if(strlen($title)>5) {
        $end_time = date('Y-m-d H:i:s', time() + 10);
        $mysqli->query("INSERT INTO topic_votes(title,proposed_by,end_time) VALUES('$title','{$_SESSION['user_id']}','$end_time')");
    }
}

// 투표
if(isset($_GET['vote_yes'])) {
    $id = (int)$_GET['vote_yes'];
    if(!isset($_SESSION['voted_'.$id])) {
        $mysqli->query("UPDATE topic_votes SET yes=yes+1 WHERE id=$id");
        $_SESSION['voted_'.$id] = true;
    }
}
if(isset($_GET['vote_no'])) {
    $id = (int)$_GET['vote_no'];
    if(!isset($_SESSION['voted_'.$id])) {
        $mysqli->query("UPDATE topic_votes SET no=no+1 WHERE id=$id");
        $_SESSION['voted_'.$id] = true;
    }
}

// 투표 끝난 주제 → 자동으로 게시글 생성 (한 번만)
$finished = $mysqli->query("SELECT * FROM topic_votes WHERE end_time < NOW() AND yes > no AND NOT EXISTS (SELECT 1 FROM posts WHERE title = topic_votes.title)");
while($f = $finished->fetch_assoc()) {
    $title = e($f['title']);
    $content = "투표 결과 이 주제가 선정되었습니다!\n\n자유롭게 의견을 나눠주세요";
    $author = $f['proposed_by'];
    $mysqli->query("INSERT INTO posts(title,content,author,category) VALUES('$title','$content','$author','debate')");
}

// 댓글 작성
if(isset($_POST['write_comment']) && isset($_SESSION['user_id'])) {
    $post_id = (int)$_POST['post_id'];
    $content = e($_POST['content']);
    $author = $_SESSION['user_id'];
    $mysqli->query("INSERT INTO comments(post_id,content,author) VALUES($post_id,'$content','$author')");
}

// 글 작성
if(isset($_POST['write_post']) && isset($_SESSION['user_id'])) {
    $title = e($_POST['title']);
    $content = e($_POST['content']);
    $author = $_SESSION['user_id'];
    $mysqli->query("INSERT INTO posts(title,content,author) VALUES('$title','$content','$author')");
    echo "<script>alert('글 작성 완료!');</script>";
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debate ON</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="wrap">
        <h1 class="logo" onclick="location.href='index.php'">Debate ON</h1>
        <div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <b><?=$_SESSION['nickname']?>님</b>
                <a href="?logout" style="margin-left:1rem;color:#e74c3c;">로그아웃</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="container">
    <main>
        <?php if(!isset($_SESSION['user_id'])): ?>
    <div class="card" style="max-width:480px;margin:8rem auto;padding:3rem;text-align:center;border-radius:24px;">
        <h1 class="logo" onclick="location.href='index.php'" style="font-size:3.2rem;margin-bottom:1.5rem;cursor:pointer;">Debate ON</h1>
        
        <?php if(isset($_GET['register']) || isset($_POST['register'])): ?>
            <!-- 회원가입 화면 -->
            <h2 style="margin-bottom:2rem;color:#2d3436;">회원가입</h2>
            <form method="post">
                <input name="uid" placeholder="아이디 (4~12자)" required minlength="4" maxlength="12" style="margin-bottom:1rem;">
                <input name="pw" type="password" placeholder="비밀번호 (6자 이상)" required minlength="6" style="margin-bottom:1rem;">
                <input name="nick" placeholder="닉네임 (2~10자)" required minlength="2" maxlength="10" style="margin-bottom:2rem;">
                <button type="submit" name="register" class="btn" style="padding:1.2rem 3rem;font-size:1.2rem;">가입하기</button>
            </form>
            <?php if(isset($reg_error)): ?>
                <p style="color:#e74c3c;margin-top:1.5rem;font-weight:600;"><?=$reg_error?></p>
            <?php endif; ?>
            <p style="margin-top:2rem;">
                이미 계정이 있나요? <a href="index.php" style="color:#667eea;font-weight:600;">로그인하기 →</a>
            </p>

        <?php else: ?>
            <!-- 로그인 화면 -->
            <h2 style="margin-bottom:2rem;color:#2d3436;">로그인</h2>
            <form method="post">
                <input name="uid" placeholder="아이디" required style="margin-bottom:1rem;">
                <input name="pw" type="password" placeholder="비밀번호" required style="margin-bottom:2rem;">
                <button type="submit" name="login" class="btn" style="padding:1.2rem 3rem;font-size:1.2rem;">입장하기</button>
            </form>
            <?php if(isset($login_error)): ?>
                <p style="color:#e74c3c;margin-top:1.5rem;font-weight:600;"><?=$login_error?></p>
            <?php endif; ?>
            <p style="margin-top:2rem;">
                아직 계정이 없나요? <a href="?register=1" style="color:#667eea;font-weight:600;">회원가입하기 →</a>
            </p>
        <?php endif; ?>
    </div>
        <?php else: ?>
            <!-- 오늘의 토론 주제 투표 -->
            <div class="card vote-card">
                <h2>오늘의 토론 주제 투표 (10분)</h2>
                <form method="post" style="margin:2rem 0;">
                    <input name="topic_title" placeholder="새 주제 제안하기" required>
                    <button type="submit" name="new_topic" class="btn">제안</button>
                </form>

                <?php
                $current = $mysqli->query("SELECT v.*, u.nickname FROM topic_votes v LEFT JOIN users u ON v.proposed_by=u.user_id WHERE end_time > NOW() ORDER BY (yes-no) DESC LIMIT 3");
                while($v = $current->fetch_assoc()):
                    $remain = strtotime($v['end_time']) - time();
                    $min = floor($remain/60); $sec = $remain%60;
                ?>
                    <div style="background:white;padding:1.5rem;border-radius:16px;margin:1rem 0;">
                        <h3><?=$v['title']?></h3>
                        <p>제안자: <?=$v['nickname']?> | 남은시간: <?=$min?>분 <?=$sec?>초</p>
                        <div style="display:flex;gap:1rem;margin-top:1rem;">
                            <a href="?vote_yes=<?=$v['id']?>" class="btn">찬성 (<?=$v['yes']?>)</a>
                            <a href="?vote_no=<?=$v['id']?>" class="btn" style="background:#e74c3c;">반대 (<?=$v['no']?>)</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- 오늘의 토론 (투표 끝난 주제) -->
            <?php
            $today_debate = $mysqli->query("SELECT p.*, u.nickname FROM posts p LEFT JOIN users u ON p.author=u.user_id WHERE category='debate' ORDER BY id DESC LIMIT 1")->fetch_assoc();
            if($today_debate):
            ?>
                <div class="card">
                    <h2>오늘의 토론</h2>
                    <div class="post-card">
                        <div class="post-header">
                            <div class="post-title"><?=$today_debate['title']?></div>
                            <div class="post-meta"><?=$today_debate['nickname']?> · 투표로 선정됨</div>
                        </div>
                        <div class="post-body">
                            <?=nl2br(htmlspecialchars($today_debate['content']))?>
                            
                            <!-- 댓글 -->
                            <div style="margin-top:2rem;padding-top:2rem;border-top:1px solid #eee;">
                                <?php
                                $comments = $mysqli->query("SELECT c.*, u.nickname FROM comments c LEFT JOIN users u ON c.author=u.user_id WHERE post_id={$today_debate['id']} ORDER BY id");
                                while($c = $comments->fetch_assoc()): ?>
                                    <div style="background:#f8f9fc;padding:1rem;border-radius:12px;margin:1rem 0;">
                                        <strong><?=$c['nickname']?></strong>
                                        <p><?=nl2br(htmlspecialchars($c['content']))?></p>
                                    </div>
                                <?php endwhile; ?>
                                
                                <form method="post" style="margin-top:1rem;">
                                    <input type="hidden" name="post_id" value="<?=$today_debate['id']?>">
                                    <textarea name="content" rows="3" placeholder="의견을 남겨주세요" required></textarea>
                                    <button type="submit" name="write_comment" class="btn">댓글 달기</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 자유게시판 -->
            <div class="card">
                <h2>자유게시판</h2>
                <form method="post" style="margin-bottom:2rem;">
                    <input name="title" placeholder="제목" required>
                    <textarea name="content" rows="5" placeholder="내용" required></textarea>
                    <button type="submit" name="write_post" class="btn">글쓰기</button>
                </form>

                <?php
                $posts = $mysqli->query("SELECT p.*, u.nickname FROM posts p LEFT JOIN users u ON p.author=u.user_id WHERE category!='debate' ORDER BY id DESC LIMIT 10");
                while($p = $posts->fetch_assoc()): ?>
                    <div class="post-card">
                        <div class="post-header">
                            <div class="post-title"><?=$p['title']?></div>
                            <div class="post-meta"><?=$p['nickname']?> · <?=date('m/d H:i', strtotime($p['created_at']))?></div>
                        </div>
                        <div class="post-body"><?=nl2br(htmlspecialchars($p['content']))?></div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </main>

    <div class="sidebar">
        <div class="card">
            <h3>오늘의 토론 주제들</h3>
            <?php
            $debates = $mysqli->query("SELECT title FROM posts WHERE category='debate' ORDER BY id DESC LIMIT 5");
            while($d = $debates->fetch_assoc()): ?>
                <div style="padding:1rem;background:#f8f9fc;margin:0.5rem 0;border-radius:12px;">
                    <?=$d['title']?>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
</body>
</html>