<?php

/**
 * 網站初始作業
 */
class BaseWork
{
    /**
     * SESSION設定時間
     * @param int $expire
     */
    public static function start_session($expire = 0)
    {
        if ($expire == 0) {
            $expire = ini_get('session.gc_maxlifetime');
        } else {
            ini_set('session.gc_maxlifetime', $expire);
        }

        if (empty($_COOKIE['PHPSESSID'])) {
            session_set_cookie_params($expire);
            session_start();
        } else {
            session_start();
            setcookie('PHPSESSID', session_id(), time() + $expire);
        }
    }

    /**
     * +----------------------------------------------------------
     * Cookie 設置、獲取、清除 (支持數組或對像直接設置) 2009-07-9
     * +----------------------------------------------------------
     * 1 獲取cookie: cookie('name')
     * 2 清空當前設置前綴的所有cookie: cookie(null)
     * 3 刪除指定前綴所有cookie: cookie(null,'think_') | 註：前綴將不區分大小寫
     * 4 設置cookie: cookie('name','value') | 指定保存時間: cookie('name','value',3600)
     * 5 刪除cookie: cookie('name',null)
     * +----------------------------------------------------------
     * @param string $name cookie名稱
     * @param string $value cookie值
     * @param string $option cookie設置
     * +----------------------------------------------------------
     * $option 可用設置prefix,expire,path,domain
     * 支持數組形式:cookie('name','value',array('expire'=>1,'prefix'=>'think_'))
     * 支持query形式字符串:cookie('name','value','prefix=tp_&expire=10000')
     */
    public static function cookie($name, $value = '', $option = null)
    {
        // 默認設置
        $config = array(
            'prefix' => '', // cookie 名稱前綴
            'expire' => 3600, // cookie 保存時間
            'path' => '/',   // cookie 保存路徑
            'domain' => '', // cookie 有效域名
        );
        // 參數設置(會覆蓋默認設置)
        if (!empty($option)) {
            if (is_numeric($option))
                $option = array('expire' => $option);
            elseif (is_string($option))
                parse_str($option, $option);
            $config = array_merge($config, array_change_key_case($option));
        }
        // 清除指定前綴的所有cookie
        if (is_null($name)) {
            if (empty($_COOKIE)) return;
            // 要刪除的cookie前綴，不指定則刪除config設置的指定前綴
            $prefix = empty($value) ? $config['prefix'] : $value;
            if (!empty($prefix)) // 如果前綴為空字符串將不作處理直接返回
            {
                foreach ($_COOKIE as $key => $val) {
                    if (0 === stripos($key, $prefix)) {
                        setcookie($key, '', time() - 3600, $config['path'], $config['domain']);
                        unset($_COOKIE[$key]);
                    }
                }
            } else { //參數為空 設置也為空 刪除所有cookie
                foreach ($_COOKIE as $key => $val) {
                    setcookie($key, '', time() - 3600, $config['path'], $config['domain']);
                    unset($_COOKIE[$key]);
                }
            }
            return;
        }
        $name = $config['prefix'] . $name;
        if ('' === $value) {
            return isset($_COOKIE[$name]) ? unserialize($_COOKIE[$name]) : null; // 獲取指定Cookie
        } else {
            if (is_null($value)) {
                setcookie($name, '', time() - 3600, $config['path'], $config['domain']);
                unset($_COOKIE[$name]); // 刪除指定cookie
            } else {
                // 設置cookie
                $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
                setcookie($name, serialize($value), $expire, $config['path'], $config['domain']);
                $_COOKIE[$name] = serialize($value);
            }
        }
    }

    /**
     * get變數替代方案
     * @param string $str 名稱
     * @return mixed|null
     */
    public static function _get($str)
    {
        $val = !empty($_GET[$str]) ? $_GET[$str] : null;
        return $val;
    }

    /**
     * post變數替代方案
     * @param string $str 名稱
     * @return mixed|null
     */
    public static function _post($str)
    {
        $val = !empty($_POST[$str]) ? $_POST[$str] : null;
        return $val;
    }
}
/**
 * 系統行為
 */
class SYSAction
{

    /**
     * Dialog(自定義彈出對話框)
     */
    public static function DialogMsg()
    {
        if (BaseWork::cookie('Msg')) {
            echo "<div id=\"dialog-message\" class=\"hide\"><p>" . BaseWork::cookie('Msg') . "</p></div>";
        }
    }

    /**
     * Dialog js code(自定義彈出對話框)
     */
    public static function DialogJs()
    {
        if (BaseWork::cookie('Msg')) {
            echo "
					$.widget(\"ui.dialog\", $.extend({}, $.ui.dialog.prototype, {
						_title: function(title) {
						var \$title = this.options.title || '&nbsp;'
						if( (\"title_html\" in this.options) && this.options.title_html == true )
							title.html(\$title);
						else title.text(\$title);
						}
					}));
					var dialog = $( \"#dialog-message\" ).removeClass('hide').dialog({
							modal: true,
							title: \"<div class='widget-header widget-header-small'><h4><i class='ace-icon fa fa-info-circle'></i> <b>訊息通知</b></h4></div>\",
							title_html: true,
							buttons: [{
								text: \"OK\",
								\"class\" : \"btn btn-primary btn-minier\",
								click: function() {
									$( this ).dialog( \"close\" ); 
								} 
							}]
					});
			";
        }
    }

    /**
     * 驗證是否有登入和此帳號是否擁有檢視當前頁面的權限
     * @param string $MM_restrictGoTo 驗證失敗要跳轉的頁面
     */
    public static function SYS_Login_Chk($MM_restrictGoTo)
    {
        //判斷此帳號是否擁有檢視當前頁面的權限
        $page = $_GET['PageName'];
        if (isset($_SESSION['SYS_Username'])) {
            if ($page != "") { //如果是首頁或自己的帳號修改頁面則略過此判斷

                MYPDO::$table = 'sys_left_menu';
                MYPDO::$join = ['sys_admin_permissions' => ['sys_left_menu.id', 'sys_admin_permissions.left_menu_id', 'LEFT', '=']];
                MYPDO::$where = [
                    'url' => $page,
                    'admin_group_id' => $_SESSION['SYS_UserGroup']
                ];
                $results = MYPDO::select();
                $count = count($results);
                $on = SYSAction::SQL_Data('sys_left_menu', 'url', $page, 'switch');
                if ($count == 0 && $on == 'ON') {
                    header("Location: " . $MM_restrictGoTo);
                    exit;
                }
            }
        } else if (isset($_SESSION['COM_UserID'])) {
            if ($page != "" && $page != "profile") { //如果是首頁或自己的帳號修改頁面則略過此判斷
                MYPDO::$table = 'sys_left_menu';
                MYPDO::$join = ['sys_company_permissions' => ['sys_left_menu.id', 'sys_company_permissions.left_menu_id', 'LEFT', '=']];
                MYPDO::$where = [
                    'url' => $page,
                    'company_id' => $_SESSION['COM_UserID']
                ];
                $results = MYPDO::select();
                $count = count($results);
                $on = SYSAction::SQL_Data('sys_left_menu', 'url', $page, 'switch');
                if ($count == 0 && $on == 'ON') {
                    header("Location: " . $MM_restrictGoTo);
                    exit;
                }
            }
        } else {
            //如果未登入即跳轉
            header("Location: " . $MM_restrictGoTo);
            exit;
        }
    }

    /**
     * 取出某資料表單筆資料
     * @param string $table_name 資料表名稱。
     * @param string $in_title 篩選條件欄位名稱。
     * @param string $val 篩選值。
     * @param string $out_title 輸出欄位。
     */
    public static function SQL_Data($table_name, $in_title, $val, $out_title)
    {

        MYPDO::$table = $table_name;
        MYPDO::$where = [
            $in_title => $val
        ];

        $row = MYPDO::first();
        if (!empty($row))
            return $row[$out_title];
    }

    /**
     * 工業設定左側選單
     */
    public static function SYS_LeftMenu()
    {
        MYPDO::$table = 'sys_left_menu';
        MYPDO::$join = [
            'sys_admin_permissions' => ['sys_left_menu.id', 'sys_admin_permissions.left_menu_id', 'LEFT', '=']
        ];
        MYPDO::$where = [
            'admin_group_id' => $_SESSION['SYS_UserGroup'],
            'switch' => 'ON',
            'type' => 'SYS'
        ];
        MYPDO::$field = 'sys_left_menu.*';
        MYPDO::$order = [
            'sort' => 'asc',
            'sys_left_menu.id' => 'asc'
        ];
        $results = MYPDO::select();

        echo '<ul class="nav nav-list">';

        foreach ($results as $row) {
            $li_class = $row['url'] == BaseWork::_get('PageName') ? "active" : "";
            //當選單為單層選單時
            if ($row['belong_id'] == "" && $row['url'] != "") {
                echo '
					<li class="' . $li_class . '">
						<a href="?PageName=' . $row['url'] . '">
							<i class="menu-icon fa fas fa-wrench"></i>
							<span class="menu-text"> ' . $row['name'] . ' </span>
						</a>

						<b class="arrow"></b>
					</li>
				';
            }
            //當選單為兩層選單時
            if ($row['belong_id'] == "" && $row['url'] == "") {
                $NOW_belong_id = self::SQL_Data('sys_left_menu', 'url', BaseWork::_get('PageName'), 'belong_id');
                $li_class2 = $NOW_belong_id == $row['id'] ? "active open" : "";
                echo '
					<li class="' . $li_class2 . '">
						<a href="#" class="dropdown-toggle">
							<i class="menu-icon fa fas fa-wrench"></i>

							<span class="menu-text"> ' . $row['name'] . ' </span>

							<b class="arrow fa fa-angle-down"></b>
						</a>

						<b class="arrow"></b>

						<ul class="submenu">

					';
                //撈出第二層選單資料
                MYPDO::$table = 'sys_left_menu';
                MYPDO::$join = [
                    'sys_admin_permissions' => ['sys_left_menu.id', 'sys_admin_permissions.left_menu_id', 'LEFT', '=']
                ];
                MYPDO::$where = [
                    'admin_group_id' => $_SESSION['SYS_UserGroup'],
                    'belong_id' => $row['id'],
                    'switch' => 'ON',
                    'type' => 'SYS'
                ];
                MYPDO::$order = [
                    'sort' => 'asc',
                    'sys_left_menu.id' => 'asc'
                ];
                $results2 = MYPDO::select();

                foreach ($results2 as $row2) {
                    $li_class = $row2['url'] == BaseWork::_get('PageName') ? "active" : "";
                    echo '
							<li class="' . $li_class . '">
								<a href="?PageName=' . $row2['url'] . '">
									<i class="menu-icon fa fa-caret-right"></i>
									' . $row2['name'] . '
								</a>

								<b class="arrow"></b>
							</li>
					';
                }
                echo '
							
						</ul>
					</li>
				';
            }
        }
        echo '</ul>';
    }

    /**
     * 企業帳號左側選單
     */
    public static function COM_LeftMenu()
    {
        MYPDO::$table = 'sys_left_menu';
        MYPDO::$join = [
            'sys_company_permissions' => ['sys_left_menu.id', 'sys_company_permissions.left_menu_id', 'LEFT', '='],
            'sys_company' => ['sys_company_permissions.company_id', 'sys_company.id', 'LEFT', '='],
        ];
        // MYPDO::$where = [
        //     'sys_company.id' => $_SESSION['COM_UserID'], //company_id
        //     'left_menu_switch' => 'ON',
        //     'left_menu_type' => 'COM'
        // ];
        MYPDO::$where = 'sys_company.id = ' . $_SESSION['COM_UserID'] . ' and sys_left_menu.switch = "ON" and type = "COM"';
        MYPDO::$field = 'sys_left_menu.*';
        MYPDO::$order = [
            'sort' => 'asc',
            'sys_company.id' => 'asc'
        ];
        $results = MYPDO::select();

        echo '<ul class="nav nav-list">';

        foreach ($results as $row) {
            $li_class = $row['url'] == BaseWork::_get('PageName') ? "active open" : "";
            //當選單為單層選單時
            if ($row['belong_id'] == "" && $row['url'] != "") {
                echo '
					<li class="' . $li_class . '">
						<a href="?PageName=' . $row['url'] . '">
							<i class="menu-icon fa fas fa-wrench"></i>
							<span class="menu-text"> ' . $row['name'] . ' </span>
						</a>

						<b class="arrow"></b>
					</li>
				';
            }
            //當選單為兩層選單時
            if ($row['belong_id'] == "" && $row['url'] == "") {
                $NOW_belong_id = self::SQL_Data('sys_left_menu', 'url', BaseWork::_get('PageName'), 'belong_id');
                $li_class2 = $NOW_belong_id == $row['id'] ? "active open" : "";
                echo '
					<li class="' . $li_class2 . '">
						<a href="#" class="dropdown-toggle">
							<i class="menu-icon fa fas fa-wrench"></i>

							<span class="menu-text"> ' . $row['name'] . ' </span>

							<b class="arrow fa fa-angle-down"></b>
						</a>

						<b class="arrow"></b>

						<ul class="submenu">

					';
                //撈出第二層選單資料
                MYPDO::$table = 'sys_left_menu';
                MYPDO::$join = [
                    'sys_company_permissions' => ['sys_left_menu.id', 'sys_company_permissions.left_menu_id', 'LEFT', '='],
                    'sys_company' => ['sys_company_permissions.company_id', 'sys_company.id', 'LEFT', '='],
                ];
                // MYPDO::$where = [
                //     'sys_company.id' => $_SESSION['COM_UserID'], //company_id
                //     'belong_id' => $row['id'],
                //     'sys_left_menu.switch' => 'ON',
                //     'type' => 'COM'
                // ];
                MYPDO::$field = 'sys_left_menu.*';
                MYPDO::$where = 'sys_company.id = ' . $_SESSION['COM_UserID'] . ' and belong_id = ' . $row['id'] . ' and sys_left_menu.switch = "ON" and type = "COM"';
                MYPDO::$order = [
                    'sort' => 'asc',
                    'sys_company.id' => 'asc'
                ];
                $results2 = MYPDO::select();

                foreach ($results2 as $row2) {
                    if ($row2['url'] == BaseWork::_get('PageName'))
                        $li_class = "active open";
                    else
                        $li_class = "";
                    echo '
							<li class="' . $li_class . '">
								<a href="?PageName=' . $row2['url'] . '">
									<i class="menu-icon fa fa-caret-right"></i>
									' . $row2['name'] . '
								</a>

								<b class="arrow"></b>
							</li>
					';
                }
                echo '
							
						</ul>
					</li>
				';
            }
        }
        echo '</ul>';
    }

    /**
     * 上方選單導覽
     */
    public static function Menu_MAP()
    {

        $Now_Page_Name = self::SQL_Data('sys_left_menu', 'url', BaseWork::_get('PageName'), 'name');
        $Belong_Page_ID = self::SQL_Data('sys_left_menu', 'url', BaseWork::_get('PageName'), 'belong_id');
        if ($Belong_Page_ID) {
            $Belong_Page_ID2 = self::SQL_Data('sys_left_menu', 'id', $Belong_Page_ID, 'belong_id');
            if ($Belong_Page_ID2) {
                $Belong_Page_Name2 = self::SQL_Data('sys_left_menu', 'id', $Belong_Page_ID2, 'name');
                echo '<li>' . $Belong_Page_Name2 . '</li>'; //第一層(如果有的話)
            }
            $Belong_Page_Name = self::SQL_Data('sys_left_menu', 'id', $Belong_Page_ID, 'name');
            $Belong_Page_Url = self::SQL_Data('sys_left_menu', 'id', $Belong_Page_ID, 'url');
            //如果有url則變成超連結狀態
            if ($Belong_Page_Url)
                echo '<li><a href="index.php?PageName=' . $Belong_Page_Url . '">' . $Belong_Page_Name . '</a></li>'; //第二層(如果有的話)
            else
                echo '<li>' . $Belong_Page_Name . '</li>'; //第二層(如果有的話)
        }
        echo '<li>' . $Now_Page_Name . '</li>'; //目前頁面
    }

    /**
     * 獲取副檔名
     * @param string $path 帶入值為檔案名稱.副檔名。
     */
    public static function extension($path)
    {
        $qpos = strpos($path, "?");
        if ($qpos !== false)
            $path = substr($path, 0, $qpos);

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return $extension;
    }

    /**
     * 米瑟奇簡訊發送功能api
     * @param string $c 簡訊內容。
     * @param string $p 要接收簡訊的手機號碼。
     * URL參數說明:
     * id 使用者帳號 必填
     * sdate 發送時間(直接發送則不用設)
     * tel 電話一;電話二;電話三 必填 *max:100
     * password 密碼 必填
     * msg 簡訊內容 若使用URL編碼,參考附表二
     * mtype 簡訊種類 (預設G) G:一般簡訊（G為大寫）
     * encoding 簡訊內容的編碼方式 big5 (預設值)
     * utf8:簡訊內容採用UTF-8編碼
     * urlencode:簡訊內容採用URL編碼
     * urlencode_utf8:簡訊內容採用URL與UTF-8編碼
     */
    public static function MIKI_sms_send($user, $pwd, $c, $p)
    {        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.message.net.tw/send.php?id=' . $user . '&password=' . $pwd . '&tel=' . $p . '&msg=' . urlencode($c) . '&mtype=G&encoding=utf8');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //將curl_exec()獲取的訊息以文件流的形式返回，而不是直接輸出。 這參數很重要 因為如果有輸出的話你api 解析json時會有錯誤
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * google api 二維碼生成【QRcode可以存儲最多4296個字母數字類型的任意文本，具體可以查看二維碼數據格式】
     * @param string $chl 二維碼包含的信息，可以是數字、字符、二進制信息、漢字。
     * 不能混合數據類型，數據必須經過UTF-8 URL-encoded
     * @param int $widhtHeight 生成二維碼的尺寸設置
     * @param string $EC_level 可選糾錯級別，QR碼支持四個等級糾錯，用來恢復丟失的、讀錯的、模糊的、數據。
     * L-默認：可以識別已損失的7%的數據
     * M-可以識別已損失15%的數據
     * Q-可以識別已損失25%的數據
     * H-可以識別已損失30%的數據
     * @param int $margin 生成的二維碼離圖片邊框的距離
     */
    public static function generateQRfromGoogle($chl, $widhtHeight = '150', $EC_level = 'L', $margin = '0')
    {
        $chl = urlencode($chl);
        return '<img src="https://chart.apis.google.com/chart?chs=' . $widhtHeight . 'x' . $widhtHeight . '&cht=qr&chld=' . $EC_level . '|' . $margin . '&chl=' . $chl . '" border="0" alt="QR code" />';
    }
}

/**
 * 使用者行為
 */
class UserAction
{

    /**
     * 工業設定登入
     * @param string $loginUsername 帳號。
     * @param string $password 密碼
     * @param string $RememberMe 是否記住帳號(任意值存在代表記住)。
     */
    public static function SYS_Login($loginUsername, $password, $RememberMe)
    {
        if (isset($loginUsername)) {

            if (isset($RememberMe)) { //判斷是否要記住帳密
                BaseWork::cookie('s_remuser', $loginUsername, 86400 * 30); //設定使用者名稱的 Cookie 值,保留30天
                BaseWork::cookie('s_rempwd', Form_token_Core::URIAuthcode($password, 'ENCODE'), 86400 * 30); //設定使用者密碼的 Cookie 值,保留30天
                BaseWork::cookie('s_remchk', $RememberMe, 86400 * 30); //設定核取的 Cookie 值,保留30天
            } else {
                BaseWork::cookie('s_remuser', null); //去除使用者名稱的 Cookie 值
                BaseWork::cookie('s_remchk', null); //去除核取的 Cookie 值
            }

            MYPDO::$table = 'sys_admin';
            MYPDO::$where = [
                'account' => $loginUsername,
                'password' => hash('sha512', $password)
            ];
            $row = MYPDO::first();

            //判斷帳號是否存在
            if ($row['account'] != "") {
                //判斷帳號是否停用
                if ($row['switch'] == 'OFF') {
                    //帳號停用
                    BaseWork::cookie('Msg', '此帳號停用', 1);
                    header("Location:login.php");
                } else {
                    $_SESSION['SYS_Username'] = $loginUsername;
                    $_SESSION['SYS_UserGroup'] = $row['admin_group_id'];

                    //帳號密碼正確
                    BaseWork::cookie('Msg', $loginUsername . ' 您好，歡迎登入!!', 1);
                    header("Location:index.php");
                }
            } else {
                //帳號密碼不正確
                BaseWork::cookie('Msg', '您輸入的帳號或密碼有誤', 1);
                header("Location:login.php");
            }
        }
        //return;
    }

    /**
     * 企業設定登入
     * @param string $loginUsername 帳號。
     * @param string $password 密碼
     * @param string $RememberMe 是否記住帳號(任意值存在代表記住)。
     * @param boolean $sha512 代入的密碼是否加密，false(不加密)。
     */
    public static function COM_Login($loginUsername, $password, $RememberMe, $sha512 = true)
    {
        if (isset($loginUsername)) {

            if (isset($RememberMe)) { //判斷是否要記住帳密
                BaseWork::cookie('remuser', $loginUsername, 86400 * 30); //設定使用者名稱的 Cookie 值,保留30天
                BaseWork::cookie('rempwd', Form_token_Core::URIAuthcode($password, 'ENCODE'), 86400 * 30); //設定使用者密碼的 Cookie 值,保留30天
                BaseWork::cookie('remchk', $RememberMe, 86400 * 30); //設定核取的 Cookie 值,保留30天
            } else {
                BaseWork::cookie('remuser', null); //去除使用者名稱的 Cookie 值
                BaseWork::cookie('remchk', null); //去除核取的 Cookie 值
            }

            if ($sha512 === true)
                $password = hash('sha512', $password);

            MYPDO::$table = 'sys_company';
            MYPDO::$where = [
                'account' => $loginUsername,
                'password' => $password
            ];
            $row = MYPDO::first();

            //判斷帳號是否存在
            if ($row['account'] != "") {
                //判斷帳號是否停用
                if ($row['switch'] == 'OFF') {
                    //帳號停用
                    BaseWork::cookie('Msg', '此帳號停用', 1);
                    header("Location:login.php");
                } else {
                    $_SESSION['COM_Username'] = $loginUsername;
                    $_SESSION['COM_UserID'] = $row['id'];
                    $_SESSION['COM_UserGroup'] = $row['company_group_id'];

                    if (isset($_SESSION['cPrevUrl']) && false) {
                        $MM_redirectLoginSuccess = $_SESSION['cPrevUrl'];
                    }
                    //帳號密碼正確
                    BaseWork::cookie('Msg', $loginUsername . ' 您好，歡迎登入!!', 1);
                    header("Location:index.php");
                }
            } else {
                //帳號密碼不正確
                BaseWork::cookie('Msg', '您輸入的帳號或密碼有誤', 1);
                header("Location:login.php");
            }
        }
        //return;
    }

    /**
     * 創建多重資料夾函數
     * @param string $path 資料夾/路徑
     */
    public static function creatdir($path)
    {
        if (!is_dir($path)) {
            if (self::creatdir(dirname($path))) {
                $old = umask(0);
                mkdir($path, 0777);
                umask($old);
                return true;
            }
        } else {
            return true;
        }
        return false;
    }

    /**
     * 將欲上傳圖片按照等比例進行縮圖
     * 注意:png透明背景經過縮圖會失去透明效果
     * @param string $src 原圖存放路徑
     * @param string $dest 縮圖存放路徑
     * @param int $destW 縮圖寬
     * @param int $destH 縮圖高
     */
    public static function imagesResize($src, $dest, $destW, $destH)
    {
        if (file_exists($src) && isset($dest)) {
            //取得檔案資訊
            $srcSize = getimagesize($src);
            $srcExtension = $srcSize[2];
            $srcRatio = $srcSize[0] / $srcSize[1];
            //依長寬比判斷長寬像素
            if ($srcRatio > 1) {
                $destH = $destW / $srcRatio;
            } else {
                $destH = $destW;
                $destW = $destW * $srcRatio;
            }
        }
        //建立影像 
        $destImage = imagecreatetruecolor($destW, $destH);

        //根據檔案格式讀取圖檔 
        switch ($srcExtension) {
            case 1:
                $srcImage = imagecreatefromgif($src);
                break;
            case 2:
                $srcImage = imagecreatefromjpeg($src);
                break;
            case 3:
                $srcImage = imagecreatefrompng($src);
                break;
        }

        //取樣縮圖 
        imagecopyresampled(
            $destImage,
            $srcImage,
            0,
            0,
            0,
            0,
            $destW,
            $destH,
            imagesx($srcImage),
            imagesy($srcImage)
        );

        //輸出圖檔 
        switch ($srcExtension) {
            case 1:
                imagegif($destImage, $dest);
                break;
            case 2:
                imagejpeg($destImage, $dest, 85);
                break;
            case 3:
                imagepng($destImage, $dest);
                break;
        }
        //釋放資源
        imagedestroy($destImage);
    }

    /**
     * 根據條件取得下層資料ˋ
     * @param $selectColName 取得資料欄位名稱
     * @param $tableName 資料表名稱
     * @param $whereColName 篩選名稱
     * @param $whereColValue 篩選值
     
     */

    public static function getChildren($selectColName, $tableName, $whereColName, $whereColValue)
    {
        if (!isset($array_data)) {
            $array_data;
            // $array_data[] = $parentID;
        }
        $array_data[] = $whereColValue;
        for ($i = 0; $i < 99; $i++) {
            $data = SYSAction::SQL_Data($tableName, $whereColName, $array_data[$i], $selectColName);
            if ($data != "") {
                $array_data[] = $data;
            } else {
                break;
            }

        }

        return $array_data;
    }
}

/**
 * 表單令牌(防止表單惡意提交)
 */
class Form_token_Core
{

    const SESSION_KEY = 'SESSION_KEY';

    /**
     * 生成一個當前的token
     * @param string $form_name
     * @return string
     */
    public static function grante_token()
    {
        $key = self::grante_key();
        $_SESSION[SESSION_KEY] = $key;
        $token = md5(substr(time(), 0, 3) . $key);
        return $token;
    }

    /**
     * 生成一個密鑰
     * @return string
     */
    public static function grante_key()
    {
        $encrypt_key = md5(((float)date("YmdHis") + rand(100, 999)) . rand(1000, 9999));
        return $encrypt_key;
    }

    /**
     * 驗證一個當前的token
     * @param string $form_name
     * @return string
     */
    public static function is_token($token)
    {
        $key = $_SESSION[SESSION_KEY];
        $old_token = md5(substr(time(), 0, 3) . $key);
        if ($old_token == $token) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 刪除一個token
     * @param string $form_name
     * @return boolean
     */
    public static function drop_token()
    {
        unset($_SESSION[SESSION_KEY]);
        return true;
    }

    /**
     * 將字串進行加解密
     * @param string $string 明文 或 密文
     * @param string $operation DECODE表示解密,其它表示加密
     * @param string $key 密匙
     * @param int $expiry 密文有效期
     * @return false|string
     */
    public static function URIAuthcode($string, $operation = 'DECODE', $key = 'Winmai#Astra|45894216', $expiry = 0)
    {
        if ($operation == 'DECODE')
            $string = str_replace(array("-", "_"), array('+', '/'), $string);
        $ckey_length = 4;
        $key = md5($key ? $key : $GLOBALS['discuz_auth_key']);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace(array("=", "+", "/"), array('', '-', '_'), base64_encode($result));
        }
    }
}

/**
 * WebService API專用
 */
class Params
{

    const GET = "GET";
    const POST = "POST";
    const PUT = "PUT";
    const DELETE = "DELETE";

    private $params = array();
    private $method;

    public function __construct()
    {
        $this->_parseParams();
    }

    private function _parseParams()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];

        switch ($this->method) {
            case self::PUT:
                parse_str(file_get_contents('php://input'), $this->params);
                $GLOBALS["_{$this->method}"] = $this->params;

                // Add these request vars into _REQUEST, mimicing default behavior, PUT/DELETE will override existing COOKIE/GET vars
                $_REQUEST = $this->params + $_REQUEST;
                break;
            case self::DELETE:
                parse_str(file_get_contents('php://input'), $this->params);
                $GLOBALS["_{$this->method}"] = $this->params;

                // Add these request vars into _REQUEST, mimicing default behavior, PUT/DELETE will override existing COOKIE/GET vars
                $_REQUEST = $this->params + $_REQUEST;
                break;
            case self::GET:
                $this->params = $_GET;
                break;
            case self::POST:
                $this->params = $_POST;
                break;
        }
    }

    /**
     * @brief Lookup request params
     *
     * @param string $name
     *            Name of the argument to lookup
     * @param mixed $default
     *            Default value to return if argument is missing
     * @returns The value from the GET/POST/PUT/DELETE value, or $default if not set
     */
    public function get($name, $default = null)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        } else {
            return $default;
        }
    }

    public function getMethoad()
    {
        return $this->method;
    }
}

/**
 * MultiProgress 多進程運行類
 * @author Terrence
 */
class MProgress
{

    /** error_msg */
    const ERROR_MSG = array('status' => 'error', 'message' => 'popen is error');

    public function __construct()
    {
        /** 初始化進程池 */
        $this->pids = array();
    }

    /**
     * set 在進程池中進行任務設置
     * @param any 進程名字
     * @param string task任務路徑
     * @author Terrence
     */
    public function set($taskName = '', $taskPath)
    {
        if (empty($taskName)) {
            $this->pids[] = popen($taskPath, 'r');
        } else {
            $this->pids[$taskName] = popen($taskPath, 'r');
        }
    }

    /**
     * get 獲取進程執行結果
     * @param any $taskName 進程名字
     * @param boolean $isJson 返回的結果是否進行解json操作
     * @return array 執行結果
     * @author Terrence
     */
    public function get($taskName, $isJson = false)
    {
        try {
            // 讀取進程的執行結果 (讀取進程中 echo 出來的信息)
            $result = fgets($this->pids[$taskName]);
            if ($isJson) {
                try {
                    $result = json_decode($result, true);
                } catch (Exception $th) {
                    $result = [];
                }
            }
        } catch (Exception $th) {
            $result = $isJson ? [] : '';
        }
        // 殺死進程
        pclose($this->pids[$taskName]);
        unset($this->pids[$taskName]);
        return $result;
    }

    /**
     * getPids 獲取當前的進程池裡的進程名稱
     * @author Terrence
     */
    public function getPids()
    {
        return array_keys($this->pids);
    }

    /**
     * clear 清空進程池
     * @author Terrence
     */
    public function clear()
    {
        foreach ($this->pids as &$pid) {
            try {
                $single = fgets($pid);
                pclose($pid);
            } catch (Exception $th) {
            }
        }
        $this->pids = [];
    }
}

/*
 * Response JSON Format
 */
class Response
{

    /**
     * Response JSON Format
     *
     * @param string $status 狀態
     * @param string $message 狀態訊息
     * @param array $value json data array
     * @return array[[String]] Get JSON Text
     */
    public static function getResponseData($status, $message, $value): string
    {
        $responseJson = array("status" => $status, "message" => $message, "value" => $value);

        // 加 JSON_UNESCAPED_UNICODE，表示不被 UNICODE
        return stripslashes(json_encode($responseJson, JSON_UNESCAPED_UNICODE));
    }

    public static function decode($text): array
    {
        // json_decode 無法處理換行標記，所以要轉換
        //        $text = str_replace("\n", '\\\\n', $text);
        return json_decode($text, true);
    }
}

/*
 * Webservice API用
 */
class UrlManager
{

    const GET = "GET";
    const POST = "POST";

    public static function runRequest($url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // Set so curl_exec returns the result instead of outputting it.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Get the response and close the channel.
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public static function runRequestWithAuth($method, $url, $dataArray)
    {
        $username = AUTH_USER;
        $password = AUTH_PW;

        $headers = array(
            'Authorization: Basic ' . base64_encode("$username:$password")
        );

        if ($method === UrlManager::GET) {
            $url = $url . "?";
            foreach ($dataArray as $key => $value) {
                $url = $url . $key . "=" . $value . "&";
            }
            $url = substr($url, 0, -1);

            // Create a stream
            $opts = array(
                'http' => array(
                    'method' => $method,
                    'header' => $headers
                )
            );
        } else {
            $opts = array(
                'http' => array(
                    'method' => $method,
                    'header' => $headers,
                    'content' => http_build_query($dataArray)
                )
            );
        }

        if (preg_match("/^https/", $url)) {
            $opts["ssl"] = [
                "verify_peer" => false,
                "verify_peername" => false
            ];
        }

        $context = stream_context_create($opts);

        // Open the file using the HTTP headers set above
        return file_get_contents($url, false, $context);
    }
}
