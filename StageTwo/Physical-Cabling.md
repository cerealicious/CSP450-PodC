## 🗺️ Cabling Matrix

| From Device | Port | To Device | Port | Purpose / Configuration Reference |
| :--- | :--- | :--- | :--- | :--- |
| **Aruba 6300 Core** | 1/1/3 | **Aruba 2530 Access** | 3 | **Inter-Switch Trunk Link**<br>Carries tagged traffic for VLAN 231 & VLAN 217.<br>*Config:* `vlan trunk native 1`, `vlan trunk allowed all` |
| **Aruba 6300 Core** | 1/1/4 | **Catalan Router VM** | ens37 | **Catalan WAN Point-to-Point Link**<br>Subnet: `192.168.3.156/30`<br>Switch IP: `.157` \| Router IP: `.158`<br>*Config:* `ip address 192.168.3.157/30`, `ip ospf 1 area 0` |
| **Aruba 6300 Core** | 1/1/5 | **jMalaqui Router VM** | ens37 | **jMalaqui WAN Point-to-Point Link**<br>Subnet: `192.168.3.100/30`<br>Switch IP: `.101` \| Router IP: `.102`<br>*Config:* `ip address 192.168.3.101/30`, `ip ospf 1 area 0` |
| **Aruba 2530 Access** | 1 | **Catalan Client/Server** | ens33 | **Catalan LAN Access Port**<br>Untagged VLAN 231 only.<br>SVI Gateway: `172.16.57.193`<br>*Config:* `untagged 1`, `tagged 3` |
| **Aruba 2530 Access** | 2 | **jMalaqui Client/Server** | ens33 | **jMalaqui LAN Access Port**<br>Untagged VLAN 217 only.<br>SVI Gateway: `172.16.54.65`<br>*Config:* `untagged 2`, `tagged 3` |


---

*Last Updated: July 03, 2026*
