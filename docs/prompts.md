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
32. 請幫我檢查為甚麼/myadm/server_add.php?an=145這個頁面我修改了銀行轉帳金流服務點選送出後，卻沒有正確儲存資料，DOMAIN在此https://test.paygo.tw/，存檔後RELOAD即可更新code，請幫我測試直到選擇速買配後點確認修改，重整後有正確顯示
33. server_add.php的738，是可以在執行前印出他的SQL的嗎，請用繁中回覆，直接print_r+die執行就可以了，我要除錯優先
34. 幫我整理一下server_add.php的code排版，不該連續空行的都修正，不該空行的空行的也修正，讓他看起來舒服一點，565那個input是怎麼回事，一堆空行，為甚麼都沒有刪除
35. 幫我在pay_bank.php，寫一隻查詢支付資訊的function，假如果給了servers_log的auton，要查出他的銀行支付資訊為何，請清除掉檔案中的亂碼
36. 幫我在smilepay_next.php的37行，取得該筆訂單，smilepay的資訊，運用剛剛35寫出來的function，記得要引入
37. 幫我寫一支測試api，在paybank中，並且需要提供網址路徑，先顯示123就好
38. 幫我在paybank底下建立API，我要可以輸入訂單號碼，可以取得該訂單的金流資訊，需要非常詳細，請使用35寫的function，我要一步一步除錯
39. 幫我在ebpay_payok.php的19行加入功能，先透過$orderid取得訂單資訊，如果他的訂單資訊中，paytype為2且is_bank為1，參考38點的做法，取得對應的第三方金流資料
40. 幫我讀取index.php，輸入完驗證碼按ENTER要可以送出
41. 完成以下項目
    - 幫我在server_add.php的1072行加入幾個項目，我需要資料表名稱以及帳號欄位，請參考該頁面其他的表單設計新增這兩個欄位
    - 完成後在下面多一個動態新增的欄位，欄位名稱+欄位資料為一組項目，每次新增就是新增一組項目，可以新增與刪除項目，每次刪除從最後面刪除，畫面最少要保留一組，只剩一組的時候刪除功能要關閉
42. 幫我調整好看一點，我希望每一組的欄位都是一列
43. 新增欄位幫我拉到上面，超過5個幫我用scroll bar
44. 完成以下功能
    - 幫這個功能建立sql，寫入docs\sql中
    - 幫我為這個功能建立獨立API
    - 幫我加個icon，然後調整送出的邏輯，送出後需要一併送出至API
45. 幫我調整，用POST與GET就好，不要用PUT與DELETE，完成後撰寫所有測試程式包含API
46. 幫我在send_gift.php中加入source資料夾的圖片功能，需要完整版，用jquery，先處理前端就好，表單送出有FORM DATA即可
47. 承46.，我講解一下細部功能，幫我調整
    - 他會有兩個階段
    - 第一階段，需要選擇這個帳號旗下的伺服器，如果只有一個，請直接預設選擇他
    - 另外會需要輸入遊戲帳號
    - 完成以上步驟後，可以選擇物品與數量
    - 送出後，會到第二階段，會列出第一階段的細項，確認無誤後送出，並留下派送紀錄
48. 完成以下功能
    - 在右上角新增一顆物品設定的按鈕，點下去會打開物品設定，需要輸入物品在遊戲資料庫的名稱與資訊
    - 選擇圖片與已選擇圖片可以移除
49. 物品設定只要遊戲名稱與資料庫名稱就好，其餘請全部移除，按鈕顏色請不要用漸層
50. 選擇物品與數量的圖片請全部移除
51. 完成以下功能
    - 物品設定要有一個清單，顯示目前伺服器上的所有物品有哪些
    - 選擇物品與數量預設給五組，可以自由新增予與刪除，送出時沒有填資料的不會送出
    - 贈送訊息的部分移除
52. 請移除localstorage持久化，這些功能都會透過api傳到後端的
53. 伺服器物品清單，可以給我一個簡易的table嗎，另外需要有一顆新增按鈕，不要每次送出在打開，多一顆新增按鈕後，送出按鈕可以移除
54. 可以把table改在下面嗎，這樣好難看，然後調整成好看一點的樣式
55. Table中的資料幫我置中，按鈕幫我加入刪除的文字，然後移除關閉的按鈕與Footer，最後確認一下專案中有沒有使用sweet alert，有的話請幫我通知改用那個
56. 物品管理幫我右上角多一顆叉叉
57. 幫我看到server_add.php，最下面有派獎設定，目前送出應該還沒有帶資料，幫我看完後，建立這個區域的資料表，把sql寫入docs\sql\send_gift.sql中
58. 可以問一下57新增的檔案74~76的用意為何嗎，但如果如你所說的，是否就不用send_gift_settings這張表了，幫我移除ALTER TABLE servers那一段，因為他這個常常要複製來複製去，同一張表怕會漏掉，我是要保留send_gift_settings，移除ALTER TABLE servers，你不要亂搞阿
59. 先幫我add commit push，然後先處理server_add的部分，要送出後確實有把資料更新進資料庫
60. 幫我調整send_gift，移除FOREIGN KEY的部分
61. 第59點的server_add的部分，最下面有新增欄位，但是送出後沒有寫入資料庫
62. 幫我完成send_gift中的所有api，並執行串接
63. 選擇伺服器的下拉選單，要顯示他有權限的伺服器呀，權限的部分請參照/myadm/index這個頁面的顯示，選擇伺服器一直顯示載入失敗，No servers available for current user，請不要改方法，一樣用API的方案，你可以帶入目前的資訊到API中，讓API可以篩選出該使用者有權限的伺服器
64. 最上方的進度，通過的打勾請放在圓圈上方，另外，給我一個查詢log的api，我要取得他寫入伺服器資料庫的sql，目前我看資料庫是沒有寫入的，幫我把有可能需要的資料寫入那隻API中，我API要打甚麼才能使用
65. 我找到問題了，在server_add的派獎設定，要再多兩個欄位，一個是物品欄位，另一個是數量欄位，完成後記得送出也要改，docs\sql\send_gift.sql也要改，並且在最下面加一個ALTER TABLE的加入這兩個欄位
66. 請確認一下連線是沒有問題的，要連線到遊戲伺服器哦，送出的時候先檢測一下是不是有正常，包含資料表存在，欄位也要都存在，才執行，完成後需要確認資料表中有那筆資料
67. 完成以下功能
    - 結果頁幫我多一顆按鈕，發送下一筆，會回到上一頁並把上一筆的資料清掉
    - server_add這個頁面，物品欄位改成道具編號
    - server_add這個頁面，道具編號後面多一組道具名稱的label+，記得幫我修改docs\sql\send_gift.sql，在最下面要有新欄位的ALTER TABLE SQL，前端與後端API都要一併調整
    - send_gift這一頁，物品設定改道具設定，原先寫物品的都改成道具，然後新增道具的部分改為道具編號與道具名稱，道具名稱為非必填
68. 新增道具點下去，道具名稱一樣會被要求必填
69. /myadm/api/gift_api.
70. 當我選只有編號的道具，第二部會出現尚未選擇道具
71. 可以幫我道具編號如果是有道具名稱的，後面加括號寫入道具名稱嗎，另外第二頁的贈送詳情的字體大一點
72. 第二頁的測試連線，如果他要送出的道具是有道具名稱的，也要一併檢測
73. 道具設定的字體放大一點，並且如果沒有選伺服器就點，請自動focus選擇伺服器
74. 完成以下功能
    - 我送了兩筆道具，一筆有道具名稱的欄位，一筆只有道具編號，但是測試的時候沒有檢測道具名稱欄位，請幫我調整，只要要送出的道具，有任何一筆有道具名稱的欄位，就需要檢測
    - 確認送出禮物的時候，先幫我執行測試連線，但是不要顯示視窗，幫我背景執行，如果有錯誤，先跳出提示再顯示測試連線結果
    - 檢測連線也太久了吧，幫我確認並修復，報錯了，testGameServerConnection(...).then(...).then(...).catch is not a function，還是一樣的錯誤阿，回覆請用zh-tw
75. server_add的銀行轉帳金流服務幫我在"無"前面，多加一個ANT的選項
76. 這樣沒問題了，幫我隱藏測試連線的按鈕
77. 目前是這樣，要在系統上加入ANT的這個銀行轉帳服務，但他與其他的不同的地方是，她轉帳需要提供轉帳者的銀行代號與密碼，所以前台如果該伺服器是選用ANT，且玩家使用銀行轉帳的時候，要多出兩個欄位，讓使用者填入她的銀行代號與密碼，幫我view過整個專案，給出執行建議
78. 調整一下，是銀行代號與帳號，不是密碼，然後幫我針對77點給的建議記錄下來寫入docs\ant.md
79. 調整一下，因為那個欄位只有這個支付會用到，所以幫我預設可以NULL
80. 第一點的資料庫已經更新，幫我從第二點往後執行，然後API的部分我會再提供給你，那部分先幫我預留API即可
81. https://documenter.getpostman.com/view/4494782/2sAYBSkDHS#3fcfc44b-0c39-4b7a-b3a5-678a105437e5，API說明網址如上，先幫我規劃出功能項目，寫入docs\ant-api.md
82. 依據docs\ant-api.md的規劃實作API的功能部分，這個資料庫沒有對外，請問你是要測試甚麼
83. 幫我調整一下，ant-api的callback可以幫我用另一張表紀錄server_log寫入嗎，我不想要再加在server_log裡面
84. 幫我閱讀有關ant-api的所有檔案，server_add的部分使用了shop_id與verify_code，但實際上它應該是HashIV與HashKey才對，幫我列出所有要修正的地方並進行修改，用zh-tw回覆
85. api要串接的網址為https://api.nubitya.com，幫我先寫一支API，用於測試使用，我要確認API是可以運行的，我是要測試你寫的是否可以運行打API功能是否可以運行，他提供了三項資訊如下，Api token : 
dkTqv40XBDmvlfBayoMngA0BAlDAxCrkzIAAUdwYB6kkKZVLOit1R06PKcgkhglASS79c6yzaokrdoPP  
Hash key : 
lyAJwWnVAKNScXjE6t2rxUOAeesvIP9S 
Hash iv :
yhncs1WpMo60azxEczokzIlVVvVuW69p
，用這個進行開單測試
86. 我用了你提供的網址，但是她一片空白，一樣是空的欸，請打開網頁來看確認有東西才代表完成