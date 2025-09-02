1. 幫我讀取ebpay_r.php的113~114，完成該功能，不用測試，先幫我把code寫出來就好
2. 幫我讀取ebpay_r.php的114~138，幫我寫入hope.php這支檔案中，並用function的方式撰寫，完成後更新ebpay_r.php的內容，引用該function，且需要確認是否有正確引用
3. 找出根目錄底下檔名為_r的檔案，在if($dd["paytable"] == "ezpay")這段後面加入ebpay_r.php更新的內容，如果有問題請提出來
4. 還有另一個r.php的檔案也要，需留意，每一支帶入的變數需要確認是否存在
5. 再幫我更新一次
6. 為甚麼總共六個檔案要更新，我查詢了整個專案只更新了兩個
7. 確認一下index.php的功能，我點下去出現archangel_II:365 指紋檢查錯誤，幫我除錯，網址在https://tt.paygo.tw/archangel_II，儲存後立即套用
8. 幫我先把finger print的部分關起來，用變數控制
9. js的部分沒有處理
10. 為甚麼r.php的32~33行都沒有執行，我測試了老半天都沒執行
11. 幫我改直接執行SQL
12. 幫我確認32~33行執行前後是否資料有寫入，沒有的話幫我分析原因
13. 他媽的error_log寫去哪裡我又不知道
14. 你還是幫我改error_log好了
15. 目前根本都沒寫入，到底在幹嘛
16. 更新後的檢查項目幫我所有項目都檢查，只有檢查那幾樣根本沒用
17. 不要分開寫，整理成一列
18. 幫我讀取servers這張表，可以看到表裏面有MerchantID2、HashIV2、HashKey2、pchome_app_id2、pchome_secret_code2、gomypay_shop_id2、gomypay_key2、smilepay_shop_id2、smilepay_key2、szfupay_shop_id2、szfupay_key2，這些類似的欄位對應到/myadm/server_add這個頁面的金流設定，原先只有信用卡金流與其他金流，上方那些欄位是給其他金流使用的，現在其他金流要拆開成超商金流與銀行轉帳金流，幫我研究一下怎麼改比較好，用繁體中文回覆我
19. 幫我新增一張bank_funds的資料表，裡面有幾個欄位
    - id，流水號
    - server_code，伺服器編號
    - third-party_payment，第三方金流代號
    - merchant_id，特店編號(包括merchant_id/shop_id)
    - hashkey
    - hashiv
    - verify_key，檢查碼
    幫我撰寫SQL，有逗號後面的資料的即為coommand，寫入docs\sql\bank_funds.md
20. 幫我讀取servers的每一筆資料，依據以下規則新增資料進bank_funds
    - 如果MerchantID2、HashIV2、HashKey2有資料，幫我建置以下共三筆資料
        * server_code，對應該筆資料的id
        * third-party_payment，三筆資料分別為ecpay、newebpay、funpoint
        * merchant_id，對應該筆資料的MerchantID2
        * hashkey，對應該筆資料的HashIV2
        * hashiv，對應該筆資料的HashKey2
        * 其餘沒有用到的空值就好
    - 如果gomypay_shop_id2、gomypay_key2，幫我建置對應資料
        * server_code，對應該筆資料的id欄位
        * third-party_payment，資料為gomypay
        * merchant_id，對應該筆資料的gomypay_shop_id2
        * verify_key，對應該筆資料的gomypay_key2
        * 其餘沒有用到的空值就好
    - 如果smilepay_shop_id2、smilepay_key2，幫我建置對應資料
        * server_code，對應該筆資料的id欄位
        * third-party_payment，資料為smilepay
        * merchant_id，對應該筆資料的smilepay_shop_id2
        * verify_key，對應該筆資料的smilepay_key2
        * 其餘沒有用到的空值就好
    - 如果szfupay_shop_id2、szfupay_key2，幫我建置對應資料
        * server_code，對應該筆資料的id欄位
        * third-party_payment，資料為szfupay
        * merchant_id，對應該筆資料的szfupay_shop_id2
        * verify_key，對應該筆資料的szfupay_key2
        * 其餘沒有用到的空值就好
21. 幫我針對這次改動寫一支查詢用sql，例如我要查詢server_code為145的伺服器，他的銀行轉帳金流設定應該怎麼下，如果我要改成像servers原先那樣的，並且要有servers的資訊
22. 如果我想要的是，例如145的伺服器，要包含他的名字，他的所有銀行轉帳金流設定，例如ecpay的shop_id+key加上其他第三方金流的，payment_config那個欄位請直接拆成三個欄位
23. 請確認這個有沒有拆到，綜合查詢 - 特定伺服器所有金流設定
24. 我指的獨立欄位是指HashKey、HashIV、Verify_key，沒有資料就留空，幫我更新綜合查詢 - 特定伺服器所有金流設定
25. 保留這個就好"綜合查詢 - 特定伺服器所有金流設定"，其他可以刪掉了
26. 看到server_add這一頁，最下面有一個超商金流服務與銀行轉帳金流服務，超商金流服務是舊的，銀行轉帳金流服務是新的，走新的資料表結構，幫我調整銀行轉帳金流服務的欄位部分，他是從超商金流服務複製出來的，元件的name都還沒調整，幫我調整成正確的，並且在送出的時候，確認可以確實寫入新的資料表中，如果有缺API請建立
27. 改好的話為甚麼銀行轉帳金流服務的radio box跟超商金流服務還會連動調整，請完整確認功能
28. 目前是正常了，但邏輯有點問題，現在是移除所有第三方的資訊，僅保留更新的，但我要的是僅更新該筆資料，除非他資料不存在，那才用新增的，不然都用更新的
29. 請幫我調整，我的意思是即使我更新的smilepay的資料，其餘的第三方的資料一樣維持，這樣切換的時候才可以看到各自的資訊，你現在儲存smilepay後就刪除其他的了，這樣就不能切換看別的資訊了
30. 完成以下功能
    - 欄位已經有了，請確認server_add送出表單時，pay_bank與gstats_bank有正確存入資料庫中
31. 讀取index.php，送出的時候請確認$("#pt").val()的資料，如果是銀行轉帳，請幫我傳送is_bank為1，預設傳送0，並確認後端寫入servers_log，地端沒有php環境，請確認邏輯就好，不用測試