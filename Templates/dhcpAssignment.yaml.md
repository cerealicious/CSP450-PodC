# Ubuntu Corporate Server Netplan Configuration
**Target Location:** `/etc/netplan/dhcpAssignment.yaml` on the **Ubuntu Server VM**.

## Overview
This file configures the business server's primary network card (`ens33`) to communicate across your internal LAN network infrastructure. 

When applied, it forces the operating system to send out an automated DHCP discovery request across the local area network. The **Aruba 6300 Core Switch** will intercept this request, read the virtual machine's unique physical MAC address hardware fingerprint, and automatically bind your reserved, high-boundary static profile IP address (`172.16.57.254`).

---

## Netplan Deployment Script
Copy and paste this exact layout directly into your configuration directory using a terminal text editor (e.g., `sudo nano /etc/netplan/dhcpAssignment.yaml`):

```yaml
network:
  version: 2
  ethernets:
    # ens33 handles internal LAN data inside VMnet5, plugged directly into Access Switch Port 1
    ens33:
      dhcp4: true                      # ----> Listens for Core Switch Automated MAC Bound Static Allocation
