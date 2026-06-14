# Scenario A: Aruba 6300 Core + Aruba 2530/2540 Access Switch Setup
---

## PART 1: ARUBA 6300 CORE SWITCH CONFIGURATION
*To be executed by `catalan` on the primary Core Switch (ARUBA 6300).*

```text
en
conf t

hostname catalan-6300

# Configure Your Isolated Local LAN Domain
vlan 231
  spanning-tree
  name catalan-vlan
interface vlan 231
  no shutdown
  ip address 172.16.57.193/26          # ----> Your Core SVI Gateway (Your First Usable IP)
  ip ospf 1 area 0                     # ----> Links your local LAN to backbone OSPF
  y
  y
  exit

# Configure Your Partner's Isolated LAN Domain
vlan 217
  spanning-tree
  name jmalaqui-vlan
interface vlan 217
  no shutdown
  ip address 172.16.54.65/26           # ----> Partner Core SVI Gateway (Your Partner's First Usable IP)
  ip ospf 1 area 0                     # ----> Links partner LAN to backbone OSPF
  exit

# Core-to-Access 802.1Q Data Uplink Trunk Interface
interface 1/1/3 
  no shutdown 
  no routing 
  vlan trunk native 1 
  vlan trunk allowed all
  exit

# Layer-3 Routed WAN Link Facing Your Linux Router WAN Port
interface 1/1/4 
  no shutdown
  routing
  ip address 192.168.3.157/30          # ----> Switch-side Transit IP (Your Router's First Usable IP)
  ip ospf 1 area 0                     # ----> Exposes your Router link to OSPF fabric
  exit

# Layer-3 Routed WAN Link Facing Your Partner's Linux Router WAN Port
interface 1/1/5
  no shutdown
  routing
  ip address 192.168.3.101/30          # ----> Partner Switch-side Transit IP (Your Partner's Router First Usable IP)
  ip ospf 1 area 0                     # ----> Exposes partner Router link to OSPF fabric
  exit

# Centralized DHCP Server Pools Infrastructure
dhcp-server vrf default

  # Your Subnet Allocation Pool & Server Static Reservation
  pool vlan231
    range 172.16.57.194 172.16.57.253 prefix-len 26 
    default-router 172.16.57.193      # ----> Points local clients to your Core Gateway (Your First Usable IP)
    static-bind ip 172.16.57.254 mac xx:xx:xx:xx:xx:xx # ----> Binds last usable address to your Server VM (Your Last Usable IP + Server VM MAC Address)
    dns-server 192.168.3.158          # ----> Points local clients to your Edge Linux Router IP
    exit

  # Your Partner's Subnet Allocation Pool & Server Static Reservation
  pool vlan217
    range 172.16.54.66 172.16.54.125 prefix-len 26
    default-router 172.16.54.65       # ----> Points partner clients to partner Core Gateway (Your Partner's Router First Usable IP)
    static-bind ip 172.16.54.126 mac xx:xx:xx:xx:xx:xx # ----> Binds last usable address to partner Server VM (Your Partner Last Usable IP + Partner's Server VM MAC Address)
    dns-server 192.168.3.102          # ----> Points partner clients to partner Edge Linux Router IP (Your Partners Router Last Usable IP)
    exit

# Dynamic OSPF Backbone Identity Configuration
enable
router ospf 1
  router-id 231.217.231.217           # ----> Combined UIDs
  exit

# Outbound Gateway Traversal Static Paths
ip route 0.0.0.0 0.0.0.0 192.168.3.158 # ----> Primary default route out through Your Router WAN IP (Your Router's Last Usable IP)
ip route 0.0.0.0 0.0.0.0 192.168.3.102 # ----> Backup default route out through Partner Router WAN IP (Your Partner Router's Last Usable IP)

write memory
```

====================================================================================================
## PART 2: ARUBA 2530 CORE SWITCH CONFIGURATION
*To be executed by `jmalaqui` on the floor Access Switch (ARUBA 2530).*

```text
en
conf t

# Point switch controller to Core SVI Gateway address for out-of-subnet management connectivity
ip default-gateway 172.16.57.193
hostname "catalan-25XX"
spanning-tree

# Map Local Workspace Isolation Rules for Your Office Floor
vlan 231
  name "catalan-vlan"
  ip address 172.16.57.194/26          # ----> Access Switch Unique SVI IP (Your Subnet - Second Usable IP)
  untagged 1                           # ----> Maps physical port 1 strictly to Your desk drop
  tagged 3                             # ----> Shunts Your data tags up the trunk line to Core
  exit

# Map Local Workspace Isolation Rules for Your Partner's Floor
vlan 217
  name "jmalaqui-vlan"
  ip address 172.16.54.66/26           # ----> Access Switch Unique SVI IP (Partner Subnet - Partners Second Usable IP)
  untagged 2                           # ----> Maps physical port 2 strictly to Partner desk drop
  tagged 3                             # ----> Shunts Partner data tags up the trunk line to Core
  exit

write memory
