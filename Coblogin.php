<?php
error_reporting(E_ALL ^ E_ALL);
//error_reporting(E_ALL ^ E_NOTICE);
ob_end_flush();
session_start();
$ini_array = parse_ini_file(__DIR__."/myapp.ini");


$url = $ini_array["BASE_URL"].$ini_array["COB_LOGIN_URL"];
$node_url = $ini_array["NODE_URL"];
$login_url = $ini_array["BASE_URL"].$ini_array["USER_LOGIN_URL"];
$linked_acc_url = $ini_array["BASE_URL"].$ini_array["GET_ACCOUNTS_URL"];
$trans_url = $ini_array["BASE_URL"].$ini_array["GET_TRANSACTIONS_URL"];
$fast_link_url = $ini_array["BASE_URL"].$ini_array["GET_FASTLINK_URL"].'appIds=10003600';
$cobrand = $ini_array["COBRAND_LOGIN"];
$cobrandPassword = $ini_array["COBRAND_PASSWORD"];
$cobSession = "";
$userSession = "";
$allDataSet = "";
//print_r($_GET);
//var_dump($_REQUEST);
if (isset($_GET["action"]) && !empty($_GET["action"]))
{
    $action = $_GET["action"];
    if($action == 'init')
    {
        if($cobSession == null)
            $cobSession = cobrandLoginSession($url, $cobrand, $cobrandPassword);
        if($cobSession != null && strlen($cobSession) > 0)
        {
            $_SESSION['cobSession'] = $cobSession;
            $data = ['cobSession'=> $cobSession];
            header('Content-Type: text/plain; charset=utf-8');
            echo json_encode($data);
            //echo json_encode($response);
            //$response = ['cobSession':$cobSession];
        }
        else {
            $response = ['error'=>'true', 'message'=>'Cobrand Configuration Check Failed. Please check settings in config.properties'];
            header('Content-Type: text/plain; charset=utf-8');
            echo json_encode($response);
        }
    }
    else
        {

            $userSession = $_SESSION['userSession'];
            $cobSession = $_SESSION['cobSession'];
            if($userSession != null && strlen($userSession) > 0)
            {
                if($action == 'getAccounts')
                {
                    $getAccounts = getUserAccounts($linked_acc_url, $cobSession, $userSession);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($getAccounts);

                }
                if($action == 'getFastLinkToken')
                {

                    //echo "in fast link url";
                    $getFastLinkToken = getFastLinkAccount($fast_link_url, $cobSession, $userSession);
                    $data = ['userSession'=>$userSession,'fastlinkToken'=>$getFastLinkToken,'nodeUrl'=>$node_url,'dataset'=>$allDataSet];
                    //print_r($data);
                    header('Content-Type: application/json; charset=utf-8');
                   // print_r(json_encode($data));
                    echo json_encode($data);

                }
                if($action == 'getTransactions')
                {
                    $accountId = $_GET['accountId'];
                    //echo "the account id in transactons: $accountId";
                    $getTransactions = getTransactions($trans_url, $accountId, $cobSession, $userSession);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($getTransactions);

                }
                if($action == 'deleteAccount')
                {
                    $accountId = $_GET['accountId'];
                    echo "this is the numbr of account:$accountId
                    ";
                    $deleteAccount = deleteAccounts($linked_acc_url, $accountId, $cobSession, $userSession);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($deleteAccount);

                }
            }
    }
}
if (isset($_POST) && !empty($_POST)) {
    $userLogin = $_POST["username"];
    $userPassword = $_POST["password"];
    $cobSession = $_SESSION['cobSession'];
    $userSession = userLogin($login_url, $cobSession, $userLogin, $userPassword);
    $_SESSION['userSession'] = $userSession;

    if ($userSession != null) {
        $data = ['error' => 'false', 'message' => 'User authentication successfull.'];
        header('Content-Type: text/plain; charset=utf-8');
        echo json_encode($data);
    } else {
        $data = ['error' => 'true', 'message' => 'Error in user Login, Invalid user credentials.'];
        header('Content-Type: text/plain; charset=utf-8');
        echo json_encode($data);
    }
}

function getUserAccounts($url, $cobSession, $userSession)
{
    $linked_acc_url = $url.'?container=creditCard';
    $userTokenSession = 'Authorization:cobSession=' . $cobSession . ',userSession=' . $userSession;
    $ch2 = curl_init($url);
    curl_setopt($ch2, CURLOPT_URL, $linked_acc_url);
    curl_setopt($ch2, CURLOPT_HEADER, 0);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Cobrand-Name:restserver', 'Api-Version:1.1', $userTokenSession));
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch2);
    curl_close($ch2);
    $errorDetails = checkForError($response);
    if (!empty($errorDetails)) {
        echo "error in the function";
    } else {
        return $response;
    }
}
function getFastLinkAccount($url, $cobSession, $userSession)
{
    $fast_link_url = $url;
    $userTokenSession = 'Authorization:cobSession=' . $cobSession . ',userSession=' . $userSession;
    $ch2 = curl_init($fast_link_url);
    curl_setopt($ch2, CURLOPT_URL, $fast_link_url);
    curl_setopt($ch2, CURLOPT_HEADER, 0);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Cobrand-Name:restserver', 'Api-Version:1.1', $userTokenSession));
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch2);
    curl_close($ch2);

    $errorDetails = checkForError($response);
    if (!empty($errorDetails)) {
        echo "error in the function";
    } else {
        $responseObj = json_decode($response, true);
        $fastlinkToken = $responseObj[user][accessTokens][0][value];
        return $fastlinkToken;
    }
}

function getTransactions($url, $accountId, $cobSession, $userSession)
{
    $newDate = date("Y-m-d", strtotime("-5 month"));
    //echo $newDate;
    $transactions_acc_url = $url.'?fromDate='.$newDate.'+&accountId='.$accountId;
    $userTokenSession = 'Authorization:cobSession=' . $cobSession . ',userSession=' . $userSession;
    //echo $userTokenSession;
    $ch3 = curl_init($url);
    curl_setopt($ch3, CURLOPT_URL, $transactions_acc_url);
    curl_setopt($ch3, CURLOPT_HEADER, 0);
    curl_setopt($ch3, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Cobrand-Name:restserver', 'Api-Version:1.1', $userTokenSession));
    curl_setopt($ch3, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch3);
    curl_close($ch3);
    //print_r($response);
    $errorDetails = checkForError($response);
    if (!empty($errorDetails)) {
        echo "error in the function";
    } else {
        return $response;
    }

}
function deleteAccounts($url, $accountId, $cobSession, $userSession)
{

    $transactions_acc_url = $url.$accountId;
    $userTokenSession = 'Authorization:userSession=' . $userSession . ',cobSession=' . $cobSession;
    $ch3 = curl_init($transactions_acc_url);
    curl_setopt($ch3, CURLOPT_URL, $transactions_acc_url);
    curl_setopt($ch3, CURLOPT_HEADER, 0);
    curl_setopt($ch3, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch3, CURLOPT_HTTPHEADER, array('Cobrand-Name:restserver', 'Api-Version:1.1', $userTokenSession));
    curl_setopt($ch3, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch3);
    curl_close($ch3);
    //echo "in delete account\n\n";
    print_r($response);
    echo "\n\n";
    $errorDetails = checkForError($response);
    if (!empty($errorDetails)) {
        echo "error in the function";
    } else {
        return $response;
    }

}


    function cobrandLoginSession($cobLoginUrl, $cobrandLogin, $cobrandPassword)
    {
        $response = cobLogin($cobLoginUrl, $cobrandLogin, $cobrandPassword);
        //echo "the rresponse: ";
        //print_r($response);
        //echo"\n\n";
        $errorDetails = checkForError($response);
        if (!empty($errorDetails)) {
            echo "error in the function";
        } else {
            $cobSessionToken = parseCobLogin($response);
            //echo $cobSessionToken;
            return $cobSessionToken;
        }
    }




    function parseCobLogin($response)
    {
        //print_r($response["body"]);
        $responseObj = parseJson($response["body"]);
        //print_r($responseObj);
        $cobSessionToken = $responseObj['session']['cobSession'];
        return $cobSessionToken;
    }

    function parseUserLogin($response)
    {
        $responseObj = parseJson($response["body"]);
        $cobSessionToken = $responseObj['user']['session']['userSession'];
        return $cobSessionToken;
    }

    function userLogin($userLoginUrl, $cobSessionToken, $userLogin, $userPassword)
    {
        $response = userDetailLogin($userLoginUrl, $cobSessionToken, $userLogin, $userPassword);
        //print_r($response);
        $errorDetails = checkForError($response);
        if (!empty($errorDetails)) {
            //echo("error in user login");
        } else {
            $userSessionToken = parseUserLogin($response);
            return $userSessionToken;
        }
    }

    function cobLogin($url, $cobrandLogin, $cobrandPassword)
    {
        $request = $url;
        $cobrandLoginJson = array('cobrandLogin' => $cobrandLogin, 'cobrandPassword' => $cobrandPassword);
        $postargs = json_encode($cobrandLoginJson);
        $postargs = '{"cobrand":' . $postargs . '}';
        //echo "in post args: $postargs \n$request";
        $responseObj = httpPost($request, $postargs, null, null);
        //print_r($responseObj);
        return $responseObj;
    }

    function userDetailLogin($url, $cobSession, $userLogin, $userPassword)
    {
        $request = $url;
       //echo "in here:$request";
        $userLoginJson = array('loginName' => $userLogin, 'password' => $userPassword);
        $postargs = json_encode($userLoginJson);
        $postargs = '{"user":' . $postargs . '}';
        //echo $postargs;

        $responseObj = httpPost($request, $postargs, $cobSession, null);
        return $responseObj;
    }

    function httpPost($request, $postargs, $cobSession, $userSession)
    {
        $auth = null;
        if (!empty($cobSession)) {
            $auth = "{cobSession=" . $cobSession . "}";
        }
        if (!empty($cobSession) && !empty($userSession)) {
            $auth = "{cobSession=" . $cobSession . ",userSession=" . $userSession . "}";
        }

        $session = curl_init($request);
        curl_setopt ($session, CURLOPT_POST, true);
        curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
        curl_setopt($session, CURLOPT_HTTPHEADER, array('Authorization:'.$auth,'Content-type: application/json','Cobrand-Name:restserver', 'Api-Version:1.1'));
        curl_setopt($session, CURLOPT_HEADER, true);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($session);

        if ($response === false) {
            // Utils::logMessage(self::fq_name,'Curl error: ' . curl_error($session));
            //Utils::logMessage(self::fq_name," URL : ".$request."".PHP_EOL.PHP_EOL);
        }

        $header_size = curl_getinfo($session, CURLINFO_HEADER_SIZE);
        $headers = get_headers_from_curl_response($response);
        $body = substr($response, $header_size);
        $httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);

        curl_close($session);
        //$responseDetails;
        $details["httpcode"] = $httpcode;
        $details["body"] = $body;
        $details["headers"] = $headers;
        //print_r($details);
        return $details;
    }

    function get_headers_from_curl_response($response)
    {
        $headers = array();
        $links = array();
        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

        foreach (explode("\r\n", $header_text) as $i => $line)
            if ($i === 0)
                $headers['http_code'] = $line;
            else {
                list ($key, $value) = explode(': ', $line);
                if ($key == "Link") {
                    //echo "This is Link Headerss...".PHP_EOL;
                    $linksSize = count($links);
                    //echo "linksSize...".$linksSize.PHP_EOL;
                    //if(count($links)) $links[$linksSize++] = $value;
                    list($k, $v) = explode(';', $value);
                    $links[$v] = $k;
                    $headers[$key] = $links;
                } else {
                    $headers[$key] = $value;
                }
            }
        return $headers;
    }

    function checkForError($response)
    {
        $body = $response;
        $responseObj = parseJson($body);
        if (!empty($responseObj['errorCode']))
            return $responseObj;
        else
            return null;
    }

    function parseJson($json)
    {
        ////echo "\n\n";
        //sprint_r($json);
        //echo "\n\n";
        return json_decode($json, true);
    }

    ?>
