<?include("include.php");

// check_login();
  
top_html();
?>

<section id="middle">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-gift"></i> 手動派獎
                        </h3>
                        <div class="panel-actions">
                            <button type="button" class="btn btn-sm btn-info" id="itemSettingsBtn" title="物品設定">
                                <i class="fa fa-cog"></i> 物品設定
                            </button>
                        </div>
                    </div>
                    <div class="panel-body">
                        <!-- 階段指示器 -->
                        <div class="stage-indicator">
                            <div class="stage-item active" id="stage1-indicator">
                                <div class="stage-number">1</div>
                                <div class="stage-title">選擇伺服器與帳號</div>
                            </div>
                            <div class="stage-item" id="stage2-indicator">
                                <div class="stage-number">2</div>
                                <div class="stage-title">確認資訊並送出</div>
                            </div>
                        </div>

                        <!-- 第一階段：選擇伺服器與帳號 -->
                        <div id="stage1" class="stage-content">
                            <form id="stage1Form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="serverSelect">選擇伺服器 <span class="text-danger">*</span></label>
                                            <select class="form-control" id="serverSelect" name="serverSelect" required>
                                                <option value="">載入中...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="gameAccount">遊戲帳號 <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="gameAccount" name="gameAccount" placeholder="請輸入遊戲帳號" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="items-section">
                                            <div class="items-header">
                                                <h4>選擇物品與數量</h4>
                                                <div class="items-controls">
                                                    <button type="button" class="btn btn-sm btn-success" id="addItemBtn">
                                                        <i class="fa fa-plus"></i> 新增物品
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div id="itemsContainer" class="items-container">
                                                <!-- 物品項目將通過 JavaScript 動態產生 -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary btn-lg" id="nextStageBtn">
                                            <i class="fa fa-arrow-right"></i> 下一步
                                        </button>
                                        <button type="reset" class="btn btn-default btn-lg">
                                            <i class="fa fa-refresh"></i> 重置
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- 第二階段：確認資訊並送出 -->
                        <div id="stage2" class="stage-content" style="display: none;">
                            <div class="confirmation-section">
                                <h4><i class="fa fa-check-circle"></i> 確認贈送資訊</h4>
                                
                                <div class="panel panel-info">
                                    <div class="panel-heading">
                                        <h5 class="panel-title">贈送詳情</h5>
                                    </div>
                                    <div class="panel-body">
                                        <dl class="dl-horizontal">
                                            <dt>伺服器：</dt>
                                            <dd id="confirmServer">-</dd>
                                            <dt>遊戲帳號：</dt>
                                            <dd id="confirmGameAccount">-</dd>
                                            <dt>選擇物品：</dt>
                                            <dd id="confirmItems">-</dd>
                                        </dl>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-default btn-lg" id="prevStageBtn">
                                            <i class="fa fa-arrow-left"></i> 上一步
                                        </button>
                                        <button type="button" class="btn btn-success btn-lg" id="finalSubmitBtn">
                                            <i class="fa fa-send"></i> 確認送出禮物
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /MIDDLE -->

<!-- 物品設定 Modal -->
<div class="modal fade" id="itemSettingsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-cog"></i> 物品設定
                </h4>
            </div>
            <div class="modal-body">
                <div class="item-settings-section">
                    <h5 class="section-title">新增物品</h5>
                    <form id="itemSettingsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="itemGameName">物品遊戲名稱 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="itemGameName" name="itemGameName" placeholder="輸入物品在遊戲中的名稱" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="itemDatabaseName">資料庫物品名稱 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="itemDatabaseName" name="itemDatabaseName" placeholder="輸入資料庫中的物品名稱" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-right">
                                <button type="button" class="btn btn-success" id="addServerItemBtn">
                                    <i class="fa fa-plus"></i> 新增物品
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <hr class="modal-divider">
                
                <div class="server-items-section">
                    <h5 class="section-title">伺服器物品清單</h5>
                    <div class="items-table-container">
                        <div class="table-responsive">
                            <table class="table table-hover items-table" id="serverItemsTable">
                                <thead>
                                    <tr>
                                        <th width="40%">遊戲名稱</th>
                                        <th width="40%">資料庫名稱</th>
                                        <th width="20%">操作</th>
                                    </tr>
                                </thead>
                                <tbody id="serverItemsTableBody">
                                    <tr>
                                        <td colspan="3" class="text-center text-muted table-loading">
                                            <i class="fa fa-spinner fa-spin"></i> 載入中...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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


/* 表單樣式增強 */
.panel {
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    border: none;
    border-radius: 8px;
}

.panel-heading {
    background-color: #667eea;
    color: white;
    border-radius: 8px 8px 0 0;
    border: none;
}

.panel-title {
    font-size: 18px;
    font-weight: 600;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

.form-control {
    border-radius: 6px;
    border: 1px solid #ced4da;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* 階段指示器樣式 */
.stage-indicator {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
    position: relative;
}

.stage-indicator::before {
    content: '';
    position: absolute;
    top: 25px;
    left: 25%;
    right: 25%;
    height: 2px;
    background-color: #dee2e6;
    z-index: 1;
}

.stage-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    max-width: 200px;
    position: relative;
    z-index: 2;
}

.stage-number {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #dee2e6;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 20px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}

.stage-title {
    text-align: center;
    font-size: 14px;
    color: #6c757d;
    font-weight: 600;
    transition: color 0.3s ease;
}

.stage-item.active .stage-number {
    background-color: #667eea;
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.stage-item.active .stage-title {
    color: #667eea;
}

.stage-item.completed .stage-number {
    background-color: #28a745;
    color: white;
}

.stage-item.completed .stage-title {
    color: #28a745;
}

.stage-item.completed .stage-number::after {
    content: '✓';
    position: absolute;
    font-size: 16px;
}

/* 階段內容樣式 */
.stage-content {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.items-section {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    background-color: #f8f9fa;
    margin-top: 15px;
}

.items-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #dee2e6;
}

.items-header h4 {
    margin: 0;
    color: #495057;
}

.items-controls .btn {
    margin-left: 5px;
}

.items-container {
    min-height: 150px;
}

.item-group {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 15px;
    background-color: white;
    position: relative;
}

.item-group:last-child {
    margin-bottom: 0;
}

.item-group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.item-group-title {
    font-weight: 600;
    color: #495057;
    margin: 0;
}

.item-group-remove {
    color: #dc3545;
    cursor: pointer;
    font-size: 18px;
    transition: color 0.3s ease;
}

.item-group-remove:hover {
    color: #c82333;
}

.server-items-list h5 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 15px;
}

.list-group-item {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    font-size: 14px;
}

.list-group-item:first-child {
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
}

.list-group-item:last-child {
    border-bottom-left-radius: 6px;
    border-bottom-right-radius: 6px;
}

.list-group-item + .list-group-item {
    border-top: 0;
}

.item-name {
    font-weight: 600;
    color: #495057;
}

.item-database {
    font-size: 12px;
    color: #6c757d;
}

.confirmation-section .panel {
    margin-top: 20px;
}

.dl-horizontal dt {
    width: 120px;
    font-weight: 600;
    color: #495057;
}

.dl-horizontal dd {
    margin-left: 140px;
}

/* 按鈕樣式增強 */
.btn-lg {
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: #667eea;
    border-color: #667eea;
}

.btn-primary:hover {
    background-color: #5a6fd8;
    border-color: #5a6fd8;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

/* Panel heading 按鈕樣式 */
.panel-heading {
    position: relative;
}

.panel-actions {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
}

.panel-actions .btn {
    margin-left: 5px;
}

/* 物品設定 Modal 樣式 */
.modal-lg {
    width: 90%;
    max-width: 800px;
}

.modal-header .modal-title {
    color: #495057;
    font-weight: 600;
}

.modal-body .form-group label {
    font-weight: 600;
    color: #495057;
}

/* 模組區域樣式 */
.item-settings-section {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.server-items-section {
    background-color: #ffffff;
}

.section-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
}

.modal-divider {
    margin: 30px 0;
    border-top: 2px solid #e9ecef;
}

/* 表格樣式優化 */
.items-table-container {
    max-height: 350px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background-color: #ffffff;
}

.items-table {
    margin-bottom: 0;
    font-size: 14px;
}

.items-table thead {
    background-color: #667eea;
    color: white;
    position: sticky;
    top: 0;
    z-index: 10;
}

.items-table thead th {
    border: none;
    font-weight: 600;
    text-align: center;
    vertical-align: middle;
    padding: 15px 12px;
}

.items-table tbody td {
    vertical-align: middle;
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
    text-align: center;
}

.items-table tbody tr:hover {
    background-color: #f8f9fa;
}

.items-table tbody tr:last-child td {
    border-bottom: none;
}

.table-loading {
    padding: 40px !important;
    font-size: 16px;
}

.table-loading i {
    margin-right: 8px;
    color: #667eea;
}

/* 響應式設計 */
@media (max-width: 768px) {
    .table-responsive {
        border: none;
    }
    
    .btn-group-xs .btn {
        padding: 6px 10px;
        font-size: 14px;
    }
    
    .table tbody td {
        padding: 10px 6px;
        font-size: 14px;
    }
    
    .form-inline .form-group {
        margin-right: 10px;
        margin-bottom: 8px;
    }
    
    .form-inline .form-control {
        font-size: 16px;
    }
    
}
</style>

<script type="text/javascript">

// 階段管理變數
let currentStage = 1;
let stageData = {
    server: null,
    gameAccount: '',
    items: []
};

// 物品設定相關變數
let itemSettings = {
    gameName: '',
    databaseName: ''
};

// 物品管理相關變數
let serverItems = []; // 伺服器上的所有物品

// 等待 DOM 載入完成
$(document).ready(function () {
    // 載入伺服器列表
    loadServerList();
    
    
    
    // 綁定事件處理器
    bindEventHandlers();
    
    // 初始化表單
    initializeForm();
    
    // TODO: 載入物品設定將透過API實作
    
    // 初始化物品組
    initializeItemGroups();
});

// 載入伺服器列表
function loadServerList() {
    const serverSelect = $('#serverSelect');
    serverSelect.html('<option value="">載入中...</option>');
    
    // 從 API 載入伺服器資料
    $.ajax({
        url: 'api/gift_api.php',
        method: 'GET',
        data: { action: 'get_servers' },
        dataType: 'json'
    })
    .done(function(response) {
        if (response.success) {
            const servers = response.data;
            
            serverSelect.empty();
            serverSelect.append('<option value="">請選擇伺服器</option>');
            
            servers.forEach(function(server) {
                serverSelect.append(`<option value="${server.id}">${server.name}</option>`);
            });
            
            // 如果只有一個伺服器，自動選擇
            if (servers.length === 1) {
                serverSelect.val(servers[0].id);
                stageData.server = {
                    id: servers[0].id,
                    name: servers[0].name
                };
                // 載入該伺服器的物品清單
                loadServerItemsForSelect(servers[0].id);
            }
        } else {
            serverSelect.html('<option value="">載入失敗</option>');
            showNotification('載入伺服器清單失敗：' + response.message, 'error');
        }
    })
    .fail(function(xhr, status, error) {
        serverSelect.html('<option value="">載入失敗</option>');
        showNotification('載入伺服器清單失敗：連線錯誤', 'error');
        console.error('Load servers error:', error);
    });
}


// 綁定事件處理器
function bindEventHandlers() {
    // 伺服器選擇
    $('#serverSelect').change(function() {
        const selectedValue = $(this).val();
        const selectedText = $(this).find('option:selected').text();
        
        if (selectedValue) {
            stageData.server = {
                id: selectedValue,
                name: selectedText
            };
            // 載入該伺服器的物品清單
            loadServerItemsForSelect(selectedValue);
        } else {
            stageData.server = null;
            serverItems = [];
            updateAllItemOptions();
        }
    });
    
    // 遊戲帳號輸入
    $('#gameAccount').on('input', function() {
        stageData.gameAccount = $(this).val().trim();
    });
    
    // 新增物品按鈕
    $('#addItemBtn').click(function() {
        addItemGroup();
    });
    
    // 動態綁定移除物品事件
    $(document).on('click', '.item-group-remove', function() {
        removeItemGroup($(this).closest('.item-group'));
    });
    
    // 動態綁定物品輸入事件
    $(document).on('change input', '.item-select, .item-quantity', function() {
        updateStageDataItems();
    });
    
    
    // 下一步按鈕
    $('#nextStageBtn').click(function() {
        if (validateStage1()) {
            goToStage2();
        }
    });
    
    // 上一步按鈕
    $('#prevStageBtn').click(function() {
        goToStage1();
    });
    
    // 最終送出按鈕
    $('#finalSubmitBtn').click(function() {
        handleFinalSubmit();
    });
    
    // 重置按鈕
    $('#stage1Form').on('reset', function() {
        setTimeout(function() {
            resetStageData();
        }, 100);
    });
    
    // 物品設定按鈕
    $('#itemSettingsBtn').click(function() {
        openItemSettingsModal();
    });
    
    // 新增伺服器物品
    $('#addServerItemBtn').click(function() {
        addServerItem();
    });
    
    // 物品設定表單輸入監聽
    $('#itemGameName').on('input', function() {
        itemSettings.gameName = $(this).val().trim();
    });
    
    $('#itemDatabaseName').on('input', function() {
        itemSettings.databaseName = $(this).val().trim();
    });
}


// 初始化表單
function initializeForm() {
    // 初始化第一階段
    currentStage = 1;
    updateStageIndicators();
}

// 重置階段資料
function resetStageData() {
    stageData = {
        server: null,
        gameAccount: '',
        items: []
    };
}

// 更新階段指示器
function updateStageIndicators() {
    $('.stage-item').removeClass('active completed');
    
    for (let i = 1; i <= 2; i++) {
        const indicator = $(`#stage${i}-indicator`);
        if (i < currentStage) {
            indicator.addClass('completed');
        } else if (i === currentStage) {
            indicator.addClass('active');
        }
    }
}

// 驗證第一階段
function validateStage1() {
    let isValid = true;
    let errorMessages = [];
    
    // 檢查伺服器選擇
    if (!stageData.server) {
        errorMessages.push('請選擇伺服器');
        $('#serverSelect').focus();
        isValid = false;
    }
    
    // 檢查遊戲帳號
    if (!stageData.gameAccount) {
        errorMessages.push('請輸入遊戲帳號');
        if (isValid) $('#gameAccount').focus();
        isValid = false;
    }
    
    if (!isValid) {
        alert('請檢查以下問題：\n' + errorMessages.join('\n'));
    }
    
    return isValid;
}

// 進入第二階段
function goToStage2() {
    // 隱藏第一階段，顯示第二階段
    $('#stage1').hide();
    $('#stage2').show();
    currentStage = 2;
    updateStageIndicators();
    
    // 更新確認資訊
    updateConfirmationDisplay();
}

// 返回第一階段
function goToStage1() {
    $('#stage2').hide();
    $('#stage1').show();
    currentStage = 1;
    updateStageIndicators();
}

// 更新確認顯示
function updateConfirmationDisplay() {
    $('#confirmServer').text(stageData.server ? stageData.server.name : '-');
    $('#confirmGameAccount').text(stageData.gameAccount || '-');
    
    // 顯示選擇的物品
    if (stageData.items.length > 0) {
        let itemsText = '';
        stageData.items.forEach((item, index) => {
            if (index > 0) itemsText += '、';
            itemsText += `${item.name} x${item.quantity}`;
        });
        $('#confirmItems').text(itemsText);
    } else {
        $('#confirmItems').text('尚未選擇物品');
    }
}

// 驗證表單
function validateForm() {
    let isValid = true;
    let errorMessages = [];
    
    // 檢查必填欄位
    const recipient = $('#recipient').val().trim();
    const giftType = $('#giftType').val();
    
    if (!recipient) {
        errorMessages.push('請輸入收件人');
        $('#recipient').focus();
        isValid = false;
    }
    
    if (!giftType) {
        errorMessages.push('請選擇禮物類型');
        if (isValid) $('#giftType').focus();
        isValid = false;
    }
    
    // 檢查是否選擇了圖片
    if (selectedImages.length === 0) {
        errorMessages.push('請至少選擇一張圖片');
        isValid = false;
    }
    
    if (!isValid) {
        alert('請檢查以下問題：\n' + errorMessages.join('\n'));
    }
    
    return isValid;
}

// 處理最終送出
function handleFinalSubmit() {
    // 顯示確認對話框
    const confirmText = `確定要送出禮物嗎？\n\n` +
        `伺服器：${stageData.server.name}\n` +
        `遊戲帳號：${stageData.gameAccount}\n` +
        `選擇物品：${stageData.items.length} 項`;
    
    if (confirm(confirmText)) {
        submitGiftData();
    }
}

// 送出禮物資料
function submitGiftData() {
    // 顯示載入中
    const submitBtn = $('#finalSubmitBtn');
    const originalText = submitBtn.html();
    submitBtn.html('<i class="fa fa-spinner fa-spin"></i> 處理中...').prop('disabled', true);
    
    // 準備資料
    const giftData = {
        action: 'send_gift',
        server_id: stageData.server.id,
        server_name: stageData.server.name,
        game_account: stageData.gameAccount,
        items: JSON.stringify(stageData.items)
    };
    
    // 發送到後端API
    $.ajax({
        url: 'api/gift_api.php',
        method: 'POST',
        data: giftData,
        dataType: 'json'
    })
    .done(function(response) {
        if (response.success) {
            // 顯示成功訊息
            showNotification('禮物送出成功！', 'success');
            
            // 顯示詳細資訊
            alert('禮物送出成功！\n\n' + 
                  `伺服器：${stageData.server.name}\n` +
                  `遊戲帳號：${stageData.gameAccount}\n` +
                  `物品數量：${stageData.items.length} 項\n` +
                  `記錄編號：${response.data.log_id}`);
            
            // 重置表單並回到第一階段
            resetForm();
        } else {
            showNotification('禮物送出失敗：' + response.message, 'error');
        }
    })
    .fail(function(xhr, status, error) {
        showNotification('禮物送出失敗：連線錯誤', 'error');
        console.error('Submit gift error:', error);
    })
    .always(function() {
        // 恢復按鈕狀態
        submitBtn.html(originalText).prop('disabled', false);
    });
}

// 記錄派送資料
function recordDelivery(giftData) {
    // 新增派送記錄
    const deliveryRecord = {
        id: Date.now(), // 簡單的ID生成
        ...giftData,
        status: '已送出',
        created_at: new Date().toLocaleString('zh-TW')
    };
    
    // TODO: 送出到 API 保存派送記錄
    console.log('派送記錄:', deliveryRecord);
}

// 重置表單
function resetForm() {
    // 清空表單資料
    $('#serverSelect').val('');
    $('#gameAccount').val('');
    
    // 重置階段資料
    resetStageData();
    
    // 重新初始化物品組
    initializeItemGroups();
    
    // 回到第一階段
    goToStage1();
}

// 取得物品類型文字
function getItemTypeText(itemType) {
    const itemTypeText = {
        'points': '點數',
        'coupon': '優惠券',
        'item': '實體物品'
    };
    return itemTypeText[itemType] || itemType;
}

// 顯示派送記錄（可選功能）
function showDeliveryRecords() {
    // TODO: 從 API 載入派送記錄
    alert('此功能將透過 API 實作');
}

// 開啟物品設定 Modal
function openItemSettingsModal() {
    $('#itemSettingsModal').modal('show');
    
    // 載入現有設定到表單
    $('#itemGameName').val(itemSettings.gameName);
    $('#itemDatabaseName').val(itemSettings.databaseName);
    
    // 載入伺服器物品清單
    loadServerItems();
}


// 驗證物品設定表單
function validateItemSettings() {
    let isValid = true;
    let errorMessages = [];
    
    // 檢查必填欄位
    if (!itemSettings.gameName) {
        errorMessages.push('請輸入物品遊戲名稱');
        $('#itemGameName').focus();
        isValid = false;
    }
    
    if (!itemSettings.databaseName) {
        errorMessages.push('請輸入資料庫物品名稱');
        if (isValid) $('#itemDatabaseName').focus();
        isValid = false;
    }
    
    if (!isValid) {
        alert('請檢查以下問題：\n' + errorMessages.join('\n'));
    }
    
    return isValid;
}

// 新增伺服器物品
function addServerItem() {
    // 驗證表單
    if (!validateItemSettings()) {
        return;
    }
    
    // 檢查是否選擇了伺服器
    if (!stageData.server || !stageData.server.id) {
        alert('請先選擇伺服器');
        return;
    }
    
    const addBtn = $('#addServerItemBtn');
    const originalText = addBtn.html();
    addBtn.html('<i class="fa fa-spinner fa-spin"></i> 新增中...').prop('disabled', true);
    
    // 儲存到 API
    $.ajax({
        url: 'api/gift_api.php',
        method: 'POST',
        data: {
            action: 'add_server_item',
            server_id: stageData.server.id,
            game_name: itemSettings.gameName,
            database_name: itemSettings.databaseName
        },
        dataType: 'json'
    })
    .done(function(response) {
        if (response.success) {
            // 重新載入伺服器物品清單（用於選擇）
            loadServerItemsForSelect(stageData.server.id);
            
            // 重新載入伺服器物品清單（用於顯示）
            loadServerItems();
            
            // 顯示成功訊息
            showNotification('物品新增成功！', 'success');
            
            // 清空表單
            $('#itemGameName').val('');
            $('#itemDatabaseName').val('');
            itemSettings.gameName = '';
            itemSettings.databaseName = '';
        } else {
            showNotification('物品新增失敗：' + response.message, 'error');
        }
    })
    .fail(function(xhr, status, error) {
        showNotification('物品新增失敗：連線錯誤', 'error');
        console.error('Add server item error:', error);
    })
    .always(function() {
        addBtn.html(originalText).prop('disabled', false);
    });
}


// 載入伺服器物品清單（用於選擇選項）
function loadServerItemsForSelect(serverId) {
    if (!serverId) {
        serverItems = [];
        updateAllItemOptions();
        return;
    }
    
    $.ajax({
        url: 'api/gift_api.php',
        method: 'GET',
        data: { 
            action: 'get_server_items',
            server_id: serverId 
        },
        dataType: 'json'
    })
    .done(function(response) {
        if (response.success) {
            serverItems = response.data.map(item => ({
                id: item.id,
                gameName: item.game_name,
                databaseName: item.database_name
            }));
            updateAllItemOptions();
        } else {
            serverItems = [];
            updateAllItemOptions();
            showNotification('載入物品清單失敗：' + response.message, 'error');
        }
    })
    .fail(function(xhr, status, error) {
        serverItems = [];
        updateAllItemOptions();
        showNotification('載入物品清單失敗：連線錯誤', 'error');
        console.error('Load server items error:', error);
    });
}

// 載入已儲存的物品設定
function loadSavedItemSettings() {
    // 這個函數在選擇伺服器時會被調用
    itemSettings = {
        gameName: '',
        databaseName: ''
    };
}

// 初始化物品組（預設5組）
function initializeItemGroups() {
    const container = $('#itemsContainer');
    container.empty();
    
    // 建立5個預設物品組
    for (let i = 0; i < 5; i++) {
        addItemGroup();
    }
}

// 新增物品組
function addItemGroup() {
    const container = $('#itemsContainer');
    const groupIndex = container.children('.item-group').length;
    
    const itemGroup = $(`
        <div class="item-group" data-index="${groupIndex}">
            <div class="item-group-header">
                <h6 class="item-group-title">物品 ${groupIndex + 1}</h6>
                <span class="item-group-remove" title="移除此物品">
                    <i class="fa fa-times"></i>
                </span>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>選擇物品</label>
                        <select class="form-control item-select" data-index="${groupIndex}">
                            <option value="">請選擇物品</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>數量</label>
                        <input type="number" class="form-control item-quantity" data-index="${groupIndex}" value="1" min="1">
                    </div>
                </div>
            </div>
        </div>
    `);
    
    container.append(itemGroup);
    
    // 載入物品選項
    updateItemOptions(itemGroup.find('.item-select'));
    
    // 更新移除按鈕狀態
    updateRemoveButtons();
}

// 移除物品組
function removeItemGroup(itemGroup) {
    const container = $('#itemsContainer');
    const totalGroups = container.children('.item-group').length;
    
    // 至少保留一組
    if (totalGroups > 1) {
        itemGroup.remove();
        updateItemIndices();
        updateStageDataItems();
        updateRemoveButtons();
    }
}

// 更新物品組索引
function updateItemIndices() {
    $('#itemsContainer .item-group').each(function(index) {
        $(this).attr('data-index', index);
        $(this).find('.item-group-title').text(`物品 ${index + 1}`);
        $(this).find('.item-select, .item-quantity').attr('data-index', index);
    });
}

// 更新移除按鈕狀態
function updateRemoveButtons() {
    const container = $('#itemsContainer');
    const totalGroups = container.children('.item-group').length;
    
    if (totalGroups <= 1) {
        $('.item-group-remove').hide();
    } else {
        $('.item-group-remove').show();
    }
}

// 更新物品選項
function updateItemOptions(selectElement) {
    selectElement.empty();
    selectElement.append('<option value="">請選擇物品</option>');
    
    serverItems.forEach(function(item) {
        selectElement.append(`<option value="${item.databaseName}">${item.gameName}</option>`);
    });
}

// 更新所有物品選項
function updateAllItemOptions() {
    $('.item-select').each(function() {
        const currentValue = $(this).val();
        updateItemOptions($(this));
        $(this).val(currentValue);
    });
}

// 更新階段資料中的物品
function updateStageDataItems() {
    const items = [];
    
    $('#itemsContainer .item-group').each(function() {
        const itemSelect = $(this).find('.item-select');
        const itemQuantity = $(this).find('.item-quantity');
        
        const itemValue = itemSelect.val();
        const quantity = parseInt(itemQuantity.val()) || 1;
        
        if (itemValue) {
            const itemName = itemSelect.find('option:selected').text();
            items.push({
                databaseName: itemValue,
                name: itemName,
                quantity: quantity
            });
        }
    });
    
    stageData.items = items;
}

// 載入伺服器物品清單
function loadServerItems() {
    if (!stageData.server || !stageData.server.id) {
        const tableBody = $('#serverItemsTableBody');
        tableBody.html('<tr><td colspan="3" class="text-center text-muted">請先選擇伺服器</td></tr>');
        return;
    }
    
    const tableBody = $('#serverItemsTableBody');
    tableBody.html('<tr><td colspan="3" class="text-center"><i class="fa fa-spinner fa-spin"></i> 載入中...</td></tr>');
    
    $.ajax({
        url: 'api/gift_api.php',
        method: 'GET',
        data: { 
            action: 'get_server_items',
            server_id: stageData.server.id 
        },
        dataType: 'json'
    })
    .done(function(response) {
        if (response.success) {
            const items = response.data;
            
            if (items.length === 0) {
                tableBody.html('<tr><td colspan="3" class="text-center text-muted">目前沒有設定任何物品</td></tr>');
            } else {
                let html = '';
                items.forEach(function(item) {
                    html += `
                        <tr>
                            <td>${item.game_name}</td>
                            <td>${item.database_name}</td>
                            <td>
                                <button type="button" class="btn btn-xs btn-danger" onclick="removeServerItem(${item.id})" title="刪除物品">
                                    <i class="fa fa-trash"></i> 刪除
                                </button>
                            </td>
                        </tr>
                    `;
                });
                tableBody.html(html);
            }
        } else {
            tableBody.html('<tr><td colspan="3" class="text-center text-danger">載入失敗：' + response.message + '</td></tr>');
        }
    })
    .fail(function(xhr, status, error) {
        tableBody.html('<tr><td colspan="3" class="text-center text-danger">載入失敗：連線錯誤</td></tr>');
        console.error('Load server items error:', error);
    });
}

// 工具函數：顯示通知
function showNotification(message, type = 'info') {
    const alertClass = type === 'error' ? 'alert-danger' : 
                      type === 'success' ? 'alert-success' : 
                      'alert-info';
    
    const notification = $(`
        <div class="alert ${alertClass} alert-dismissible fade in" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
            ${message}
        </div>
    `);
    
    $('body').append(notification);
    
    // 自動移除通知
    setTimeout(function() {
        notification.alert('close');
    }, 5000);
}

// 移除伺服器物品
function removeServerItem(itemId) {
    if (confirm('確定要移除此物品嗎？')) {
        $.ajax({
            url: 'api/gift_api.php',
            method: 'POST',
            data: {
                action: 'delete_server_item',
                item_id: itemId
            },
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                showNotification('物品移除成功！', 'success');
                
                // 重新載入伺服器物品清單（用於選擇）
                if (stageData.server && stageData.server.id) {
                    loadServerItemsForSelect(stageData.server.id);
                }
                
                // 重新載入伺服器物品清單（用於顯示）
                loadServerItems();
            } else {
                showNotification('物品移除失敗：' + response.message, 'error');
            }
        })
        .fail(function(xhr, status, error) {
            showNotification('物品移除失敗：連線錯誤', 'error');
            console.error('Remove server item error:', error);
        });
    }
}

</script>

