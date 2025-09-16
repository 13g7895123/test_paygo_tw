1. 看到/myadm/server_add.php，最下方有一組欄位名稱與欄位資料，目前預設是至少有一組，幫我改成預設最少零組，有需要的再用新增欄位新增即可，幫我連dynamic_fields_container這個id的element都隱藏
2. add commit push，於手動派獎下方多一個派獎記錄，紀載發放的紀錄、時間這些還有發放者的IP，越詳盡越好，table的樣式請參照其他頁面的，並且幫我用前後端分離的方式處理
3. 更新表的SQL有問題，幫我重新確認
4. 第二點你誤會我意思了，我是要在側邊欄手動派獎的下方多一個項目派獎記錄，幫我轉移到正確的位置
5. 第四點還沒有處理完，請view過完整專案，結合git unstage的部分，繼續往下修改，理論上應該不用動到資料庫，我只有要取log查詢顯示而已，用zh-tw回答
6. gift-records有這個錯誤，Uncaught ReferenceError: $ is not defined
7. 重新整理的按鈕放在清除右側，然後這裡有依據權限篩選伺服器了嗎