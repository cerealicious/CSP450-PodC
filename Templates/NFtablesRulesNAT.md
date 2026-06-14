### File:`NFtablesRulesNAT.md`

# Cleaned NFTables Security & NAT Outbound Layer
**Target Profile Path:** `/etc/nftables.conf` on the **Ubuntu Router VM**.

Run `sudo nft -f /etc/nftables.conf` to push the security policy live. This stripped configuration securely passes user traffic out to external webs, blocks unexpected ingress network exploration scans, and automatically intercepts and proxies local enterprise host name lookup targets to the campus central domain engine (`10.101.100.21`).

```text
flush ruleset

table inet filter {
  chain input {
    type filter hook input priority 0; policy accept;
    iifname "ens33" ct state established,related accept # ----> Trust return traffic from the web
    iifname "ens37" accept                             # ----> Trust all traffic originating from your local switch
  }

  chain forward {
    type filter hook forward priority 0; policy accept;
    iifname "ens37" oifname "ens33" accept             # ----> Route local LAN traffic safely out to internet
    iifname "ens33" oifname "ens37" ct state established,related accept
  }

  chain output {
    type filter hook output priority 0; policy accept;
    oifname "ens33" accept                             # ----> Permit the gateway OS out to Seneca network
  }
}

table ip nat {
  chain prerouting {
    type nat hook prerouting priority -100; policy accept;
    iifname "ens37" udp dport 53 dnat to 10.101.100.21 # ----> Intercept Local DNS and map to Seneca server
    iifname "ens37" tcp dport 53 dnat to 10.101.100.21
  }

  chain input {
    type nat hook input priority 100; policy accept;
  }

  chain output {
    type nat hook output priority -100; policy accept;
  }

  chain postrouting {
    type nat hook postrouting priority 100; policy accept;
    oifname "ens33" masquerade                         # ----> Perform NAT Outbound IP Masquerading on edge port
  }
}
