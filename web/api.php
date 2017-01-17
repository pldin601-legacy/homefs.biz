<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/core/application.class.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/core/functions.core.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/core/common.core.php");

$config = homefs::app('conf')->config();
$fs = homefs::app('filesystem');

homefs::app('account')->login_session();

ob_start("my_ghandler");

$begin = microtime(true);
site_init();

switch (filter_input(INPUT_GET, 'action'))
{
    case 'get_dir':
        echo json_encode($fs->get_dir($_GET['id']));
        break;
    case 'get_path':
        echo json_encode($fs->build_parent_ids($_GET['id']));
        break;
    case 'get_dir_info':
        echo json_encode($fs->get_directory_info($_GET['id']));
        break;
    case 'go':
        $id = _get_default('id', 0);
        if ($id == 0) $id = null;
        $this_dir = $fs->get_dir($id);
        $this_path = $fs->build_parent_ids($id);
        $this_info = $fs->get_directory_info($id);
        $total_dirs = $fs->count_dirs($id);
        $total_files = $fs->count_files($id);
        $file_start = _get_default('start', 0);
        $file_max = $config['navigation']['files_per_block'];
        $result_dirs = $fs->list_dirs($id, $file_start, $file_max);
        $result_files = $fs->list_files($id, $file_start - $total_dirs + count($result_dirs), $file_max - count($result_dirs));
        $files_ids = $fs->concat_files_ids($result_files);
        $result_metadata = $fs->get_file_metadata($files_ids);
        echo json_encode(array(
            'dir' => $this_dir,
            'path' => $this_path,
            'info' => $this_info,
            'dirs' => $result_dirs,
            'files' => $result_files,
            'meta' => $result_metadata,
            'variables' => array(
                'start_from' => $file_start,
                'total_dirs' => $total_dirs,
                'total_files' => $total_files,
                'files_max' => $file_max
            )
        ));
        break;
    case 'search':
        $query = _get_default('q', '');
        $file_start = _get_default('start', 0);
        $file_max = $config['navigation']['files_per_block'];

        $benchmark_start = microtime(true);

        /* Special query test */
        if (preg_match("/^\#\w+\s{0,1}.*/", $query))
        {
            $splits = explode(' ', substr($query, 1), 2);
            if (count($splits) == 2)
                list($param, $value) = $splits;
            else if (count($splits) == 1)
                $param = $splits[0];

            switch ($param)
            {
                case 'dups':
                    $result_files = $fs->get_dup_files($file_start, $file_max);
                    $result_dirs = array();
                    $total_files = $fs->dup_files_count();
                    $total_dirs = 0;
                    break;
                case 'artist':
                    $result_files = $fs->get_files_by_artist($value, $file_start, $file_max);
                    $total_files = $fs->count_files_by_artist($value);
                    $result_dirs = array();
                    $total_dirs = 0;
                    break;
                case 'genre':
                    $result_files = $fs->get_files_by_genre($value, $file_start, $file_max);
                    $total_files = $fs->count_files_by_genre($value);
                    $result_dirs = array();
                    $total_dirs = 0;
                    break;
                case 'type':
                    $result_files = $fs->get_files_by_type($value, $file_start, $file_max);
                    $total_files = $fs->count_files_by_type($value);
                    $result_dirs = array();
                    $total_dirs = 0;
                    break;
            }
        }
        else
        {
            $query_encoded = $fs->encode_query($query);
            //$total_files = $fs->search_files_count($query_encoded);
            //$total_dirs = $fs->search_dirs_count($query_encoded);
            list($total_dirs, $result_dirs) = $fs->search_dirs($query_encoded, $file_start, $file_max);
            list($total_files, $result_files) = $fs->search_files($query_encoded, $file_start - $total_dirs + count($result_dirs), $file_max - count($result_dirs));
        }


        $benchmark_duration = number_format(microtime(true) - $benchmark_start, 3);

        $files_ids = $fs->concat_files_ids($result_files);
        $result_metadata = $fs->get_file_metadata($files_ids);

        echo json_encode(array(
            'dirs' => $result_dirs,
            'files' => $result_files,
            'meta' => $result_metadata,
            'variables' => array(
                'query' => $query,
                'start_from' => $file_start,
                'total_dirs' => $total_dirs,
                'total_files' => $total_files,
                'benchmark' => $benchmark_duration,
                'files_max' => $file_max
            )
        ));

        if (($file_start == 0) && ($total_files > 0 || $total_dirs > 0))
            $fs->log_search_request($query);

        break;
    case 'popup':
        $query = _get_default('q', '');

        echo json_encode(array($fs->get_search_requests($query)));
        break;
    case 'wave':
        if (!$id = _get_default('id'))
        {
            json_error('File id not set!');
            break;
        }
        if (!$wavedata = $fs->get_waveform($id))
        {
            json_error('No wave data found!');
            break;
        }

        echo json_encode(array('wavedata' => base64_encode($wavedata)));
        break;
    case 'checkicon':
        if (!$id = _get_default('id'))
        {
            json_error('File id not set!');
            break;
        }
        $metadata = $fs->get_single_file_metadata($id);
        if (isset($metadata, $metadata['width'], $metadata['height']))
            echo json_encode(array('cover_present' => 1));
        else
            echo json_encode(array('cover_present' => 0));
        break;
    case 'icon':
        if (!$id = _get_default('id'))
        {
            json_error('File id not set!');
            break;
        }
        if (!$filename = $fs->get_file_path($id))
        {
            json_error('File not found!');
            break;
        }
        $filenamequote = escapeshellarg($filename);
        header("Content-type: image/jpg");
        passthru($config['bin']['ffmpeg'] . " -i $filenamequote -vf scale=46:-1 -ss 0 -f image2 -vframes 1 -");
        break;
    case 'sub':
        if (!$id = _get_default('id'))
        {
            json_error('File id not set!');
            break;
        }
        $parent = $fs->get_parent_id($id);
        $childs = $fs->list_dirs($parent, 0, 100);
        echo json_encode(array('dirs' => $childs));
        break;
    case 'test':
        $query = isset($_GET['q']) ? $_GET['q'] : false;
        $query_encoded = encode_query($query);
        print_r($query_encoded);
        break;
    default:
        echo json_error('Unknown action!');
        break;
}

function json_error($msg)
{
    die(json_encode(array('error' => $msg)));
}

?>