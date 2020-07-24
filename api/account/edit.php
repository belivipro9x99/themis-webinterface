<?php
	//? |-----------------------------------------------------------------------------------------------|
	//? |  /api/account/edit.php                                                                        |
	//? |                                                                                               |
	//? |  Copyright (c) 2018-2020 Belikhun. All right reserved                                         |
	//? |  Licensed under the MIT License. See LICENSE in the project root for license information.     |
	//? |-----------------------------------------------------------------------------------------------|

	// SET PAGE TYPE
	define("PAGE_TYPE", "API");
	
	require_once $_SERVER["DOCUMENT_ROOT"] ."/lib/ratelimit.php";
	require_once $_SERVER["DOCUMENT_ROOT"] ."/lib/belibrary.php";
	require_once $_SERVER["DOCUMENT_ROOT"] ."/lib/logs.php";
	require_once $_SERVER["DOCUMENT_ROOT"] ."/module/config.php";

	if (!isLoggedIn())
		stop(11, "Bạn chưa đăng nhập", 401);
		
	checkToken();

	$username = reqForm("username");
	$id = getForm("id");
	$password = getForm("password");
	$name = getForm("name");

	if ($_SESSION["id"] !== "admin")
		stop(31, "Access Denied!", 403);

	require_once $_SERVER["DOCUMENT_ROOT"] ."/module/account.php";
		$acc = new account($username);

	if (!$acc -> dataExist())
		stop(13, "Tài khoản với tên người dùng \"$username\" không tồn tại!", 404, Array( "username" => $username ));

	// Avatar file process
	if (isset($_FILES["avatar"])) {
		$file = strtolower($_FILES["avatar"]["name"]);
		$extension = pathinfo($file, PATHINFO_EXTENSION);

		if (!in_array($extension, IMAGE_ALLOW))
			stop(43, "Không chấp nhận loại ảnh!", 400, Array( "allow" => IMAGE_ALLOW ));

		if ($_FILES["avatar"]["size"] > MAX_IMAGE_SIZE)
			stop(42, "Ảnh quá lớn!", 400, Array(
				"size" => $_FILES["avatar"]["size"],
				"max" => MAX_IMAGE_SIZE
			));

		if ($_FILES["avatar"]["error"] > 0)
			stop(-1, "Lỗi không rõ!", 500);

		$imagePath = AVATAR_DIR ."/". $username;
		$oldFiles = glob($imagePath .".{". join(",", IMAGE_ALLOW) ."}", GLOB_BRACE);

		// Find old avatar files and remove them
		if (count($oldFiles) > 0)
			foreach ($oldFiles as $oldFile) {
				$ext = pathinfo($oldFile, PATHINFO_EXTENSION);
				unlink($imagePath .".". $ext);
			}

		// Move new avatar
		move_uploaded_file($_FILES["avatar"]["tmp_name"], $imagePath .".". $extension);
	}

	$data = Array();
	$id ? $data["id"] = htmlspecialchars(strip_tags($id)) : null;
	$password ? $data["password"] = password_hash($password, PASSWORD_DEFAULT) : null;
	$name ? $data["name"] = htmlspecialchars(strip_tags($name)) : null;

	$res = $acc -> update($data);

	writeLog("OKAY", "Đã chỉnh sửa tài khoản [$id] \"$username\"");
	stop(0, "Chỉnh sửa tài khoản thành công!", 200, $data);