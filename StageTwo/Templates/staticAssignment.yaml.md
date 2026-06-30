### File: staticAssignment.md

# Ubuntu Router Netplan Configuration
**Target Location:** `/etc/netplan/staticAssignment.yaml` on the **Ubuntu Router VM**.

This profile establishes the tight interconnect line between your Linux machine and the core hardware switch engine. This step assigns your designated transit address profile directly to your dedicated external hardware communication adapter card (`ens37`).

```yaml
network:
  version: 2
  ethernets:
    # ens37 tracks the point-to-point physical WAN link on VMnet7 facing Core Switch Port 1/1/4
    ens37:
      dhcp4: false
      addresses: [192.168.3.158/30]             # ----> Your Linux Router WAN interface (Last Usable IP)
