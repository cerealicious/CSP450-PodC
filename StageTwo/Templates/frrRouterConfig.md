### File: `frrRouterConfig.md`

# Linux Router OSPF Routing Daemon Configuration
**Platform Execution Engine:** Free Range Routing interactive terminal utility via `sudo vtysh`.

These command strings program your local routing architecture to calculate paths dynamically across the campus network topology fabric.


```text
sudo vtysh
conf t
router ospf
  router-id router-id 231.0.0.1                  # ----> Loopback/Identity identifier derived from Your UID
  network 10.0.0.0/8 area 0.0.0.0     # ----> Seneca Lab Backbone Network (Ensure subnet value matches active room)
  network 192.168.3.156/30 area 0.0.0.0 # ----> Local Switch-Facing Point-to-Point Link Network ID
  exit
ip route 0.0.0.0 0.0.0.0 ens33          # ----> Gateway-of-Last-Resort shunted out through internet port
```


Note: I think I have to add my Local VLAN for Stage 3:
network 172.16.57.192/26 area 0.0.0.0  # ----> advertise your own LAN so classmates can find your store.
