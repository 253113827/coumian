<?php
header('Content-Type: application/json');

$response = array();

if ($_FILES['sound_file']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['sound_file']['tmp_name'];
    $name = basename($_FILES['sound_file']['name']);
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    
    // 只允许上传音频文件
    $allowed_extensions = array('mp3', 'wav', 'ogg');
    
    if (in_array($extension, $allowed_extensions)) {
        // 生成唯一的文件名
        $new_filename = uniqid() . '.' . $extension;
        $upload_path = 'uploads/sounds/' . $new_filename;
        
        if (move_uploaded_file($tmp_name, $upload_path)) {
            $response['success'] = true;
            $response['message'] = '文件上传成功';
            $response['file_path'] = $upload_path;
        } else {
            $response['success'] = false;
            $response['message'] = '文件保存失败';
        }
    } else {
        $response['success'] = false;
        $response['message'] = '只允许上传 MP3, WAV 或 OGG 格式的音频文件';
    }
} else {
    $response['success'] = false;
    $response['message'] = '文件上传失败: ' . $_FILES['sound_file']['error'];
}

echo json_encode($response);
