<?php

include("include.php");

check_login();

top_html();

?>

<!--
	MIDDLE
-->

<section id="middle">

	<div id="content" class="dashboard padding-20">

		<div id="panel-1" class="panel panel-default">

			<div class="panel-heading">

				<span class="title elipsis">

					<strong>銀行金流 SQL 日誌查詢</strong>

					<small class="size-12 weight-300 text-mutted hidden-xs">Bank Funds SQL Logger</small>

				</span>

				<!-- right options -->

				<ul class="options pull-right list-inline">

					<li><a href="#" class="opt panel_fullscreen hidden-xs" data-toggle="tooltip" title="Fullscreen" data-placement="bottom"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></a></li>

				</ul>

				<!-- /right options -->

			</div>

			<!-- panel content -->

			<div class="panel-body">

				<a href="index.php" class="btn btn-primary"><i class="fa-solid fa-backward"></i> 返回</a>

				<div class="row margin-top-20">
					<div class="col-md-6">
						<label>選擇日期：</label>
						<input type="date" id="date_selector" class="form-control" value="<?=date('Y-m-d')?>" style="display:inline-block; width:auto;">
						<button id="load_logs_btn" class="btn btn-info"><i class="fa fa-search"></i> 查詢</button>
					</div>
					<div class="col-md-6">
						<label>操作類型：</label>
						<select id="operation_filter" class="form-control" style="display:inline-block; width:auto;">
							<option value="">全部</option>
							<option value="CHECK">CHECK</option>
							<option value="UPDATE">UPDATE</option>
							<option value="INSERT">INSERT</option>
						</select>
					</div>
				</div>

				<div class="row margin-top-20">
					<div class="col-md-12">
						<div id="log_summary" class="alert alert-info" style="display:none;">
							<strong>日誌摘要：</strong> <span id="summary_text"></span>
						</div>
					</div>
				</div>

				<div class="table margin-top-20">

					<table class="table table-bordered table-striped" id="logs_table">
						<thead>
							<tr style="background:#666;color:white;">
								<th>時間</th>
								<th>操作</th>
								<th>Server ID</th>
								<th>SQL 語句</th>
								<th>參數</th>
								<th>結果</th>
							</tr>
						</thead>
						<tbody id="logs_tbody">
							<tr>
								<td colspan="6" class="text-center">請選擇日期並點擊查詢</td>
							</tr>
						</tbody>
					</table>

				</div>

			</div>

			<!-- /panel content -->

		</div>

	</div>

</section>

<!-- /MIDDLE -->

<?down_html()?>

<script type="text/javascript">

$(document).ready(function() {

	// 載入日誌
	function loadLogs() {
		var date = $('#date_selector').val();
		var operationFilter = $('#operation_filter').val();

		if(!date) {
			alert('請選擇日期');
			return;
		}

		$('#logs_tbody').html('<tr><td colspan="6" class="text-center"><i class="fa fa-spinner fa-spin"></i> 載入中...</td></tr>');

		$.ajax({
			url: 'api/bank_funds_sql_logger_api.php',
			method: 'GET',
			data: {
				action: 'view',
				date: date,
				limit: 500
			},
			dataType: 'json',
			success: function(response) {
				if(response.success) {
					displayLogs(response.data.logs, operationFilter);
					$('#log_summary').show();
					$('#summary_text').text('日期: ' + response.data.date + ', 共 ' + response.data.count + ' 筆記錄');
				} else {
					$('#logs_tbody').html('<tr><td colspan="6" class="text-center text-danger">錯誤: ' + response.message + '</td></tr>');
				}
			},
			error: function(xhr, status, error) {
				$('#logs_tbody').html('<tr><td colspan="6" class="text-center text-danger">載入失敗: ' + error + '</td></tr>');
			}
		});
	}

	// 顯示日誌
	function displayLogs(logs, operationFilter) {
		var html = '';
		var filteredCount = 0;

		if(logs.length === 0) {
			html = '<tr><td colspan="6" class="text-center">此日期沒有記錄</td></tr>';
		} else {
			logs.forEach(function(log) {
				// 過濾操作類型
				if(operationFilter && log.operation !== operationFilter) {
					return;
				}

				filteredCount++;

				// 操作類型顏色
				var operationColor = '';
				switch(log.operation) {
					case 'CHECK':
						operationColor = 'label-info';
						break;
					case 'UPDATE':
						operationColor = 'label-warning';
						break;
					case 'INSERT':
						operationColor = 'label-success';
						break;
					default:
						operationColor = 'label-default';
				}

				// 格式化參數
				var paramsHtml = '<pre style="margin:0; max-height:100px; overflow:auto; font-size:11px;">' +
								 JSON.stringify(log.parameters, null, 2) +
								 '</pre>';

				// 格式化 SQL
				var sqlHtml = '<code style="font-size:11px; display:block; max-height:100px; overflow:auto;">' +
							 escapeHtml(log.sql) +
							 '</code>';

				html += '<tr>';
				html += '<td style="white-space:nowrap;">' + log.timestamp + '</td>';
				html += '<td><span class="label ' + operationColor + '">' + log.operation + '</span></td>';
				html += '<td>' + (log.server_id || '-') + '</td>';
				html += '<td>' + sqlHtml + '</td>';
				html += '<td>' + paramsHtml + '</td>';
				html += '<td style="font-size:11px;">' + (log.result || '-') + '</td>';
				html += '</tr>';
			});

			if(filteredCount === 0) {
				html = '<tr><td colspan="6" class="text-center">沒有符合條件的記錄</td></tr>';
			}
		}

		$('#logs_tbody').html(html);
	}

	// HTML 轉義
	function escapeHtml(text) {
		var map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g, function(m) { return map[m]; });
	}

	// 查詢按鈕
	$('#load_logs_btn').click(function() {
		loadLogs();
	});

	// 操作類型過濾
	$('#operation_filter').change(function() {
		loadLogs();
	});

	// 自動載入今天的日誌
	loadLogs();

});

</script>

<style>
#logs_table {
	font-size: 12px;
}

#logs_table td {
	vertical-align: top;
}

#logs_table code {
	background-color: #f5f5f5;
	padding: 5px;
	border-radius: 3px;
	word-break: break-all;
}

#logs_table pre {
	background-color: #f5f5f5;
	padding: 5px;
	border-radius: 3px;
	border: 1px solid #ddd;
}
</style>
