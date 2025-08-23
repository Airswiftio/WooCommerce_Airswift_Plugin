# Pelago Payment for WooCommerce 使用说明

## 插件简介

Pelago Payment for WooCommerce 是一个专为 WordPress WooCommerce 商店设计的数字支付插件，支持通过 Pelago 的数字 QR 码支付方式进行在线支付。

## 系统要求

- WordPress 5.0 或更高版本
- WooCommerce 3.0 或更高版本
- PHP 7.4 或更高版本
- SSL 证书（推荐用于生产环境）

## 安装步骤

### 方法一：手动上传文件夹

将整个 `pelagopay-gateway` 文件夹上传到您的 WordPress 网站的 `/wp-content/plugins/` 目录下。

### 方法二：通过 WordPress 后台上传 ZIP 文件

1. 从 GitHub Release 下载最新版本的 `pelagopay-gateway.zip` 文件：
   - 访问插件的 GitHub 仓库
   - 点击 **Releases** 选项卡
   - 选择最新的 tag 版本
   - 下载 `pelagopay-gateway.zip` 文件
2. 登录 WordPress 后台管理界面
3. 导航到 **插件** > **安装插件**
4. 点击 **上传插件** 按钮
5. 选择下载的 `pelagopay-gateway.zip` 文件并点击 **现在安装**
6. 安装完成后点击 **启用插件**

### 激活插件（方法一使用）

1. 登录 WordPress 后台管理界面
2. 导航到 **插件** > **已安装的插件**
3. 找到 "Pelago Payment for WooCommerce" 插件
4. 点击 **启用** 按钮

### 3. 验证依赖

插件会自动检查 WooCommerce 是否已安装并激活。如果 WooCommerce 未安装或未激活，您会看到相应的提示信息。

## 配置设置

### 1. 访问支付设置

1. 在 WordPress 后台，导航到 **WooCommerce** > **设置**
2. 点击 **支付** 选项卡
3. 找到 **Pelago Payment** 选项

### 2. 基本设置

#### 启用/禁用
- **启用 Pelago Payment**：勾选此选项以启用 Pelago 支付方式

#### 显示设置
- **标题**：客户在结账页面看到的支付方式名称
  - 默认值：`Pelago Payment`
- **描述**：客户在结账页面看到的支付方式描述
  - 默认值：`Pay with Pelago's digital QR Code payment method.`
- **说明**：将添加到感谢页面和订单邮件中的说明文字

### 3. API 配置（重要）

以下配置项是插件正常工作的必需参数，请联系 Pelago 获取：

#### merchantId
- **说明**：您的 PelagoPay 商户 ID
- **获取方式**：从 Pelago 商户后台获取

#### appKey  
- **说明**：您的 PelagoPay 应用密钥
- **获取方式**：从 Pelago 商户后台获取

#### merchantPrikey
- **说明**：商户私钥，用于签名验证
- **安全提示**：请妥善保管，不要泄露给他人

#### platformPublicKey
- **说明**：平台公钥，用于验证回调签名
- **获取方式**：从 Pelago 提供的技术文档中获取

### 4. 测试模式

- **启用测试模式**：勾选此选项以使用测试环境
  - 测试环境 API：`https://pgpay-stage.weroam.xyz` 和 `https://stage-api.pelagotech.com`
  - 生产环境 API：`https://pgpay.weroam.xyz` 和 `https://api.pelagotech.com`

## 使用流程

### 1. 客户支付流程

1. 客户在您的商店选择商品并添加到购物车
2. 在结账页面选择 "Pelago Payment" 作为支付方式
3. 点击 "下单" 按钮
4. 系统会自动进行货币转换（转换为 USD）
5. 客户被重定向到 Pelago 支付页面
6. 客户使用 QR 码完成支付
7. 支付完成后自动返回到订单确认页面

### 2. 订单状态管理

插件会根据 Pelago 的回调自动更新订单状态：

- **支付成功**：订单状态更新为 "处理中"
- **支付超时**：订单状态更新为 "失败"
- **支付取消**：订单状态更新为 "已取消"
- **部分支付**：订单状态更新为 "失败"
- **超额支付**：订单状态更新为 "处理中"

## 回调 URL 配置（可选）

插件会自动生成回调 URL，格式为：
```
https://您的域名/?wc-api=wc_pelagopay_gateway
```

请将此 URL 提供给 Pelago 技术支持团队进行配置。
下单时会在订单中把回调链接一起提交上去，Pelago 支付成功后会调用这个链接。

## 货币支持

- 插件支持多种货币，会自动将订单金额转换为 USD 进行支付
- 支持的货币取决于您的 WooCommerce 设置和 Pelago 的汇率服务

## 日志记录

插件包含详细的日志记录功能：
- 测试模式下会记录详细的调试信息
- 生产模式下记录关键操作和错误信息
- 日志有助于排查支付问题

## 安全特性

- **签名验证**：所有 API 请求和回调都使用 RSA 签名进行验证
- **数据加密**：敏感数据传输采用 HTTPS 加密
- **防重放攻击**：使用时间戳和随机数防止重放攻击

## 故障排除

### 常见问题

1. **支付页面无法访问**
   - 检查 API 配置是否正确
   - 确认网络连接正常
   - 验证 SSL 证书有效

2. **回调失败**
   - 检查回调 URL 是否正确配置
   - 确认服务器可以接收外部请求
   - 验证签名配置是否正确

3. **货币转换失败**
   - 检查网络连接
   - 确认货币代码格式正确
   - 联系技术支持检查汇率服务

### 调试步骤

1. 启用测试模式进行调试
2. 检查 WordPress 错误日志
3. 联系 Pelago 技术支持获取帮助

## 技术支持

如需技术支持，请联系：
- **Pelago 官网**：https://pelagotech.com
- **技术支持**：请通过官方渠道联系

## 版本信息

- **当前版本**：2.0.0
- **兼容性**：WooCommerce 3.0+
- **更新日期**：请查看插件文件获取最新信息

## 注意事项

1. 请在生产环境使用前充分测试所有功能
2. 定期备份您的网站数据
3. 保持插件和 WordPress 系统更新到最新版本
4. 妥善保管 API 密钥和私钥信息
5. 建议在 SSL 环境下使用本插件