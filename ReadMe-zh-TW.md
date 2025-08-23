# Pelago Payment for WooCommerce 使用說明

## 外掛簡介

Pelago Payment for WooCommerce 是一個專為 WordPress WooCommerce 商店設計的數位支付外掛，支援透過 Pelago 的數位 QR 碼支付方式進行線上支付。

## 系統要求

- WordPress 5.0 或更高版本
- WooCommerce 3.0 或更高版本
- PHP 7.4 或更高版本
- SSL 憑證（建議用於正式環境）

## 安裝步驟

### 方法一：手動上傳資料夾

將整個 `pelagopay-gateway` 資料夾上傳到您的 WordPress 網站的 `/wp-content/plugins/` 目錄下。

### 方法二：透過 WordPress 後台上傳 ZIP 檔案

1. 從 GitHub Release 下載最新版本的 `pelagopay-gateway.zip` 檔案：
   - 造訪外掛的 GitHub 儲存庫
   - 點擊 **Releases** 分頁
   - 選擇最新的 tag 版本
   - 下載 `pelagopay-gateway.zip` 檔案
2. 登入 WordPress 後台管理介面
3. 導航到 **外掛** > **安裝外掛**
4. 點擊 **上傳外掛** 按鈕
5. 選擇下載的 `pelagopay-gateway.zip` 檔案並點擊 **立即安裝**
6. 安裝完成後點擊 **啟用外掛**

### 啟用外掛（方法一使用）

1. 登入 WordPress 後台管理介面
2. 導航到 **外掛** > **已安裝的外掛**
3. 找到 "Pelago Payment for WooCommerce" 外掛
4. 點擊 **啟用** 按鈕

### 3. 驗證相依性

外掛會自動檢查 WooCommerce 是否已安裝並啟用。如果 WooCommerce 未安裝或未啟用，您會看到相應的提示訊息。

## 設定配置

### 1. 存取付款設定

1. 在 WordPress 後台，導航到 **WooCommerce** > **設定**
2. 點擊 **付款** 分頁
3. 找到 **Pelago Payment** 選項

### 2. 基本設定

#### 啟用/停用
- **啟用 Pelago Payment**：勾選此選項以啟用 Pelago 支付方式

#### 顯示設定
- **標題**：客戶在結帳頁面看到的支付方式名稱
  - 預設值：`Pelago Payment`
- **描述**：客戶在結帳頁面看到的支付方式描述
  - 預設值：`Pay with Pelago's digital QR Code payment method.`
- **說明**：將新增到感謝頁面和訂單郵件中的說明文字

### 3. API 設定（重要）

以下設定項目是外掛正常運作的必需參數，請聯絡 Pelago 取得：

#### merchantId
- **說明**：您的 PelagoPay 商戶 ID
- **取得方式**：從 Pelago 商戶後台取得

#### appKey  
- **說明**：您的 PelagoPay 應用程式金鑰
- **取得方式**：從 Pelago 商戶後台取得

#### merchantPrikey
- **說明**：商戶私鑰，用於簽章驗證
- **安全提示**：請妥善保管，不要洩露給他人

#### platformPublicKey
- **說明**：平台公鑰，用於驗證回呼簽章
- **取得方式**：從 Pelago 提供的技術文件中取得

### 4. 測試模式

- **啟用測試模式**：勾選此選項以使用測試環境
  - 測試環境 API：`https://pgpay-stage.weroam.xyz` 和 `https://stage-api.pelagotech.com`
  - 正式環境 API：`https://pgpay.weroam.xyz` 和 `https://api.pelagotech.com`

## 使用流程

### 1. 客戶付款流程

1. 客戶在您的商店選擇商品並新增到購物車
2. 在結帳頁面選擇 "Pelago Payment" 作為支付方式
3. 點擊 "下訂單" 按鈕
4. 系統會自動進行貨幣轉換（轉換為 USD）
5. 客戶被重新導向到 Pelago 支付頁面
6. 客戶使用 QR 碼完成支付
7. 支付完成後自動返回到訂單確認頁面

### 2. 訂單狀態管理

外掛會根據 Pelago 的回呼自動更新訂單狀態：

- **支付成功**：訂單狀態更新為 "處理中"
- **支付逾時**：訂單狀態更新為 "失敗"
- **支付取消**：訂單狀態更新為 "已取消"
- **部分支付**：訂單狀態更新為 "失敗"
- **超額支付**：訂單狀態更新為 "處理中"

## 回呼 URL 設定（可選）

外掛會自動產生回呼 URL，格式為：
```
https://您的網域/?wc-api=wc_pelagopay_gateway
```

請將此 URL 提供給 Pelago 技術支援團隊進行設定。
下訂單時會在訂單中把回呼連結一起提交上去，Pelago 支付成功後會呼叫這個連結。

## 貨幣支援

- 外掛支援多種貨幣，會自動將訂單金額轉換為 USD 進行支付
- 支援的貨幣取決於您的 WooCommerce 設定和 Pelago 的匯率服務

## 日誌記錄

外掛包含詳細的日誌記錄功能：
- 測試模式下會記錄詳細的除錯資訊
- 正式模式下記錄關鍵操作和錯誤資訊
- 日誌有助於排查支付問題

## 安全特性

- **簽章驗證**：所有 API 請求和回呼都使用 RSA 簽章進行驗證
- **資料加密**：敏感資料傳輸採用 HTTPS 加密
- **防重放攻擊**：使用時間戳記和隨機數防止重放攻擊

## 故障排除

### 常見問題

1. **支付頁面無法存取**
   - 檢查 API 設定是否正確
   - 確認網路連線正常
   - 驗證 SSL 憑證有效

2. **回呼失敗**
   - 檢查回呼 URL 是否正確設定
   - 確認伺服器可以接收外部請求
   - 驗證簽章設定是否正確

3. **貨幣轉換失敗**
   - 檢查網路連線
   - 確認貨幣代碼格式正確
   - 聯絡技術支援檢查匯率服務

### 除錯步驟

1. 啟用測試模式進行除錯
2. 檢查 WordPress 錯誤日誌
3. 聯絡 Pelago 技術支援取得協助

## 技術支援

如需技術支援，請聯絡：
- **Pelago 官網**：https://pelagotech.com
- **技術支援**：請透過官方管道聯絡

## 版本資訊

- **目前版本**：2.0.0
- **相容性**：WooCommerce 3.0+
- **更新日期**：請查看外掛檔案取得最新資訊

## 注意事項

1. 請在正式環境使用前充分測試所有功能
2. 定期備份您的網站資料
3. 保持外掛和 WordPress 系統更新到最新版本
4. 妥善保管 API 金鑰和私鑰資訊
5. 建議在 SSL 環境下使用本外掛
