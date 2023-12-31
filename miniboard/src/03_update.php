<?php
	define("ROOT", $_SERVER["DOCUMENT_ROOT"]."/src/"); // 웹서버root
	define("FILE_HEADER", ROOT."header.php"); // 헤더 패스
	define("ERROR_MSG_PARAM", "<%s 입력!>"); // 파라미터 에러 메세지 // 제목, 내용
	require_once(ROOT."lib/lib_db.php");// DB관련 라이브러리

	$conn = null; // DB Connection 변수
	$http_method = $_SERVER["REQUEST_METHOD"]; // 메소드 확인
	$arr_err_msg = []; // 에러메세지 저장용 변수(배열) 설정
	$id = "";
	$title = ""; // 제목 세팅
	$content = ""; // 내용 세팅
	$update_at = ""; // 내용 세팅
	
	try {
		if(!db_conn($conn)) { // DB 연결
			throw new Exception("DB Error : PDO Instance");
		}

		if($http_method === "GET") { // 메소드가 GET일 경우(유저가 처리할 수 없는 에러)
			$id = isset($_GET["id"]) ? $_GET["id"] : ""; // id 세팅			
			$page = isset($_GET["page"]) ? $_GET["page"] : ""; // page 세팅					
			if($id === "") {
				$arr_err_msg[] = sprintf(ERROR_MSG_PARAM, "id");
			}
			// id의 값이 공백일 때 에러메세지 출력
			if($page === "") {
				$arr_err_msg[] = sprintf(ERROR_MSG_PARAM, "page");
			}
			// page : id와 동일
			if(count($arr_err_msg) >= 1) {
				throw new Exception(implode("<br>", $arr_err_msg));
			}					
		} else { // 메소드가 POST일 경우(유저가 처리할 수 있는 에러)					
			$id = isset($_POST["id"]) ? $_POST["id"] : "";
			// id 세팅 : post id 제출 시 true-post로 제출된 id를 $id에 저장
			// false-빈 값 제출
			$page = isset($_POST["page"]) ? $_POST["page"] : "";
			// page 세팅 : id 동일
			$title = trim(isset($_POST["title"]) ? trim($_POST["title"]) : "");
			// title 세팅 : post title 작성 시 true-앞뒤공백 제거 후 title 값 저장
			// false-빈 값 제출
			$content = trim(isset($_POST["content"]) ? trim($_POST["content"]) : "");
			// content 세팅 : title 동일			
			$update_at = trim(isset($_POST["update_at"]) ? trim($_POST["update_at"]) : "");
			// update_at 세팅 : title 동일			
	
			if($id === "") {
				$arr_err_msg[] = sprintf(ERROR_MSG_PARAM, "id");
			}
			if($page === "") {
				$arr_err_msg[] = sprintf(ERROR_MSG_PARAM, "page");
			}

			if(count($arr_err_msg) >= 1) { // id나 page 공백일 때
				throw new Exception(implode("<br>", $arr_err_msg));
			}
			// 에러메세지가 1 이상일 때 예외발생
	
			if($title === "") {
				$arr_err_msg[] = sprintf(ERROR_MSG_PARAM, "제목");
			}
			// title 공백 제출 시 $arr_err_msg[]에 에러메세지 저장
			// 상수선언해둔 결과로 "제목을 입력해 주세요." 출력
			if($content === "") {
				$arr_err_msg[] = sprintf(ERROR_MSG_PARAM, "내용");
			}
			$now = date("Y-m-d H:i:s");
			// var_dump($now);
			// title과 동일
			// 에러 메세지가 없을 경우에 업데이트 처리
			if(count($arr_err_msg) === 0) { // 에러메세지가 0일 경우 
				$arr_param = [
					"id" => $id
					,"title" => $title
					,"content" => $content
					,"update_at" => $now
				];
				// $_POST 제출 값 $arr_param에 저장
			
				$conn->beginTransaction();
				// 메소드 post일 경우에만 트랜잭션 시작
				if(!db_update_boards_id($conn, $arr_param)) {
					throw new Exception("DB Error : Update_Boards_id");
				}
				$conn->commit();
				header("Location: 02_detail.php?id={$id}&page={$page}");
				exit;
			}
		}
		$arr_param = [
			"id" => $id
		];
		// 게시글 데이터 조회 위한 파라미터 세팅 

		$result = db_select_boards_id($conn, $arr_param);					

		if($result === false) { // id 조회 실패일 경우
			throw new Exception("DB Error : PDO Select_id");
		} else if(!(count($result) === 1)) { // id 조회의 결과가 1이 아닌 경우
			throw new Exception("DB Error : PDO Select_id Count, ".count($result));
		}
		// 오류 부분
		$item = $result[0];
		if($http_method === "GET"){ // GET으로 처음 고유의 값 tit랑 con을 받아온다
			$tit_stay= $item["title"];
			$con_stay= $item["content"];
		} else { // 에러가 떴을 때 수정 중인 내용을 POST 메소드에 저장해서 그 값을 val값으로 넣어줘서 변경 중이던 값을 그대로 출력할 수 있게 해준다
			$tit_stay= $_POST["title"];
			$con_stay= $_POST["content"];
		}
		
		// 오류 부분
	} catch(Exception $e) {
		if(!$http_method === "POST") {
			$conn->rollBack();
		}		
		// try문에서 예외 발생 시 catch문 실행, 메소드가 POST일 때는 트랜잭션으로 롤백
		echo $e->getMessage(); 
		exit;			
	} finally {
		db_destroy_conn($conn);
	}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>update</title>
	<link rel="stylesheet" href="./css/common.css">
	<link href="https://fonts.googleapis.com/css2?family=Orbit&display=swap" rel="stylesheet">
</head>
<body>
	<?php
		require_once(FILE_HEADER);
	?>
	<main>		
		<div class="main_layout">	
			<form class="update_form" action="03_update.php" method="post">
				<input type="hidden" name="id" value="<?php echo $id ?>">
				<input type="hidden" name="page" value="<?php echo $page ?>">			
				<table class="board_table">
					<colgroup>
						<col width="10%"> 
						<col width="20%">
						<col width="50%">
						<col width="20%">
					</colgroup>
					<thead class="board_table_head">
						<tr>
							<th class="head_th_1">번호</th>
							<th class="head_th_1">제목</th>
							<th class="head_th_1">내용</th>
							<th class="head_th_1">수정일</th>
						</tr>					
					</thead>
					<tbody class="board_table_body">
						<?php
							foreach($result as $item) {
						?>
						<tr height="618px">
							<td class="body_td_2">
								<?php echo $item["id"]; ?>
							</td>						
							<td class="body_td_2">
								<label for="title"></label>
								<input class="up_textarea_1" name="title" id="title" value="<?php echo $tit_stay; ?>" 
								maxlength="25" spellcheck="false">
							</td>
							<td class="body_td_2">
								<textarea class="up_textarea_2" name="content" id="content" cols="25" rows="10"
								maxlength="300" spellcheck="false"><?php echo $con_stay; ?></textarea>				
							</td>
							<td class="body_td_2">
								<?php echo $item["update_at"]; ?>
							</td>
						</tr>
						<?php	
						} 
						?>				
					</tbody>
				</table>
				<br>
				<div class="update_err_layout">
					<?php
						foreach ($arr_err_msg as $item) {
					?>					
					<span class="update_err"><?php echo $item; ?></span>
					<?php
						}
					?>	
				</div>					 				
				<div class="paging_layout">										
					<button class="btn" type="button" onclick="location.href='02_detail.php?id=<?php echo $id; ?>&page=<?php echo $page; ?>'">취 소</button>
					<button class="btn" type="submit">수 정</button>												
				</div>
							 
			</form>  		
		</div>
	</main>
	<script src="./css/style.js"></script>
</body>
</html>