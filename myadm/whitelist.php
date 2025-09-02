<?include("include.php");

check_login();

if($_REQUEST["st"] == "svr_del") {
	if($_REQUEST["del_server_alln"] == "") alert("編號錯誤。", 0);	
	
	$pdo = openpdo(); 	  
    $query = $pdo->prepare("update servers_log set indel=1 where auton in (".$_REQUEST["del_server_alln"].")");    
    $query->execute($input);
    
    alert("刪除完成。", 0);
    die();
}
$isdel = $_GET["isdel"];

if($isdel == "1") $tts = "刪除回收區";
  
top_html();
?>

<section id="middle">
	<div id="content" class="dashboard padding-20">
		<div id="panel-1" class="panel panel-default">
			<div class="panel-heading">
				<span class="title elipsis">
					<strong><a href="whitelist">白名單</a></strong> <!-- panel title -->								
					<?if($tts) echo "<small>".$tts."</small>"?>
					<?if($_GET["keyword"] != "") echo "<small>搜尋：".$_GET["keyword"]."</small>"?>
				</span>

				<!-- right options -->
				<ul class="options pull-right list-inline">								
					<li><a href="#" class="opt panel_fullscreen hidden-xs" data-toggle="tooltip" title="Fullscreen" data-placement="bottom"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></a></li>
				</ul>
				<!-- /right options -->

			</div>
			<!-- panel content -->
			<div class="panel-body">
				<form name="form2" method="get" class="form-inline nomargin noborder padding-bottom-10">	              	              	              	
					<div class="form-group">
						<input type="text" name="search_keyword" id="search_keyword" class="form-control" placeholder="搜尋 ID、伺服器、帳號" value="">                
					</div>
					<div class="form-group">
						<button type="button" id="search_btn" class="btn btn-primary">
							<i class="fa fa-search"></i> 搜尋
						</button>
						<button type="button" id="clear_btn" class="btn btn-default">
							<i class="fa fa-times"></i> 清除
						</button>
						<button type="button" id="refresh_btn" class="btn btn-success">
							<i class="fa fa-refresh"></i> 重新整理
						</button>
					</div>
					
				</form>
				
				<!-- 搜尋結果統計 -->
				<div id="search_stats" class="search-stats" style="display: none;">
					<i class="fa fa-info-circle"></i>
					<span id="stats_text"></span>
				</div>
						
				<div class="table-responsive">
					<table class="table table-hover">
					<thead>
						<tr>
							<th>伺服器</th>
							<th>帳號</th>
							<th>Fingerprint</th>
							<th>付款狀態</th>
							<th>建立時間</th>
							<th>更新時間</th>
						</tr>
					</thead>
									<tbody id='form_tbody'>
					<!-- API 資料將在這裡動態載入 -->
					<tr>
						<td colspan="6" class="text-center">
							<div class="loading-spinner">
								<i class="fa fa-spinner fa-spin fa-2x"></i>
								<p>載入中...</p>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<span id='pagestr'></span>
		

			</div>
			<!-- /panel content -->


		</div>

	</div>
</section>
<!-- /MIDDLE -->

 <?php
down_html();
?>

<style>
/* 表格美化樣式 */
.table {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    font-size: 16px; /* 增加表格整體字體大小 */
}

.table thead th {
    background-color: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
    font-weight: 600;
    text-align: center;
    padding: 15px 8px;
    font-size: 16px; /* 增加標題字體大小 */
}

.table tbody tr {
    transition: all 0.3s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.table tbody td {
    vertical-align: middle;
    padding: 12px 8px;
    border-top: 1px solid #e9ecef;
    font-size: 15px; /* 增加表格內容字體大小 */
}

/* 載入動畫 */
.loading-spinner {
    padding: 40px;
    text-align: center;
}

.loading-spinner i {
    color: #667eea;
    margin-bottom: 10px;
}

.loading-spinner p {
    color: #6c757d;
    margin: 0;
}

/* 無資料狀態 */
.no-data {
    padding: 40px;
    text-align: center;
}

.no-data i {
    margin-bottom: 15px;
    opacity: 0.5;
}

/* 錯誤狀態 */
.error-message {
    padding: 40px;
    text-align: center;
}

/* 標籤樣式 */
.label {
    padding: 6px 10px;
    border-radius: 12px;
    font-size: 13px; /* 增加標籤字體大小 */
    font-weight: 600;
    text-transform: uppercase;
}

.label-success {
    background-color: #28a745;
    color: white;
}

.label-warning {
    background-color: #ffc107;
    color: #212529;
}

.label-info {
    background-color: #17a2b8;
    color: white;
}

.label-primary {
    background-color: #007bff;
    color: white;
}

/* 徽章樣式 */
.badge {
    padding: 6px 10px;
    border-radius: 10px;
    font-size: 13px; /* 增加徽章字體大小 */
    font-weight: 600;
}

.badge-primary {
    background-color: #007bff;
    color: white;
}



/* 分頁樣式 */
.pagination {
    margin: 20px 0;
    justify-content: center;
}

.pagination > li > a {
    color: #667eea;
    border: 1px solid #dee2e6;
    margin: 0 2px;
    border-radius: 4px;
    padding: 8px 12px;
    transition: all 0.3s ease;
}

.pagination > li > a:hover {
    background-color: #667eea;
    border-color: #667eea;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

.pagination > .active > a {
    background-color: #667eea;
    border-color: #667eea;
    color: white;
    font-weight: bold;
}

.pagination > .disabled > a {
    color: #6c757d;
    cursor: not-allowed;
    opacity: 0.5;
}

.pagination > .disabled > a:hover {
    background-color: transparent;
    border-color: #dee2e6;
    color: #6c757d;
    transform: none;
    box-shadow: none;
}

/* 分頁圖示樣式 */
.pagination i {
    font-size: 12px;
}

/* 省略號樣式 */
.pagination .disabled a[href="#"] {
    background-color: transparent;
    border-color: transparent;
    color: #6c757d;
    cursor: default;
}

/* 搜尋區域樣式 */
.form-inline .form-group {
    margin-right: 15px;
    margin-bottom: 10px;
}

.form-inline .form-control {
    border-radius: 6px;
    border: 1px solid #ced4da;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    font-size: 16px; /* 增加輸入框字體大小 */
}

.form-inline .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn-primary {
    background-color: #667eea;
    border-color: #667eea;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background-color: #5a6fd8;
    border-color: #5a6fd8;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

.btn-default {
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-default:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-success {
    background-color: #28a745;
    border-color: #28a745;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
}

/* 搜尋結果統計 */
.search-stats {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px 15px;
    margin-bottom: 15px;
    font-size: 16px; /* 增加搜尋統計字體大小 */
    color: #6c757d;
}

/* 響應式設計 */
@media (max-width: 768px) {
    .table-responsive {
        border: none;
    }
    
    .btn-group-xs .btn {
        padding: 6px 10px;
        font-size: 14px; /* 增加手機版按鈕字體大小 */
    }
    
    .table tbody td {
        padding: 10px 6px;
        font-size: 14px; /* 增加手機版表格字體大小 */
    }
    
    .form-inline .form-group {
        margin-right: 10px;
        margin-bottom: 8px;
    }
    
    .form-inline .form-control {
        font-size: 16px; /* 增加手機版輸入框字體大小 */
    }
}
</style>

<script type="text/javascript">
// 全域變數
let allData = [];
let filteredData = [];
let currentPage = 1;
const itemsPerPage = 10;

// 定義 fetchList 函數
async function fetchList() {
    try {
        const response = await fetch('https://backend.pcgame.tw/api/fingerprint/main');
        const data = await response.json();
        return data;
    } catch (error) {
        throw error;
    }
}

// 格式化日期
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('zh-TW', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// 渲染表格資料
function renderTable(data, page = 1) {
    const tbody = $('#form_tbody');
    const startIndex = (page - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageData = data.slice(startIndex, endIndex);
    
    let html = '';
    
    if (pageData.length === 0) {
        html = `
            <tr>
                <td colspan="6" class="text-center">
                    <div class="no-data">
                        <i class="fa fa-inbox fa-3x text-muted"></i>
                        <p class="text-muted">暫無資料</p>
                    </div>
                </td>
            </tr>
        `;
    } else {
        pageData.forEach(item => {
            const statusClass = item.is_paid === '1' ? 'success' : 'warning';
            const statusText = item.is_paid === '1' ? '已付款' : '未付款';
            
            // 處理 fingerprint 資料，支援多個 fingerprint 分開顯示
            let fingerprintHtml = '';
            if (item.fingerprint) {
                const fingerprints = Array.isArray(item.fingerprint) ? item.fingerprint : [item.fingerprint];
                fingerprintHtml = fingerprints.map(fp => 
                    `<span class="badge badge-primary" style="display: block; margin: 2px 0;">${fp}</span>`
                ).join('');
            } else {
                fingerprintHtml = '<span class="text-muted">無</span>';
            }
            
            html += `
                <tr class="data-row">
                    <td><span class="">${item.server_name}[${item.server}]</span></td>
                    <td><strong>${item.account}</strong></td>
                    <td>${fingerprintHtml}</td>
                    <td><span class="label label-${statusClass}">${statusText}</span></td>
                    <td><small class="text-muted">${formatDate(item.created_at)}</small></td>
                    <td><small class="text-muted">${formatDate(item.updated_at)}</small></td>
                </tr>
            `;
        });
    }
    
    tbody.html(html);
}

// 渲染分頁
function renderPagination(data) {
    const totalPages = Math.ceil(data.length / itemsPerPage);
    const pagination = $('#pagestr');
    
    if (totalPages <= 1) {
        pagination.html('');
        return;
    }
    
    let html = '<nav><ul class="pagination pagination-sm">';
    
    // 首頁
    if (currentPage > 1) {
        html += `<li><a href="#" onclick="changePage(1)" title="首頁"><i class="fa fa-angle-double-left"></i></a></li>`;
    } else {
        html += '<li class="disabled"><a href="#" title="首頁"><i class="fa fa-angle-double-left"></i></a></li>';
    }
    
    // 上一頁
    if (currentPage > 1) {
        html += `<li><a href="#" onclick="changePage(${currentPage - 1})" title="上一頁"><i class="fa fa-angle-left"></i></a></li>`;
    } else {
        html += '<li class="disabled"><a href="#" title="上一頁"><i class="fa fa-angle-left"></i></a></li>';
    }
    
    // 頁碼
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    // 如果開始頁面不是第一頁，顯示省略號
    if (startPage > 1) {
        html += `<li><a href="#" onclick="changePage(1)">1</a></li>`;
        if (startPage > 2) {
            html += '<li class="disabled"><a href="#">...</a></li>';
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            html += `<li class="active"><a href="#">${i}</a></li>`;
        } else {
            html += `<li><a href="#" onclick="changePage(${i})">${i}</a></li>`;
        }
    }
    
    // 如果結束頁面不是最後一頁，顯示省略號
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += '<li class="disabled"><a href="#">...</a></li>';
        }
        html += `<li><a href="#" onclick="changePage(${totalPages})">${totalPages}</a></li>`;
    }
    
    // 下一頁
    if (currentPage < totalPages) {
        html += `<li><a href="#" onclick="changePage(${currentPage + 1})" title="下一頁"><i class="fa fa-angle-right"></i></a></li>`;
    } else {
        html += '<li class="disabled"><a href="#" title="下一頁"><i class="fa fa-angle-right"></i></a></li>';
    }
    
    // 末頁
    if (currentPage < totalPages) {
        html += `<li><a href="#" onclick="changePage(${totalPages})" title="末頁"><i class="fa fa-angle-double-right"></i></a></li>`;
    } else {
        html += '<li class="disabled"><a href="#" title="末頁"><i class="fa fa-angle-double-right"></i></a></li>';
    }
    
    html += '</ul></nav>';
    
    // 添加頁面資訊
    html += `<div class="text-muted" style="font-size: 15px;">顯示 ${(currentPage - 1) * itemsPerPage + 1} - ${Math.min(currentPage * itemsPerPage, data.length)} 筆，共 ${data.length} 筆資料</div>`;
    
    pagination.html(html);
}

// 切換頁面
function changePage(page) {
    currentPage = page;
    renderTable(filteredData, page);
    renderPagination(filteredData);
}



// 搜尋功能
function performSearch() {
    const keyword = $('#search_keyword').val().toLowerCase().trim();
    
    filteredData = allData.filter(item => {
        // 關鍵字搜尋
        const keywordMatch = !keyword || 
            item.id.toString().includes(keyword) ||
            item.server.toLowerCase().includes(keyword) ||
            item.account.toLowerCase().includes(keyword) ||
            (item.fingerprint && (
                Array.isArray(item.fingerprint) 
                    ? item.fingerprint.some(fp => fp.toLowerCase().includes(keyword))
                    : item.fingerprint.toLowerCase().includes(keyword)
            ));
        
        return keywordMatch;
    });
    
    currentPage = 1; // 重置到第一頁
    renderTable(filteredData, 1);
    renderPagination(filteredData);
    
    // 顯示搜尋統計
    updateSearchStats(keyword);
}

// 更新搜尋統計
function updateSearchStats(keyword) {
    const statsDiv = $('#search_stats');
    const statsText = $('#stats_text');
    
    if (keyword) {
        let stats = `找到 ${filteredData.length} 筆符合條件的資料`;
        stats += ` (關鍵字: "${keyword}")`;
        
        statsText.text(stats);
        statsDiv.show();
    } else {
        statsDiv.hide();
    }
}

// 清除搜尋
function clearSearch() {
    $('#search_keyword').val('');
    filteredData = allData;
    currentPage = 1;
    renderTable(allData, 1);
    renderPagination(allData);
    $('#search_stats').hide();
}

// 重新整理功能
function refreshList() {
    currentPage = 1; // 重置到第一頁
    renderTable(allData, 1);
    renderPagination(allData);
    $('#search_stats').hide(); // 清除搜尋統計
}

// 等待 DOM 載入完成
$(document).ready(function () {
    // 載入資料
    (async function() {
        try {
            const data = await fetchList();
            allData = data;
            filteredData = data;
            renderTable(data, 1);
            renderPagination(data);
        } catch (error) {
            $('#form_tbody').html(`
                <tr>
                    <td colspan="6" class="text-center">
                        <div class="error-message">
                            <i class="fa fa-exclamation-triangle fa-2x text-danger"></i>
                            <p class="text-danger">載入資料失敗</p>
                        </div>
                    </td>
                </tr>
            `);
        }
    })();
    
    // 搜尋按鈕事件
    $('#search_btn').on('click', performSearch);
    
    // 清除按鈕事件
    $('#clear_btn').on('click', clearSearch);

    // 重新整理按鈕事件
    $('#refresh_btn').on('click', refreshList);
    
    // 關鍵字輸入框按 Enter 搜尋
    $('#search_keyword').on('keypress', function(e) {
        if (e.which === 13) {
            performSearch();
        }
    });
});

</script>

