<?php

namespace App\Http\Controllers\Server;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerLog;
use Illuminate\Support\Facades\DB;

/*
 * XrayR 4 V2Board
 * Github: https://github.com/iddddg/script
 */

class XrayRController extends Controller
{
    public function __construct(Request $request)
    {
        $token = $request->input('token');
        if (empty($token)) {
            abort(500, 'token is null');
        }
        if ($token !== config('v2board.server_token')) {
            abort(500, 'token is error');
        }
    }

    public function config(Request $request)
    {
        $nodeAddr = $request->server("REMOTE_ADDR");
        $nodePort = rand(10000, 20000);
        $group = DB::table('v2_server_group')->pluck('id');
        $nodeId = DB::table('v2_server_v2ray')->insertGetId([
            'group_id' => empty($group) ? '[]' : json_encode($group),
            'name' => 'Node [' . $nodeAddr . ']',
            'parent_id' => null,
            'host' => $nodeAddr,
            'port' => $nodePort,
            'server_port' => $nodePort,
            'tls' => 0,
            'tags' => null,
            'rate' => 1,
            'network' => 'ws',
            'networkSettings' => '{"path":"\/ws-path"}',
            'show' => 0,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $ApiHost = rtrim(config('v2board.app_url'), "/");
        $ApiKey = config('v2board.server_token');
        $nodeConfig = <<<NODE_CONFIG
Log:
  Level: none # Log level: none, error, warning, info, debug
  AccessPath: # /etc/XrayR/access.Log
  ErrorPath: # /etc/XrayR/error.log
DnsConfigPath: # /etc/XrayR/dns.json # Path to dns config, check https://xtls.github.io/config/dns.html for help
RouteConfigPath: # /etc/XrayR/route.json # Path to route config, check https://xtls.github.io/config/routing.html for help
InboundConfigPath: # /etc/XrayR/custom_inbound.json # Path to custom inbound config, check https://xtls.github.io/config/inbound.html for help
OutboundConfigPath: # /etc/XrayR/custom_outbound.json # Path to custom outbound config, check https://xtls.github.io/config/outbound.html for help
ConnetionConfig:
  Handshake: 4 # Handshake time limit, Second
  ConnIdle: 10 # Connection idle time limit, Second
  UplinkOnly: 2 # Time limit when the connection downstream is closed, Second
  DownlinkOnly: 4 # Time limit when the connection is closed after the uplink is closed, Second
  BufferSize: 64 # The internal cache size of each connection, kB
Nodes:
  - PanelType: "V2board" # Panel type: SSpanel, V2board, PMpanel
    ApiConfig:
      ApiHost: "{$ApiHost}"
      ApiKey: "{$ApiKey}"
      NodeID: {$nodeId}
      NodeType: V2ray # Node type: V2ray, Shadowsocks, Trojan
      Timeout: 30 # Timeout for the api request
      EnableVless: false # Enable Vless for V2ray Type
      EnableXTLS: false # Enable XTLS for V2ray and Trojan
      SpeedLimit: 0 # Local settings will replace remote settings, 0 means disable
      DeviceLimit: 0 # Local settings will replace remote settings, 0 means disable
      RuleListPath: # /etc/XrayR/rulelist Path to local rulelist file
      DisableCustomConfig: false # Disable custom config
    ControllerConfig:
      ListenIP: 0.0.0.0 # IP address you want to listen
      SendIP: 0.0.0.0 # IP address you want to send pacakage
      UpdatePeriodic: 60 # Time to update the nodeinfo, how many sec.
      EnableDNS: false # Use custom DNS config, Please ensure that you set the dns.json well
      DNSType: AsIs # AsIs, UseIP, UseIPv4, UseIPv6, DNS strategy
      DisableUploadTraffic: false # Disable Upload Traffic to the panel
      DisableGetRule: false # Disable Get Rule from the panel
      DisableIVCheck: false # Disable the anti-reply protection for Shadowsocks
      DisableSniffing: false # Disable domain sniffing
      EnableProxyProtocol: false # Only works for WebSocket and TCP
      EnableFallback: false # Only support for Trojan and Vless
      FallBackConfigs: # Support multiple fallbacks
        - SNI: # TLS SNI(Server Name Indication), Empty for any
          Path: # HTTP PATH, Empty for any
          Dest: 80 # Required, Destination of fallback, check https://xtls.github.io/config/fallback/ for details.
          ProxyProtocolVer: 0 # Send PROXY protocol version, 0 for dsable
      CertConfig:
        CertMode: none # Option about how to get certificate: none, file, http, dns. Choose "none" will forcedly disable the tls config.
        RejectUnknownSni: false # Reject unknown SNI, default false
        CertDomain: "xxx" # Domain to cert
        CertFile: /etc/XrayR/cert/ssl.cert # Provided if the CertMode is file
        KeyFile: /etc/XrayR/cert/ssl.key
        Provider: cloudflare # DNS cert provider, Get the full support list here: https://go-acme.github.io/lego/dns/
        Email: xxx
        DNSEnv: # DNS ENV option used by DNS provider
          CF_DNS_API_TOKEN: xxx
NODE_CONFIG;
        exit($nodeConfig);
    }

    public function install(Request $request)
    {
        $Config_CreateURL = rtrim(config('v2board.app_url'), "/") . "/api/v1/server/XrayR/config?token=" . config('v2board.server_token');
        ?>
        #!/usr/bin/env bash
        clear;
        Config_CreateURL="<?php echo $Config_CreateURL; ?>";

        # =========================================================
        # XRayR Install Script for V2Board
        # Author: iddddg
        # Version: 1.0.0
        # =========================================================
        Font_Black="\033[30m";
        Font_Red="\033[31m";
        Font_Green="\033[32m";
        Font_Yellow="\033[33m";
        Font_Blue="\033[34m";
        Font_Purple="\033[35m";
        Font_SkyBlue="\033[36m";
        Font_White="\033[37m";
        Font_Suffix="\033[0m";

        InstallXRayR(){
        echo -e ${Font_Yellow}" ** Installing XRayR Program..."${Font_Suffix};
        bash <(curl -sSL "https://raw.githubusercontent.com/XrayR-project/XrayR-release/master/install.sh") > /dev/null 2>&1;
        if [ $? -ne 0 ];then
        echo -e ${Font_Red}"    Install failed"${Font_Suffix};
        exit;
        fi
        systemctl stop XrayR > /dev/null 2>&1;
        echo -e ${Font_Green}"    Installed XRayR";
        }

        GetConfig(){
        echo -e ${Font_Yellow}" ** Download XRayR Config for V2Board";
        wget -qO /etc/XrayR/config.yml "${Config_CreateURL}";
        if [ $? -ne 0 ];then
        echo -e ${Font_Red}"    Download config failed"${Font_Suffix};
        exit;
        fi
        echo -e ${Font_Green}"    The config saved"${Font_Suffix};
        systemctl start XrayR > /dev/null 2>&1;
        }

        echo -e ${Font_SkyBlue}"XRayR Install Script for V2Board"${Font_Suffix};

        wget -V > /dev/null;
        if [ $? -ne 0 ];then
        echo -e "${Font_Red}Please install wget${Font_Suffix}";
        exit;
        fi

        InstallXRayR;
        GetConfig;

        echo "===============================================";
        echo "Please configure this node on V2Board";
        echo "Restart XRayR Command: systemctl restart XrayR";
        <?php
        exit();
    }
}
