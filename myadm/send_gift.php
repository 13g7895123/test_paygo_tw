<?include("include.php");

check_login();
  
top_html();
?>

<section id="middle">
	
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


// 等待 DOM 載入完成
$(document).ready(function () {
    
});

</script>

