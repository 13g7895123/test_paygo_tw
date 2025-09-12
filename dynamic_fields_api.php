<?php
/**
 * 動態欄位功能API
 * Dynamic Fields API
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 引入資料庫連線
require_once('inc/db.php');

// 取得請求方法和參數
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// API回應函數
function apiResponse($success = true, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

try {
    switch ($method) {
        case 'GET':
            // 取得動態欄位資料
            if (isset($_GET['action'])) {
                $action = $_GET['action'];
                
                if ($action === 'get' && isset($_GET['server_id'])) {
                    $server_id = intval($_GET['server_id']);
                    
                    // 取得伺服器基本資訊
                    $stmt = $pdo->prepare("SELECT id, names, table_name, account_field FROM servers WHERE id = ?");
                    $stmt->execute([$server_id]);
                    $server = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$server) {
                        apiResponse(false, null, '伺服器不存在', 404);
                    }
                    
                    // 取得動態欄位詳細資料
                    $stmt = $pdo->prepare("SELECT field_name, field_value, sort_order FROM server_dynamic_field_details WHERE server_id = ? ORDER BY sort_order ASC");
                    $stmt->execute([$server_id]);
                    $dynamic_fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $result = [
                        'server' => $server,
                        'dynamic_fields' => $dynamic_fields
                    ];
                    
                    apiResponse(true, $result, '取得成功');
                } else {
                    apiResponse(false, null, '不支援的GET操作或缺少參數', 400);
                }
            } else {
                apiResponse(false, null, '缺少action參數', 400);
            }
            break;
            
        case 'POST':
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'save':
                    // 新增或更新動態欄位資料
                    if (!isset($input['server_id'])) {
                        apiResponse(false, null, '缺少server_id參數', 400);
                    }
                    
                    $server_id = intval($input['server_id']);
                    $table_name = $input['table_name'] ?? '';
                    $account_field = $input['account_field'] ?? '';
                    $dynamic_fields = $input['dynamic_fields'] ?? [];
                    
                    $pdo->beginTransaction();
                    
                    try {
                        // 更新servers表的基本資訊
                        $stmt = $pdo->prepare("UPDATE servers SET table_name = ?, account_field = ? WHERE id = ?");
                        $stmt->execute([$table_name, $account_field, $server_id]);
                        
                        // 刪除現有的動態欄位
                        $stmt = $pdo->prepare("DELETE FROM server_dynamic_field_details WHERE server_id = ?");
                        $stmt->execute([$server_id]);
                        
                        // 插入新的動態欄位
                        if (!empty($dynamic_fields)) {
                            $stmt = $pdo->prepare("INSERT INTO server_dynamic_field_details (server_id, field_name, field_value, sort_order) VALUES (?, ?, ?, ?)");
                            
                            foreach ($dynamic_fields as $index => $field) {
                                if (!empty($field['field_name'])) {
                                    $stmt->execute([
                                        $server_id,
                                        $field['field_name'],
                                        $field['field_value'] ?? '',
                                        $index + 1
                                    ]);
                                }
                            }
                        }
                        
                        $pdo->commit();
                        apiResponse(true, ['server_id' => $server_id], '儲存成功');
                        
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        apiResponse(false, null, '儲存失敗: ' . $e->getMessage(), 500);
                    }
                    break;
                    
                case 'update_field':
                    // 更新單一動態欄位
                    if (!isset($input['id']) || !isset($input['field_name'])) {
                        apiResponse(false, null, '缺少必要參數', 400);
                    }
                    
                    $id = intval($input['id']);
                    $field_name = $input['field_name'];
                    $field_value = $input['field_value'] ?? '';
                    
                    $stmt = $pdo->prepare("UPDATE server_dynamic_field_details SET field_name = ?, field_value = ? WHERE id = ?");
                    $result = $stmt->execute([$field_name, $field_value, $id]);
                    
                    if ($result) {
                        apiResponse(true, ['id' => $id], '更新成功');
                    } else {
                        apiResponse(false, null, '更新失敗', 500);
                    }
                    break;
                    
                case 'delete':
                    // 刪除動態欄位
                    if (isset($input['server_id'])) {
                        // 刪除整個伺服器的動態欄位
                        $server_id = intval($input['server_id']);
                        
                        $pdo->beginTransaction();
                        
                        try {
                            // 清空servers表的相關欄位
                            $stmt = $pdo->prepare("UPDATE servers SET table_name = NULL, account_field = NULL WHERE id = ?");
                            $stmt->execute([$server_id]);
                            
                            // 刪除動態欄位詳細資料
                            $stmt = $pdo->prepare("DELETE FROM server_dynamic_field_details WHERE server_id = ?");
                            $stmt->execute([$server_id]);
                            
                            $pdo->commit();
                            apiResponse(true, ['server_id' => $server_id], '刪除成功');
                            
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            apiResponse(false, null, '刪除失敗: ' . $e->getMessage(), 500);
                        }
                        
                    } elseif (isset($input['id'])) {
                        // 刪除單一動態欄位
                        $id = intval($input['id']);
                        
                        $stmt = $pdo->prepare("DELETE FROM server_dynamic_field_details WHERE id = ?");
                        $result = $stmt->execute([$id]);
                        
                        if ($result) {
                            apiResponse(true, ['id' => $id], '刪除成功');
                        } else {
                            apiResponse(false, null, '刪除失敗', 500);
                        }
                    } else {
                        apiResponse(false, null, '缺少必要參數', 400);
                    }
                    break;
                    
                default:
                    apiResponse(false, null, '不支援的POST操作', 400);
                    break;
            }
            break;
            
        default:
            apiResponse(false, null, '不支援的請求方法', 405);
            break;
    }
    
} catch (Exception $e) {
    apiResponse(false, null, 'API錯誤: ' . $e->getMessage(), 500);
}
?>