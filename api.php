<?
    // session_start();

    if (!array_key_exists('action', $_GET)) {
        echo "{}";
        return;
    }

    include("functions.php");
    $p = file_get_contents("php://input");

    switch ($_GET['action']) {
    case 'skimmers_in_polygon':
        echo json_encode(skimmers_in_polygon($p)); 
        break;
    case 'save_polygons':
        $ret = save_polygons($_GET['ownCall'], $p);
        echo json_encode(array("status" => $ret));
        break;
    case 'view_calendar':
        $page = intval($_GET['page']+0);
        echo json_encode(calendar($page),  JSON_UNESCAPED_SLASHES); 
        break;
    case 'save_calendar':
        $ret = save_calendar($p);
        echo json_encode(array("status" => $ret));
        break;
    case 'del_calendar':
        $id = intval($_GET['id']);
        $all = intval($_GET['all']);
        $ret = del_calendar($id, $all);
        echo json_encode(array("status" => $ret));
        break;
    default:
        echo "{}";
        break;
    }
?>
