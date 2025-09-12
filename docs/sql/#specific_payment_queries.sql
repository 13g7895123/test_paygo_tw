-- ============================================
-- bank_funds 綜合查詢 SQL - 特定伺服器所有金流設定
-- 建立日期：2024-08-30
-- 說明：查詢特定伺服器的所有銀行轉帳金流設定
-- ============================================

-- ============================================
-- 綜合查詢 - 特定伺服器所有金流設定
-- ============================================

-- 查詢 server_code = 145 的所有銀行轉帳金流設定（三個獨立欄位：HashKey、HashIV、Verify_key）
SELECT 
    s.auton as server_code,
    s.names as server_name,
    s.id as server_suffix,
    s.stats as server_status,
    bf.third_party_payment as payment_provider,
    bf.merchant_id as shop_id,
    
    -- 獨立欄位1：HashKey（僅 ecpay/newebpay/funpoint 有值，其他留空）
    CASE 
        WHEN bf.third_party_payment IN ('ecpay', 'ebpay', 'funpoint') 
        THEN bf.hashkey
        ELSE NULL
    END as hashkey,
    
    -- 獨立欄位2：HashIV（僅 ecpay/newebpay/funpoint 有值，其他留空）
    CASE 
        WHEN bf.third_party_payment IN ('ecpay', 'ebpay', 'funpoint') 
        THEN bf.hashiv
        ELSE NULL
    END as hashiv,
    
    -- 獨立欄位3：Verify_key（僅 gomypay/smilepay/szfupay 有值，其他留空）
    CASE 
        WHEN bf.third_party_payment IN ('gomypay', 'smilepay', 'szfupay') 
        THEN bf.verify_key
        ELSE NULL
    END as verify_key,
    
    -- 設定完整度
    CASE 
        WHEN bf.third_party_payment IN ('ecpay', 'ebpay', 'funpoint') THEN
            CASE WHEN bf.merchant_id != '' AND bf.hashkey != '' AND bf.hashiv != '' 
                 THEN '✓ 完整' ELSE '✗ 不完整' END
        WHEN bf.third_party_payment IN ('gomypay', 'smilepay', 'szfupay') THEN
            CASE WHEN bf.merchant_id != '' AND bf.verify_key != '' 
                 THEN '✓ 完整' ELSE '✗ 不完整' END
        ELSE '未知'
    END as completeness,
    
    bf.created_at as setup_date,
    bf.updated_at as last_modified
    
FROM servers s
INNER JOIN bank_funds bf ON s.auton = bf.server_code
WHERE s.auton = '145'
ORDER BY bf.third_party_payment;

-- ============================================
-- 使用說明：
-- 
-- 1. 將 '145' 替換為實際的 server_code
-- 2. 查詢結果包含三個獨立欄位：hashkey、hashiv、verify_key
-- 3. 沒有資料的欄位會顯示為 NULL
-- 4. 根據金流類型自動分配正確的欄位值
-- ============================================