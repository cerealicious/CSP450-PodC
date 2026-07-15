

# CSP450 Stage Two Setup and Configurations

## Pre-Flight Requirements Before Beginning
1. **Your UID / VLAN ID:** `231`
2. **Your Partner's UID / VLAN ID:** `217`
3. **Your LAN Subnet Space:** `172.16.57.192/26`
4. **Your Point-to-Point WAN Link:** `192.168.3.156/30`
5. **Your Switch Management Subnet:** Check the pod chart on your specific rack (e.g., `10.10.10.X/28`).

---

## PHASE 0: INFRASTRUCTURE PACKAGE PREREQUISITES
Before deploying any configuration logic files across your target Linux virtual machines, you must install the core network utilities and software daemons. Ensure your virtual machines have a functional connection to the internet, open your terminal screens, and run the following setup commands:

### Client & Server VMs

#### Update repository index and install networking/routing diagnostic utilities
```bash
sudo apt update
sudo apt install isc-dhcp-client traceroute net-tools iputils-ping -y
```
### Router VM
#### Update repository index and install the FRRouting engine and Netfilter hooks
```bash
sudo apt update
sudo apt install frr nftables traceroute net-tools iputils-ping -y
```

#### Enable and ignite the system daemons on boot
```bash
sudo systemctl enable --now frr
sudo systemctl enable --now nftables
```
---

## PHASE 1: HOST VM NETWORK PLUMBING & MAPPING
**Goal:** Rebuild the virtual network environment on your Windows Host machine before booting any Virtual Machines.

###  Task 1.1: Reconstruct the Virtual Network Editor Layout
Open VMware Workstation Virtual Network Editor as an Administrator. Delete any stock settings and map the following three required virtual networks strictly to your physical computer network cards:

* **VMnet5** &rarr; Change type to **Bridged**, and set "**Bridged to:**" explicitly to your **primary Ethernet Family Controller card** (this physical port handles your internal LAN facing the Access Switch).
* **VMnet6** &rarr; Change type to **Bridged**, and set "**Bridged to:**" explicitly to your **Intel Ethernet Connection card** (this physical port connects directly to the Seneca network for internet access).
* **VMnet7** &rarr; Change type to **Bridged**, and set "**Bridged to:**" explicitly to your **secondary Ethernet Family Controller #2 card** (this physical port handles your external OSPF WAN link to the Core Switch).

###  Task 1.2: Correct Router Virtual Hardware Mapping
Right-click your Router VM settings (**Ensure the VM is powered OFF**). Verify that your network adapters are bound to the correct VMnets so that Linux aligns them with the proper internal names (`ens33` and `ens37`):

* **Network Adapter 1:** Must be set to **Custom: VMnet6** (Faces the Internet / Seneca Network via interface `ens33`).
* **Network Adapter 2:** Must be set to **Custom: VMnet7** (Faces the Aruba 6300 Core Switch via interface `ens37`).

###  Task 1.3: Map Workstation Virtual Hardware
Right-click your Client VM and Server VM settings. Set their Network Adapters strictly to **Custom: VMnet5** (Faces the local office LAN switch ports via interface `ens33`).

---

## PHASE 2: PHYSICAL MANAGEMENT LAYER CUTOVER
**Goal:** Establish direct communications from your desk to the console of the physical hardware switches.

###  Task 2.1: Run Physical Management Cables
Go into the back server rack room and look at your assigned Pod letter rack.

* **For Switch 2 (Aruba 6300 Core):** Run a physical network patch cable from the port labeled **MGMT** on the back of the switch into your desk's corresponding patch panel port (example: Port C5 if you sit at desk C5).
* **For Switch 1 (Aruba 2500 / 2530 / 6300):** Your partner runs a second network patch cable from the management port into their desk's patch panel port. *(Note: On 2500/2530 switches, use the dedicated port or Port 23/24).*

###  Task 2.2: Hardcode the Windows Host Management IP Address
Back at your desk, open **View Network Connections** in Windows. Right-click the network card icon corresponding to your physical management cable connection, open **IPv4 Properties**, and enter:

* **IP Address:** Choose an available IP inside your Pod's specific switch management block (e.g., if the switch is `10.10.10.34`, set your PC to `10.10.10.35`).
* **Subnet Mask:** Enter the exact mask listed on your pod's chart (typically `255.255.255.240` for a `/28` block).
* **Default Gateway:** Leave completely blank.

###  Task 2.3: Verify Management Link Adjacency
Open the Windows Command Prompt and test the physical line: `ping [Your Switch Management IP]`.

Once you receive stable replies, open PuTTY, choose **SSH**, type the **Switch Management IP**, and log in using `"student"`.

---

## PHASE 3: SWITCH ARCHITECTURE PROVISIONING & PHYSICAL CABLING
**Goal:** Load custom routing configurations into the bare-metal hardware and complete physical production wiring.

* **Files needed:** `6300-25xxSwitchCommandsStage2_2Students.txt` or `6300-6300SwitchCommandsStage2_2Students.txt`
* **When to use it:** Tasks 3.1 & 3.2
* **How it's used:** You will open an SSH session via PuTTY directly to the switches over your temporary management lines. You will copy the entire block of commands from your text file and paste them directly into the terminal window to automate building your VLANs, inter-switch links, routing interfaces, and DHCP server scopes.

###  Task 3.1: Provision Switch 2 (The Aruba 6300 Core Switch)
Once logged into the 6300 CLI, enter privileged mode (`en` then `conf t`). Paste your pre-edited configuration block to execute the following logic:
1. Create your branch virtual domain (VLAN 231) and your partner's branch domain (VLAN 217).
2. Turn on OSPF Routing Area 0 on those VLAN interfaces.
3. Change interfaces `1/1/4` and `1/1/5` into fully layer-3 routed ports (`routing`) and assign your Point-to-Point WAN transit IP addresses.
4. Build the `dhcp-server vrf default` pools for both network segments, ensuring you update the static-bind hardware MAC address strings with the actual fingerprints of your respective Ubuntu Server VMs.

###  Task 3.2: Provision Switch 1 (Choose your Hardware Route below)
* **ROUTE A (If Switch 1 is an older Aruba 2500/2530 Access Switch):** Log into the switch console. Use classic ProCurve syntax to set the management gateway (`ip default-gateway`), create both student VLANs, and apply physical port mapping rules: Set Port 1 as Untagged for your VLAN, Port 2 as Untagged for your partner's VLAN, and Port 3 as Tagged to act as the trunk uplink wire to the core switch.
* **ROUTE B (If Switch 1 is a modern Aruba 6300 Core Switch):** Log into the switch console. Use modern ArubaOS-CX syntax to match Switch 2. Replicate both student VLAN IDs, assign respective access IPs, and configure interface `1/1/3` on both switches as an enterprise trunk network line (`vlan trunk allowed all`).

###  Task 3.3: Production Cable Cutover & Physical Wiring Plan
Once both switch configurations are done, return to the back rack room. Unplug your temporary management lines and execute the complete structural layout patch according to the matrix below:

#### Physical Cable & Topology Matrix

| Connection Source (From Device) | Port / Interface | Target Destination (To Device / Location) | Subnet / Network Domain | Purpose / Traffic Type |
| :--- | :--- | :--- | :--- | :--- |
| **Aruba 6300 Core Switch** | `1/1/3` | **Aruba 2530 Access Switch** Port `3` | Tagged VLANs 231 & 217 | Inter-Switch 802.1Q Trunk Pipe |
| **Aruba 6300 Core Switch** | `1/1/4` | **Your Router WAN** (`ens37` via Desk Block Panel / VMnet7) | `192.168.3.156/30` | Your Point-to-Point Routing Interface |
| **Aruba 6300 Core Switch** | `1/1/5` | **Partner Router WAN** (via Partner Desk Block Panel) | `192.168.3.100/30` | Partner's Point-to-Point Routing Interface |
| **Aruba 2530 Access Switch** | `1` | **Your Desk Node Block** (Maps to VMnet5 / `ens33` / Family Controller) | `172.16.57.192/26` | Your Private Office LAN (Client & Server) |
| **Aruba 2530 Access Switch** | `2` | **Partner Desk Node Block** (Maps to Partner VMnet5) | `172.16.54.64/26` | Partner's Private Office LAN (Client & Server) |

---

## PHASE 4: ENDPOINT ACTIVATION & INTER-BRANCH CONNECTIVITY
**Goal:** Boot up your local business infrastructure nodes and check foundational local area connectivity.

* **File needed:** `dhcpAssignment.yaml` (Originally `dhcpAssignment.yaml.txt`)
* **When to use:** Task 4.1
* **How it's used:** Once your Server VM is booted on VMnet5, you will open a terminal inside Ubuntu and write these configurations into your Netplan system folder at `/etc/netplan/dhcpAssignment.yaml`. Running `sudo netplan apply` forces the server to look across the switch network via DHCP, matching its MAC address to claim its reserved `.254` IP.

###  Task 4.1: Bring Corporate Server Online
Boot your Ubuntu Server VM on VMnet5. Ensure its netplan is configured to look for DHCP (`dhcp4: true`). Once booted, run `ip a` to verify that it successfully caught its reserved static profile IP address (the very last usable address in your pool, like `172.16.57.254`) directly from the switch engine.

###  Task 4.2: Bring Corporate Client Online
Boot your Ubuntu Client VM on VMnet5. Verify that it automatically pulls a dynamic IP address from your switch's configured lease range (`172.16.57.194` to `172.16.57.253`).

###  Task 4.3: Validate Local Network Fabric
Open a terminal screen on your Client VM and execute a continuous ping directly to your Server VM: `ping 172.16.57.254`. Ensure this routes successfully with zero dropped packets.

---

## PHASE 5: LINUX GATEWAY PROVISIONING & OSPF NEIGHBORHOOD IGNITION
**Goal:** Transform your Ubuntu Router into an advanced multi-vendor gateway and join the core routing fabric.

* **File needed:** `staticAssignment.yaml` (Originally `staticAssignment.yaml.txt`)
* **When to use:** Task 5.1
* **How it's used:** As soon as your Router VM boots up, you will write this block into the Netplan directory at `/etc/netplan/staticAssignment.yaml`. This manual step assigns the hardcoded point-to-point IP (`192.168.3.158/30`) to your switch-facing interface card (`ens37`), setting up your primary connection to the core network switch.
<p>

* **File needed:** `frrRouterConfig.txt`
* **When to use:** Task 5.4
* **How it's used:** After confirming that the `ospfd` routing engine daemon has been toggled to `yes`, you will type `sudo vtysh` in the terminal to enter the multi-vendor shell environment. From there, you copy and paste the commands from this file into the prompt to tell your router how to dynamically exchange network paths with the other student pods using OSPF.

###  Task 5.1: Initialize Static WAN Routing Link
Boot your Ubuntu Router VM. Open your Netplan configuration file (`sudo nano /etc/netplan/staticAssignment.yaml`). Bind your tight point-to-point IP address profile directly to your switch-facing interface `ens37` with `dhcp4: false`. Run `sudo netplan apply` to lock it into place.

###  Task 5.2: Activate Structural System Kernel Forwarding
By default, Linux blocks data packets from traveling between different network cards. Enable system routing by opening the system control configuration architecture: `sudo sysctl -w net.ipv4.ip_forward=1`. *(To make this change permanent through restarts, uncomment the line `net.ipv4.ip_forward=1` inside the `/etc/sysctl.conf` file or `/etc/sysctl.d/99-custom.conf`).*

###  Task 5.3: Ignite the OSPF Routing Daemon Engine
Open the Free Range Routing daemon settings file (`sudo nano /etc/frr/daemons`) and change the line reading `ospfd=no` to `ospfd=yes`. Sometimes manual file edits can accidentally change the file permissions, causing the FRR process to crash on boot because it cannot read its own config, so we have to reset the permission of the frr folder by running: `sudo chown -R frr:frr /etc/frr/`. Then restart the routing system manager by running: `sudo systemctl restart frr`.

###  Task 5.4: Broadcast Local Subnet Routes
Enter the interactive routing suite engine by typing `sudo vtysh`. Enter configuration mode (`conf t`), configure your `router ospf` environment, assign your unique router-id `231.1.1.2`, and use network command strings to advertise both your Point-to-Point WAN link and your private local subnet out to Area 0.

---

## PHASE 6: FIREWALL SECURITY & FINAL VERIFICATION
**Goal:** Apply security isolation masks and record verification metric milestones.

* **File needed:** `NFtablesRulesNAT.txt`
* **When to use:** Task 6.1
* **How to use:** In your Router VM, open your main system security file at `/etc/nftables.conf` on the router, wipe out any stock parameters, and paste this entire security rule layout. Running `sudo nft -f /etc/nftables.conf` turns on your firewall, forces local corporate DNS masking, and enables internet connection Sharing (NAT masquerading) out through interface `ens33`.

###  Task 6.1: Load Security Rules and Outbound NAT Masking
Open your firewall ruleset deployment file (`sudo nano /etc/nftables.conf`). Paste your customized packet-filtering strings to enable established-state tracking on interface `ens33` and transparently proxy internal corporate DNS queries directly over to the main lab resolver address (`10.101.100.21`). Turn the rules live using: `sudo nft -f /etc/nftables.conf`.

###  Task 6.2: Disable VMware Backdoors & Test Edge Routing
Go to your Client VM and Server VM network options. Ensure that any standalone second network cards that connect straight to the internet via VMware are completely disabled or disconnected. Your workstations must now access the open web exclusively by passing traffic through your Aruba switch up to your custom Linux Router.

###  Task 6.3: Capture Lab Deliverable Sign-Off Metrics
From your isolated Client VM, test your new network architecture:
1. **Verify edge path tracking** by running a ping and traceroute: `traceroute google.ca`.
2. **Verify your multi-vendor OSPF neighbor tables are active.** Inside your Router's vtysh and your Aruba 6300 core terminal, run: `show ip ospf neighbor` and `show ip route`. You should see your network expanding as your classmates' subnets dynamically pop into your screen!
3. **Capture required protocol validation handshakes.** Open Wireshark, capture the required protocol validation handshakes (DHCP lease adjustments, local SSH key management, and outer DNS requests), and export your tracking logs for lab check-off.

---

## 📸 Visual Inspection & Lab Report SCREENSHOT Guide

This section outlines the exact verification commands required for the visual inspection, along with a checklist of mandatory SCREENSHOTs needed for the stage two report submission.

---

### 1. Client VM Verification & Visual Inspection
Before verifying, make sure all dynamic VMware internet backdoors on the Client are disabled. All traffic must travel explicitly through the physical switch to your custom Linux Router.

#### Live Commands to Run
* **Renew DHCP Lease & Verify Dynamic Scope Allocation:**
    ```bash
    sudo dhclient -r && sudo dhclient -v
    ```
    *Verify that your Client captures a dynamic IP in your pool range (`172.16.57.194` to `172.16.57.253`).*

* **Execute Network Path Tracking Checks:**
    ```bash
    # Test 1: Verify your local default gateway hop paths
    traceroute 192.168.3.158

    # Test 2: Verify name resolution and end-to-end internet route traversal
    traceroute youtube.com
    ```

#### Required SCREENSHOTs for Report
*  **📸SCREENSHOT #1:** The complete terminal output window showing a successful traceroute path tracking matrix straight from the client to your own router gateway interface.
*  **📸SCREENSHOT #2:** The complete terminal output window showing a successful traceroute path tracking matrix from the client to YouTube.

---
### 2. Server VM Verification & Hardening Inspection
Your production server relies on the Aruba core switch's DHCP service matching its physical interface MAC address layout to map its dedicated static reservation profile. It must also have its password entry backdoors permanently locked down for the audit user.

#### Live Commands to Run
* **1. Renew Statically Bound IP Lease Mapping:**
    ```bash
    sudo dhclient -r && sudo dhclient -v
    ```
    *Verify that the server adapter captures its exact reservation address layout (`172.16.57.254`).*

* **2. Initialize the Audit User Profile & Disable Password Authentication:**
    ```bash
    # Create the mandatory laboratory user
    sudo adduser SSHtest

    # Lock the password field to completely disable password-based logins
    sudo passwd -l SSHtest
    ```
  (Note: This forces the account to only accept secure, pre-authorized cryptographic key handshakes).*
  
   #### 2.1 Harden the SSH Service Config (Disable Password Logins)
   a.) On your **Server VM**, open the master SSH configuration file:
   ```bash
   sudo nano /etc/ssh/sshd_config
   ```
   b.) Scroll down and find the line: `#PasswordAuthentication yes` (or `PasswordAuthentication yes`).
   c.) Uncomment it (remove the `#`) and change it to:
   ```bash
   PasswordAuthentication no
   ```
   d.) Save and close nano (**Ctrl+O**, **Enter**, then **Ctrl+X**)

   e.) Restart the SSH service
   ```bash
   sudo systemctl restart ssh
   ```
   f.) Verify the SSHtest Local Password Lockout
   On your **Server VM**, run:
   ```bash
   sudo passwd -S SSHtest
   ```
   **🔎Expected Output:** `SSHtest L 07/15/2026...` **L** means **locked**.

   g.)  Verify SSHtest Network Password Rejection
   ```bash
   ssh SSHtest@172.16.57.254
   ```
   **🔎Expected Output:** `SSHtest@172.16.57.254: Permission denied (publickey)`.

#### 3. Verify Outbound Internet Edge Routing:**
```bash
ping -c 4 www.google.ca
```

#### Required SCREENSHOTs for Report
*  **📸SCREENSHOT #3:** Verification showing that internet access is fully functional from the Server by pinging www.google.ca with 0% packet loss.


---
### 3. Linux Router VM Verification & Routing Fabric Inspection
The Linux Router VM handles your secure firewall boundary rules, dynamic NAT packet masquerading, and internal OSPF fabric convergence advertisements with neighboring classroom pods.

#### Live Commands to Run
* **Inspect Active Firewall Rule Arrays:**
    ```bash
    sudo nft list ruleset | less
    ```
    *(Verify that your NAT prerouting redirects internal local corporate DNS lookups to your gateway, and postrouting masquerades outbound interface `ens33` traffic).*

* **Verify Active Multi-Vendor OSPF Neighbor Status:**
    ```bash
    sudo vtysh
    ```
    Inside the interactive `vtysh` routing terminal suite, execute the following diagnostic checks:
    ```text
    # Check overall engine operational state and area layout
    show ip ospf

    # Display active neighborhood adjacency handshakes with other student pods
    show ip ospf neighbor

    # Display the current live kernel routing map tables
    show ip route
    ```

#### Required SCREENSHOTs for Report
*  **📸SCREENSHOT #4:** The full terminal output window of your `nft list ruleset` capture, displaying your live firewall and NAT rules.
*  **📸SCREENSHOT #5:** Your interactive routing console displaying the comprehensive metrics from running your OSPF process verification check (`show ip ospf`).
*  **📸SCREENSHOT #6:** The complete kernel routing table matrix (`show ip route`) highlighting the dynamically learned paths (`O`) generated by your partner's pod and other classmate subnets.

---

### 4. Aruba Hardware Switch Verification (In-Band Terminal Steps)
*Note: Because your host PC's physical line is re-patched into the production network, you can no longer use a management port line. You must run these verification commands by opening an in-band SSH session right from your Client VM terminal.*

1. Open a terminal window on your **Client VM**.
2. SSH directly into your Core Switch's network gateway interface: `ssh student@172.16.57.193`
3. Enter execution mode: `en`

#### Required SCREENSHOTs for Report
*  **📸SCREENSHOT #7 (`show vlan`):** Run this command to verify that your branch domain (VLAN 231) and your partner `jmalaquis`'s branch domain (VLAN 217) are successfully mapped and operational on the core switch fabric.
*  **📸SCREENSHOT #8 (`show ip route`):** Run this to verify your backbone routing pathways. Ensure your fallback static default paths point straight to your Linux Router's switch-facing IP interface address (`192.168.3.158`)
*  **📸SCREENSHOT #9 (`show ip interface brief`):** Run this to verify that your virtual SVI gateways (`vlan231` and `vlan217`) and the physical routing uplink ports (`1/1/4` and `1/1/5`) are reading fully as `up/up`.
*  **📸SCREENSHOT #10 (`show dhcp-server vrf default`):** Run this to display your dynamic subnet allocation counters and prove your server's hardware MAC address reservation profile is active.
*  **📸SCREENSHOT #11 (`show running-config`):** Run this and scroll down to verify that all dual-tenant scopes, OSPF routing zones, and configuration lines are securely saved to non-volatile memory.


#### Aruba 2500/2530 Access Switch Commands
1. While still inside your Core switch session, jump across the inter-switch trunk pipeline link straight to the Access switch management node: 
   ```bash
   ssh student@172.16.57.194
   ```
 **📸SCREENSHOT #12 (`show vlan`)**: Run this to confirm Port 1 is untagged for your network (VLAN 231) and Port 2 is untagged for your partner (VLAN 217).

 **📸SCREENSHOT #13 (`show vlans ports 3`)**: Do not run 'show trunks' as it will throw a syntax error on this specific hardware model. Run `show vlans ports 3` instead to capture your inter-switch trunk pipeline. Verify that Port 3 shows up as explicitly Tagged for both VLAN 231 and VLAN 217.

---

## 🔐 PHASE 7: KEY EXCHANGE DEPLOYMENT & ADDITIONAL CHECKS

This section outlines the generation, propagation, and validation of passwordless cryptographic identities across your target multi-vendor infrastructure.

#### Step A: Generate the Key Pair on your Client VM
1. Open a terminal on your **Client VM**.
2. Run the generator: `ssh-keygen -t ed25519`
3. Press **Enter** to accept the default file path. Press **Enter** twice more to leave the passphrase completely blank (this allows passwordless authentication).

#### Step B: Distribute the Key to your Linux Nodes
From your Client VM terminal, execute the automated transmission tool to inject your public signature into your destination systems (replace `catalan` with your actual Linux user account profile name):

```bash
#Push key to your Ubuntu Router
ssh-copy-id catalan@192.168.3.158

#Push key to your Ubuntu Server
ssh-copy-id catalan@172.16.57.254
```
*(Note: Type "yes" when prompted to accept host authenticity, then enter your user's password one final time to authorize the transmission).*

#### Step C: Verify if all the keys are working.
Open a new TERMINAL on your **Client VM**, and run these tests. You should log into all of them instantly without being prompted for a password:

```bash
ssh catalan@172.16.57.254
exit

ssh catalan@192.168.3.158
exit
```

Test also if you can ssh to your Core Switch using `student` as both username and password
```bash
ssh student@172.16.57.193
exit
```

### Additional Routing Verification Checks
#### Execute Network Path Tracking Checks:
Open a new TERMINAL on your **Client VM** and execute the final path-validation traces:
```bash
traceroute 192.168.3.158
```

** 📸SCREENSHOT #14:** The complete terminal output window showing a successful `traceroute` path tracking matrix straight from the client to your Ubuntu router interface (`192.168.3.158`).

On your **Client VM** terminal run:
```bash
traceroute youtube.com
```

** 📸SCREENSHOT #15:** The complete terminal output window showing a successful traceroute path tracking matrix from the client out to YouTube, confirming outbound edge traversal.

---
---

## Wireshark Packet Capture & Trimming Guide
This procedure outlines how to capture, filter, and export only the required transaction packets to keep your submission file clean. 
### 1: Initialize the Wireshark Capture Engine

1. Boot your Client VM and open terminal and run `sudo wireshark`.
2. Double-click your primary network adapter interface card (typically listed as `ens33`). A live, scrolling window of network packets will appear.
3. Click **Capture** or the **Blue Fin**.
4. Minimize the Wireshark application window.


### 2: Execute the Traffic Trigger Sequence
Execute these commands in sequence to generate the necessary packet traces:
```bash
# Trigger 1: SSH from Client to Server & response
ssh catalan@172.16.57.254 'exit'

# Trigger 2: SSH from Client to Router & response
ssh catalan@192.168.3.158 'exit'

# Trigger 3: SSH from Client to the Aruba 6300 Core Switch & response
ssh student@172.16.57.193 'exit'

# Trigger 4: DHCP Release and Renew (Forces a 4-packet DORA Handshake)
sudo dhclient -r && sudo dhclient -v

# Trigger 5: DNS query for YouTube
nslookup youtube.com
```


### A3: Filter and Extract Only the Target Packets
1. Return to Wireshark and click the Red Square (Stop Capture) button.
2. In the green Display Filter bar at the top, paste this exact consolidated filter string and hit **Enter**:
```bash
(ssh && (ip.addr == 172.16.57.254 || ip.addr == 192.168.3.158 || ip.addr == 172.16.57.193)) || (bootp || dhcp) || (dns && dns.qry.name == "youtube.com")
```

3. Verify that your packet window displays only:
* The SSH key exchange and initialization packets between the client and your three nodes (Server, Router, and Core Switch).
* The four chronological DHCP packets (`DHCP Discover`, `DHCP Offer`, `DHCP Request`, and `DHCP ACK`).
* The DNS query packet looking for youtube.com and its corresponding gateway reply.


### A4: Export the Sub-Selected Capture File
To avoid submitting unnecessary background noise (like ARP, STP, or SSDP traffic), follow these steps to save only the filtered packets:

1. In the top menu bar, navigate to **File → Export Specified Packets...**

2. In the file dialogue box:
* Name your file: `stage_two_capture.pcapng`
* Under **Packet Range**, ensure the radio button is set to **All packets** but verify that **All packets matching the display filter** is checked.
3. Click *Save*. Open your new file in Wireshark to confirm it contains only the specific requested packets before uploading it to Blackboard.

---
---
## 🔄 Mandatory Boot Sequence

1. **Physical Switches:** Ensure Aruba 6300 and Aruba 2530 are fully booted and configured *before* powering on any VMs.
2. **Start Router VM (First)**: Wait until it reaches the login prompt
    - Apply Netplan: `sudo netplan apply`
    - Enable IP Forwarding: `sudo sysctl -w net.ipv4.ip_forward=1`
    - Apply Nftables: `sudo nft -f /etc/nftables.conf`
    - Start FRR: `sudo systemctl restart frr`
    - *Wait 30 seconds for OSPF neighbors to form.*
      - ✅Core Switch (231.231.231.231) shows state FULL/DR or FULL/BDR

3. **Start Server VM (Second)**: Wait for login prompt.
    - Renew DHCP lease immediately: `sudo dhclient -r ens33 && sudo dhclient -v ens33`
      - ✅VERIFY: `ip a` show `ens33` returns `172.16.57.254/26`.
4. **Start Client VM (Last)**: Only after Server confirms correct IP.
    - Renew DHCP lease: `sudo dhclient -r ens33 && sudo dhclient -v ens33`
      - ✅VERIFY: `ip a` show `ens33` returns address in `172.16.57.194–253` range.
    - Test connectivity: `ping 172.16.57.193` (Gateway) → `ping 8.8.8.8` (Google DNS).

---

### 🔧 Troubleshooting: "Client Has No Internet After Boot"

If the Client fails to reach the internet despite following the boot sequence, check these **in order**:

| Check | Command / Action | Expected Result |
| :--- | :--- | :--- |
| **1. Client IP Assignment** | `ip a show ens33` on Client | Must be in `172.16.57.194–253` range. If it's `169.254.x.x`, DHCP failed. |
| **2. Default Gateway Reachability** | `ping 172.16.57.193` from Client | Must reply. If not, VLAN/trunk misconfiguration on 2530/6300. |
| **3. Router IP Forwarding** | `cat /proc/sys/net/ipv4/ip_forward` on Router | Must return `1`. If `0`, run `sudo sysctl -w net.ipv4.ip_forward=1`. |
| **4. Nftables Masquerade Rule** | `sudo nft list chain ip nat postrouting` on Router | Must show `oifname "ens33" masquerade`. Missing = no NAT. |
| **5. Router OSPF Neighbor State** | `show ip ospf neighbor` in `vtysh` on Router | Must show Core Switch as `FULL/DR`. If `INIT/DOWN`, fix Router ID or network statements. |
| **6. DNS Resolution** | `nslookup youtube.com` from Client | Must resolve. If not, check Nftables DNAT rule for port 53 → `10.101.100.21`. |
| **7. Firewall Blocking Return Traffic** | `sudo nft list chain inet filter forward` on Router | Must allow `iifname "ens33" oifname "ens37" ct state established,related accept`. |

---


## 🚨 Critical DHCP Troubleshooting Scenarios

If following the mandatory boot sequence does not resolve connectivity issues, use these specific diagnostic paths based on which VMs are failing to obtain an IP address.

### Scenario A: Server VM Has No IP (APIPA `169.254.x.x`)
*The Client has internet, but the Server cannot get its static-bound IP (`172.16.57.254`).*

| Check | Command / Action | Expected Result | Fix If Failed |
| :--- | :--- | :--- | :--- |
| **1. MAC Address Match** | Compare `ip link show ens33` on Server vs. `static-bind` in 6300 config | MACs must match **exactly** (case-insensitive) | Re-run `static-bind ip 172.16.57.254 mac <ACTUAL_MAC>` on Aruba 6300. Save memory. |
| **2. DHCP Pool Range** | `show dhcp-server vrf default pool vlan231` on 6300 | Range includes `.254` and prefix-len is `/26` | Ensure range is `172.16.57.194 172.16.57.253`. Static binds *outside* the dynamic range are still valid, but verify syntax. |
| **3. SVI Status** | `show ip interface brief \| include Vlan231` on 6300 | State is `up/up` | Run `interface vlan 231` → `no shutdown`. Verify VLAN 231 exists and is active. |
| **4. Access Port Tagging** | `show interfaces ethernet 1/1/x switchport` on 2530 | Port connected to Server is `untagged 231` | If port is `tagged`, Server won't get untagged DHCP. Change to `untagged 231`. |
| **5. Force Renew** | `sudo dhclient -r ens33 && sudo dhclient -v ens33` on Server | Should receive `172.16.57.254` | Watch verbose output for `DHCPACK`. If `DHCPNAK`, MAC or pool mismatch exists. |

### Scenario B: BOTH Server & Client Have No IP
*Neither VM receives any address from the Aruba 6300.*

| Check | Command / Action | Expected Result | Fix If Failed |
| :--- | :--- | :--- | :--- |
| **1. Trunk Link Health** | `show interfaces ethernet 1/1/3 switchport` on 6300 | Mode: `trunk`, Native: `1`, Allowed: `all` | If trunk is down or misconfigured, no VLAN traffic reaches the 2530. Re-apply trunk config. |
| **2. 2530 Uplink** | `show interfaces ethernet 3 switchport` on 2530 | Mode: `trunk`, Tagged: `231,217` | Port 3 on 2530 MUST be tagged for both VLANs. If untagged, only native VLAN passes. |
| **3. OSPF Adjacency** | `show ip ospf neighbor` on 6300 | Router shows `FULL/BDR` or `FULL/DR` | If OSPF is down, the 6300 may not be routing between VLAN SVIs properly. Check Router ID conflicts. |
| **4. DHCP Service** | `show dhcp-server vrf default statistics` on 6300 | Active leases > 0 | If service is stopped, run `dhcp-server vrf default` → `no shutdown`. |
| **5. Physical Cabling** | Verify LED status on 6300 Port 1/1/3 and 2530 Port 3 | Both ports show solid green link light | Replace cable if amber/off. Ensure correct port mapping (Port 3 = Uplink). |
| **6. VLAN Existence** | `show vlan` on BOTH switches | VLAN 231 and 217 exist and are `active` | VLANs created on 6300 do NOT auto-propagate to 2530. Must create manually on 2530. |

### Scenario C: Client Gets IP But Cannot Reach Internet
*Server works fine, Client has valid IP (`172.16.57.x`), but `ping 8.8.8.8` fails.*

| Check | Command / Action | Expected Result | Fix If Failed |
| :--- | :--- | :--- | :--- |
| **1. Default Gateway** | `ip route show default` on Client | Points to `172.16.57.193` | If missing, DHCP didn't send option 3. Check `default-router` in 6300 pool config. |
| **2. Router Forwarding** | `cat /proc/sys/net/ipv4/ip_forward` on Router | Returns `1` | Run `sudo sysctl -w net.ipv4.ip_forward=1`. Make persistent in `/etc/sysctl.conf`. |
| **3. NAT Masquerade** | `sudo nft list chain ip nat postrouting` on Router | Shows `oifname "ens33" masquerade` | Without masquerade, return traffic from internet is dropped. Re-apply Nftables. |
| **4. DNS Resolution** | `nslookup youtube.com` on Client | Resolves to public IP | If fails but ping works, check DNAT rule: `iifname "ens37" udp dport 53 dnat to 10.101.100.21`. |
| **5. Firewall Forward** | `sudo nft list chain inet filter forward` on Router | Allows `ens37→ens33` and established return | Missing forward rules block LAN-to-WAN traffic entirely. |
| **6. OSPF Route** | `vtysh -c "show ip route 0.0.0.0/0"` on Router | Shows default via `ens33` | If router has no default route, it cannot forward to Seneca network. Check `ip route 0.0.0.0 0.0.0.0 ens33`. |

---
*Last Updated: July 15, 2026*
