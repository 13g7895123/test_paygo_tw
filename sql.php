<?php
/*
  ***********************
  ***** 修改資料庫用 *****
  ***********************
*/

// include("./myadm/include.php");

// $pdo = openpdo();
// $query = $pdo->prepare("SELECT * FROM servers_log where pay_cp=? AND RtnCode=?");
// $sql = $query->execute(array('smilepay', 1));

// if(!$results = $query->fetchAll()) die($v);

// $data = [];
// // $data['count'] = count($results);
// // $temp_count = 0;
// // $temp_count2 = 0;
// $exe_count = 0;
// foreach ($results as $key => $val){
//     // $data[$key]['id'] = $val['auton'];
//     // $data[$key]['RtnCode'] = $val['RtnCode'];
//     // $data[$key]['RtnMsg'] = $val['RtnMsg'];

//     // if ($val['RtnMsg'] == '模擬付款成功') $temp_count += 1;
//     if ($val['RtnMsg'] != '模擬付款成功'){
//         // $shq = $pdo->prepare("UPDATE servers_log SET RtnMsg=? where auton=?");
// 		// $shq->execute(array('付款成功', $val['auton']));
//         $exe_count += 1;
//         $data[$key]['id'] = $val['auton'];
//     } 
// }
// // $data['temp_count'] = $temp_count;
// // $data['temp_count2'] = $temp_count2;
// $data['exe_count'] = $exe_count;
// // $data['count'] = count($data);

// echo json_encode($data);

?>