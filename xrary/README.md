# XrayR for V2Board
为V2Board自动安装XrayR

## 使用说明
- 将php文件应放到V2Board目录/app/Http/Controllers/Server下
- 如果网站有CDN请确保Web服务器能正常获取客户端IP

## 一键对接
- 请先安装curl
- `bash <(curl -Ls 你的V2Board地址/api/v1/server/XrayR/install?token=服务端通讯密钥)`
- 执行后会自动安装 XrayR，并在 V2Ray 服务器表内自动插入新的服务器信息
- 在节点服务器防火墙开放相关端口
- 在 V2Board 面板调整节点其他相关信息（可选）
