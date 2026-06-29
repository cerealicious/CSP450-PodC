# CSP450-PodC
CSP450 Stage Two Setup and Configurations

## Pre-Flight Requirements Before Beginning
1. **Your UID / VLAN ID:** `UID#`
2. **Your Partner's UID / VLAN ID:** `[Partner's UID]`
3. **Your LAN Subnet Space:** `172.16.X.X/26`
4. **Your Point-to-Point WAN Link:** `192.168.X.X/30`
5. **Your Switch Management Subnet:** Check the pod chart on your specific rack (e.g., `10.10.10.X/28`).

---

## PHASE 1: HOST VM NETWORK PLUMBING & MAPPING
**Goal:** Rebuild the virtual network environment on your Windows Host machine before booting any Virtual Machines.

### [ ] Task 1.1: Reconstruct the Virtual Network Editor Layout
Open VMware Workstation Virtual Network Editor as an Administrator. Delete any stock settings and map the following three required virtual networks strictly to your physical computer network cards:

* **VMnet5** &rarr; Change type to **Bridged**, and set "**Bridged to:**" explicitly to your **primary Ethernet Family Controller card** (this physical port handles your internal LAN).
* **VMnet6** &rarr; Change type to **Bridged**, and set "**Bridged to:**" explicitly to your **Intel Ethernet Connection card** (this physical port connects directly to the Seneca network for internet access).
* **VMnet7** &rarr; Change type to **Bridged**, and set "**Bridged to:**" explicitly to your **secondary Ethernet Family Controller #2 card** (this physical port handles your external OSPF WAN link to the switch).

### [ ] Task 1.2: Correct Router Virtual Hardware Mapping
Right-click your Router VM settings (**Ensure the VM is powered OFF**). Verify that your network adapters are bound to the correct VMnets so that Linux aligns them with the proper internal names (`ens33` and `ens37`):

* **Network Adapter 1:** Must be set to **Custom: VMnet6** (Faces the Internet).
* **Network Adapter 2:** Must be set to **Custom: VMnet7** (Faces the Switch).

### [ ] Task 1.3: Map Workstation Virtual Hardware
Right-click your Client VM and Server VM settings. Set their Network Adapters strictly to **Custom: VMnet5** (Faces the local office LAN switch ports).

---

## PHASE 2: PHYSICAL MANAGEMENT LAYER CUTOVER
**Goal:** Establish direct communications from your desk to the console of the physical hardware switches.

### [ ] Task 2.1: Run Physical Management Cables
Go into the back server rack room and look at your assigned Pod letter rack.

* **For Switch 2 (Aruba 6300 Core):** Run a physical network patch cable from the port labeled **MGMT** on the back of the switch into your desk's corresponding patch panel port (example: Port C5 if you sit at desk C5).
* **For Switch 1 (Aruba 2500 / 2530 / 6300):** Your partner runs a second network patch cable from the management port into their desk's patch panel port. *(Note: On 2500/2530 switches, use the dedicated port or Port 23/24).*

### [ ] Task 2.2: Hardcode the Windows Host Management IP Address
Back at your desk, open **View Network Connections** in Windows. Right-click the network card icon corresponding to your physical management cable connection, open **IPv4 Properties**, and enter:

* **IP Address:** Choose an available IP inside your Pod's specific switch management block (e.g., if the switch is `10.10.10.34`, set your PC to `10.10.10.35`).
* **Subnet Mask:** Enter the exact mask listed on your pod's chart (typically `255.255.255.240` for a `/28` block).
* **Default Gateway:** Leave completely blank.

### [ ] Task 2.3: Verify Management Link Adjacency
Open the Windows Command Prompt and test the physical line: `ping [Your Switch Management IP]`.

Once you receive stable replies, open PuTTY, choose **SSH**, type the **Switch Management IP**, and log in using `"student"`.

---

## PHASE 3: SWITCH ARCHITECTURE PROVISIONING
**Goal:** Load custom routing configurations into the bare-metal hardware screens.

* **Files needed:** `6300-25xxSwitchCommandsStage2_2Students.txt` or `6300-6300SwitchCommandsStage2_2Students.txt`
* **When to use it:** Tasks 3.1 & 3.2
* **How it's used:** You will open an SSH session via PuTTY directly to the switches over your temporary management lines. You will copy the entire block of commands from your text file and paste them directly into the terminal window to automate building your VLANs, inter-switch links, routing interfaces, and DHCP server scopes.

### [ ] Task 3.1: Provision Switch 2 (The Aruba 6300 Core Switch)
Once logged into the 6300 CLI, enter privileged mode (`en` then `conf t`). Paste your pre-edited configuration block to execute the following logic:

1. Create your branch virtual domain (your vlan UID) and your partner's branch domain (vlan Partner UID).
2. Turn on OSPF Routing Area 0 on those VLAN interfaces.
3. Change interfaces `1/1/4` and `1/1/5` into fully layer-3 routed ports (`routing`) and assign your Point-to-Point WAN transit IP addresses.
4. Build the `dhcp-server vrf default` pools for both network segments, ensuring you update the static-bind hardware MAC address strings with the actual fingerprints of your respective Ubuntu Server VMs.

### [ ] Task 3.2: Provision Switch 1 (Choose your Hardware Route below)
* **ROUTE A (If Switch 1 is an older Aruba 2500/2530 Access Switch):** Log into the switch console. Use classic ProCurve syntax to set the management gateway (`ip default-gateway`), create both student VLANs, and apply physical port mapping rules: Set Port 1 as Untagged for your VLAN, Port 2 as Untagged for your partner's VLAN, and Port 3 as Tagged to act as the trunk uplink wire to the core switch.
* **ROUTE B (If Switch 1 is a modern Aruba 6300 Core Switch):** Log into the switch console. Use modern ArubaOS-CX syntax to match Switch 2. Replicate both student VLAN IDs, assign respective access IPs, and configure interface `1/1/3` on both switches as an enterprise trunk network line (`vlan trunk allowed all`).

### [ ] Task 3.3: Production Cable Cutover
Once both switch configurations are fully saved (`write memory`), return to the back rack room. Unplug your temporary management lines. Re-patch your desk data cables into the active production ports you just programmed (e.g., plug your line into Port 1 for your VLAN, and your partner plugs into Port 2 for their VLAN).

---

---

## The Switch-to-Switch Interconnection (The Trunk Line)
This is the main data highway that links your two switches together so that data packets can travel between the Core layer and the Access layer.


**Switch 2 (Aruba 6300 Core) Port:** **`1/1/3`** 
**Switch 1 (Aruba 2500/2530 Access) Port:** **`3`** 

---

## Workstation Connections (Your Access Layer)

These are the cables running from the patch panel ports leading to your actual lab desks where your VMs are running.

### 1. Your Workspace Branch (VLAN 231)
**Connection:** Run a patch cable from **Port 1** on the Aruba 2500/2530 switch into your personal desk block port on the patch panel (e.g., `C5`).
**Result:** This connects your physical host PC's network card (which is running **VMnet5** for your Client and Server VMs) directly into your private VLAN 231 subnet domain.


### 2. Your Partner's Workspace Branch (VLAN 217)
**Connection:** Your partner runs a patch cable from **Port 2** on the Aruba 2500/2530 switch into *their* personal desk block port on the patch panel.
**Result:** This connects your partner's host PC network card into their respective VLAN 217 subnet domain.

## Router Connections (Your Routed Layer)
These cables connect the Core 6300 switch directly to your Ubuntu Router VMs so they can act as gateways to the internet.

### 1. Your Ubuntu Router WAN Connection

**Connection:** Run a patch cable from **Port 1/1/4** on the Aruba 6300 switch into your desk's secondary patch panel port (the one tied to your PC's secondary Realtek card running **VMnet7**).
**Result:** This lights up the tight `192.168.3.156/30` point-to-point routing transit link between the 6300 switch (`.157`) and your Linux Router interface `ens37` (`.158`).

### 2. Your Partner's Ubuntu Router WAN Connection

**Connection:** Run a patch cable from **Port 1/1/5** on the Aruba 6300 switch into your partner's secondary patch panel port.
**Result:** This lights up your partner's point-to-point routing link (`192.168.3.100/30`) on the core switch (`.101`).

---
### Physical Cable & Topology Matrix

| Connection Source (From Device) | Port / Interface | Target Destination (To Device / Location) | Subnet / Network Domain | Purpose / Traffic Type |
| :--- | :--- | :--- | :--- | :--- |
| **Aruba 6300 Core Switch** | `1/1/3` | **Aruba 2530 Access Switch** Port `3` | Tagged VLANs 231 & 217 | Inter-Switch 802.1Q Trunk Pipe |
| **Aruba 6300 Core Switch** | `1/1/4` | **Your Router WAN** (`ens37` via Desk Block Panel /vmnet 6 -- Intel® Ethernet Connection (Seneca Network)) | `192.168.3.156/30` | Your Point-to-Point Routing Interface |
| **Aruba 6300 Core Switch** | `1/1/5` | **Partner Router WAN** (via Partner Desk Block Panel) | `192.168.3.100/30` | Partner's Point-to-Point Routing Interface |
| **Aruba 2530 Access Switch** | `1` | **Your Desk Node Block** (Maps to VMnet5/ens33/vmnet5 – Family Controller) | `172.16.57.192/26` | Your Private Office LAN (Client & Server) |
| **Aruba 2530 Access Switch** | `2` | **Partner Desk Node Block** (Maps to Partner VMnet5) | `172.16.54.64/26` | Partner's Private Office LAN (Client & Server) |

---




## PHASE 4: ENDPOINT ACTIVATION & INTER-BRANCH CONNECTIVITY
**Goal:** Boot up your local business infrastructure nodes and check foundational local area connectivity.

* **File needed:** `dhcpAssignment.yaml.txt` (Originally `dhcpAssignment.yaml.txt`)
* **When to use:** Task 4.1
* **How it's used:** Once your Server VM is booted on VMnet5, you will open a terminal inside Ubuntu and write these configurations into your Netplan system folder at `/etc/netplan/dhcpAssignment.yaml`. Running `sudo netplan apply` forces the server to look across the switch network via DHCP, matching its MAC address to claim its reserved `.254` IP.

### [ ] Task 4.1: Bring Corporate Server Online
Boot your Ubuntu Server VM on VMnet5. Ensure its netplan is configured to look for DHCP (`dhcp4: true`). Once booted, run `ip a` to verify that it successfully caught its reserved static profile IP address (the very last usable address in your pool, like `.254`) directly from the switch engine.

### [ ] Task 4.2: Bring Corporate Client Online
Boot your Ubuntu Client VM on VMnet5. Verify that it automatically pulls a dynamic IP address from your switch's configured lease range.

### [ ] Task 4.3: Validate Local Network Fabric
Open a terminal screen on your Client VM and execute a continuous ping directly to your Server VM: `ping [Your Server IP Address]`. Ensure this paths successfully with zero dropped packets.

---

## PHASE 5: LINUX GATEWAY PROVISIONING & OSPF NEIGHBORHOOD IGNITION
**Goal:** Transform your Ubuntu Router into an advanced multi-vendor gateway and join the core routing fabric.

* **File needed:** `staticAssignment.yaml` (originally `staticAssignment.yaml.txt`)
* **When to use:** Task 5.1
* **How it's used:** As soon as your Router VM boots up, you will write this block into the Netplan directory at `/etc/netplan/staticAssignment.yaml`. This manual step assigns the hardcoded point-to-point IP (`192.168.3.158/30`) to your switch-facing interface card (`ens37`), setting up your primary connection to the core network switch.

<br>

* **File needed:** `frrRouterConfig.txt`
* **When to use:** Task 5.4
* **How it's used:** After confirming that the `ospfd` routing engine daemon has been toggled to `yes`, you will type `sudo vtysh` in the terminal to enter the multi-vendor shell environment. From there, you copy and paste the commands from this file into the prompt to tell your router how to dynamically exchange network paths with the other student pods using OSPF.

### [ ] Task 5.1: Initialize Static WAN Routing Link
Boot your Ubuntu Router VM. Open your Netplan configuration file (`sudo nano /etc/netplan/staticAssignment.yaml`). Bind your tight point-to-point IP address profile directly to your switch-facing interface `ens37` with `dhcp4: false`. Run `sudo netplan apply` to lock it into place.

### [ ] Task 5.2: Activate Structural System Kernel Forwarding
By default, Linux blocks data packets from traveling between different network cards. Enable system routing by opening the system control configuration architecture: `sudo sysctl -w net.ipv4.ip_forward=1`. *(To make this change permanent through restarts, uncomment the line `net.ipv4.ip_forward=1` inside the `/etc/sysctl.conf` file).*

### [ ] Task 5.3: Ignite the OSPF Routing Daemon Engine
Open the Free Range Routing daemon settings file (`sudo nano /etc/frr/daemons`) and change the line reading `ospfd=no` to `ospfd=yes`. Restart the routing system manager: `sudo systemctl restart frr`.

### [ ] Task 5.4: Broadcast Local Subnet Routes
Enter the interactive routing suite engine by typing `sudo vtysh`. Enter configuration mode (`conf t`), configure your `router ospf` environment, assign your unique router-id `[Your UID].1.1.2`, and use network command strings to advertise both your Point-to-Point WAN link and the Seneca network out to Area 0.

---

## PHASE 6: FIREWALL SECURITY & FINAL VERIFICATION
**Goal:** Apply security isolation masks and record verification metric milestones.

* **File needed:** `NFtablesRulesNAT.txt`
* **When to use:** Task 6.1
* **How to use:** In your Router VM, open your main system security file at `/etc/nftables.conf` on the router, wipe out any stock parameters, and paste this entire security rule layout. Running `sudo nft -f /etc/nftables.conf` turns on your firewall, forces local corporate DNS masking, and enables internet connection Sharing (NAT masquerading) out through interface `ens33`.

### [ ] Task 6.1: Load Security Rules and Outbound NAT Masking
Open your firewall ruleset deployment file (`sudo nano /etc/nftables.conf`). Paste your customized packet-filtering strings to enable established-state tracking on interface `ens33` and transparently proxy internal corporate DNS queries directly over to the main lab resolver address (`10.101.100.21`). Turn the rules live using: `sudo nft -f /etc/nftables.conf`.

### [ ] Task 6.2: Disable VMware Backdoors & Test Edge Routing
Go to your Client VM and Server VM network options. Ensure that any standalone second network cards that connect straight to the internet via VMware are completely disabled or disconnected. Your workstations must now access the open web exclusively by passing traffic through your Aruba switch up to your custom Linux Router.

### [ ] Task 6.3: Capture Lab Deliverable Sign-Off Metrics
From your isolated Client VM, test your new network architecture:

1. **Verify edge path tracking** by running a ping and traceroute: `traceroute google.ca`.
2. **Verify your multi-vendor OSPF neighbor tables are active.** Inside your Router's vtysh and your Aruba 6300 core terminal, run: `show ip ospf neighbor` and `show ip route`. You should see your network expanding as your classmates' subnets dynamically pop into your screen!
3. **Capture required protocol validation handshakes.** Open Wireshark, capture the required protocol validation handshakes (DHCP lease adjustments, local SSH key management, and outer DNS requests), and export your tracking logs for lab check-off.


## 📸 Visual Inspection & Lab Report 📸SCREENSHOT Guide

This section outlines the exact verification commands required for the visual inspection, along with a checklist of mandatory 📸SCREENSHOTs needed for the stage two report submission.

---

### 1. Client VM Verification
Before verifying, make sure all dynamic VMware internet backdoors on the Client are disabled. All traffic must travel explicitly through the physical switch to your custom Linux Router.

#### Live Commands to Run
* **Renew DHCP Lease:**
    ```bash
    sudo dhclient -r && sudo dhclient -v
    ```
    *Verify that your Client captures a dynamic IP in your pool range (e.g., `172.16.57.194` to `.253`).*

#### Required SCREENSHOTs for Report
* [ ] **📸SCREENSHOT #1:** Client terminal displaying the successful dynamic IP acquisition after running `dhclient -v`.
* [ ] **📸SCREENSHOT #2:** A successful ping and network route tracking test from the Client terminal out to the open web:
    ```bash
    ping -c 4 www.google.ca
    traceroute youtube.com
    ```
    *(Note: Your traceroute will show the packet bouncing to your Router IP first, then hitting the college infrastructure, and finally arriving at YouTube).*

---

### 2. Server VM Verification
Your server relies on the switch's DHCP service matching its unique hardware MAC fingerprint to deliver its dedicated static profile address.

#### Live Commands to Run
* **Renew Statically Bound Lease:**
    ```bash
    sudo dhclient -r && sudo dhclient -v
    ```
    *Verify that the server is allocated its fixed reservation address (e.g., `172.16.57.254`).*

#### Required SCREENSHOTs for Report
* [ ] **📸SCREENSHOT #3:** Server terminal showing `ip a` or a successful `dhclient` renewal reflecting its exact allocated reservation IP.
* [ ] **📸SCREENSHOT #4:** Verification showing that internet access is fully functional from the Server:
    ```bash
    ping -c 4 www.google.ca
    ```

---

### 3. Linux Router VM Verification
The Linux Router is the core firewall engine and dynamic gateway of your branch infrastructure.

#### Live Commands to Run
* **Check Active Routing Tables & OSPF Neighbors:**
    ```bash
    sudo vtysh
    # Run these two commands inside the FRR routing shell:
    show ip ospf neighbor
    show ip route
    exit
    ```
* **Check Live Firewall Engine Layout:**
    ```bash
    sudo nft list ruleset | less
    ```

#### Required SCREENSHOTs for Report
* [ ] **📸SCREENSHOT #5:** The live Netfilter security profile matching your configured ruleset (`nft list ruleset`).
* [ ] **📸SCREENSHOT #6:** The OSPF status overview window displaying active neighborhood adjacency tables (`show ip ospf neighbor`).
* [ ] **📸SCREENSHOT #7:** The full kernel routing table (`show ip route`) displaying dynamically learned paths (`O`) from your partner's pod and the rest of the classroom network.

---

### 4. Aruba Hardware Switch Verification
You must show that your physical switches are cleanly managing your infrastructure parameters. You can run these commands by opening a PuTTY SSH session directly from your Client VM into the switch IPs.

#### Aruba 6300 Core Switch Commands
Log in and enter execution mode (`en`), then capture the following system health outputs:
* [ ] **📸SCREENSHOT #8:** Active virtual LAN allocation tables:
    ```text
    show vlan
    ```
* [ ] **📸SCREENSHOT #9:** Hardware interface summary and assigned trunk lines:
    ```text
    show ip interface brief
    ```
* [ ] **📸SCREENSHOT #10:** Live IP address tables learned by the core backbone processor:
    ```text
    show ip route
    ```
* [ ] **📸SCREENSHOT #11:** Verification that the active automated lease distribution pools are running smoothly:
    ```text
    show dhcp-server vrf default
    ```
* [ ] **📸SCREENSHOT #12:** The raw configuration script safely committed to the switch's non-volatile memory block:
    ```text
    show running-config
    ```

#### Aruba 2500/2530 Access Switch Commands
Log in to your partner's access switch interface and capture the following verification metrics:
* [ ] **📸SCREENSHOT #13:** Active local virtual LAN allocations reflecting your untagged access ports:
    ```text
    show vlan
    ```
* [ ] **📸SCREENSHOT #14:** Active backbone trunk link configuration metrics showing physical connectivity up to the Core switch:
    ```text
    show trunks
    ```

### 5. Key Exchange & Wireshark Deliverables Checklist
Before completing your documentation report, make sure you configure an SSH key pair for your administrator account (`your-mySenecaID`) across all systems. You must demonstrate that you can securely SSH from your Client VM directly into your Server VM, your Router VM, and your Aruba 6300 Switch **without entering a password**.

Follow this sequence from your Client VM terminal to complete the deployment:

### Generate the Cryptographic Key Pair on the Client VM
Log into your **Client VM** terminal and execute the following command to generate a modern, high-security Ed25519 key pair:
```bash
ssh-keygen -t ed25519
```

#### Mandatory Wireshark Trace Captures
Start Wireshark on your Client interface card and export a single network analysis capture file containing **only** the following transaction exchanges:
1.  **📸SSH Handshake (Client ➔ Server):** The secure key authentication sequence and subsequent response.
2.  **📸SSH Handshake (Client ➔ Router):** The network path connection verification packets.
3.  **📸SSH Handshake (Client ➔ Aruba 6300):** The infrastructure terminal management transport stream.
4.  **📸DHCP Lease Exchange:** A clean, 4-packet sequence (`Discover, Offer, Request, Acknowledge`) captured while releasing and renewing your Client workstation's address space.
5.  **📸DNS Name Resolution:** The full query and system translation lookup packets generated when your client navigates to `youtube.com` or `google.ca`.
