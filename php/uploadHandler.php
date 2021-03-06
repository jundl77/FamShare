<?php
include "../includes/sanitize.php";
include "../includes/utility.php";
$configs = include('../config/server/server_config.php');

/**
 * This is the backup upload script. It does not have resumable uploads and it interacts with Dropzone instead of
 * with resumable.js.
 */

if (!empty($_FILES)) {
    $configs = $GLOBALS["configs"];

    // Get root directories
    $rootDir = $configs["root_upload_dirs"]["upload_data"];
    $thumbDir = $configs["root_upload_dirs"]["upload_data_thumb"];

    // Get allowed file types
    $exts = $configs["legal_exts"];

    // Get allowed file size
    $max_size_script = $configs["max_size_byte_script"];

    // 1 GB in bytes
    $ONE_GB = 1073741824;

    if (!file_exists($rootDir) || !file_exists($thumbDir)) {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-type: text/plain');
        exit("A root directory does not exist");
    }

    $path = sanitize($_POST["filePath"]);
    if ($path == false && !empty($path)) {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-type: text/plain');
        exit("Invalid file path: " . $path . ".");
    }

    foreach ($_FILES as $file) {
        $file_name = sanitize($file['name']);
        if (!$file_name) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-type: text/plain');
            exit("Invalid file name");
        }

        $file_size = sanitize($file['size']);
        if (!$file_size) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-type: text/plain');
            exit("Invalid file size");
        }

        $file_tmp_name = $file['tmp_name'];

        $fileExt = strtolower(end(explode('.', $file_name)));
        if ($file_name === '') {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-type: text/plain');
            exit("Please select a file");
        } elseif (!in_array($fileExt, $exts)) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-type: text/plain');
            exit("Wrong file type selected");
        } elseif ($file_size >= $max_size_script) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-type: text/plain');
            exit("File is too large (max. " . $max_size_script / $ONE_GB . " GB)");
        } elseif (!file_exists($rootDir . $path)) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-type: text/plain');
            exit("File directory does not exist");
        } else if ($file['error'] !== UPLOAD_ERR_OK) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-type: text/plain');
            exit("Upload failed with error code " . sanitize($_FILES['file']['error']));
        } else {
            ini_set('upload_max_filesize', $configs["upload_max_filesize"]);
            ini_set('post_max_size', $configs["post_max_size"]);
            ini_set('max_input_time', $configs["max_input_time"]);
            ini_set('max_execution_time', $configs["max_execution_time"]);
            $storePath = $rootDir . $path . $file_name;
            $thumbPath = $thumbDir . $path . $file_name;

            if (move_uploaded_file($file_tmp_name, $storePath)) {
                make_thumb($storePath, $thumbPath, $fileExt, 200);
            } else {
                header('HTTP/1.1 500 Internal Server Error');
                header('Content-type: text/plain');
                exit("Unexpected error encountered");
            }
        }
    }
}