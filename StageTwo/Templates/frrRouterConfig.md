### File: `frrRouterConfig.md`

# Linux Router OSPF Routing Daemon Configuration
**Platform Execution Engine:** Free Range Routing interactive terminal utility via `sudo vtysh`.

These command strings program your local routing architecture to calculate paths dynamically across the campus network topology fabric.


```text
sudo vtysh
conf t
router ospf
  router-id 231.1.1.2                  # ----> Loopback/Identity identifier derived from Your UID
  network 10.0.0.0/24 area 0.0.0.0     # ----> Seneca Lab Backbone Network (Ensure subnet value matches active room)
  network 192.168.3.156/30 area 0.0.0.0 # ----> Local Switch-Facing Point-to-Point Link Network ID
  exit
ip route 0.0.0.0 0.0.0.0 ens33          # ----> Gateway-of-Last-Resort shunted out through internet port
