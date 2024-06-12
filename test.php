<?php
class bookmark
{
    private function __construct()
    {
    }
    private static $singleton = null;

    protected function send_html_file($file)
    {
        $this->send_html(file_get_contents($file));
    }

    public function send_html($html)
    {
        header("Content-type: text/html;charset=utf-8");
        echo $html;
    }
    protected function db_connect() {
        $servername = "localhost"; 
        $username = "root";  
        $password = ""; 
        $dbname = "bookmark_app";
    
        $conn = new mysqli($servername, $username, $password, $dbname);
    
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    
        return $conn;
    }
    
    protected function logout($uid)
    {
        
        session_start();
        session_destroy();
        unset($_SESSION[$uid]);
        header("Location: test.php");
        
        
    }
    protected function login($username, $password) {
        $conn = $this->db_connect();
        $username = $conn->real_escape_string($username); 
        $password = $conn->real_escape_string($password);
    
        $auth_sql = "SELECT uid, user_name,password FROM users WHERE user_name='$username' AND password='$password'";
        $auth_result = $conn->query($auth_sql);
    
        if ($auth_result && $auth_result->num_rows > 0) {
            $auth_result = $auth_result->fetch_assoc();
            session_start();
            $_SESSION['uid'] = $auth_result['uid'];
            $_SESSION['user_name'] = $auth_result['user_name'];
            $html=file_get_contents("test.html");  
            $html = str_replace('$uname$', $auth_result['user_name'], $html);
            $html = str_replace('$uid$', $auth_result['uid'], $html);
            static::$singleton->send_html($html);
            exit;
        } else {
            static::$singleton->send_html_file("login.html");
        }
    
        $conn->close();

    }
    protected function extract_title($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36');

        $html = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return null;
        }

        preg_match('/<title>(.*?)<\/title>/si', $html, $matches);
        if (isset($matches[1])) {
            return trim($matches[1]);
        } else {
            return null;  
        }
    }

    protected function add_bookmark( $url ) {
        $uid=$_SESSION['uid'];
        $title = $this->extract_title($url);
        if($title != null){
            $add_bookmark_sql="INSERT INTO bookmarks (user_id, title, url) VALUES ('$uid', '$title', '$url')";
            $conn = $this->db_connect();
            $add_res = mysqli_query($conn, $add_bookmark_sql);
            $conn->close();
        }else{
        }
        return;
    }



    protected function fetch_bookmarks($uid) {
        $conn = $this->db_connect();
        $uid = $conn->real_escape_string($uid);
        
        $fetch_bookmarks_sql = "SELECT * FROM bookmarks WHERE user_id='$uid'";
        $bookmarks_result = $conn->query($fetch_bookmarks_sql);
        
        $bookmarks = array();
        if ($bookmarks_result && $bookmarks_result->num_rows > 0) {
            while ($bookmark = $bookmarks_result->fetch_assoc()) {
                $bookmarks[] = $bookmark;
            }
        }
        
        $conn->close();
        
        return $bookmarks;
    }

    protected function fetch_shared_bookmarks($uid) {
        $conn = $this->db_connect();
        $uid = $conn->real_escape_string($uid);
        $fetch_shared_bookmarks_sql = "SELECT * FROM bookmarks WHERE user_id!='$uid' AND ownership='public'";
        $shared_result = $conn->query($fetch_shared_bookmarks_sql);
    }
    

    
    

    public static function dispatch($req)
    {
        if (is_null(static::$singleton)) {
            static::$singleton = new bookmark();
        }
        $action = isset($req["action"]) ? $req["action"] : "";
        switch ($action) {
            case "login":
                static::$singleton->login($req['username'],$req['password']);
                break;
            // case "signup":
            //     static::$singleton->signup();
                // break;
            case "logout":
                $x=fopen("test.txt","a");
                fwrite($x,json_encode($req));
                fclose($x);
                static::$singleton->logout($req['uid']);
            case "add_bookmark":
                $x=fopen("test.txt","a");
                fwrite($x,json_encode($req));
                fclose($x);
                static::$singleton->add_bookmark($req["url"]);
                break;
            case "fetch_bookmark":
                    $bookmarks = static::$singleton->fetch_bookmarks($req["uid"]);
                    header('Content-Type: application/json');
                    echo json_encode($bookmarks);
                    break;
            // case 'fetch_shared_bookmark':
            //     $bookmarks = static::$singleton->fetch_shared_bookmarks($uid);
            //         ($req['uid']);
                // header('Content-Type: application/json');
                    // echo json_encode($bookmarks);
                    // break;

            
                

            default:static::$singleton->send_html_file("login.html");
                break;
        }
    }
}

bookmark::dispatch($_REQUEST);
