# CSP450-PodC
CSP450 Stage Two Setup and Configurations
# Networking Lab: Multi-Vendor Infrastructure & Routing Architecture

## Pre-Flight Requirements Before Beginning
1. [cite_start]**Your UID / VLAN ID:** `UID#` [cite: 2]
2. [cite_start]**Your Partner's UID / VLAN ID:** `[Partner's UID]` [cite: 3]
3. [cite_start]**Your LAN Subnet Space:** `172.16.X.X/26` [cite: 3]
4. [cite_start]**Your Point-to-Point WAN Link:** `192.168.X.X/30` [cite: 4]
5. [cite_start]**Your Switch Management Subnet:** Check the pod chart on your specific rack (e.g., `10.10.10.X/28`). [cite: 5]

---

## PHASE 1: HOST VM NETWORK PLUMBING & MAPPING
[cite_start]**Goal:** Rebuild the virtual network environment on your Windows Host machine before booting any Virtual Machines. [cite: 7]

### - [ ] Task 1.1: Reconstruct the Virtual Network Editor Layout
Open VMware Workstation Virtual Network Editor as an Administrator. [cite_start]Delete any stock settings and map the following three required virtual networks strictly to your physical computer network cards: [cite: 9]

* **VMnet5** &rarr; [cite_start]Change type to **Bridged**, and set "**Bridged to:**" explicitly to your **primary Ethernet Family Controller card** (this physical port handles your internal LAN). [cite: 10]
* **VMnet6** &rarr; [cite_start]Change type to **Bridged**, and set "**Bridged to:**" explicitly to your **Intel Ethernet Connection card** (this physical port connects directly to the Seneca network for internet access). [cite: 11]
* **VMnet7** &rarr; [cite_start]Change type to **Bridged**, and set "**Bridged to:**" explicitly to your **secondary Ethernet Family Controller #2 card** (this physical port handles your external OSPF WAN link to the switch). [cite: 12]

### - [ ] Task 1.2: Correct Router Virtual Hardware Mapping
[cite_start]Right-click your Router VM settings (**Ensure the VM is powered OFF**). [cite: 13] [cite_start]Verify that your network adapters are bound to the correct VMnets so that Linux aligns them with the proper internal names (`ens33` and `ens37`): [cite: 14]

* [cite_start]**Network Adapter 1:** Must be set to **Custom: VMnet6** (Faces the Internet). [cite: 15]
* [cite_start]**Network Adapter 2:** Must be set to **Custom: VMnet7** (Faces the Switch). [cite: 16]

### - [ ] Task 1.3: Map Workstation Virtual Hardware
[cite_start]Right-click your Client VM and Server VM settings. [cite: 17] [cite_start]Set their Network Adapters strictly to **Custom: VMnet5** (Faces the local office LAN switch ports). [cite: 18]

---

## PHASE 2: PHYSICAL MANAGEMENT LAYER CUTOVER
[cite_start]**Goal:** Establish direct communications from your desk to the console of the physical hardware switches. [cite: 20]

### - [ ] Task 2.1: Run Physical Management Cables
[cite_start]Go into the back server rack room [cite: 21] [cite_start]and look at your assigned Pod letter rack. [cite: 22]

* [cite_start]**For Switch 2 (Aruba 6300 Core):** Run a physical network patch cable from the port labeled **MGMT** on the back of the switch into your desk's corresponding patch panel port (example: Port C5 if you sit at desk C5). [cite: 23]
* **For Switch 1 (Aruba 2500 / 2530 / 6300):** Your partner runs a second network patch cable from the management port into their desk's patch panel port. [cite_start]*(Note: On 2500/2530 switches, use the dedicated port or Port 23/24).* [cite: 24, 25]

### - [ ] Task 2.2: Hardcode the Windows Host Management IP Address
[cite_start]Back at your desk, open **View Network Connections** in Windows. [cite: 26] [cite_start]Right-click the network card icon corresponding to your physical management cable connection, open **IPv4 Properties**, and enter: [cite: 27]

* [cite_start]**IP Address:** Choose an available IP inside your Pod's specific switch management block (e.g., if the switch is `10.10.10.34`, set your PC to `10.10.10.35`). [cite: 28]
* [cite_start]**Subnet Mask:** Enter the exact mask listed on your pod's chart (typically `255.255.255.240` for a `/28` block). [cite: 29]
* [cite_start]**Default Gateway:** Leave completely blank. [cite: 30]

### - [ ] Task 2.3: Verify Management Link Adjacency
[cite_start]Open the Windows Command Prompt and test the physical line: `ping [Your Switch Management IP]`. [cite: 32]

[cite_start]Once you receive stable replies, open PuTTY, choose **SSH**, type the **Switch Management IP**, and log in using `"student"`. [cite: 33]

---

## PHASE 3: SWITCH ARCHITECTURE PROVISIONING
[cite_start]**Goal:** Load custom routing configurations into the bare-metal hardware screens. [cite: 35]

* [cite_start]**Files needed:** `6300-25xxSwitchCommandsStage2_2Students.txt` or `6300-6300SwitchCommandsStage2_2Students.txt` [cite: 36]
* [cite_start]**When to use it:** Tasks 3.1 & 3.2 [cite: 36]
* [cite_start]**How it's used:** You will open an SSH session via PuTTY directly to the switches over your temporary management lines. [cite: 37] [cite_start]You will copy the entire block of commands from your text file and paste them directly into the terminal window to automate building your VLANs, inter-switch links, routing interfaces, and DHCP server scopes. [cite: 38]

### - [ ] Task 3.1: Provision Switch 2 (The Aruba 6300 Core Switch)
[cite_start]Once logged into the 6300 CLI, enter privileged mode (`en` then `conf t`). [cite: 39] [cite_start]Paste your pre-edited configuration block to execute the following logic: [cite: 40]

1. [cite_start]Create your branch virtual domain (your vlan UID) and your partner's branch domain (vlan Partner UID). [cite: 41]
2. [cite_start]Turn on OSPF Routing Area 0 on those VLAN interfaces. [cite: 42]
3. [cite_start]Change interfaces `1/1/4` and `1/1/5` into fully layer-3 routed ports (`routing`) and assign your Point-to-Point WAN transit IP addresses. [cite: 43]
4. [cite_start]Build the `dhcp-server vrf default` pools for both network segments, ensuring you update the static-bind hardware MAC address strings with the actual fingerprints of your respective Ubuntu Server VMs. [cite: 44]

### - [ ] Task 3.2: Provision Switch 1 (Choose your Hardware Route below)
* [cite_start]**ROUTE A (If Switch 1 is an older Aruba 2500/2530 Access Switch):** Log into the switch console. [cite: 45] [cite_start]Use classic ProCurve syntax to set the management gateway (`ip default-gateway`), create both student VLANs, and apply physical port mapping rules: Set Port 1 as Untagged for your VLAN, Port 2 as Untagged for your partner's VLAN, and Port 3 as Tagged to act as the trunk uplink wire to the core switch. [cite: 46]
* [cite_start]**ROUTE B (If Switch 1 is a modern Aruba 6300 Core Switch):** Log into the switch console. [cite: 47] [cite_start]Use modern ArubaOS-CX syntax to match Switch 2. Replicate both student VLAN IDs, assign respective access IPs, and configure interface `1/1/3` on both switches as an enterprise trunk network line (`vlan trunk allowed all`). [cite: 48]

### - [ ] Task 3.3: Production Cable Cutover
[cite_start]Once both switch configurations are fully saved (`write memory`), return to the back rack room. [cite: 49] Unplug your temporary management lines. [cite_start]Re-patch your desk data cables into the active production ports you just programmed (e.g., plug your line into Port 1 for your VLAN, and your partner plugs into Port 2 for their VLAN). [cite: 50]

---

## PHASE 4: ENDPOINT ACTIVATION & INTER-BRANCH CONNECTIVITY
[cite_start]**Goal:** Boot up your local business infrastructure nodes and check foundational local area connectivity. [cite: 53]

* [cite_start]**File needed:** `dhcpAssignment.yaml.txt` (Originally `dhcpAssignment.yaml.txt`) [cite: 54]
* [cite_start]**When to use:** Task 4.1 [cite: 54]
* [cite_start]**How it's used:** Once your Server VM is booted on VMnet5, you will open a terminal inside Ubuntu and write these configurations into your Netplan system folder at `/etc/netplan/dhcpAssignment.yaml`. [cite: 55] [cite_start]Running `sudo netplan apply` forces the server to look across the switch network via DHCP, matching its MAC address to claim its reserved `.254` IP. [cite: 56]

### - [ ] Task 4.1: Bring Corporate Server Online
[cite_start]Boot your Ubuntu Server VM on VMnet5. [cite: 57] [cite_start]Ensure its netplan is configured to look for DHCP (`dhcp4: true`). [cite: 58] [cite_start]Once booted, run `ip a` to verify that it successfully caught its reserved static profile IP address (the very last usable address in your pool, like `.254`) directly from the switch engine. [cite: 59]

### - [ ] Task 4.2: Bring Corporate Client Online
[cite_start]Boot your Ubuntu Client VM on VMnet5. [cite: 61] [cite_start]Verify that it automatically pulls a dynamic IP address from your switch's configured lease range. [cite: 62]

### - [ ] Task 4.3: Validate Local Network Fabric
[cite_start]Open a terminal screen on your Client VM and execute a continuous ping directly to your Server VM: `ping [Your Server IP Address]`. [cite: 63] [cite_start]Ensure this paths successfully with zero dropped packets. [cite: 64]

---

## PHASE 5: LINUX GATEWAY PROVISIONING & OSPF NEIGHBORHOOD IGNITION
[cite_start]**Goal:** Transform your Ubuntu Router into an advanced multi-vendor gateway and join the core routing fabric. [cite: 67]

* [cite_start]**File needed:** `staticAssignment.yaml` (originally `staticAssignment.yaml.txt`) [cite: 68]
* [cite_start]**When to use:** Task 5.1 [cite: 68]
* [cite_start]**How it's used:** As soon as your Router VM boots up, you will write this block into the Netplan directory at `/etc/netplan/staticAssignment.yaml`. [cite: 69] [cite_start]This manual step assigns the hardcoded point-to-point IP (`192.168.3.158/30`) to your switch-facing interface card (`ens37`), setting up your primary connection to the core network switch. [cite: 70]

<br>

* [cite_start]**File needed:** `frrRouterConfig.txt` [cite: 71]
* [cite_start]**When to use:** Task 5.4 [cite: 71]
* [cite_start]**How it's used:** After confirming that the `ospfd` routing engine daemon has been toggled to `yes`, you will type `sudo vtysh` in the terminal to enter the multi-vendor shell environment. [cite: 72] [cite_start]From there, you copy and paste the commands from this file into the prompt to tell your router how to dynamically exchange network paths with the other student pods using OSPF. [cite: 73, 74]

### - [ ] Task 5.1: Initialize Static WAN Routing Link
[cite_start]Boot your Ubuntu Router VM. [cite: 75] Open your Netplan configuration file (`sudo nano /etc/netplan/staticAssignment.yaml`). [cite_start]Bind your tight point-to-point IP address profile directly to your switch-facing interface `ens37` with `dhcp4: false`. [cite: 76] [cite_start]Run `sudo netplan apply` to lock it into place. [cite: 77]

### - [ ] Task 5.2: Activate Structural System Kernel Forwarding
[cite_start]By default, Linux blocks data packets from traveling between different network cards. [cite: 78] Enable system routing by opening the system control configuration architecture: `sudo sysctl -w net.ipv4.ip_forward=1`. [cite_start]*(To make this change permanent through restarts, uncomment the line `net.ipv4.ip_forward=1` inside the `/etc/sysctl.conf` file).* [cite: 79]

### - [ ] Task 5.3: Ignite the OSPF Routing Daemon Engine
Open the Free Range Routing daemon settings file (`sudo nano /etc/frr/daemons`) and change the line reading `ospfd=no` to `ospfd=yes`. [cite: 80] Restart the routing system manager: `sudo systemctl restart frr`. [cite: 81]

### - [ ] Task 5.4: Broadcast Local Subnet Routes
Enter the interactive routing suite engine by typing `sudo vtysh`. [cite: 82] Enter configuration mode (`conf t`), configure your `router ospf` environment, assign your unique router-id `[Your UID].1.1.2`, and use network command strings to advertise both your Point-to-Point WAN link and the Seneca network out to Area 0. [cite: 83]

---

## PHASE 6: FIREWALL SECURITY & FINAL VERIFICATION
**Goal:** Apply security isolation masks and record verification metric milestones. [cite: 85]

* [cite_start]**File needed:** `NFtablesRulesNAT.txt` [cite: 86]
* [cite_start]**When to use:** Task 6.1 [cite: 87]
* [cite_start]**How to use:** In your Router VM, open your main system security file at `/etc/nftables.conf` on the router, wipe out any stock parameters, and paste this entire security rule layout. [cite: 88] [cite_start]Running `sudo nft -f /etc/nftables.conf` turns on your firewall, forces local corporate DNS masking, and enables internet connection Sharing (NAT masquerading) out through interface `ens33`. [cite: 89]

### - [ ] Task 6.1: Load Security Rules and Outbound NAT Masking
[cite_start]Open your firewall ruleset deployment file (`sudo nano /etc/nftables.conf`). [cite: 90] [cite_start]Paste your customized packet-filtering strings to enable established-state tracking on interface `ens33` and transparently proxy internal corporate DNS queries directly over to the main lab resolver address (`10.101.100.21`). [cite: 91, 92] [cite_start]Turn the rules live using: `sudo nft -f /etc/nftables.conf`. [cite: 93]

### - [ ] Task 6.2: Disable VMware Backdoors & Test Edge Routing
[cite_start]Go to your Client VM and Server VM network options. [cite: 94] [cite_start]Ensure that any standalone second network cards that connect straight to the internet via VMware are completely disabled or disconnected. [cite: 95] [cite_start]Your workstations must now access the open web exclusively by passing traffic through your Aruba switch up to your custom Linux Router. [cite: 96]

### - [ ] Task 6.3: Capture Lab Deliverable Sign-Off Metrics
[cite_start]From your isolated Client VM, test your new network architecture: [cite: 97]

1. [cite_start]**Verify edge path tracking** by running a ping and traceroute: `traceroute google.ca`. [cite: 98]
2. [cite_start]**Verify your multi-vendor OSPF neighbor tables are active.** Inside your Router's vtysh and your Aruba 6300 core terminal, run: `show ip ospf neighbor` and `show ip route`. [cite: 99] [cite_start]You should see your network expanding as your classmates' subnets dynamically pop into your screen! [cite: 100]
3. [cite_start]**Capture required protocol validation handshakes.** Open Wireshark, capture the required protocol validation handshakes (DHCP lease adjustments, local SSH key management, and outer DNS requests), and export your tracking logs for lab check-off. [cite: 101]
