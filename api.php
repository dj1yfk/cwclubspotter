<?
    // session_start();

    if (!array_key_exists('action', $_GET)) {
        echo "{}";
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
    default:
        echo "{}";
        break;
    }
?>
